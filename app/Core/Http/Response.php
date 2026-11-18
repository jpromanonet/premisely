<?php

declare(strict_types=1);

namespace Premisely\Core\Http;

final class Response
{
    public function __construct(
        private string $content = '',
        private int $status = 200,
        private array $headers = ['Content-Type' => 'text/html; charset=utf-8'],
    ) {
    }

    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status);
    }

    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            $status,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public static function redirect(string $path, int $status = 302): never
    {
        redirect($path, $status);
    }

    /** Binary file download with a stable filename (avoids browser .tmp saves). */
    public static function download(string $binary, string $filename, string $contentType): never
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename) ?: 'download.bin';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (function_exists('ini_set')) {
            @ini_set('zlib.output_compression', '0');
        }

        // Send headers directly: a single ASCII filename avoids Edge/Chrome saving as .tmp.
        http_response_code(200);
        header_remove('Content-Encoding');
        header_remove('Transfer-Encoding');
        header('Content-Type: ' . $contentType, true);
        header('Content-Disposition: attachment; filename="' . $safe . '"', true);
        header('Content-Length: ' . (string) strlen($binary), true);
        header('Content-Transfer-Encoding: binary', true);
        header('Cache-Control: private, no-transform, no-store, must-revalidate', true);
        header('Pragma: public', true);
        header('Expires: 0', true);
        header('X-Content-Type-Options: nosniff', true);

        echo $binary;
        exit;
    }

    public function send(): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (function_exists('ini_set')) {
            @ini_set('zlib.output_compression', '0');
        }
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }
        echo $this->content;
        exit;
    }
}
