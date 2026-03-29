<?php

namespace App\Modules\Stations\Models;

use CodeIgniter\Model;
use App\Modules\Stations\Services\QueueService;
use RuntimeException;

/**
 * Model for handling fingerprint captures and their associated queue.
 */
class HuellaModel extends Model
{
    protected $db;
    private $queueService;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
        $this->queueService = new QueueService();
    }

    public function getQueue(int $limit = 100): array
    {
        return $this->queueService->getQueue('fingerprint', $limit);
    }

    public function getCurrentByTurno(int $ticketId): ?array
    {
        return $this->queueService->getCurrent('fingerprint', $ticketId);
    }

    public function getNextPending(): ?array
    {
        return $this->queueService->getCurrent('fingerprint');
    }

    public function getByAlumnoId(int $studentId): ?array
    {
        return $this->queueService->getByStudentId('fingerprint', $studentId);
    }

    /**
     * Saves a fingerprint from a data URL, creating a file record and updating student/ticket.
     */
    public function saveHuella(int $studentId, int $ticketId, string $fingerprintDataUrl): array
    {
        $current = $this->getCurrentByTurno($ticketId);

        if (!$current || $current['student_id'] != $studentId) {
            throw new RuntimeException('El turno seleccionado no está disponible para captura de huella.');
        }

        [$binary, $mime, $extension] = $this->decodeImageDataUrl($fingerprintDataUrl);

        $relativePath = 'uploads/huellas/fingerprint_' . $studentId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $absolutePath = FCPATH . $relativePath;
        $dir = dirname($absolutePath);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo preparar el directorio para guardar la huella.');
        }

        if (file_put_contents($absolutePath, $binary) === false) {
            throw new RuntimeException('No se pudo escribir el archivo de la huella.');
        }

        $sha256 = hash('sha256', $binary);
        $size = filesize($absolutePath);
        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        $this->db->table('files')->insert([
            'type'       => 'fingerprint',
            'path'       => $relativePath,
            'sha256'     => $sha256,
            'mime'       => $mime,
            'size_bytes' => $size,
            'created_at' => $now,
        ]);

        $fileId = (int) $this->db->insertID();

        $this->db->table('students')
            ->where('id_student', $studentId)
            ->update([
                'fingerprint_file_id' => $fileId,
                'updated_at'          => $now,
            ]);

        $currentTicket = $this->db->table('tickets')
            ->select('current_stage_id, ticket_status_id')
            ->where('id_ticket', $ticketId)
            ->get()
            ->getRowArray();

        $nextStageId = $this->findEtapaId(['fingerprint_saved', 'FINGERPRINT_CAPTURED', 'HUELLA_CAPTURADA', 'COMPLETED']);
        $statusId = $this->findEstatusId(['finished', 'FINALIZADO', 'COMPLETED']);

        $ticketUpdate = ['updated_at' => $now];
        if ($nextStageId !== null) {
            $ticketUpdate['current_stage_id'] = $nextStageId;
        }
        if ($statusId !== null) {
            $ticketUpdate['ticket_status_id'] = $statusId;
        }

        $this->db->table('tickets')
            ->where('id_ticket', $ticketId)
            ->update($ticketUpdate);

        $this->db->table('ticket_events')->insert([
            'ticket_id'          => $ticketId,
            'event_type'         => 'fingerprint_saved',
            'previous_stage_id'  => $currentTicket['current_stage_id'] ?? null,
            'new_stage_id'       => $nextStageId,
            'previous_status_id' => $currentTicket['ticket_status_id'] ?? null,
            'new_status_id'      => $statusId,
            'user_id'            => session('auth')['id'] ?? null,
            'details_json'       => json_encode(['file_id' => $fileId, 'path' => $relativePath], JSON_UNESCAPED_UNICODE),
            'created_at'         => $now,
        ]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            @unlink($absolutePath);
            throw new RuntimeException('No fue posible guardar la huella en la base de datos.');
        }

        return [
            'file_id' => $fileId,
            'url'     => base_url($relativePath),
            'path'    => $relativePath,
        ];
    }

    private function decodeImageDataUrl(string $dataUrl): array
    {
        if (!preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#', $dataUrl, $matches)) {
            throw new RuntimeException('El formato de la huella es inválido.');
        }

        $mime = strtolower($matches[1]);
        $base64 = $matches[2];
        $binary = base64_decode($base64, true);

        if ($binary === false) {
            throw new RuntimeException('No se pudo decodificar la huella enviada.');
        }

        $extension = match ($mime) {
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/bmp'  => 'bmp',
            default      => throw new RuntimeException('El tipo de imagen de la huella no está soportado.'),
        };

        return [$binary, $mime, $extension];
    }

    private function findEtapaId(array $codes): ?int
    {
        foreach ($codes as $code) {
            $row = $this->db->table('cat_stages')
                ->select('id_stage')
                ->where('code', $code)
                ->get(1)
                ->getRowArray();

            if ($row) {
                return (int) $row['id_stage'];
            }
        }

        return null;
    }

    private function findEstatusId(array $codes): ?int
    {
        foreach ($codes as $code) {
            $row = $this->db->table('cat_ticket_status')
                ->select('id_status')
                ->where('code', $code)
                ->get(1)
                ->getRowArray();

            if ($row) {
                return (int) $row['id_status'];
            }
        }

        return null;
    }
}
