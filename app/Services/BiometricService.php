<?php

namespace App\Services;

use App\Repositories\FileRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TicketRepository;
use RuntimeException;

class BiometricService extends BaseService
{
    protected FileRepository $fileRepo;
    protected StudentRepository $studentRepo;
    protected TicketRepository $ticketRepo;

    public function __construct()
    {
        $this->fileRepo = new FileRepository();
        $this->studentRepo = new StudentRepository();
        $this->ticketRepo = new TicketRepository();
    }

    /**
     * Processes a Base64 biometric image, saves it to disk, and updates DB records.
     */
    public function processAndSave(string $type, int $studentId, int $ticketId, string $base64Data, ?int $userId = null): array
    {
        if (!preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#', $base64Data, $matches)) {
            throw new RuntimeException('Invalid image format.');
        }

        $mime = strtolower($matches[1]);
        $binary = base64_decode($matches[2], true);
        if ($binary === false) {
            throw new RuntimeException('Could not decode biometric data.');
        }

        $extension = $this->getExtensionFromMime($mime);
        $folder = $this->getFolderByType($type);
        
        $relativePath = "uploads/{$folder}/{$type}_{$studentId}_" . date('Ymd_His') . "_" . bin2hex(random_bytes(4)) . "." . $extension;
        $absolutePath = FCPATH . $relativePath;
        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Could not create directory: {$directory}");
        }

        if (file_put_contents($absolutePath, $binary) === false) {
            throw new RuntimeException('Could not save file to disk.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $fileId = $this->fileRepo->create([
                'type'       => $type,
                'path'       => $relativePath,
                'sha256'     => hash('sha256', $binary),
                'mime'       => $mime,
                'size_bytes' => filesize($absolutePath),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $this->studentRepo->updateBiometric($studentId, $type, $fileId);

            $this->advanceTicketStage($ticketId, $type, $fileId, $userId);

            $db->transComplete();

            if (!$db->transStatus()) {
                throw new RuntimeException('Database transaction failed.');
            }
        } catch (\Exception $e) {
            $db->transRollback();
            @unlink($absolutePath);
            throw $e;
        }

        return [
            'file_id' => $fileId,
            'path'    => $relativePath,
            'url'     => base_url($relativePath)
        ];
    }

    protected function getExtensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/bmp'  => 'bmp',
            default      => 'jpg',
        };
    }

    protected function getFolderByType(string $type): string
    {
        return match ($type) {
            'photo'       => 'photos',
            'signature'   => 'firmas',
            'fingerprint' => 'huellas',
            default       => 'misc',
        };
    }

    protected function advanceTicketStage(int $ticketId, string $type, int $fileId, ?int $userId): void
    {
        $db = \Config\Database::connect();
        $ticket = $db->table('tickets')->where('id', $ticketId)->get()->getRowArray();

        if (!$ticket) {
            throw new RuntimeException("Ticket ID {$ticketId} not found.");
        }

        $stageCode = null;
        $statusCode = null;

        switch ($type) {
            case 'photo':
                $stageCode = $this->findExistingCode('cat_stages', ['PHOTO_CAPTURED', 'photo_saved', 'captured']);
                $statusCode = $this->findExistingCode('cat_ticket_status', ['WAITING', 'active', 'IN_PROGRESS']);
                break;
            case 'signature':
                $stageCode = $this->findExistingCode('cat_stages', ['SIGNATURE_CAPTURED', 'signature_saved', 'SIGNATURE_REGISTERED', 'FIRMA_REGISTRADA']);
                $statusCode = $this->findExistingCode('cat_ticket_status', ['WAITING', 'active', 'IN_PROGRESS', 'EN_PROCESO']);
                break;
            case 'fingerprint':
                $stageCode = $this->findExistingCode('cat_stages', ['FINGER_CAPTURED', 'fingerprint_saved', 'FINGERPRINT_CAPTURED', 'HUELLA_CAPTURADA', 'COMPLETED']);
                $statusCode = $this->findExistingCode('cat_ticket_status', ['FINISHED', 'finished', 'FINALIZADO', 'COMPLETED']);
                break;
        }

        $stageId = $stageCode ? $this->ticketRepo->getStageIdByCode($stageCode) : null;
        $statusId = $statusCode ? $this->ticketRepo->getStatusIdByCode($statusCode) : null;

        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        if ($stageId) {
            $updateData['stage_id'] = $stageId;
        }
        if ($statusId) {
            $updateData['status_id'] = $statusId;
        }

        $this->ticketRepo->update($ticketId, $updateData);

        $this->ticketRepo->logEvent([
            'ticket_id'          => $ticketId,
            'event_type'         => $type . '_saved',
            'previous_stage_id'  => $ticket['stage_id'],
            'new_stage_id'       => $stageId,
            'previous_status_id' => $ticket['status_id'],
            'new_status_id'      => $statusId,
            'user_id'            => $userId,
            'details_json'       => json_encode(['file_id' => $fileId], JSON_UNESCAPED_UNICODE),
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }

    private function findExistingCode(string $table, array $codes): ?string
    {
        $db = \Config\Database::connect();
        foreach ($codes as $code) {
            $exists = $db->table($table)->where('code', $code)->countAllResults() > 0;
            if ($exists) {
                return $code;
            }
        }
        return null;
    }
}
