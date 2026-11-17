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
            'image_source' => 'nullable|required_with:image|string|max:150',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        if ($error = $this->alcoholEnDeportes($request)) {
            return back()->withInput()->withErrors(['content' => $error]);
        }
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
            'image_source' => 'nullable|required_with:image|string|max:150',
            'end_date' => 'nullable|date',
        ]);
        if ($error = $this->alcoholEnDeportes($request)) {
            return back()->withInput()->withErrors(['content' => $error]);
        }
        $data = $request->except(['image']);
        if (!$request->filled('image_source')) {
            unset($data['image_source']); // sin imagen nueva se conserva el origen que tenía
        }
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

    /**
     * B-25 (LGS art. 308): la publicidad de alcohol no puede asociarse con deportes, así que una novedad
     * de la categoría «Deportes» no puede hablar de bebidas con alcohol.
     */
    private function alcoholEnDeportes(Request $request): ?string
    {
        if (mb_strtolower(trim((string) $request->input('category'))) !== 'deportes') {
            return null;
        }

        $texto = ' ' . preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower(\Illuminate\Support\Str::ascii($request->input('title') . ' ' . $request->input('content')))) . ' ';
        $palabras = ['cerveza', 'cervezas', 'chela', 'chelas', 'cubeta', 'cubetas', 'michelada', 'tequila', 'mezcal', 'whisky', 'vodka', 'ron', 'coctel', 'cocteles', 'alcohol', 'trago', 'tragos', 'shots', 'barra libre'];
        $encontradas = array_values(array_filter($palabras, fn ($p) => str_contains($texto, ' ' . $p . ' ')));

        return $encontradas === [] ? null
            : 'Una novedad de Deportes no puede mencionar bebidas con alcohol (Ley General de Salud, art. 308). Quita: «' . implode('», «', $encontradas) . '», o publícala en otra categoría sin relacionarla con deportes.';
    }
}
