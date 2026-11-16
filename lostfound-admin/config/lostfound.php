<?php

// Folder shared with the PHP API so both parts see the same item photos.
$path = env('UPLOAD_PATH', '../php-api/public/uploads');
$absolute = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path);

return ['upload_path' => $absolute ? $path : base_path($path)];
