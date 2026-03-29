<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaApiController extends Controller
{
    public function lapangan(string $filename): BinaryFileResponse
    {
        return $this->servePublicFile('images/lapangan', $filename);
    }

    public function bukti(string $filename): BinaryFileResponse
    {
        return $this->servePublicFile('bukti_pembayaran', $filename);
    }

    public function image(string $filename): BinaryFileResponse
    {
        return $this->servePublicFile('images', $filename);
    }

    private function servePublicFile(string $directory, string $filename): BinaryFileResponse
    {
        $safeFilename = basename(rawurldecode($filename));
        $filePath = public_path($directory . DIRECTORY_SEPARATOR . $safeFilename);

        abort_unless(is_file($filePath), 404, 'File tidak ditemukan.');

        return response()->file($filePath, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
