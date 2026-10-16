@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1/dist/cropper.min.js"></script>

<style>
    .an {
        --accent:   #2563EB;
        --accent2:  #1D4ED8;
        --danger:   #EF4444;

        --bg:       #F8F8F8;
        --bg-card:  #FFFFFF;
        --bg-input: #F4F4F5;
        --txt:      #18181B;
        --txt-sub:  #71717A;
        --bdr:      #E4E4E7;
    }
    .dark .an {
        --bg:       #0A0A0A;
        --bg-card:  #111111;
        --bg-input: #1C1C1C;
        --txt:      #FAFAFA;
        --txt-sub:  #71717A;
        --bdr:      rgba(255,255,255,0.08);
    }

    .an { background: var(--bg); min-height: 100%; }

    /* header */
    .an-header {
        display: flex; align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        gap: 16px; flex-wrap: wrap;
    }
    .an-header h1 { font-size: 20px; font-weight: 700; color: var(--txt); margin: 0 0 2px; }
    .an-header p  { font-size: 13px; color: var(--txt-sub); margin: 0; }

    .an-btn-primary {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--accent); color: #fff;
        font-size: 13px; font-weight: 600;
        padding: 8px 16px; border-radius: 7px; border: none;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(37,99,235,.3);
        transition: background .15s, transform .1s;
    }
    .an-btn-primary:hover { background: var(--accent2); transform: translateY(-1px); }

    /* category pill colors */
    .cat-deportes  { background: rgba(249,115,22,.1);  color: #EA580C; }
    .cat-avisos    { background: rgba(234,179,8,.1);   color: #B45309; }
    .cat-eventos   { background: rgba(22,163,74,.1);   color: #15803D; }
    .dark .cat-deportes { color: #FB923C; }
    .dark .cat-avisos   { color: #FCD34D; }
    .dark .cat-eventos  { color: #4ADE80; }

    /* ── LIST ── */
    .an-list { display: flex; flex-direction: column; gap: 6px; }

    .an-row {
        display: flex; align-items: center;
        gap: 14px;
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 9px;
        padding: 12px 14px;
        transition: border-color .15s, box-shadow .15s;
    }
    .an-row:hover {
        border-color: #D4D4D8;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
    }
    .dark .an-row:hover { border-color: rgba(255,255,255,.14); }

    /* thumbnail */
    .an-thumb {
        width: 56px; height: 56px; border-radius: 7px;
        overflow: hidden; background: var(--bg-input);
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .an-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .an-thumb-placeholder {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
    }
    .an-thumb-placeholder svg { width: 22px; height: 22px; color: var(--txt-sub); opacity: .4; }

    /* text */
    .an-info { flex: 1; min-width: 0; }
    .an-meta {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 3px;
    }
    .an-cat {
        font-size: 10px; font-weight: 700;
        letter-spacing: .5px; text-transform: uppercase;
        padding: 2px 8px; border-radius: 4px;
    }
    .an-date {
        font-size: 11px; color: var(--txt-sub);
        display: flex; align-items: center; gap: 3px;
    }
    .an-date svg { width: 11px; height: 11px; opacity: .6; }
    .an-row-title {
        font-size: 14px; font-weight: 600; color: var(--txt);
        margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .an-row-preview {
        font-size: 12px; color: var(--txt-sub); margin: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* actions */
    .an-actions { display: flex; gap: 4px; flex-shrink: 0; }
    .an-act-btn {
        width: 30px; height: 30px; border-radius: 6px;
        background: var(--bg-input); border: 1px solid var(--bdr);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: background .15s, border-color .15s;
    }
    .an-act-btn svg { width: 13px; height: 13px; }
    .an-act-btn.edit  { color: var(--accent); }
    .an-act-btn.edit:hover  { background: rgba(37,99,235,.1); border-color: rgba(37,99,235,.3); }
    .an-act-btn.del   { color: var(--danger); }
    .an-act-btn.del:hover   { background: rgba(239,68,68,.08); border-color: rgba(239,68,68,.3); }

    /* empty */
    .an-empty {
        background: var(--bg-card); border: 1px solid var(--bdr);
        border-radius: 9px; padding: 48px 24px; text-align: center;
    }
    .an-empty svg { width: 36px; height: 36px; color: var(--txt-sub); margin: 0 auto 10px; opacity: .5; }
    .an-empty h3 { font-size: 15px; font-weight: 700; color: var(--txt); margin: 0 0 4px; }
    .an-empty p  { font-size: 13px; color: var(--txt-sub); margin: 0; }

    /* ── MODAL ── */
    .an-overlay {
        position: fixed; inset: 0; z-index: 50;
        background: rgba(0,0,0,.5); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; padding: 16px;
    }
    .an-modal {
        background: var(--bg-card); border: 1px solid var(--bdr);
        border-radius: 12px; width: 100%; max-width: 560px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
    }
    .an-modal-head {
        display: flex; align-items: center;
        justify-content: space-between;
        padding: 16px 18px; border-bottom: 1px solid var(--bdr);
        position: sticky; top: 0; background: var(--bg-card); z-index: 1;
    }
    .an-modal-head h3 { font-size: 15px; font-weight: 700; color: var(--txt); margin: 0; }
    .an-modal-close {
        width: 26px; height: 26px; border-radius: 6px;
        background: var(--bg-input); border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: var(--txt-sub); transition: background .15s;
    }
    .an-modal-close:hover { background: var(--bdr); }
    .an-modal-body { padding: 18px; display: flex; flex-direction: column; gap: 14px; }
    .an-modal-foot {
        display: flex; gap: 8px;
        padding: 14px 18px; border-top: 1px solid var(--bdr);
    }

    /* form */
    .an-label {
        display: block; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .5px;
        color: var(--txt-sub); margin-bottom: 4px;
    }
    .an-input {
        width: 100%; padding: 8px 10px;
        background: var(--bg-input); border: 1px solid var(--bdr);
        border-radius: 7px; font-size: 13px; color: var(--txt);
        outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .an-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .an-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    /* upload */
    .an-upload {
        border: 1px dashed var(--bdr); border-radius: 7px;
        aspect-ratio: 16 / 9; width: 100%; cursor: pointer; overflow: hidden;
        position: relative; background: var(--bg-input);
        display: flex; align-items: center; justify-content: center;
        transition: border-color .15s;
    }
    .an-upload:hover { border-color: var(--accent); }
    .an-upload-ph { display: flex; flex-direction: column; align-items: center; gap: 5px; }
    .an-upload-ph svg { width: 22px; height: 22px; color: var(--txt-sub); }
    .an-upload-ph span { font-size: 12px; color: var(--txt-sub); font-weight: 500; }
    .an-upload img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .an-upload-ov {
        position: absolute; inset: 0; background: rgba(0,0,0,.45);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity .2s;
    }
    .an-upload:hover .an-upload-ov { opacity: 1; }
    .an-upload-ov span { font-size: 12px; color: #fff; font-weight: 600; }
    .an-imghelp { font-size: 11px; color: var(--txt-sub); margin: 6px 2px 0; line-height: 1.5; }
    .an-imghelp strong { color: var(--txt); }

    /* ── RECORTE DE IMAGEN (Cropper.js) ── */
    .an-crop-overlay {
        position: fixed; inset: 0; z-index: 70;
        background: rgba(0,0,0,.65); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .an-crop-modal {
        background: var(--bg-card); border: 1px solid var(--bdr); border-radius: 12px;
        width: 100%; max-width: 520px; box-shadow: 0 24px 64px rgba(0,0,0,.35);
        display: flex; flex-direction: column; overflow: hidden;
    }
    .an-crop-hd { padding: 16px 18px; border-bottom: 1px solid var(--bdr); }
    .an-crop-hd h3 { font-size: 15px; font-weight: 700; color: var(--txt); margin: 0 0 2px; }
    .an-crop-hd p  { font-size: 12px; color: var(--txt-sub); margin: 0; }
    .an-crop-body { background: #000; height: 340px; }
    .an-crop-body img { display: block; max-width: 100%; }
    .an-crop-foot { padding: 14px 18px; border-top: 1px solid var(--bdr); display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .an-crop-hint { font-size: 11px; color: var(--txt-sub); }
    .an-crop-actions { display: flex; gap: 8px; }
    @media (max-width: 480px) { .an-crop-body { height: 260px; } .an-crop-foot { flex-direction: column; align-items: stretch; } }

    /* modal buttons */
    .an-btn-cancel {
        flex: 1; padding: 8px; border-radius: 7px;
        background: var(--bg-input); border: 1px solid var(--bdr);
        font-size: 13px; font-weight: 600; color: var(--txt-sub);
        cursor: pointer; transition: background .15s;
    }
    .an-btn-cancel:hover { background: var(--bdr); }
    .an-btn-save {
        flex: 2; padding: 8px; border-radius: 7px;
        background: var(--accent); color: #fff;
        border: none; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
        box-shadow: 0 1px 3px rgba(37,99,235,.3);
    }
    .an-btn-save:hover { background: var(--accent2); }
    .an-btn-save:disabled { opacity: .6; cursor: not-allowed; }

    /* word counter */
    .an-wordcount {
        font-size: 11px; color: var(--txt-sub);
        text-align: right; margin: 4px 2px 0;
    }
    .an-wordcount.over { color: var(--danger); font-weight: 600; }

    @media (max-width: 540px) {
        .an-row-2 { grid-template-columns: 1fr; }
        .an-thumb { width: 44px; height: 44px; }
    }
</style>

<div class="an" x-data="newsManager()">

    @if(session('success'))
    <div style="background: rgba(22,163,74,.1); border: 1px solid rgba(34,197,94,.3); color: #15803D; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 13px;">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #B91C1C; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 13px;">
        {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div style="background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); color: #B91C1C; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px;">
        <strong>Revisa lo siguiente:</strong>
        <ul style="margin: 4px 0 0 18px; padding: 0;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- HEADER --}}
    <div class="an-header">
        <div>
            <h1>Novedades</h1>
            <p>{{ $news->count() }} publicación{{ $news->count() !== 1 ? 'es' : '' }}</p>
        </div>
        <button @click="openCreate()" class="an-btn-primary">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Publicación
        </button>
    </div>

    {{-- LIST --}}
    <div class="an-list">
        @forelse($news as $item)
        <div class="an-row">

            {{-- Thumbnail --}}
            <div class="an-thumb">
                @if($item->image)
                    <img src="{{ (str_starts_with($item->image, 'http://') || str_starts_with($item->image, 'https://')) ? $item->image : asset('storage/' . $item->image) }}" alt="{{ $item->title }}">
                @else
                    <div class="an-thumb-placeholder">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="an-info">
                <div class="an-meta">
                    <span class="an-cat cat-{{ strtolower($item->category) }}">{{ $item->category }}</span>
                    @if($item->end_date)
                    <span class="an-date">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                    </span>
                    @endif
                </div>
                <p class="an-row-title">{{ $item->title }}</p>
                <p class="an-row-preview">{{ $item->content }}</p>
            </div>

            {{-- Actions --}}
            <div class="an-actions">
                <button @click="openEdit({{ json_encode($item) }})" class="an-act-btn edit" title="Editar">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <form action="{{ route('admin.news.destroy', $item->id) }}" method="POST"
                      onsubmit="return confirm('¿Eliminar «{{ $item->title }}»?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="an-act-btn del" title="Eliminar">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="an-empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            <h3>Sin publicaciones</h3>
            <p>Crea tu primera novedad con el botón de arriba.</p>
        </div>
        @endforelse
    </div>

    {{-- MODAL --}}
    <div x-show="openModal" x-cloak class="an-overlay" @click.self="openModal = false">
        <div class="an-modal" @click.stop>

            <div class="an-modal-head">
                <h3 x-text="isEdit ? 'Editar Publicación' : 'Nueva Publicación'" :aria-label="isEdit ? 'Editar Publicación' : 'Nueva Publicación'">Publicación</h3>
                <button @click="openModal = false" class="an-modal-close">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="actionUrl" method="POST" enctype="multipart/form-data"
                  @submit="if(isSubmitting){$event.preventDefault()}else{isSubmitting=true}">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!isEdit">

                <div class="an-modal-body">

                    {{-- Imagen --}}
                    <div>
                        <label for="news-image" class="an-label">Imagen</label>
                            <label class="an-upload">
                            <template x-if="imagePreview">
                                <img :src="imagePreview" alt="Vista previa de la noticia">
                            </template>
                            <template x-if="imagePreview">
                                <div class="an-upload-ov"><span>Cambiar imagen</span></div>
                            </template>
                            <div x-show="!imagePreview" class="an-upload-ph">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Subir imagen</span>
                            </div>
                            <input type="file" id="news-image" name="image" class="hidden" accept="image/*"
                                   x-ref="newsImgFile"
                                   @change="previewImage($event)">
                        </label>
                        <p class="an-imghelp">Recomendado: imagen <strong>rectangular (16:9)</strong>, mínimo 1000×563px.<br>Aceptamos JPG, PNG, WEBP y la mayoría de formatos.</p>
                    </div>

                    {{-- Título --}}
                    <div>
                        <label for="news-title" class="an-label">Título *</label>
                        <input id="news-title" type="text" name="title" x-model="newsData.title"
                               required placeholder="Ej: Partido del Siglo esta noche"
                               class="an-input">
                    </div>

                    {{-- Categoría + Fecha --}}
                    <div class="an-row-2">
                        <div>
                            <label for="news-category" class="an-label">Categoría *</label>
                            <select id="news-category" name="category" x-model="newsData.category" class="an-input">
                                <option value="Deportes">Deportes</option>
                                <option value="Avisos">Avisos</option>
                                <option value="Eventos">Eventos</option>
                            </select>
                        </div>
                        <div>
                            <label for="news-end-date" class="an-label">Fecha límite</label>
                            <input id="news-end-date" type="date" name="end_date" x-model="newsData.end_date" class="an-input">
                        </div>
                    </div>

                    {{-- Contenido --}}
                    <div>
                        <label for="news-content" class="an-label">Contenido *</label>
                        <textarea id="news-content" name="content" x-model="newsData.content"
                                  rows="4" required maxlength="2200"
                                  placeholder="Describe la novedad..."
                                  @input="enforceWordLimit($event)"
                                  class="an-input resize-none"></textarea>
                        <p class="an-wordcount" :class="wordCount(newsData.content) >= 300 ? 'over' : ''">
                            <span x-text="wordCount(newsData.content)"></span> / 300 palabras
                        </p>
                    </div>

                </div>

                <div class="an-modal-foot">
                    <button type="button" @click="openModal = false" class="an-btn-cancel">Cancelar</button>
                    <button type="submit" :disabled="isSubmitting" class="an-btn-save"
                            x-text="isSubmitting ? 'Guardando…' : (isEdit ? 'Guardar Cambios' : 'Publicar')">
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DE RECORTE DE IMAGEN (Cropper.js) --}}
    <div x-show="cropperOpen" x-cloak class="an-crop-overlay" @click.self="cancelCrop()">
        <div class="an-crop-modal" @click.stop>
            <div class="an-crop-hd">
                <h3>Ajustar imagen</h3>
                <p>Mueve y haz zoom para elegir qué parte se mostrará (formato 16:9)</p>
            </div>
            <div class="an-crop-body">
                <template x-if="cropperSrc">
                    <img x-ref="newsCropperImg" :src="cropperSrc">
                </template>
            </div>
            <div class="an-crop-foot">
                <span class="an-crop-hint">Se guardará como imagen 1000×563px</span>
                <div class="an-crop-actions">
                    <button type="button" @click="cancelCrop()" class="an-btn-cancel">Cancelar</button>
                    <button type="button" @click="confirmCrop()" class="an-btn-save">Recortar y usar</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function newsManager() {
        return {
            openModal: false,
            isEdit: false,
            isSubmitting: false,
            actionUrl: '',
            newsBaseUrl: '{{ route("admin.news") }}',
            imagePreview: null,
            cropperOpen: false,
            cropperSrc: null,
            cropperInstance: null,
            newsData: { title: '', category: 'Deportes', end_date: '', content: '' },

            openCreate() {
                this.isEdit = false;
                this.actionUrl = '{{ route("admin.news.store") }}';
                this.imagePreview = null;
                this.newsData = { title: '', category: 'Deportes', end_date: '', content: '' };
                this.isSubmitting = false;
                this.openModal = true;
            },

            openEdit(item) {
                this.isEdit = true;
                this.actionUrl = this.newsBaseUrl + '/' + item.id;
                this.imagePreview = item.image ? (item.image.startsWith('http') ? item.image : `/storage/${item.image}`) : null;
                this.newsData = {
                    title: item.title,
                    category: item.category,
                    end_date: item.end_date || '',
                    content: item.content
                };
                this.isSubmitting = false;
                this.openModal = true;
            },

            wordCount(text) {
                if (!text) return 0;
                return text.trim().split(/\s+/).filter(Boolean).length;
            },

            enforceWordLimit(e) {
                const words = e.target.value.trim().split(/\s+/).filter(Boolean);
                if (words.length > 300) {
                    const trimmed = words.slice(0, 300).join(' ');
                    this.newsData.content = trimmed;
                    e.target.value = trimmed;
                }
            },

            previewImage(e) {
                const file = e.target.files[0];
                if (!file) return;
                if (!file.type.startsWith('image/')) {
                    alert('Por favor selecciona un archivo de imagen válido (JPG, PNG, WEBP, etc.)');
                    e.target.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = ev => {
                    this.cropperSrc  = ev.target.result;
                    this.cropperOpen = true;
                    this.$nextTick(() => this.initCropper());
                };
                reader.readAsDataURL(file);
            },

            initCropper() {
                if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
                this.cropperInstance = new Cropper(this.$refs.newsCropperImg, {
                    aspectRatio: 16 / 9,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    movable: true,
                    zoomable: true,
                    scalable: false,
                    responsive: true,
                });
            },

            confirmCrop() {
                if (!this.cropperInstance) return;
                const canvas = this.cropperInstance.getCroppedCanvas({
                    width: 1000, height: 563, imageSmoothingQuality: 'high',
                });
                canvas.toBlob((blob) => {
                    const croppedFile = new File([blob], 'novedad-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                    const dt = new DataTransfer();
                    dt.items.add(croppedFile);
                    this.$refs.newsImgFile.files = dt.files;
                    this.imagePreview = canvas.toDataURL('image/jpeg', 0.92);
                    this.cropperOpen = false;
                    this.cropperInstance.destroy();
                    this.cropperInstance = null;
                    this.cropperSrc = null;
                }, 'image/jpeg', 0.92);
            },

            cancelCrop() {
                this.cropperOpen = false;
                if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
                this.cropperSrc = null;
                this.$refs.newsImgFile.value = '';
            }
        }
    }
</script>

@endsection