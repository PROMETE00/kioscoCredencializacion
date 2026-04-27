<?php
// app/Controllers/HuellaController.php
namespace App\Modules\PublicPortal\Controllers;

use App\Controllers\BaseController;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\Binary\ByteBuffer;

class FingerprintController extends BaseController
{
    private WebAuthn $webAuthn;
    private string $rpName = 'Instituto Tecnológico de Oaxaca';

    public function __construct()
    {
        // El rpId DEBE coincidir exactamente con tu dominio (sin https://)
        $this->webAuthn = new WebAuthn($this->rpName, $_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    /**
     * Muestra la vista de autoservicio para la huella.
     */
    public function index()
    {
        $pending = session()->get('pending_huella');
        if (!$pending) {
            return redirect()->to(base_url('turno'));
        }

        return view('public/autoservicio_huella', [
            'turno'     => $pending['turno'],
            'alumno'    => ['nombre' => $pending['turno']['nombre_completo'], 'identificador' => $pending['turno']['identificador'], 'carrera' => $pending['turno']['carrera'], 'campus' => 'Instituto Tecnológico de Oaxaca'],
            'studentId' => $pending['student_id'],
            'ticketId'  => $pending['ticket_id'],
        ]);
    }

    /**
     * Finaliza el proceso de captura de biométricos.
     */
    public function finishFlow()
    {
        $pending = session()->get('pending_huella');
        if (!$pending) {
            return redirect()->to(base_url('turno'));
        }

        $studentId    = (int) ($this->request->getPost('alumno_id') ?? 0);
        $ticketId     = (int) ($this->request->getPost('turno_id') ?? 0);
        $signatureB64 = (string) ($this->request->getPost('firma_png') ?? '');

        // Validar que coincidan con la sesión
        if ($studentId !== (int) $pending['student_id'] || $ticketId !== (int) $pending['ticket_id']) {
            return redirect()->to(base_url('turno'));
        }

        // Si se envió una firma en este paso final, guardarla físicamente
        if ($signatureB64 !== '' && str_starts_with($signatureB64, 'data:image/')) {
            try {
                $this->saveFinalSignature($studentId, $ticketId, $signatureB64);
            } catch (\RuntimeException $e) {
                log_message('error', 'Final signature save failed: ' . $e->getMessage());
            }
        }

        session()->set('pending_photo', [
            'student_id' => $studentId,
            'ticket_id'  => $ticketId,
            'turno'      => $pending['turno'],
        ]);
        session()->remove('pending_huella');
        
        return redirect()->to(base_url('foto'));
    }

    private function saveFinalSignature(int $studentId, int $ticketId, string $dataUrl): void
    {
        if (!preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#', $dataUrl, $matches)) {
            throw new \RuntimeException('Formato de firma inválido.');
        }

        $binary = base64_decode($matches[2], true);
        $relativePath = 'uploads/firmas/final_signature_' . $studentId . '_' . date('Ymd_His') . '.png';
        $absolutePath = FCPATH . $relativePath;

        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        file_put_contents($absolutePath, $binary);

        $db = \Config\Database::connect();
        $db->table('files')->insert([
            'type'       => 'signature',
            'path'       => $relativePath,
            'sha256'     => hash('sha256', $binary),
            'mime'       => 'image/png',
            'size_bytes' => strlen($binary),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        $fileId = $db->insertID();
        $db->table('students')->where('id', $studentId)->update(['signature_file_id' => $fileId]);
    }

    // ── 1. Generar challenge de REGISTRO ──────────────────────────────
    public function registerChallenge()
    {
        $alumnoId   = $this->request->getPost('alumno_id');
        $alumnoNombre = $this->request->getPost('nombre');

        // Generar argumentos de creación
        $createArgs = $this->webAuthn->getCreateArgs(
            $alumnoId,                   // userId (bytes)
            $alumnoId,                   // userName
            $alumnoNombre,               // displayName
            60,                          // timeout
            false,                       // residentKey
            'required',                  // userVerification
            null,
            'platform'                   // Lector HID / Platform
        );

        // Guardar el challenge en la sesión para la verificación posterior
        session()->set('webauthn_challenge', $this->webAuthn->getChallenge()->getBinaryString());
        session()->set('webauthn_user_id', $alumnoId);

        // IMPORTANTE: La librería devuelve objetos ByteBuffer que Json_encode no siempre maneja bien.
        // Nos aseguramos de que los campos críticos sean strings para el JS.
        return $this->response->setJSON($createArgs);
    }

    // ── 2. Verificar y GUARDAR credencial registrada ──────────────────
    public function Verifyregister()
    {
        $data = $this->request->getJSON(true);

        $challenge = session()->get('webauthn_challenge');
        $alumnoId  = session()->get('webauthn_user_id');

        if (!$challenge) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => 'Challenge no encontrado o expirado']);
        }

        try {
            $clientDataJSON    = base64_decode($data['response']['clientDataJSON']);
            $attestationObject = base64_decode($data['response']['attestationObject']);

            $credential = $this->webAuthn->processCreate(
                $clientDataJSON,
                $attestationObject,
                new ByteBuffer($challenge),
                'required',  // userVerification
                true,        // verificar origen
                false        // no verificar token binding
            );

            // Guardar credencial en BD
            $db = \Config\Database::connect();
            $db->table('huellas_credenciales')->insert([
                'alumno_id'       => $alumnoId,
                'credential_id'   => base64_encode($credential->credentialId),
                'public_key'      => base64_encode($credential->publicKey),
                'sign_count'      => $credential->signCount,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            session()->remove('webauthn_challenge');

            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => $e->getMessage()]);
        }
    }

    // ── 3. Generar challenge de AUTENTICACIÓN ─────────────────────────
    public function authChallenge()
    {
        $alumnoId = $this->request->getPost('alumno_id');

        // Buscar credenciales existentes del alumno
        $db = \Config\Database::connect();
        $creds = $db->table('huellas_credenciales')
            ->where('alumno_id', $alumnoId)->get()->getResultArray();

        if (empty($creds)) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'No hay huella registrada para este alumno']);
        }

        $credIds = array_map(fn($c) => base64_decode($c['credential_id']), $creds);

        $getArgs = $this->webAuthn->getGetArgs(
            $credIds,
            60,
            true, true, true, true, // transportes
            'required'              // userVerification obligatorio
        );

        session()->set('webauthn_challenge', $this->webAuthn->getChallenge()->getBinaryString());
        session()->set('webauthn_user_id', $alumnoId);

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($getArgs));
    }

    // ── 4. Verificar AUTENTICACIÓN ────────────────────────────────────
    public function Verifyauth()
    {
        $data = $this->request->getJSON(true);

        $challenge = session()->get('webauthn_challenge');
        $alumnoId  = session()->get('webauthn_user_id');

        if (!$challenge) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => 'Challenge no encontrado o expirado']);
        }

        $db = \Config\Database::connect();
        $credRow = $db->table('huellas_credenciales')
            ->where('alumno_id', $alumnoId)
            ->where('credential_id', $data['id'])
            ->get()->getRowArray();

        if (!$credRow) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Credencial no reconocida']);
        }

        try {
            $clientDataJSON    = base64_decode($data['response']['clientDataJSON']);
            $authenticatorData = base64_decode($data['response']['authenticatorData']);
            $signature         = base64_decode($data['response']['signature']);

            $result = $this->webAuthn->processGet(
                $clientDataJSON,
                $authenticatorData,
                $signature,
                base64_decode($credRow['public_key']),
                new ByteBuffer($challenge),
                $credRow['sign_count'],
                'required'
            );

            // Actualizar sign count (previene ataques de replay)
            $db->table('huellas_credenciales')
                ->where('id', $credRow['id'])
                ->update(['sign_count' => $result->signCount]);

            session()->remove('webauthn_challenge');

            return $this->response->setJSON(['success' => true, 'verified' => true]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => $e->getMessage()]);
        }
    }

    // ── 5. Verificar si el alumno ya tiene huella ─────────────────────
    public function existFingerprint()
    {
        $alumnoId = $this->request->getPost('alumno_id');
        $db = \Config\Database::connect();
        $existe = $db->table('huellas_credenciales')
            ->where('alumno_id', $alumnoId)->countAllResults();
        return $this->response->setJSON(['tiene_huella' => $existe > 0]);
    }
}