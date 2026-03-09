<?php

namespace App\Controllers\Publico;

use App\Controllers\BaseController;
use App\Models\AlumnoModel;
use App\Models\TurnoModel;
use App\Models\CatEtapaModel;
use App\Models\CatEstatusTurnoModel;
use App\Models\TurnoEventoModel;

class TurnoPublicController extends BaseController
{
    public function nuevo()
    {
        return view('public/turno_generar', [
            'error' => session()->getFlashdata('error'),
            'ok'    => session()->getFlashdata('ok'),
        ]);
    }

    public function crear()
    {
        $rules = [
            'identificador' => 'required|min_length[4]|max_length[20]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Escribe tu No. de control o No. de ficha.');
        }

        $ident = trim((string)$this->request->getPost('identificador'));

        $alumnoModel = new AlumnoModel();
        $alumno = $alumnoModel
            ->groupStart()
                ->where('numero_control', $ident)
                ->orWhere('numero_ficha', $ident)
            ->groupEnd()
            ->first();

        if (!$alumno) {
            return redirect()->back()->withInput()->with('error', 'No se encontró el alumno con ese dato.');
        }

        $turnoModel   = new TurnoModel();
        $etapaModel   = new CatEtapaModel();
        $estatusModel = new CatEstatusTurnoModel();
        $eventoModel  = new TurnoEventoModel();

        $etapaId   = $etapaModel->primeraEtapaId();
        $estatusId = $estatusModel->estatusInicialId();

        if (!$etapaId || !$estatusId) {
            return redirect()->back()->with('error', 'Faltan catálogos (cat_etapas / cat_estatus_turno).');
        }

        // Token corto para URL + guardado como hash en BD
        $token = $this->makeToken();
        $tokenHash = hash('sha256', $token);

        $ahora = date('Y-m-d H:i:s');
        $expira = date('Y-m-d 23:59:59'); // fin del día

        $db = \Config\Database::connect();
        $db->transStart();

        // Inserta con folio temporal y luego lo actualizas con el ID (simple y único)
        $turnoId = $turnoModel->insert([
            'folio'            => 'PEND',
            'alumno_id'        => (int)$alumno['id'],
            'estatus_turno_id' => (int)$estatusId,
            'etapa_actual_id'  => (int)$etapaId,
            'es_activo'        => 1,
            'fecha_expira'     => $expira,
            'qr_token_hash'    => $tokenHash,
            'llamado_at'       => null,
            'creado_at'        => $ahora,
            'updated_at'       => $ahora,
        ], true);

        // Folio por ID (ej. A-000123)
        $folio = 'A-' . str_pad((string)$turnoId, 6, '0', STR_PAD_LEFT);
        $turnoModel->update($turnoId, ['folio' => $folio, 'updated_at' => $ahora]);

        // Evento de creación
        $eventoModel->insert([
            'turno_id'            => (int)$turnoId,
            'tipo_evento'         => 'CREATED',
            'etapa_anterior_id'   => null,
            'etapa_nueva_id'      => (int)$etapaId,
            'estatus_anterior_id' => null,
            'estatus_nuevo_id'    => (int)$estatusId,
            'usuario_id'          => null,
            'detalle_json'        => json_encode([
                'ip' => $this->request->getIPAddress(),
                'ua' => $this->request->getUserAgent()->getAgentString(),
            ], JSON_UNESCAPED_UNICODE),
            'creado_at'           => $ahora,
        ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'No se pudo generar el turno. Intenta de nuevo.');
        }

        $url = base_url('t/' . $token);

        return view('public/turno_qr', [
            'folio'  => $folio,
            'nombre' => $alumno['nombre_completo'],
            'url'    => $url,
            'expira' => $expira,
        ]);
    }

    public function estado(string $token)
    {
        $token = trim($token);
        if ($token === '') {
            return redirect()->to(base_url('turno'));
        }

        $hash = hash('sha256', $token);

        $db = \Config\Database::connect();
        $row = $db->table('turnos t')
            ->select('t.folio, t.es_activo, t.fecha_expira, t.llamado_at, t.creado_at,
                      e.nombre AS etapa, s.nombre AS estatus')
            ->join('cat_etapas e', 'e.id = t.etapa_actual_id', 'left')
            ->join('cat_estatus_turno s', 's.id = t.estatus_turno_id', 'left')
            ->where('t.qr_token_hash', $hash)
            ->orderBy('t.id', 'DESC')
            ->get()
            ->getRowArray();

        if (!$row) {
            return view('public/turno_estado', ['notFound' => true]);
        }

        return view('public/turno_estado', [
            'notFound' => false,
            'turno' => $row,
        ]);
    }

    private function makeToken(): string
    {
        // base64url (corto, ideal para QR/URL)
        $raw = random_bytes(24);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}