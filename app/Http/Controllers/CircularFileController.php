<?php

namespace App\Http\Controllers;

use App\Enums\ResourceType;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF de una circular «solo familias»: no está en el disco público, así que
 * se entrega solo a quien puede verla (el admin o las familias a las que va
 * dirigida).
 */
class CircularFileController extends Controller
{
    public function __invoke(Request $request, Resource $resource): StreamedResponse
    {
        abort_unless($resource->type === ResourceType::Circular && $resource->file_path, 404);
        abort_unless($resource->visibleTo($request->user()), 403);

        $disk = Storage::disk($resource->fileDisk());
        abort_unless($disk->exists($resource->file_path), 404);

        return $disk->response($resource->file_path, $resource->file_name ?: 'circular.pdf', [
            'Content-Type' => 'application/pdf',
            // Que no quede guardada en cachés compartidas
            'Cache-Control' => 'private, no-store',
        ], 'inline');
    }
}
