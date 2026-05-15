<?php

namespace App\Repositories;

class FingerprintRepository extends BaseRepository
{
    public function findByAlumnoId(string $alumnoId): array
    {
        return $this->db->table('huellas_credenciales')
            ->where('alumno_id', $alumnoId)
            ->get()
            ->getResultArray();
    }

    public function findByCredentialId(string $alumnoId, string $credentialId): ?array
    {
        return $this->db->table('huellas_credenciales')
            ->where('alumno_id', $alumnoId)
            ->where('credential_id', $credentialId)
            ->get()
            ->getRowArray();
    }

    public function storeCredential(array $data): int
    {
        $this->db->table('huellas_credenciales')->insert($data);
        return (int) $this->db->insertID();
    }

    public function updateSignCount(int $id, int $signCount): bool
    {
        return $this->db->table('huellas_credenciales')
            ->where('id', $id)
            ->update(['sign_count' => $signCount]);
    }

    public function existsForAlumno(string $alumnoId): bool
    {
        return $this->db->table('huellas_credenciales')
            ->where('alumno_id', $alumnoId)
            ->countAllResults() > 0;
    }

    public function saveSignatureFile(int $studentId, int $ticketId, string $dataUrl): array
    {
        if (!preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#', $dataUrl, $matches)) {
            throw new \RuntimeException('Invalid signature format.');
        }

        $binary = base64_decode($matches[2], true);
        $relativePath = 'uploads/firmas/final_signature_' . $studentId . '_' . date('Ymd_His') . '.png';
        $absolutePath = FCPATH . $relativePath;

        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        file_put_contents($absolutePath, $binary);

        $this->db->table('files')->insert([
            'type'       => 'signature',
            'path'       => $relativePath,
            'sha256'     => hash('sha256', $binary),
            'mime'       => 'image/png',
            'size_bytes' => strlen($binary),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $fileId = (int) $this->db->insertID();
        $this->db->table('students')->where('id', $studentId)->update(['signature_file_id' => $fileId]);

        return ['file_id' => $fileId, 'path' => $relativePath];
    }
}
