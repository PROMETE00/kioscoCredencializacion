<?php

namespace App\Models;

use CodeIgniter\Model;

class CaptureQueueModel extends Model
{
    protected $DBGroup = 'default';
    protected $returnType = 'array';

    // Tablas reales
    private string $tblTurnos  = 'turnos';
    private string $tblAlumnos = 'alumnos';
    private string $tblEtapas  = 'cat_etapas';
    private string $tblEstatus = 'cat_estatus_turno';

    // PK/FK reales
    private string $pkTurno   = 'id_turno';
    private string $pkAlumno  = 'id_alumno';
    private string $pkEtapa   = 'id_etapa';
    private string $pkEstatus = 'id_estatus';

    private string $fkTurnoAlumno  = 'alumno_id';
    private string $fkTurnoEtapa   = 'etapa_actual_id';
    private string $fkTurnoEstatus = 'estatus_turno_id';

    // Campos reales
    private string $alNoControl = 'numero_control';
    private string $alNombre    = 'nombre_completo';
    private string $alCarrera   = 'carrera_nombre'; // o carrera_clave si prefieres

    private string $etapaCodigo   = 'codigo';
    private string $estatusCodigo = 'codigo';

    /**
     * Query base: cola de CAPTURA_FOTO
     * Devuelve campos ya “compatibles” con tu UI: id, alumno_id, no_control, nombre, carrera, etapa, estatus
     */
    private function baseQueueBuilder()
    {
        $t = $this->tblTurnos;
        $a = $this->tblAlumnos;
        $e = $this->tblEtapas;
        $s = $this->tblEstatus;

        return $this->db->table("$t t")
            ->select([
                "a.{$this->pkAlumno} AS id",
                "t.{$this->pkTurno} AS turno_id",
                "t.{$this->fkTurnoAlumno} AS alumno_id",
                "a.{$this->alNoControl} AS no_control",
                "a.{$this->alNombre} AS nombre",
                "a.{$this->alCarrera} AS carrera",
                "NULL AS semestre",                              // no existe en alumnos
                "e.{$this->etapaCodigo} AS etapa",
                "s.{$this->estatusCodigo} AS estatus",
                "t.created_at AS created_at",
                "t.llamado_at AS llamado_at",
                "a.foto_archivo_id AS foto_archivo_id",
            ])
            ->join("$a a", "a.{$this->pkAlumno} = t.{$this->fkTurnoAlumno}", "inner")
            ->join("$e e", "e.{$this->pkEtapa} = t.{$this->fkTurnoEtapa}", "left")
            ->join("$s s", "s.{$this->pkEstatus} = t.{$this->fkTurnoEstatus}", "left")
            ->join('turno_eventos te_foto', "te_foto.turno_id = t.{$this->pkTurno} AND te_foto.tipo_evento = 'foto_guardada'", 'left')
            ->where("t.es_activo", 1)
            ->where("t.fecha_expira >=", date('Y-m-d H:i:s'))
            ->where('te_foto.turno_id IS NULL', null, false)
            ->groupStart()
                ->where('s.codigo IS NULL', null, false)
                ->orWhereNotIn("s.{$this->estatusCodigo}", ['vencido', 'cancelado', 'finalizado', 'COMPLETADO', 'RECHAZADO'])
            ->groupEnd();
    }

