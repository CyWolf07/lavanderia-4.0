<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class SafeFilesystem extends Filesystem
{
    public function replace($path, $content, $mode = null)
    {
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;
        $directory = dirname($path);
        $tempPath = tempnam($directory, basename($path));

        if ($tempPath === false) {
            throw new RuntimeException("No se pudo crear un archivo temporal para [$path].");
        }

        if (! is_null($mode)) {
            @chmod($tempPath, $mode);
        } else {
            @chmod($tempPath, 0777 - umask());
        }

        file_put_contents($tempPath, $content);

        if (@rename($tempPath, $path)) {
            return;
        }

        if (@copy($tempPath, $path)) {
            @unlink($tempPath);

            return;
        }

        $message = error_get_last()['message'] ?? "No se pudo reemplazar el archivo [$path].";
        @unlink($tempPath);

        throw new RuntimeException($message);
    }
}
