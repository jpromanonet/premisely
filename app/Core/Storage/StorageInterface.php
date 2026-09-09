<?php

declare(strict_types=1);

namespace Premisely\Core\Storage;

interface StorageInterface
{
    /** @param array{name:string,tmp_name:string,type?:string,size?:int,error?:int} $file */
    public function store(array $file, string $directory): string;

    public function delete(string $path): bool;

    public function url(string $path): string;

    public function absolutePath(string $path): string;
}
