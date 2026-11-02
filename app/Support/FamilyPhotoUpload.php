<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class FamilyPhotoUpload
{
    public function save(Request $request, string $prefix, Closure $persist): bool
    {
        $remove = $request->boolean('remove_photo');
        $request->validate([
            'remove_photo' => ['required', 'boolean'],
            'photo' => [Rule::requiredIf(! $remove), Rule::prohibitedIf($remove), 'file', 'image', 'mimes:png', 'max:2048',
                'dimensions:min_width=64,min_height=64,max_width=1024,max_height=1024,ratio=1/1'],
        ], [
            'photo.required' => 'Seleccioná una foto y ajustá el recorte antes de guardar.',
            'photo.prohibited' => 'Elegí entre guardar una foto o quitar la actual.',
            'photo.image' => 'El archivo no es una imagen válida.',
            'photo.mimes' => 'Guardá la imagen desde el recortador para generar un PNG válido.',
            'photo.max' => 'El recorte no puede superar los 2 MB.',
            'photo.dimensions' => 'El recorte debe ser cuadrado, de entre 64 y 1024 píxeles.',
        ]);

        $filename = null;
        try {
            if (! $remove) {
                // Decode and re-encode a bounded image; never store arbitrary client bytes as a PNG.
                $source = @imagecreatefromstring($request->file('photo')->get());
                if ($source === false) {
                    throw ValidationException::withMessages(['photo' => 'No se pudo leer la imagen. Elegí otra foto.']);
                }
                $output = imagecreatetruecolor(512, 512);
                imagealphablending($output, false);
                imagesavealpha($output, true);
                imagecopyresampled($output, $source, 0, 0, 0, 0, 512, 512, imagesx($source), imagesy($source));
                ob_start();
                imagepng($output);
                $png = ob_get_clean();
                imagedestroy($source);
                imagedestroy($output);
                $filename = $prefix.Str::uuid().'.png';
                if (! Storage::disk('public')->put($filename, $png)) {
                    throw ValidationException::withMessages(['photo' => 'No se pudo guardar el archivo. Intentá nuevamente.']);
                }
            }

            DB::transaction(fn () => $persist($remove, $filename));
        } catch (Throwable $exception) {
            if ($filename) {
                // Only clean up this new upload; existing files may be shared by other applications.
                try {
                    Storage::disk('public')->delete($filename);
                } catch (Throwable $cleanup) {
                    report($cleanup);
                }
            }
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            report($exception);
            throw ValidationException::withMessages(['photo' => 'No pudimos guardar el cambio. Tu foto anterior se conserva. Intentá nuevamente.']);
        }

        return $remove;
    }
}
