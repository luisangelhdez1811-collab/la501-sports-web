<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Foto de perfil, la misma para la web y la app (B-16). Una sola validación y un solo lugar donde se
 * guarda y se borra:
 *  - el nombre del archivo lo genera el servidor (nunca el que manda el cliente) y su extensión sale del
 *    contenido real de la imagen, no del nombre que traiga;
 *  - solo JPG, PNG o WebP de hasta 2 MB y de 64 a 4000 px por lado;
 *  - al cambiarla o quitarla se borra el archivo anterior.
 */
class AvatarService
{
    private const DISK = 'public_avatars';

    /** Carpeta pública (relativa a public/) donde vive el disco; es lo que se guarda en users.avatar. */
    private const FOLDER = 'avatars';

    public static function rules(): array
    {
        return [
            'image',
            'mimes:jpeg,png,jpg,webp',
            'max:2048',
            'dimensions:min_width=64,min_height=64,max_width=4000,max_height=4000',
        ];
    }

    public static function messages(): array
    {
        return [
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.mimes' => 'La foto debe ser JPG, PNG o WebP.',
            'avatar.max' => 'La foto no puede pesar más de 2 MB.',
            'avatar.dimensions' => 'La foto debe medir entre 64 y 4000 píxeles por lado.',
            'avatar.uploaded' => 'No se pudo subir la foto. Revisa que no pese más de 2 MB.',
        ];
    }

    /** Guarda la foto nueva, borra la anterior y devuelve la ruta que se guarda en la cuenta. */
    public function replace(User $user, UploadedFile $archivo): string
    {
        $extension = $archivo->extension() ?: 'jpg';
        $nombre = 'av_' . $user->id . '_' . Str::random(32) . '.' . $extension;

        Storage::disk(self::DISK)->putFileAs('', $archivo, $nombre);

        $anterior = $user->avatar;
        $user->avatar = self::FOLDER . '/' . $nombre;
        $user->save();

        $this->deleteFile($anterior);

        return $user->avatar;
    }

    /** Quita la foto: borra el archivo y deja vacío el campo. */
    public function remove(User $user): void
    {
        $anterior = $user->avatar;

        $user->avatar = null;
        $user->save();

        $this->deleteFile($anterior);
    }

    private function deleteFile(?string $ruta): void
    {
        // Solo archivos de esta carpeta, y solo por su nombre: nunca una ruta armada por otra parte.
        if (blank($ruta) || !str_starts_with($ruta, self::FOLDER . '/')) {
            return;
        }

        Storage::disk(self::DISK)->delete(basename($ruta));
    }
}
