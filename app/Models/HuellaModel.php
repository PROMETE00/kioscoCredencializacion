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

    /**
     * Devuelve el alumno actual por ID de turno.
     */
    public function getCurrentByTurno(int $turnoId): ?array
    {
        $row = $this->baseQueueBuilder()
            ->where('t.id_turno', $turnoId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Regresa el siguiente alumno pendiente de huella.
     */
    public function getNextPending(): ?array
    {
        $row = $this->baseQueueBuilder()
            ->orderBy('t.created_at', 'ASC')
            ->orderBy('t.id_turno', 'ASC')
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Cola completa de alumnos listos para captura de huella.
     */
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

    /**
     * Guarda la huella dactilar (imagen base64) y actualiza el estado del turno.
     */
    public function saveHuella(int $alumnoId, int $turnoId, string $huellaB64): array
    {
        $registro = $this->baseQueueBuilder()
            ->select('t.id_turno, a.id_alumno')
            ->where('t.id_turno', $turnoId)
            ->where('a.id_alumno', $alumnoId)
            ->get()
            ->getRowArray();

        if (!$registro) {
            throw new RuntimeException('El turno seleccionado no está disponible para captura de huella.');
        }

        [$binary, $mime, $extension] = $this->decodeImageDataUrl($huellaB64);

        $relativePath = 'uploads/huellas/huella_' . $alumnoId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $absolutePath = FCPATH . $relativePath;
        $dir = dirname($absolutePath);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo preparar el directorio para guardar la huella.');
        }

        if (file_put_contents($absolutePath, $binary) === false) {
            throw new RuntimeException('No se pudo escribir el archivo de la huella.');
        }

        $sha256 = hash('sha256', $binary);
        $tamano = filesize($absolutePath);
        $ahora = date('Y-m-d H:i:s');

        $db = $this->db;
        $db->transStart();

        $db->table('archivos')->insert([
            'tipo'         => 'huella',
            'ruta'         => $relativePath,
            'sha256'       => $sha256,
            'mime'         => $mime,
            'tamano_bytes' => $tamano,
            'created_at'   => $ahora,
        ]);

        $archivoId = (int) $db->insertID();

        $db->table('alumnos')
            ->where('id_alumno', $alumnoId)
            ->update([
                'huella_archivo_id' => $archivoId,
                'updated_at'        => $ahora,
            ]);

        $turnoActual = $db->table('turnos')
            ->select('etapa_actual_id, estatus_turno_id')
            ->where('id_turno', $turnoId)
            ->get()
            ->getRowArray();

        $nuevaEtapaId = $this->findEtapaId(['HUELLA_CAPTURADA', 'COMPLETADO']);
        $nuevoEstatusId = $this->findEstatusId(['EN_PROCESO', 'FINALIZADO']);

        $turnoUpdate = ['updated_at' => $ahora];
        if ($nuevaEtapaId !== null) {
            $turnoUpdate['etapa_actual_id'] = $nuevaEtapaId;
        }
        if ($nuevoEstatusId !== null) {
            $turnoUpdate['estatus_turno_id'] = $nuevoEstatusId;
        }

        $db->table('turnos')
            ->where('id_turno', $turnoId)
            ->update($turnoUpdate);

        $db->table('turno_eventos')->insert([
            'turno_id'             => $turnoId,
            'tipo_evento'          => 'huella_guardada',
            'etapa_anterior_id'    => $turnoActual['etapa_actual_id'] ?? null,
            'etapa_nueva_id'       => $nuevaEtapaId,
            'estatus_anterior_id'  => $turnoActual['estatus_turno_id'] ?? null,
            'estatus_nuevo_id'     => $nuevoEstatusId,
            'usuario_id'           => session('user_id') ?: null,
            'detalle_json'         => json_encode(['archivo_id' => $archivoId, 'ruta' => $relativePath], JSON_UNESCAPED_UNICODE),
            'created_at'           => $ahora,
        ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            @unlink($absolutePath);
            throw new RuntimeException('No fue posible guardar la huella en la base de datos.');
        }

        return [
            'archivo_id' => $archivoId,
            'url'        => base_url($relativePath),
            'ruta'       => $relativePath,
        ];
    }

    private function baseQueueBuilder()
    {
        $ahora = date('Y-m-d H:i:s');

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
                'a.foto_archivo_id AS foto_archivo_id',
                'a.firma_archivo_id AS firma_archivo_id',
            ])
            ->join('alumnos a', 'a.id_alumno = t.alumno_id', 'inner')
            ->join('cat_etapas e', 'e.id_etapa = t.etapa_actual_id', 'left')
            ->join('cat_estatus_turno s', 's.id_estatus = t.estatus_turno_id', 'left')
            // Solo alumnos que ya tienen firma pero no tienen huella
            ->join('turno_eventos te_firma', "te_firma.turno_id = t.id_turno AND te_firma.tipo_evento = 'firma_guardada'", 'inner')
            ->join('turno_eventos te_huella', "te_huella.turno_id = t.id_turno AND te_huella.tipo_evento = 'huella_guardada'", 'left')
            ->where('t.es_activo', 1)
            ->where('t.fecha_expira >=', $ahora)
            ->where('te_huella.turno_id IS NULL', null, false)
            ->groupStart()
                ->where('s.codigo IS NULL', null, false)
                ->orWhereNotIn('s.codigo', ['vencido', 'cancelado', 'finalizado', 'COMPLETADO', 'RECHAZADO'])
            ->groupEnd();
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
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/bmp' => 'bmp',
            default => throw new RuntimeException('El tipo de imagen de la huella no está soportado.'),
        };

        return [$binary, $mime, $extension];
    }

    private function findEtapaId(array $codigos): ?int
    {
        foreach ($codigos as $codigo) {
            $row = $this->db->table('cat_etapas')
                ->select('id_etapa')
                ->where('codigo', $codigo)
                ->get(1)
                ->getRowArray();

            if ($row) {
                return (int) $row['id_etapa'];
            }
        }

        return null;
    }

    private function findEstatusId(array $codigos): ?int
    {
        foreach ($codigos as $codigo) {
            $row = $this->db->table('cat_estatus_turno')
                ->select('id_estatus')
                ->where('codigo', $codigo)
                ->get(1)
                ->getRowArray();

            if ($row) {
                return (int) $row['id_estatus'];
            }
        }

        return null;
    }
}
