<?php

namespace App\Libraries;

use RuntimeException;

class TurnoPdfGenerator
{
    public function generar(array $turno): array
    {
        if (!function_exists('exec')) {
            throw new RuntimeException('La función exec no está disponible para generar el PDF.');
        }

        $tempDir = WRITEPATH . 'tmp/turno-pdf-' . bin2hex(random_bytes(6));

        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new RuntimeException('No se pudo preparar el directorio temporal del PDF.');
        }

        try {
            $qrImageSrc = $this->prepararQr($turno, $tempDir);
            $html = view('public/turno_pdf', [
                'turno'      => $turno,
                'qrImageSrc' => $qrImageSrc,
                'logoImage'  => $this->resolverImagenLocal(FCPATH . 'assets/img/Instituto_Tecnologico_de_Oaxaca.png'),
            ]);

            $htmlPath = $tempDir . '/turno.html';
            $pdfPath = $tempDir . '/turno.pdf';

            if (file_put_contents($htmlPath, $html) === false) {
                throw new RuntimeException('No se pudo escribir la plantilla temporal del PDF.');
            }

            $command = 'libreoffice --headless --convert-to pdf --outdir '
                . escapeshellarg($tempDir) . ' ' . escapeshellarg($htmlPath) . ' 2>&1';

            exec($command, $output, $exitCode);

            if ($exitCode !== 0 || !is_file($pdfPath)) {
                throw new RuntimeException("No se pudo convertir el comprobante a PDF.\n" . trim(implode("\n", $output)));
            }

            $contents = file_get_contents($pdfPath);

            if ($contents === false) {
                throw new RuntimeException('No se pudo leer el PDF generado.');
            }

            return [
                'contents' => $contents,
                'filename' => $this->resolverNombreArchivo($turno['folio'] ?? 'turno'),
            ];
        } finally {
            $this->eliminarDirectorio($tempDir);
        }
    }

    private function prepararQr(array $turno, string $tempDir): ?string
    {
        if (empty($turno['qr_url'])) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 8,
            ],
        ]);

        $qrBinary = file_get_contents($turno['qr_url'], false, $context);

        if ($qrBinary === false) {
            return null;
        }

        $qrPath = $tempDir . '/qr.png';

        if (file_put_contents($qrPath, $qrBinary) === false) {
            return null;
        }

        return $this->resolverImagenLocal($qrPath);
    }

    private function resolverImagenLocal(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        return 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', realpath($path) ?: $path);
    }

    private function resolverNombreArchivo(string $folio): string
    {
        $seguro = preg_replace('/[^A-Za-z0-9_-]+/', '-', $folio);
        return 'turno-' . trim((string) $seguro, '-') . '.pdf';
    }

    private function eliminarDirectorio(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->eliminarDirectorio($fullPath);
                continue;
            }

            unlink($fullPath);
        }

        rmdir($path);
    }
}
