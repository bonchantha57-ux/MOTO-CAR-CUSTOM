<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/{folder}/{filename}', function ($folder, $filename) {
    $path = storage_path("app/public/{$folder}/{$filename}");
    if (!file_exists($path)) {
        abort(404);
    }
    $publicFolder = public_path("storage/{$folder}");
    if (!file_exists($publicFolder)) {
        @mkdir($publicFolder, 0755, true);
    }
    $publicFile = "{$publicFolder}/{$filename}";
    if (!file_exists($publicFile)) {
        @copy($path, $publicFile);
    }
    return response()->file($path);
})->where('filename', '.*');