    public function getQueue(int $limit = 200): array
    {
        return $this->baseQueueBuilder()
            ->orderBy("t.created_at", "ASC")
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getCurrent(?int $turnoId = null): ?array
    {
        $b = $this->baseQueueBuilder();

        if ($turnoId) {
            $b->where("t.{$this->pkTurno}", $turnoId);
        }

        $row = $b->orderBy("t.created_at", "ASC")->get(1)->getFirstRow('array');
        return $row ?: null;
    }

    public function getByAlumnoId(int $alumnoId): ?array
    {
        $row = $this->baseQueueBuilder()
            ->where("a.{$this->pkAlumno}", $alumnoId)
            ->orderBy("t.created_at", "ASC")
            ->get(1)
            ->getFirstRow('array');

        return $row ?: null;
    }

    public function markCaptured(int $alumnoId, int $turnoId, string $relativePath, string $mime, int $size, string $sha256): array
    {
        $registro = $this->baseQueueBuilder()
            ->where("a.{$this->pkAlumno}", $alumnoId)
            ->where("t.{$this->pkTurno}", $turnoId)
            ->get(1)
            ->getFirstRow('array');

        if (!$registro) {
            throw new \RuntimeException('El turno seleccionado ya no está disponible para captura de foto.');
        }

        $ahora = date('Y-m-d H:i:s');
        $db = $this->db;
        $db->transStart();

        $db->table('archivos')->insert([
            'tipo'         => 'foto',
            'ruta'         => $relativePath,
            'sha256'       => $sha256,
            'mime'         => $mime,
            'tamano_bytes' => $size,
            'created_at'   => $ahora,
        ]);

        $archivoId = (int) $db->insertID();

        $db->table($this->tblAlumnos)
            ->where($this->pkAlumno, $alumnoId)
            ->update([
                'foto_archivo_id' => $archivoId,
                'updated_at'      => $ahora,
            ]);

        $turnoActual = $db->table($this->tblTurnos)
            ->select("{$this->fkTurnoEtapa}, {$this->fkTurnoEstatus}")
            ->where($this->pkTurno, $turnoId)
            ->get(1)
            ->getFirstRow('array');

        $nuevaEtapaId = $this->getEtapaIdByCodigo('foto_registrada') ?? $this->getEtapaIdByCodigo('capturado');
        $nuevoEstatusId = $this->getEstatusIdByCodigo('activo') ?? $this->getEstatusIdByCodigo('EN_PROCESO');

        $turnoUpdate = ['updated_at' => $ahora];
        if ($nuevaEtapaId) {
            $turnoUpdate[$this->fkTurnoEtapa] = $nuevaEtapaId;
        }
        if ($nuevoEstatusId) {
            $turnoUpdate[$this->fkTurnoEstatus] = $nuevoEstatusId;
        }

        $db->table($this->tblTurnos)
            ->where($this->pkTurno, $turnoId)
            ->update($turnoUpdate);

        $db->table('turno_eventos')->insert([
            'turno_id'             => $turnoId,
            'tipo_evento'          => 'foto_guardada',
            'etapa_anterior_id'    => $turnoActual[$this->fkTurnoEtapa] ?? null,
            'etapa_nueva_id'       => $nuevaEtapaId,
            'estatus_anterior_id'  => $turnoActual[$this->fkTurnoEstatus] ?? null,
            'estatus_nuevo_id'     => $nuevoEstatusId,
            'usuario_id'           => session('user_id') ?: null,
            'detalle_json'         => json_encode(['archivo_id' => $archivoId, 'ruta' => $relativePath], JSON_UNESCAPED_UNICODE),
            'created_at'           => $ahora,
        ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            throw new \RuntimeException('No fue posible guardar la fotografia en la base de datos.');
        }

        return [
            'archivo_id' => $archivoId,
            'ruta'       => $relativePath,
        ];
    }

    /**
     * Cambiar etapa/estatus del turno después de guardar foto (opcional).
     * Útil cuando definas el flujo real (ej. pasar a CAPTURA_FIRMA).
     */
    private function getEtapaIdByCodigo(string $codigo): ?int
    {
        $row = $this->db->table($this->tblEtapas)
            ->select($this->pkEtapa)
            ->where($this->etapaCodigo, $codigo)
            ->get(1)->getFirstRow('array');

        return $row ? (int)$row[$this->pkEtapa] : null;
    }

    private function getEstatusIdByCodigo(string $codigo): ?int
    {
        $row = $this->db->table($this->tblEstatus)
            ->select($this->pkEstatus)
            ->where($this->estatusCodigo, $codigo)
            ->get(1)->getFirstRow('array');

        return $row ? (int)$row[$this->pkEstatus] : null;
    }
}
