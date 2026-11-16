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
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'download.bin';
        (new self($binary, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $safe . '"; filename*=UTF-8\'\'' . rawurlencode($filename),
            'Content-Length' => (string) strlen($binary),
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]))->send();
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
