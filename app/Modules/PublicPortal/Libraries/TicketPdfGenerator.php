<?php

namespace App\Modules\PublicPortal\Libraries;

use RuntimeException;

/**
 * Generador de PDF para los tickets de turno.
 */
class TicketPdfGenerator
{
    /**
     * Genera un PDF para el ticket proporcionado.
     */
    public function generate(array $ticket): array
    {
        if (!function_exists('exec')) {
            throw new RuntimeException('La función exec no está disponible para generar el PDF.');
        }

        $tempDir = WRITEPATH . 'tmp/ticket-pdf-' . bin2hex(random_bytes(6));

        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new RuntimeException('No se pudo preparar el directorio temporal del PDF.');
        }

        try {
            $qrImageSrc = $this->prepareQr($ticket, $tempDir);
            $html = view('public/turno_pdf', [
                'turno'      => $ticket,
                'qrImageSrc' => $qrImageSrc,
                'logoImage'  => $this->resolveLocalImage(FCPATH . 'assets/img/Instituto_Tecnologico_de_Oaxaca.png'),
            ]);

            $htmlPath = $tempDir . '/ticket.html';
            $pdfPath = $tempDir . '/ticket.pdf';

            if (file_put_contents($htmlPath, $html) === false) {
                throw new RuntimeException('No se pudo escribir la plantilla temporal del PDF.');
            }

            // Usamos libreoffice para la conversión
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
                'filename' => $this->resolveFilename($ticket['folio'] ?? 'ticket'),
            ];
        } finally {
            $this->removeDirectory($tempDir);
        }
    }

    private function prepareQr(array $ticket, string $tempDir): ?string
    {
        if (empty($ticket['qr_url'])) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 8,
            ],
        ]);

        $qrBinary = file_get_contents($ticket['qr_url'], false, $context);

        if ($qrBinary === false) {
            return null;
        }

        $qrPath = $tempDir . '/qr.png';

        if (file_put_contents($qrPath, $qrBinary) === false) {
            return null;
        }

        return $this->resolveLocalImage($qrPath);
    }

    private function resolveLocalImage(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        return 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', realpath($path) ?: $path);
    }

    private function resolveFilename(string $folio): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $folio);
        return 'ticket-' . trim((string) $safe, '-') . '.pdf';
    }

    private function removeDirectory(string $path): void
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
                $this->removeDirectory($fullPath);
                continue;
            }

            unlink($fullPath);
        }

        rmdir($path);
    }
}
