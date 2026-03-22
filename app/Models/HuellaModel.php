<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

class HuellaModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getCurrentByTurno(int $turnoId): ?array
    {
        $row = $this->baseQueueBuilder()
            ->where('t.id_turno', $turnoId)
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }

    public function getNextPending(): ?array
    {
        $row = $this->baseQueueBuilder()
            ->orderBy('t.created_at', 'ASC')
            ->orderBy('t.id_turno', 'ASC')
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }

    public function getQueue(): array
    {
        return $this->baseQueueBuilder()
            ->orderBy('t.created_at', 'ASC')
            ->orderBy('t.id_turno', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getByAlumnoId(int $alumnoId): ?array
    {
        $row = $this->baseQueueBuilder()
            ->where('a.id_alumno', $alumnoId)
            ->orderBy('t.created_at', 'ASC')
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }

    public function saveFingerprint(int $studentId, int $turnId, string $template, string $imageDataUrl, ?int $quality = null): array
    {
        $record = $this->baseQueueBuilder()
            ->select('t.id_turno, a.id_alumno, t.etapa_actual_id, t.estatus_turno_id')
            ->where('t.id_turno', $turnId)
            ->where('a.id_alumno', $studentId)
            ->get(1)
            ->getRowArray();

        if (!$record) {
            throw new RuntimeException('The selected turn is no longer available for fingerprint capture.');
        }

        [$binary, $mime, $extension] = $this->decodeImageDataUrl($imageDataUrl);

        $relativePath = 'uploads/fingerprints/fingerprint_' . $studentId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $absolutePath = FCPATH . $relativePath;
        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not prepare the fingerprint directory.');
        }

        if (file_put_contents($absolutePath, $binary) === false) {
            throw new RuntimeException('Could not write the fingerprint file.');
        }

        $hash = hash('sha256', $binary . '|' . $template);
        $size = filesize($absolutePath) ?: 0;
        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        $this->db->table('archivos')->insert([
            'tipo'         => 'huella',
            'ruta'         => $relativePath,
            'sha256'       => $hash,
            'mime'         => $mime,
            'tamano_bytes' => $size,
            'created_at'   => $now,
        ]);

        $fileId = (int) $this->db->insertID();

        $this->db->table('alumnos')
            ->where('id_alumno', $studentId)
            ->update([
                'huella_archivo_id' => $fileId,
                'updated_at'        => $now,
            ]);

        $nextStageId = $this->findStageId(['huella_registrada', 'capturado']);
        $nextStatusId = $this->findStatusId(['activo', 'EN_PROCESO']);

        $turnUpdate = ['updated_at' => $now];
        if ($nextStageId !== null) {
            $turnUpdate['etapa_actual_id'] = $nextStageId;
        }
        if ($nextStatusId !== null) {
            $turnUpdate['estatus_turno_id'] = $nextStatusId;
        }

        $this->db->table('turnos')
            ->where('id_turno', $turnId)
            ->update($turnUpdate);

        $this->db->table('turno_eventos')->insert([
            'turno_id'             => $turnId,
            'tipo_evento'          => 'huella_guardada',
            'etapa_anterior_id'    => $record['etapa_actual_id'] ?? null,
            'etapa_nueva_id'       => $nextStageId,
            'estatus_anterior_id'  => $record['estatus_turno_id'] ?? null,
            'estatus_nuevo_id'     => $nextStatusId,
            'usuario_id'           => session('user_id') ?: null,
            'detalle_json'         => json_encode([
                'archivo_id' => $fileId,
                'ruta'       => $relativePath,
                'template'   => $template,
                'quality'    => $quality,
            ], JSON_UNESCAPED_UNICODE),
            'created_at'           => $now,
        ]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            @unlink($absolutePath);
            throw new RuntimeException('Could not save the fingerprint in the database.');
        }

        return [
            'archivo_id' => $fileId,
            'url'        => base_url($relativePath),
            'path'       => $relativePath,
        ];
    }

    private function baseQueueBuilder()
    {
        return $this->db->table('turnos t')
            ->select([
                'a.id_alumno AS id',
                'a.id_alumno AS alumno_id',
                't.id_turno AS turno_id',
                't.folio AS turno',
                'a.nombre_completo AS nombre',
                'COALESCE(NULLIF(a.numero_control, ""), a.numero_ficha) AS no_control',
                'COALESCE(NULLIF(a.carrera_nombre, ""), a.carrera_clave) AS carrera',
                'NULL AS semestre',
                's.nombre AS estatus',
                'e.codigo AS etapa_codigo',
                'e.nombre AS etapa',
                't.created_at AS created_at',
                'a.huella_archivo_id AS huella_archivo_id',
            ])
            ->join('alumnos a', 'a.id_alumno = t.alumno_id', 'inner')
            ->join('cat_etapas e', 'e.id_etapa = t.etapa_actual_id', 'left')
            ->join('cat_estatus_turno s', 's.id_estatus = t.estatus_turno_id', 'left')
            ->join('turno_eventos te_fingerprint', "te_fingerprint.turno_id = t.id_turno AND te_fingerprint.tipo_evento = 'huella_guardada'", 'left')
            ->where('t.es_activo', 1)
            ->where('t.fecha_expira >=', date('Y-m-d H:i:s'))
            ->where('te_fingerprint.turno_id IS NULL', null, false)
            ->groupStart()
                ->where('s.codigo IS NULL', null, false)
                ->orWhereNotIn('s.codigo', ['vencido', 'cancelado', 'finalizado', 'COMPLETADO', 'RECHAZADO'])
            ->groupEnd();
    }

    private function decodeImageDataUrl(string $dataUrl): array
    {
        if (!preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#', $dataUrl, $matches)) {
            throw new RuntimeException('The fingerprint image format is invalid.');
        }

        $mime = strtolower($matches[1]);
        $binary = base64_decode($matches[2], true);

        if ($binary === false) {
            throw new RuntimeException('Could not decode the fingerprint image.');
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => throw new RuntimeException('The fingerprint image type is not supported.'),
        };

        return [$binary, $mime, $extension];
    }

    private function findStageId(array $codes): ?int
    {
        foreach ($codes as $code) {
            $row = $this->db->table('cat_etapas')
                ->select('id_etapa')
                ->where('codigo', $code)
                ->get(1)
                ->getRowArray();

            if ($row) {
                return (int) $row['id_etapa'];
            }
        }

        return null;
    }

    private function findStatusId(array $codes): ?int
    {
        foreach ($codes as $code) {
            $row = $this->db->table('cat_estatus_turno')
                ->select('id_estatus')
                ->where('codigo', $code)
                ->get(1)
                ->getRowArray();

            if ($row) {
                return (int) $row['id_estatus'];
            }
        }

        return null;
    }
}
