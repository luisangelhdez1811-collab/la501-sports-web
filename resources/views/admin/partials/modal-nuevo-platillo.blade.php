<div x-show="openModal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 dark:bg-black/80 backdrop-blur-sm"
     x-cloak>

    <div @click.away="openModal = false"
         class="bg-white dark:bg-[#1a1612] w-full max-w-lg p-8 rounded-[32px] border border-zinc-200 dark:border-white/5 shadow-2xl relative max-h-[90vh] overflow-y-auto custom-scrollbar transition-colors duration-300">

        <button @click="openModal = false" class="absolute top-6 right-6 text-zinc-400 hover:text-zinc-800 dark:hover:text-white text-2xl" aria-label="Cerrar modal">&times;</button>

        {{-- FIX Web:S6850: heading con x-text necesita aria-label como fallback estático --}}
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-1"
            x-text="isEdit ? 'Editar Platillo' : 'Nuevo Platillo'"
            x-bind:aria-label="isEdit ? 'Editar Platillo' : 'Nuevo Platillo'">
            <span class="sr-only">Platillo</span>
        </h2>
        <p class="text-zinc-500 text-sm mb-6" x-text="isEdit ? 'Modifica los datos del platillo seleccionado' : 'Completa la información detallada'"></p>

        <form :action="action" method="POST" enctype="multipart/form-data">
            @csrf
            <template x-if="isEdit">
                @method('PUT')
            </template>

            <div class="space-y-6">

                {{-- Imagen --}}
                <div>
                    {{-- FIX Web:S6853 L21: label for="platillo-image" + id en el input --}}
                    <label for="platillo-image" class="block text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3">Imagen del Platillo</label>
                    <div class="flex justify-center w-full">
                        <label for="platillo-image" class="w-48 aspect-square flex flex-col items-center justify-center border-2 border-dashed border-zinc-300 dark:border-white/10 rounded-[24px] cursor-pointer bg-zinc-50 dark:bg-black/20 hover:bg-zinc-100 dark:hover:bg-black/40 hover:border-green-500/50 transition relative overflow-hidden">

                            <template x-if="imagePreview">
                                {{-- FIX Web:ImgWithoutAltCheck L26: añadido alt --}}
                                <img :src="imagePreview" :alt="productData.name || 'Vista previa del platillo'" class="absolute inset-0 w-full h-full object-cover z-10">
                            </template>

                            <div x-show="!imagePreview" class="flex flex-col items-center justify-center z-0">
                                <svg class="w-8 h-8 text-blue-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400">Subir imagen</span>
                            </div>

                            <input type="file" id="platillo-image" name="image" class="hidden" accept="image/*"
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

                {{-- Toggle disponible --}}
                <div class="flex items-center gap-3 p-4 bg-zinc-50 dark:bg-black/20 rounded-2xl border border-zinc-100 dark:border-white/5">
                    {{-- FIX Web:S6853: label for="platillo-available" con texto incluido --}}
                    <label for="platillo-available" class="relative inline-flex items-center cursor-pointer gap-3">
                        <input type="checkbox" id="platillo-available" name="available" value="1" :checked="productData.available" class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-300 dark:bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        <span class="text-sm font-medium text-zinc-700 dark:text-white">Disponible en carta</span>
                    </label>
                </div>

                {{-- Nombre --}}
                <div>
                    {{-- FIX Web:S6853 L60: label for="platillo-name" --}}
                    <label for="platillo-name" class="block text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">Nombre del Platillo</label>
                    <input type="text" id="platillo-name" name="name" required x-model="productData.name"
                           class="w-full bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/10 rounded-2xl px-4 py-3 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500/50 transition">
                </div>

                {{-- Descripción --}}
                <div>
                    {{-- FIX Web:S6853 L66: label for="platillo-description" --}}
                    <label for="platillo-description" class="block text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">Descripción</label>
                    <textarea id="platillo-description" name="description" rows="2" x-model="productData.description"
                              class="w-full bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/10 rounded-2xl px-4 py-3 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500/50 transition"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    {{-- Precio --}}
                    <div>
                        {{-- FIX Web:S6853 L73: label for="platillo-price" --}}
                        <label for="platillo-price" class="block text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">Precio ($)</label>
                        <input type="number" id="platillo-price" step="0.01" min="0" name="price" required placeholder="0.00" x-model="productData.price"
                               class="w-full bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/10 rounded-2xl px-4 py-3 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500/50 transition">
                    </div>
                    {{-- Categoría --}}
                    <div>
                        {{-- FIX Web:S6853 L78: label for="platillo-category" --}}
                        <label for="platillo-category" class="block text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">Categoría</label>
                        <select id="platillo-category" name="category" x-model="productData.category" class="w-full bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/10 rounded-2xl px-4 py-3 text-zinc-900 dark:text-white focus:outline-none transition font-bold">
                            <optgroup label="Comida Fuerte">
                                <option value="Hamburguesas">Hamburguesas</option>
                                <option value="Jochos">Jochos</option>
                                <option value="Burritos">Burritos</option>
                                <option value="Tacos">Tacos</option>
                                <option value="Strombolis">Strombolis</option>
                                <option value="Alitas y Costillas">Alitas y Costillas</option>
                            </optgroup>
                            <optgroup label="Complementos">
                                <option value="Especialidades">Especialidades (Papas, Nachos)</option>
                                <option value="Opción Fit">Opción Fit (Ensaladas)</option>
                                <option value="Salsas y Extras">Salsas y Extras</option>
                            </optgroup>
                            <optgroup label="Postres & Bebidas">
                                <option value="Algo Dulce">Algo Dulce (Postres)</option>
                                <option value="Sin Alcohol">Sin Alcohol (Refrescos, Aguas)</option>
                            </optgroup>
                            <optgroup label="Bar">
                                <option value="Cervezas">Cervezas</option>
                                <option value="Coctelería">Coctelería</option>
                                <option value="Destilados">Destilados</option>
                            </optgroup>
                        </select>

                        <div x-show="['Coctelería', 'Destilados'].includes(productData.category)" x-transition>
                            {{-- FIX Web:S6853 L104: label for="platillo-subcategory" --}}
                            <label for="platillo-subcategory" class="block text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2 mt-3">Subcategoría</label>
                            <select id="platillo-subcategory" name="subcategory" x-model="productData.subcategory" class="w-full bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/10 rounded-2xl px-4 py-3 text-zinc-900 dark:text-white focus:outline-none transition font-bold">
                                <option value="">-- Sin subcategoría --</option>
                                <template x-if="productData.category === 'Coctelería'">
                                    <optgroup label="Coctelería">
                                        <option value="Ron">Ron</option>
                                        <option value="Vodka">Vodka</option>
                                        <option value="Tequila">Tequila</option>
                                        <option value="Mezcal">Mezcal</option>
                                        <option value="Ginebra">Ginebra</option>
                                        <option value="Digestivos">Digestivos</option>
                                    </optgroup>
                                </template>
                                <template x-if="productData.category === 'Destilados'">
                                    <optgroup label="Destilados">
                                        <option value="Tequila">Tequila</option>
                                        <option value="Ron">Ron</option>
                                        <option value="Brandy">Brandy</option>
                                        <option value="Vodka">Vodka</option>
                                        <option value="Whisky">Whisky</option>
                                        <option value="Mezcal">Mezcal</option>
                                        <option value="Copeo">Copeo</option>
                                        <option value="6 Shots">6 Shots</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Ingredientes --}}
                <div>
                    <div class="flex justify-between items-center mb-3">
                        {{-- FIX Web:S6853 L136: label asociado al grupo de ingredientes --}}
                        <span id="ingredientes-label" class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Ingredientes</span>
                        <button type="button" @click="addIngredient()"
                                class="text-[10px] bg-green-600/10 dark:bg-green-600/20 text-green-600 dark:text-green-500 px-3 py-1 rounded-full border border-green-600/30 hover:bg-green-600 hover:text-white transition">
                            + Añadir fila
                        </button>
                    </div>
                    <fieldset class="space-y-2 border-none p-0 m-0" aria-labelledby="ingredientes-label">
                        <template x-for="(ingredient, index) in ingredients" :key="index">
                            <div class="flex gap-2 relative" x-data="{ open: false }">
                                <div class="flex-1 relative">
                                    <input type="text" name="ingredients[]" required
                                           :id="'ingrediente-' + index"
                                           :aria-label="'Ingrediente ' + (index + 1)"
                                           :placeholder="'Buscar ingrediente ' + (index + 1)"
                                           x-model="ingredients[index]"
                                           @focus="open = true"
                                           @click.away="open = false"
                                           autocomplete="off"
                                           class="w-full bg-zinc-50 dark:bg-black/40 border border-zinc-200 dark:border-white/10 rounded-xl px-4 py-2 text-sm text-zinc-900 dark:text-white focus:ring-1 focus:ring-green-500/50 outline-none">

                                    <ul x-show="open"
                                        class="absolute z-50 w-full mt-1 bg-white dark:bg-[#1a1612] border border-zinc-200 dark:border-white/10 rounded-xl shadow-lg max-h-40 overflow-y-auto custom-scrollbar"
                                        :aria-label="'Sugerencias para ingrediente ' + (index + 1)">

                                        <template x-for="item in inventoryList.filter(i => i.name.toLowerCase().includes(ingredients[index].toLowerCase()))" :key="item.id">
                                            <li @click="ingredients[index] = item.name; open = false"
                                                class="px-4 py-2 text-sm cursor-pointer hover:bg-green-50 dark:hover:bg-green-900/20 text-zinc-700 dark:text-zinc-300 transition border-b border-zinc-100 dark:border-white/5 last:border-0">
                                                <span x-text="item.name"></span>
                                            </li>
                                        </template>

                                        <li x-show="inventoryList.filter(i => i.name.toLowerCase().includes(ingredients[index].toLowerCase())).length === 0"
                                            aria-hidden="true"
                                            class="px-4 py-2 text-sm text-zinc-500 italic bg-zinc-50 dark:bg-black/20">
                                            No hay coincidencias en inventario...
                                        </li>
                                    </ul>

                                </div>

                                <button type="button" @click="removeIngredient(index)" x-show="ingredients.length > 1"
                                        :aria-label="'Eliminar ingrediente ' + (index + 1)"
                                        class="text-zinc-400 hover:text-red-500 px-2 transition">
                                    &times;
                                </button>
                            </div>
                        </template>
                    </fieldset>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="button" @click="openModal = false"
                        class="flex-1 bg-zinc-200 dark:bg-zinc-800/50 hover:bg-zinc-300 dark:hover:bg-zinc-800 text-zinc-700 dark:text-white font-bold py-4 rounded-2xl transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 bg-blue-700 text-white px-6 py-4 rounded-2xl font-bold transition shadow-lg shadow-blue-600/20 hover:bg-blue-800">
                    <span x-text="isEdit ? 'Guardar Cambios' : 'Guardar Platillo'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 10px; }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #333; }
</style>
