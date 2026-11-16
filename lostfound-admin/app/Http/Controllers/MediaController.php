<?php

namespace App\Http\Controllers;

class MediaController extends Controller
{
    public function __invoke(string $file)
    {
        $path = rtrim(config('lostfound.upload_path'), '/\\') . DIRECTORY_SEPARATOR . basename($file);
        abort_unless(is_file($path), 404);
        return response()->file($path);
    }
}
