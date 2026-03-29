<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

class DashboardModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getAdminStudents(string $q = '', int $limit = 8): array
    {
        $limit = max(1, min(25, $limit));
        $builder = $this->baseAdminBuilder();

        if ($q !== '') {
            $builder->groupStart()
                ->like('a.numero_control', $q)
                ->orLike('a.numero_ficha', $q)
                ->orLike('a.nombre_completo', $q)
                ->orLike('a.carrera_nombre', $q)
                ->orLike('t.folio', $q)
            ->groupEnd();
        }

        $rows = $builder
            ->orderBy('CASE WHEN t.id_turno IS NULL THEN 1 ELSE 0 END', '', false)
            ->orderBy('COALESCE(t.updated_at, a.updated_at, a.created_at)', 'DESC', false)
            ->orderBy('a.id_alumno', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        return array_map(fn (array $row) => $this->normalizeRow($row), $rows);
    }

    public function getStatusOptions(): array
    {
        return $this->db->table('cat_estatus_turno')
            ->select('id_estatus, codigo, nombre')
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getKpis(): array
    {
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd   = date('Y-m-d 23:59:59');

        $totalAlumnos = $this->db->table('alumnos')->countAllResults();

        $turnosHoy = $this->db->table('turnos')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $fotosHoy = $this->db->table('archivos')
            ->where('tipo', 'foto')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $firmasHoy = $this->db->table('archivos')
            ->where('tipo', 'firma')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $huellasHoy = $this->db->table('archivos')
            ->where('tipo', 'huella')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        // Trámites completados hoy (aquellos que pasaron a la etapa FINALIZADO o COMPLETADO hoy)
        $completadosHoy = $this->db->table('turno_eventos')
            ->where('tipo_evento', 'huella_guardada') // Asumiendo que huella es el último paso
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        return [
            'total_alumnos'   => $totalAlumnos,
            'turnos_hoy'      => $turnosHoy,
            'fotos_hoy'       => $fotosHoy,
            'firmas_hoy'      => $firmasHoy,
            'huellas_hoy'     => $huellasHoy,
            'completados_hoy' => $completadosHoy,
        ];
    }

    public function updateTurnStatus(int $turnoId, int $estatusId): array
    {
        $turno = $this->db->table('turnos')
            ->select('id_turno, alumno_id, estatus_turno_id, etapa_actual_id')
            ->where('id_turno', $turnoId)
            ->get(1)
            ->getRowArray();

        if (!$turno) {
            throw new RuntimeException('El turno seleccionado no existe.');
        }

        $estatus = $this->db->table('cat_estatus_turno')
            ->select('id_estatus')
            ->where('id_estatus', $estatusId)
            ->get(1)
            ->getRowArray();

        if (!$estatus) {
            throw new RuntimeException('El estatus seleccionado no existe.');
        }

        $ahora = date('Y-m-d H:i:s');

        $this->db->transStart();

        $this->db->table('turnos')
            ->where('id_turno', $turnoId)
            ->update([
                'estatus_turno_id' => $estatusId,
                'updated_at'       => $ahora,
            ]);

        if ((int) $turno['estatus_turno_id'] !== $estatusId) {
            $this->db->table('turno_eventos')->insert([
                'turno_id'             => $turnoId,
                'tipo_evento'          => 'estatus_actualizado_dashboard',
                'etapa_anterior_id'    => $turno['etapa_actual_id'] ?? null,
                'etapa_nueva_id'       => $turno['etapa_actual_id'] ?? null,
                'estatus_anterior_id'  => $turno['estatus_turno_id'] ?? null,
                'estatus_nuevo_id'     => $estatusId,
                'usuario_id'           => session('user_id') ?: null,
                'detalle_json'         => json_encode(['origen' => 'dashboard'], JSON_UNESCAPED_UNICODE),
                'created_at'           => $ahora,
            ]);
        }

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('No fue posible actualizar el estatus del turno.');
        }

        return $this->getRowByAlumnoId((int) $turno['alumno_id']) ?? [];
    }

    public function clearBiometric(int $alumnoId, int $turnoId, string $tipo): array
    {
        $config = [
            'foto'   => ['field' => 'foto_archivo_id', 'event' => 'foto_guardada'],
            'firma'  => ['field' => 'firma_archivo_id', 'event' => 'firma_guardada'],
            'huella' => ['field' => 'huella_archivo_id', 'event' => 'huella_guardada'],
        ];

        if (!isset($config[$tipo])) {
            throw new RuntimeException('El biométrico solicitado no es válido.');
        }

        $row = $this->getRowByAlumnoId($alumnoId);

        if (!$row || (int) ($row['turno_id'] ?? 0) !== $turnoId) {
            throw new RuntimeException('El alumno seleccionado ya no tiene ese turno activo.');
        }

        $field = $config[$tipo]['field'];
        $event = $config[$tipo]['event'];
        $ahora = date('Y-m-d H:i:s');

        if (empty($row[$field])) {
            return $row;
        }

        $this->db->transStart();

        $this->db->table('alumnos')
            ->where('id_alumno', $alumnoId)
            ->update([
                $field       => null,
                'updated_at' => $ahora,
            ]);

        $this->db->table('turno_eventos')
            ->where('turno_id', $turnoId)
            ->where('tipo_evento', $event)
            ->delete();

        $updatedArtifacts = [
            'foto'   => $tipo === 'foto' ? false : !empty($row['foto_archivo_id']),
            'firma'  => $tipo === 'firma' ? false : !empty($row['firma_archivo_id']),
            'huella' => $tipo === 'huella' ? false : !empty($row['huella_archivo_id']),
        ];

        $etapaId = $this->resolveStageIdFromArtifacts($updatedArtifacts);

        $turnoUpdate = ['updated_at' => $ahora];
        if ($etapaId !== null) {
            $turnoUpdate['etapa_actual_id'] = $etapaId;
        }

        $this->db->table('turnos')
            ->where('id_turno', $turnoId)
            ->update($turnoUpdate);

        $this->db->table('turno_eventos')->insert([
            'turno_id'             => $turnoId,
            'tipo_evento'          => $tipo . '_borrada_dashboard',
            'etapa_anterior_id'    => $row['etapa_id'] ?? null,
            'etapa_nueva_id'       => $etapaId,
            'estatus_anterior_id'  => $row['estatus_id'] ?? null,
            'estatus_nuevo_id'     => $row['estatus_id'] ?? null,
            'usuario_id'           => session('user_id') ?: null,
            'detalle_json'         => json_encode(['origen' => 'dashboard'], JSON_UNESCAPED_UNICODE),
            'created_at'           => $ahora,
        ]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('No fue posible borrar el biométrico seleccionado.');
        }

        return $this->getRowByAlumnoId($alumnoId) ?? [];
    }

    private function baseAdminBuilder()
    {
        return $this->db->table('alumnos a')
            ->select([
                'a.id_alumno AS alumno_id',
                'a.numero_control',
                'a.numero_ficha',
                'a.nombre_completo AS nombre',
                'COALESCE(NULLIF(a.carrera_nombre, ""), a.carrera_clave, "—") AS carrera',
                '"OAXACA" AS campus',
                'a.foto_archivo_id',
                'a.firma_archivo_id',
                'a.huella_archivo_id',
                'a.updated_at AS alumno_updated_at',
                't.id_turno AS turno_id',
                't.folio',
                't.fecha_expira',
                't.updated_at AS turno_updated_at',
                's.id_estatus AS estatus_id',
                's.codigo AS estatus_codigo',
                's.nombre AS estatus_nombre',
                'e.id_etapa AS etapa_id',
                'e.codigo AS etapa_codigo',
                'e.nombre AS etapa_nombre',
            ])
            ->join('turnos t', 't.alumno_id = a.id_alumno AND t.es_activo = 1', 'left')
            ->join('cat_estatus_turno s', 's.id_estatus = t.estatus_turno_id', 'left')
            ->join('cat_etapas e', 'e.id_etapa = t.etapa_actual_id', 'left');
    }

    private function getRowByAlumnoId(int $alumnoId): ?array
    {
        $row = $this->baseAdminBuilder()
            ->where('a.id_alumno', $alumnoId)
            ->orderBy('CASE WHEN t.id_turno IS NULL THEN 1 ELSE 0 END', '', false)
            ->orderBy('COALESCE(t.updated_at, a.updated_at, a.created_at)', 'DESC', false)
            ->get(1)
            ->getRowArray();

        return $row ? $this->normalizeRow($row) : null;
    }

    private function normalizeRow(array $row): array
    {
        $row['identificador'] = $row['numero_control'] ?: ($row['numero_ficha'] ?: '—');
        $row['estatus_nombre'] = $row['estatus_nombre'] ?: 'Sin turno';
        $row['etapa_nombre'] = $row['etapa_nombre'] ?: 'Sin turno';
        $row['updated_at'] = $row['turno_updated_at'] ?: $row['alumno_updated_at'];
        $row['has_foto'] = !empty($row['foto_archivo_id']);
        $row['has_firma'] = !empty($row['firma_archivo_id']);
        $row['has_huella'] = !empty($row['huella_archivo_id']);

        return $row;
    }

    private function resolveStageIdFromArtifacts(array $artifacts): ?int
    {
        $codigo = 'turno_generado';

        if (!empty($artifacts['huella'])) {
            $codigo = 'huella_registrada';
        } elseif (!empty($artifacts['firma'])) {
            $codigo = 'firma_registrada';
        } elseif (!empty($artifacts['foto'])) {
            $codigo = 'foto_registrada';
        }

        $row = $this->db->table('cat_etapas')
            ->select('id_etapa')
            ->where('codigo', $codigo)
            ->get(1)
            ->getRowArray();

        return $row ? (int) $row['id_etapa'] : null;
    }
}
