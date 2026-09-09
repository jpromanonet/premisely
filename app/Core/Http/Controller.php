<?php

declare(strict_types=1);

namespace Premisely\Core\Http;

abstract class Controller
{
    protected function view(string $name, array $data = [], int $status = 200): never
    {
        Response::html(view($name, $data), $status)->send();
    }

    protected function redirect(string $path): never
    {
        redirect($path);
    }

    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status)->send();
    }
}
