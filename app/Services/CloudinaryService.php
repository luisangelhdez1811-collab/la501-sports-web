<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    public static function upload($file)
    {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey    = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');
        $preset    = env('CLOUDINARY_UPLOAD_PRESET');

        if (!$cloudName) {
            throw new \Exception("Falta configurar CLOUDINARY_CLOUD_NAME en el archivo .env");
        }

        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";

        if ($apiKey && $apiSecret) {
            // Subida firmada (Signed Upload)
            $timestamp = time();
            $stringToSign = "timestamp=" . $timestamp . $apiSecret;
            $signature = sha1($stringToSign);

            $response = Http::withoutVerifying()->attach(
                'file',
                fopen($file->getPathname(), 'r'),
                $file->getClientOriginalName()
            )->post($url, [
                'api_key'   => $apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);
        } elseif ($preset) {
            // Subida no firmada (Unsigned Upload)
            $response = Http::withoutVerifying()->attach(
                'file',
                fopen($file->getPathname(), 'r'),
                $file->getClientOriginalName()
            )->post($url, [
                'upload_preset' => $preset,
            ]);
        } else {
            throw new \Exception("Debes configurar CLOUDINARY_API_KEY y CLOUDINARY_API_SECRET en el .env para firmas, o CLOUDINARY_UPLOAD_PRESET para subidas no firmadas.");
        }

        if ($response->successful()) {
            $data = $response->json();
            return $data['secure_url'] ?? $data['url'];
        }

        Log::error('Error de subida a Cloudinary: ' . $response->body());
        throw new \Exception("Error al subir imagen a Cloudinary: " . ($response->json('error.message') ?? $response->body()));
    }
}
