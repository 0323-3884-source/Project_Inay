<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

class PrivateUploads
{
    // Legacy files remain readable only through authorized controllers. Apache
    // denies their old static URLs; new files always go to the private disk.
    public static function disk(string $path): Filesystem
    {
        return Storage::disk('local')->exists($path)
            ? Storage::disk('local')
            : Storage::disk('public');
    }
}
