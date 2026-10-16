<div x-show="openModal"
     class="fixed inset-0 z-50 overflow-y-auto"
     x-cloak>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-black/60 backdrop-blur-sm" @click="openModal = false"></div>

        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-[#1a1612] rounded-[32px] shadow-2xl sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-zinc-200 dark:border-white/10">
            
            {{-- La acción cambia dinámicamente gracias a Alpine --}}
            <form :action="actionUrl" method="POST" enctype="multipart/form-data" class="p-8" @submit="if(isSubmitting) { $event.preventDefault(); } else { isSubmitting = true; }">
                @csrf
                
                {{-- Truco para mandar PUT solo si estamos editando --}}
                <input type="hidden" name="_method" value="PUT" :disabled="!isEdit">
                
                <input type="hidden" name="start_date" value="{{ now()->toDateString() }}">

                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white" x-text="isEdit ? 'Editar Publicación' : 'Nueva Publicación'">Nueva Publicación</h3>
                    <button type="button" @click="openModal = false" class="text-zinc-400 hover:text-zinc-800 dark:hover:text-white text-2xl">&times;</button>
                </div>

                <div class="space-y-6">
                    {{-- IMAGEN --}}
                    <div>
                        <label for="image_upload" class="block text-xs font-bold uppercase text-zinc-500 mb-2 text-center">Imagen de Portada</label>
                        <div class="flex justify-center w-full">
                            <label for="image_upload" class="w-full max-w-sm aspect-video flex flex-col items-center justify-center border-2 border-dashed border-blue-300 dark:border-blue-500/30 rounded-2xl cursor-pointer bg-blue-50/50 dark:bg-blue-900/10 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition relative overflow-hidden group">
                                
                                <template x-if="imagePreview">
                                    <img :src="imagePreview" alt="Vista previa de la portada" class="absolute inset-0 w-full h-full object-cover z-10 group-hover:opacity-80 transition">
                                </template>
                                
                                <div x-show="!imagePreview" class="flex flex-col items-center justify-center z-0 p-4 text-center">
                                    <svg class="w-10 h-10 text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span class="text-sm font-bold text-blue-600 dark:text-blue-400" x-text="isEdit ? 'Haz clic para cambiar' : 'Haz clic para subir imagen'">Haz clic para subir imagen</span>
                                </div>

                                <template x-if="imagePreview">
                                    <div class="absolute inset-0 z-20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition bg-black/40 backdrop-blur-sm">
                                        <span class="text-white font-bold text-sm flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            Cambiar imagen
                                        </span>
                                    </div>
                                </template>

                                <input id="image_upload" type="file" name="image" class="hidden" accept="image/*"
                                       @change="
                                            const file = $event.target.files[0];
                                            if(file) {
                                                const reader = new FileReader();
                                                reader.onload = (e) => { imagePreview = e.target.result; };
                                                reader.readAsDataURL(file);
                                            }
                                       " />
                            </label>
                        </div>
                    </div>

                    {{-- TÍTULO Y CATEGORÍA --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="title" class="block text-xs font-bold uppercase text-zinc-500 mb-2">Título de la noticia</label>
                            <input id="title" type="text" name="title" x-model="newsData.title" required class="w-full bg-zinc-100 dark:bg-white/5 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 transition text-zinc-900 dark:text-white">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="category" class="block text-xs font-bold uppercase text-zinc-500 mb-2">Categoría</label>
                            <select id="category" name="category" x-model="newsData.category" class="w-full bg-zinc-100 dark:bg-white/5 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 transition text-zinc-900 dark:text-white appearance-none">
                                <option value="Deportes">⚽ Deportes</option>
                                <option value="Evento">🎉 Evento</option>
                                <option value="Aviso">📢 Aviso Importante</option>
                            </select>
                        </div>
                    </div>

                    {{-- FECHA DE CADUCIDAD --}}
                    <div class="bg-zinc-50 dark:bg-white/5 p-4 rounded-xl border border-zinc-100 dark:border-white/5">
                        <label for="end_date" class="block text-xs font-bold uppercase text-zinc-500 mb-2">¿Cuándo debe ocultarse? (Opcional)</label>
                        <input id="end_date" type="date" name="end_date" x-model="newsData.end_date" class="w-full bg-white dark:bg-[#1a1612] border border-zinc-200 dark:border-white/10 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 transition text-zinc-900 dark:text-white text-center">
                    </div>

                    {{-- DESCRIPCIÓN --}}
                    <div>
                        <label for="content_desc" class="block text-xs font-bold uppercase text-zinc-500 mb-2">Descripción / Detalles</label>
                        <textarea id="content_desc" name="content" rows="4" x-model="newsData.content" required class="w-full bg-zinc-100 dark:bg-white/5 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 transition text-zinc-900 dark:text-white resize-none"></textarea>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" x-show="!isSubmitting" @click="openModal = false" class="flex-1 px-6 py-3 rounded-xl font-bold text-zinc-500 hover:bg-zinc-100 dark:hover:bg-white/5 transition">Cancelar</button>
                    <button type="submit" :disabled="isSubmitting" :class="{ 'opacity-70 cursor-not-allowed': isSubmitting }" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-blue-600/20 transition flex justify-center items-center gap-2">
                        
                        <svg x-show="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        <svg x-show="isSubmitting" class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        
                        <span x-text="isSubmitting ? 'Guardando...' : (isEdit ? 'Guardar Cambios' : 'Publicar ahora')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
