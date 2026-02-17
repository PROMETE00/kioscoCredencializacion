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

    // AJUSTA ESTOS CÓDIGOS según tus catálogos (YA AJUSTADO A TU BD)
    private string $ETAPA_CAPTURA_FOTO = 'capturado';
    private array  $ESTATUS_EN_COLA    = ['activo'];


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
                "t.{$this->pkTurno} AS id",                      // id del TURNO
                "t.{$this->fkTurnoAlumno} AS alumno_id",
                "a.{$this->alNoControl} AS no_control",
                "a.{$this->alNombre} AS nombre",
                "a.{$this->alCarrera} AS carrera",
                "NULL AS semestre",                              // no existe en alumnos
                "e.{$this->etapaCodigo} AS etapa",
                "s.{$this->estatusCodigo} AS estatus",
                "t.created_at AS created_at",
                "t.llamado_at AS llamado_at",
            ])
            ->join("$a a", "a.{$this->pkAlumno} = t.{$this->fkTurnoAlumno}", "inner")
            ->join("$e e", "e.{$this->pkEtapa} = t.{$this->fkTurnoEtapa}", "inner")
            ->join("$s s", "s.{$this->pkEstatus} = t.{$this->fkTurnoEstatus}", "inner")
            ->where("t.es_activo", 1)
            ->where("e.{$this->etapaCodigo}", $this->ETAPA_CAPTURA_FOTO)
            ->whereIn("s.{$this->estatusCodigo}", $this->ESTATUS_EN_COLA);
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

    /**
     * Cambiar etapa/estatus del turno después de guardar foto (opcional).
     * Útil cuando definas el flujo real (ej. pasar a CAPTURA_FIRMA).
     */
    public function transitionTurno(int $turnoId, ?string $nextEtapaCodigo = null, ?string $nextEstatusCodigo = null): bool
    {
        $data = ['updated_at' => date('Y-m-d H:i:s')];

        if ($nextEtapaCodigo) {
            $nextEtapaId = $this->getEtapaIdByCodigo($nextEtapaCodigo);
            if ($nextEtapaId) $data[$this->fkTurnoEtapa] = $nextEtapaId;
        }

        if ($nextEstatusCodigo) {
            $nextEstatusId = $this->getEstatusIdByCodigo($nextEstatusCodigo);
            if ($nextEstatusId) $data[$this->fkTurnoEstatus] = $nextEstatusId;
        }

        return (bool) $this->db->table($this->tblTurnos)
            ->where($this->pkTurno, $turnoId)
            ->update($data);
    }

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