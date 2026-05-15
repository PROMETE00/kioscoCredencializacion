<?php

namespace App\Services;

use App\Repositories\FileRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TicketRepository;
use RuntimeException;

class TicketService extends BaseService
{
    protected StudentRepository $studentRepo;
    protected TicketRepository $ticketRepo;
    protected FileRepository $fileRepo;

    public function __construct()
    {
        $this->studentRepo = new StudentRepository();
        $this->ticketRepo = new TicketRepository();
        $this->fileRepo = new FileRepository();
    }

    public function createTicket(int $studentId): array
    {
        $existing = $this->ticketRepo->findActiveByStudentId($studentId);
        if ($existing) {
            return $existing;
        }

        $stageId = $this->ticketRepo->getStageIdByCode('TICKET_GENERATED');
        $statusId = $this->ticketRepo->getStatusIdByCode('WAITING');

        if (!$stageId || !$statusId) {
            throw new RuntimeException('Initial catalogs (TICKET_GENERATED / WAITING) not found.');
        }

        $now = date('Y-m-d H:i:s');
        $expires = date('Y-m-d 23:59:59');

        $ticketId = $this->ticketRepo->create([
            'folio'         => 'PEND',
            'student_id'    => $studentId,
            'status_id'     => $statusId,
            'stage_id'      => $stageId,
            'is_active'     => 1,
            'expires_at'    => $expires,
            'qr_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $folio = $this->ticketRepo->generateFolio($ticketId);
        $this->ticketRepo->update($ticketId, ['folio' => $folio]);

        $this->ticketRepo->logEvent([
            'ticket_id'          => $ticketId,
            'event_type'         => 'ticket_created',
            'new_stage_id'       => $stageId,
            'new_status_id'      => $statusId,
            'created_at'         => $now,
        ]);

        $ticket = $this->ticketRepo->findActiveByStudentId($studentId);
        return $ticket ?: [];
    }

    public function findStudentByIdentifier(string $identifier): ?array
    {
        return $this->studentRepo->findByIdentifier($identifier);
    }

    public function findActiveTicketByStudent(int $studentId): ?array
    {
        return $this->ticketRepo->findActiveWithDetails($studentId);
    }

    public function getInitialCatalogs(): ?array
    {
        return $this->ticketRepo->getInitialStageAndStatus();
    }

    public function deactivateExpiredTickets(int $studentId): void
    {
        $this->ticketRepo->deactivateExpiredForStudent($studentId);
    }

    public function savePublicPhoto(int $studentId, int $ticketId, string $dataUrl): array
    {
        if (!preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#', $dataUrl, $matches)) {
            throw new RuntimeException('Invalid photo format.');
        }

        $mime   = strtolower($matches[1]);
        $binary = base64_decode($matches[2], true);
        if ($binary === false) {
            throw new RuntimeException('Could not decode photo data.');
        }

        $ext = match ($mime) {
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            default      => 'jpg'
        };

        $relativePath = 'uploads/photos/photo_' . $studentId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $absolutePath = FCPATH . $relativePath;
        $dir = dirname($absolutePath);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create photos directory.');
        }
        $this->ensureUploadHtaccess($dir);

        if (file_put_contents($absolutePath, $binary) === false) {
            throw new RuntimeException('Could not save photo file.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();

        try {
            $fileId = $this->fileRepo->create([
                'type'       => 'photo',
                'path'       => $relativePath,
                'sha256'     => hash('sha256', $binary),
                'mime'       => $mime,
                'size_bytes' => filesize($absolutePath),
                'created_at' => $now,
            ]);

            $this->studentRepo->updateBiometric($studentId, 'photo', $fileId);

            $currentTicket = $this->ticketRepo->findById($ticketId);
            $nextStageId = $this->ticketRepo->getStageIdByCode('PHOTO_CAPTURED');

            $ticketUpdate = ['updated_at' => $now];
            if ($nextStageId !== null) {
                $ticketUpdate['stage_id'] = $nextStageId;
            }
            $this->ticketRepo->update($ticketId, $ticketUpdate);

            $this->ticketRepo->logEvent([
                'ticket_id'          => $ticketId,
                'event_type'         => 'photo_saved',
                'previous_stage_id'  => $currentTicket['stage_id'] ?? null,
                'new_stage_id'       => $nextStageId,
                'previous_status_id' => $currentTicket['status_id'] ?? null,
                'new_status_id'      => $currentTicket['status_id'] ?? null,
                'user_id'            => null,
                'details_json'       => json_encode(['file_id' => $fileId, 'source' => 'public_kiosk'], JSON_UNESCAPED_UNICODE),
                'created_at'         => $now,
            ]);

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                throw new RuntimeException('Database transaction failed while saving photo.');
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            @unlink($absolutePath);
            throw $e;
        }

        return ['file_id' => $fileId, 'url' => base_url($relativePath)];
    }

    public function savePublicSignature(int $studentId, int $ticketId, string $dataUrl): array
    {
        if (!preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#', $dataUrl, $matches)) {
            throw new RuntimeException('Invalid signature format.');
        }

        $mime   = strtolower($matches[1]);
        $binary = base64_decode($matches[2], true);
        if ($binary === false) {
            throw new RuntimeException('Could not decode signature data.');
        }

        $ext = match ($mime) {
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            default      => 'png',
        };

        $relativePath = 'uploads/firmas/signature_' . $studentId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $absolutePath = FCPATH . $relativePath;
        $dir = dirname($absolutePath);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create signatures directory.');
        }
        $this->ensureUploadHtaccess($dir);

        if (file_put_contents($absolutePath, $binary) === false) {
            throw new RuntimeException('Could not save signature file.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();

        try {
            $fileId = $this->fileRepo->create([
                'type'       => 'signature',
                'path'       => $relativePath,
                'sha256'     => hash('sha256', $binary),
                'mime'       => $mime,
                'size_bytes' => filesize($absolutePath),
                'created_at' => $now,
            ]);

            $this->studentRepo->updateBiometric($studentId, 'signature', $fileId);

            $currentTicket = $this->ticketRepo->findById($ticketId);
            $nextStageId = $this->ticketRepo->getStageIdByCode('SIGNATURE_CAPTURED');

            $ticketUpdate = ['updated_at' => $now];
            if ($nextStageId !== null) {
                $ticketUpdate['stage_id'] = $nextStageId;
            }
            $this->ticketRepo->update($ticketId, $ticketUpdate);

            $this->ticketRepo->logEvent([
                'ticket_id'          => $ticketId,
                'event_type'         => 'signature_saved',
                'previous_stage_id'  => $currentTicket['stage_id'] ?? null,
                'new_stage_id'       => $nextStageId,
                'previous_status_id' => $currentTicket['status_id'] ?? null,
                'new_status_id'      => $currentTicket['status_id'] ?? null,
                'user_id'            => null,
                'details_json'       => json_encode(['file_id' => $fileId, 'source' => 'public_kiosk'], JSON_UNESCAPED_UNICODE),
                'created_at'         => $now,
            ]);

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                throw new RuntimeException('Database transaction failed while saving signature.');
            }
        } catch (\Exception $e) {
            $this->db->transRollback();
            @unlink($absolutePath);
            throw $e;
        }

        return ['file_id' => $fileId, 'url' => base_url($relativePath)];
    }

    protected function ensureUploadHtaccess(string $directory): void
    {
        $htaccessPath = $directory . '/.htaccess';
        if (file_exists($htaccessPath)) {
            return;
        }

        $content = "Deny from all\n"
            . "<FilesMatch \"\\.(jpg|jpeg|png|gif|webp|bmp)$\">\n"
            . "    Order Allow,Deny\n"
            . "    Allow from all\n"
            . "</FilesMatch>\n";

        @file_put_contents($htaccessPath, $content);
    }
}
