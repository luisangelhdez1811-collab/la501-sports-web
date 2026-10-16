<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CarouselSlide;
use Illuminate\Support\Facades\Storage;

class CarouselController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'nullable|image|max:4096',
            'image_url' => 'nullable|url',
            'subtitle' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order' => 'required|integer',
        ]);

        if (!$request->hasFile('image') && !$request->image_url) {
            return back()->with('error', 'Debes subir una imagen o proporcionar un enlace URL.');
        }

        $imagePath = '';
        if ($request->hasFile('image')) {
            try {
                $imagePath = \App\Services\CloudinaryService::upload($request->file('image'));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir a Cloudinary: ' . $e->getMessage());
            }
        } else {
            $imagePath = $request->image_url;
        }

        CarouselSlide::create([
            'image_path' => $imagePath,
            'subtitle' => $request->subtitle,
            'title' => $request->title,
            'description' => $request->description,
            'order' => $request->order,
            'is_active' => true,
        ]);

        return back()->with('status', '¡Diapositiva agregada con éxito!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'image' => 'nullable|image|max:4096',
            'image_url' => 'nullable|url',
            'subtitle' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order' => 'required|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $slide = CarouselSlide::findOrFail($id);

        if ($request->hasFile('image')) {
            try {
                // Eliminar anterior si era archivo local
                if (str_starts_with($slide->image_path, 'storage/carousel/')) {
                    $oldPath = str_replace('storage/', '', $slide->image_path);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                $slide->image_path = \App\Services\CloudinaryService::upload($request->file('image'));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al actualizar en Cloudinary: ' . $e->getMessage());
            }
        } elseif ($request->image_url) {
            $slide->image_path = $request->image_url;
        }

        $slide->subtitle = $request->subtitle;
        $slide->title = $request->title;
        $slide->description = $request->description;
        $slide->order = $request->order;
        $slide->is_active = $request->has('is_active') ? true : false;
        $slide->save();

        return back()->with('status', '¡Diapositiva actualizada con éxito!');
    }

    public function destroy($id)
    {
        $slide = CarouselSlide::findOrFail($id);

        // Eliminar anterior si era archivo local
        if (str_starts_with($slide->image_path, 'storage/carousel/')) {
            $oldPath = str_replace('storage/', '', $slide->image_path);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $slide->delete();

        return back()->with('status', '¡Diapositiva eliminada con éxito!');
    }
}
