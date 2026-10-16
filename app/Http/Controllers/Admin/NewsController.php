<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use Illuminate\Support\Facades\Storage;
class NewsController extends Controller
{
    public function index() {
        $news = News::latest()->get();
        return view('admin.news.index', compact('news'));
    }
    public function destroy($id) {
        News::destroy($id);
        return back()->with('success', 'Novedad eliminada');
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'content' => ['required', 'string', function ($attribute, $value, $fail) {
                if (str_word_count($value) > 300) {
                    $fail('El contenido no debe superar las 300 palabras.');
                }
            }],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $data = $request->all();
        $data['active'] = 1;
        if ($request->hasFile('image')) {
            try {
                $data['image'] = \App\Services\CloudinaryService::upload($request->file('image'));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir la imagen a Cloudinary: ' . $e->getMessage());
            }
        }
        News::create($data);
        return back()->with('success', '¡Publicación creada con éxito!');
    }
    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'content' => ['required', 'string', function ($attribute, $value, $fail) {
                if (str_word_count($value) > 300) {
                    $fail('El contenido no debe superar las 300 palabras.');
                }
            }],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'end_date' => 'nullable|date',
        ]);
        $data = $request->except(['image']);
        if ($request->hasFile('image')) {
            try {
                if ($news->image && !str_starts_with($news->image, 'http') && Storage::disk('public')->exists($news->image)) {
                    Storage::disk('public')->delete($news->image);
                }
                $data['image'] = \App\Services\CloudinaryService::upload($request->file('image'));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir la imagen a Cloudinary: ' . $e->getMessage());
            }
        }
        $news->update($data);
        return back()->with('success', '¡Publicación actualizada con éxito!');
    }
}