<?php

namespace App\Http\Controllers;

use App\Services\Videos\ChunkedVideoUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Réception des morceaux d'une vidéo (formulaire public de témoignage et administration). */
class VideoUploadController extends Controller
{
    public function __invoke(Request $request, ChunkedVideoUpload $uploads): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1'],
            'chunk' => ['required', 'file', 'max:1100'],
        ]);

        $token = $uploads->receive($data['upload_id'], (int) $data['index'], (int) $data['total'], $request->file('chunk'));

        return response()->json(['done' => $token !== null, 'token' => $token]);
    }
}
