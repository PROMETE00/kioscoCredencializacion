<?php

namespace App\Services;

use App\Repositories\FingerprintRepository;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use RuntimeException;

class WebAuthnService extends BaseService
{
    protected FingerprintRepository $fingerprintRepo;
    protected WebAuthn $webAuthn;
    protected string $rpName;

    public function __construct(?string $rpId = null)
    {
        $this->fingerprintRepo = new FingerprintRepository();
        $this->rpName = 'Instituto Tecnológico de Oaxaca';
        $rpId = $rpId ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $this->webAuthn = new WebAuthn($this->rpName, $rpId);
    }

    public function createRegistrationChallenge(string $alumnoId, string $alumnoNombre): array
    {
        $createArgs = $this->webAuthn->getCreateArgs(
            $alumnoId,
            $alumnoId,
            $alumnoNombre,
            60,
            false,
            'required',
            null,
            'platform'
        );

        session()->set('webauthn_challenge', $this->webAuthn->getChallenge()->getBinaryString());
        session()->set('webauthn_user_id', $alumnoId);

        return $createArgs;
    }

    public function verifyAndStoreCredential(array $requestData): bool
    {
        $challenge = session()->get('webauthn_challenge');
        $alumnoId  = session()->get('webauthn_user_id');

        if (!$challenge) {
            throw new RuntimeException('Challenge not found or expired.');
        }

        $clientDataJSON    = base64_decode($requestData['response']['clientDataJSON']);
        $attestationObject = base64_decode($requestData['response']['attestationObject']);

        $credential = $this->webAuthn->processCreate(
            $clientDataJSON,
            $attestationObject,
            new ByteBuffer($challenge),
            'required',
            true,
            false
        );

        $this->fingerprintRepo->storeCredential([
            'alumno_id'     => $alumnoId,
            'credential_id' => base64_encode($credential->credentialId),
            'public_key'    => base64_encode($credential->publicKey),
            'sign_count'    => $credential->signCount,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        session()->remove('webauthn_challenge');

        return true;
    }

    public function createAuthChallenge(string $alumnoId): array
    {
        $creds = $this->fingerprintRepo->findByAlumnoId($alumnoId);

        if (empty($creds)) {
            throw new RuntimeException('No fingerprint registered for this student.');
        }

        $credIds = array_map(fn($c) => base64_decode($c['credential_id']), $creds);

        $getArgs = $this->webAuthn->getGetArgs(
            $credIds,
            60,
            true, true, true, true,
            'required'
        );

        session()->set('webauthn_challenge', $this->webAuthn->getChallenge()->getBinaryString());
        session()->set('webauthn_user_id', $alumnoId);

        return $getArgs;
    }

    public function verifyAuthentication(array $requestData): bool
    {
        $challenge = session()->get('webauthn_challenge');
        $alumnoId  = session()->get('webauthn_user_id');

        if (!$challenge) {
            throw new RuntimeException('Challenge not found or expired.');
        }

        $credRow = $this->fingerprintRepo->findByCredentialId($alumnoId, $requestData['id']);

        if (!$credRow) {
            throw new RuntimeException('Credential not recognized.');
        }

        $clientDataJSON    = base64_decode($requestData['response']['clientDataJSON']);
        $authenticatorData = base64_decode($requestData['response']['authenticatorData']);
        $signature         = base64_decode($requestData['response']['signature']);

        $result = $this->webAuthn->processGet(
            $clientDataJSON,
            $authenticatorData,
            $signature,
            base64_decode($credRow['public_key']),
            new ByteBuffer($challenge),
            $credRow['sign_count'],
            'required'
        );

        $this->fingerprintRepo->updateSignCount((int) $credRow['id'], $result->signCount);
        session()->remove('webauthn_challenge');

        return true;
    }

    public function hasFingerprint(string $alumnoId): bool
    {
        return $this->fingerprintRepo->existsForAlumno($alumnoId);
    }

    public function saveFinalSignature(int $studentId, int $ticketId, string $dataUrl): array
    {
        return $this->fingerprintRepo->saveSignatureFile($studentId, $ticketId, $dataUrl);
    }
}
