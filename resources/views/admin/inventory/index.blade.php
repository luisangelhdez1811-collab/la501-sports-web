@extends('layouts.admin')

@section('content')
<style>
.inv-search { position: relative; display: flex; align-items: center; width: 100%; max-width: 300px; }
.inv-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px !important; height: 16px !important; color: var(--sub); pointer-events: none; z-index: 10; }
.inv-search input { width: 100%; padding: 8px 12px 8px 35px !important; background: var(--card); border: 1px solid var(--bdr); border-radius: 8px; color: var(--txt); outline: none; }
.inv-hd svg, .inv-btn-add svg, .inv-btn-export svg, .inv-btn-import svg { width: 18px !important; height: 18px !important; flex-shrink: 0; }
.inv { --ac:#2563EB;--ac2:#1D4ED8;--gn:#16A34A;--gn2:#22C55E;--rd:#DC2626;--rd2:#EF4444;--am:#D97706;--am2:#F59E0B;--bg:#F8F8F8;--card:#FFFFFF;--inp:#F4F4F5;--txt:#18181B;--sub:#71717A;--bdr:#E4E4E7; }
.dark .inv { --bg:#0A0A0A;--card:#111111;--inp:#1C1C1C;--txt:#FAFAFA;--sub:#71717A;--bdr:rgba(255,255,255,.08); }
.inv { background:var(--bg); min-height:100%; padding:28px 32px 60px; color:var(--txt); }
.inv-hd { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.inv-hd h1 { font-size:20px; font-weight:700; color:var(--txt); margin:0 0 2px; }
.inv-hd p  { font-size:13px; color:var(--sub); margin:0; }
.inv-btn-add, .inv-btn-export, .inv-btn-import { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:7px; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; transition:background .15s; box-shadow:0 1px 4px rgba(0,0,0,.15); text-decoration:none; }
.inv-btn-add { background:var(--ac); } .inv-btn-add:hover { background:var(--ac2); }
.inv-btn-export { background:#475569; } .inv-btn-export:hover { background:#334155; }
.inv-btn-import { background:var(--gn); } .inv-btn-import:hover { background:#15803D; }
.inv-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.inv-stat { flex:1; min-width:160px; background:var(--card); border:1px solid var(--bdr); border-radius:8px; padding:12px 18px; display:flex; align-items:center; gap:12px; }
.inv-stat-ico { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.inv-stat-ico svg { width:15px !important; height:15px !important; }
.inv-stat-ico.bl { background:rgba(37,99,235,.1); color:#2563EB; } .inv-stat-ico.rd { background:rgba(220,38,38,.1); color:#DC2626; } .inv-stat-ico.gn { background:rgba(22,163,74,.1); color:#16A34A; }
.inv-stat-v { font-size:22px; font-weight:800; letter-spacing:-.5px; color:var(--txt); margin:0 0 1px; line-height:1; }
.inv-stat-l { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.6px; color:var(--sub); }
.inv-card { background:var(--card); border:1px solid var(--bdr); border-radius:10px; overflow:hidden; }
.inv-table { width:100%; border-collapse:collapse; min-width:700px; }
.inv-table th { padding:10px 16px; font-size:10px; font-weight:700; text-transform:uppercase; color:var(--sub); background:var(--inp); border-bottom:1px solid var(--bdr); text-align:left; }
.inv-table td { padding:12px 16px; border-bottom:1px solid var(--bdr); vertical-align:middle; }
.inv-bar { display:flex; gap:10px; align-items:center; margin-bottom:15px; flex-wrap:wrap; }
.inv-filter-btn { padding:6px 12px; border-radius:6px; border:1px solid var(--bdr); background:var(--card); color:var(--sub); font-size:12px; font-weight:600; cursor:pointer; }
.inv-filter-btn.on { background:var(--ac); color:#fff; border-color:var(--ac); } .inv-filter-btn.rd-on { background:var(--rd); color:#fff; border-color:var(--rd); }
.inv-actions { display:flex !important; gap:8px; justify-content:flex-end; align-items:center; }
.inv-act { width:32px !important; height:32px !important; border-radius:8px; border:1px solid var(--bdr); background:var(--inp); display:flex !important; align-items:center; justify-content:center; cursor:pointer; color:var(--sub); transition:all 0.2s; padding:0; }
.inv-act svg { width:16px !important; height:16px !important; display:block; }
.inv-act:hover { background:var(--bdr); color:var(--txt); }
.inv-act.adj:hover { color:var(--gn); border-color:var(--gn); background:rgba(22,163,74,0.1); }
.inv-act.ed:hover  { color:var(--ac); border-color:var(--ac); background:rgba(37,99,235,0.1); }
.inv-act.dl:hover  { color:var(--rd); border-color:var(--rd); background:rgba(220,38,38,0.1); }
.mi-modal-seguro { display:none; position:fixed !important; inset:0 !important; z-index:9999999 !important; background:rgba(15,23,42,0.9) !important; backdrop-filter:blur(8px) !important; align-items:center; justify-content:center; padding:16px; }
.inv-overlay { position:fixed; inset:0; z-index:50; background:rgba(15,23,42,0.9) !important; backdrop-filter:blur(8px) !important; display:flex; align-items:flex-start; justify-content:center; padding:40px 16px 60px; overflow-y:auto; }
.am-modal { background:var(--card); border:1px solid var(--bdr); border-radius:16px; width:100%; max-width:450px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); }
.inv-mhd { display:flex; align-items:center; justify-content:space-between; padding:20px 24px; border-bottom:1px solid var(--bdr); }
.inv-mbody { padding:24px; display:flex; flex-direction:column; gap:16px; }
.inv-mfoot { padding:16px 24px; border-top:1px solid var(--bdr); display:flex; align-items:center; justify-content:flex-end; gap:8px; }
.inv-lbl { display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--sub); margin-bottom:6px; }
.inv-inp { width:100%; padding:10px 12px; background:var(--inp); border:1px solid var(--bdr); border-radius:8px; font-size:14px; color:var(--txt); outline:none; }
.inv-btn-cancel { padding:10px 16px; border-radius:8px; background:var(--inp); border:1px solid var(--bdr); font-size:14px; font-weight:600; color:var(--sub); cursor:pointer; }
.inv-mclose { width:30px; height:30px; border-radius:50%; background:var(--inp); border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--sub); font-weight:bold; }
.inv-adj-toggle { display:grid; grid-template-columns:1fr 1fr; background:var(--inp); border:1px solid var(--bdr); border-radius:8px; padding:4px; }
.inv-adj-opt { padding:10px; border-radius:6px; text-align:center; font-size:14px; font-weight:700; cursor:pointer; color:var(--sub); }
.inv-adj-opt.on { background:white; box-shadow:0 2px 4px rgba(0,0,0,0.05); color:var(--ac); }
.inv-adj-opt.add.on { color:var(--gn); } .inv-adj-opt.sub.on { color:var(--rd); }
.inv-g2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
</style>

@if(session('success'))
    <div style="background: var(--gn); color: white; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; z-index: 99;">
        {{ session('success') }}
    </div>
@endif

<div class="inv" x-data="inventoryManager()" x-init="init()">

    <div class="inv-hd">
        <div>
            <h1>Inventario de Insumos</h1>
            <p>Controla ingredientes, bebidas y suministros</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap">
            <button type="button" onclick="document.getElementById('modal-import-inv').style.setProperty('display', 'flex', 'important')" class="inv-btn-import">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Importar CSV
            </button>
            <a href="{{ route('admin.inventory.export') }}" class="inv-btn-export">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Exportar CSV
            </a>
            <button @click="openCreate()" class="inv-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Artículo
            </button>
        </div>
    </div>

    <div class="inv-stats">
        <div class="inv-stat">
            <div class="inv-stat-ico bl"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
            <div><p class="inv-stat-v">{{ $totalItems }}</p><p class="inv-stat-l">Total artículos</p></div>
        </div>
        <div class="inv-stat">
            <div class="inv-stat-ico rd"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
            <div><p class="inv-stat-v" style="color:var(--rd)">{{ $lowStockCount }}</p><p class="inv-stat-l">Stock bajo</p></div>
        </div>
        <div class="inv-stat">
            <div class="inv-stat-ico gn"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div><p class="inv-stat-v" style="color:var(--gn)">{{ $totalItems - $lowStockCount }}</p><p class="inv-stat-l">En buen nivel</p></div>
        </div>
    </div>

    <div class="inv-bar">
        <div class="inv-search">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" x-model="search" placeholder="Buscar artículo...">
        </div>
        <button @click="catFilter='todos'" :class="catFilter==='todos'?'on':''" class="inv-filter-btn">Todos</button>
        <button @click="catFilter='low'" :class="catFilter==='low'?'rd-on':''" class="inv-filter-btn" style="color:var(--rd)">⚠ Stock bajo</button>
    </div>

    <div class="inv-card">
        <div class="inv-table-wrap">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th>Artículo</th><th>Categoría</th>
                        <th style="text-align:center">Stock actual</th>
                        <th style="text-align:center">Mínimo</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr x-show="rowVisible('{{ $item->category }}', {{ $item->current_stock <= $item->min_stock ? 'true' : 'false' }}, '{{ addslashes($item->name) }}')">
                        <td><p class="inv-name">{{ $item->name }}</p></td>
                        <td><span class="inv-cat-chip">{{ $item->category }}</span></td>
                        <td style="text-align:center"><b>{{ floatval($item->current_stock) }} {{ $item->unit }}</b></td>
                        <td style="text-align:center">{{ floatval($item->min_stock) }} {{ $item->unit }}</td>
                        <td>
                            <div class="inv-actions">
                                <button class="inv-act adj" title="Ajustar stock" @click="openAdjust({{ json_encode($item) }})">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </button>
                                <button class="inv-act ed" title="Editar" @click="openEdit({{ json_encode($item) }})">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form action="{{ route('admin.inventory.destroy', $item->id) }}" method="POST" @submit.prevent="confirmDelete($el)">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inv-act dl" title="Eliminar">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL CREAR/EDITAR — FIX: aria-label en h3 dinámico, for/id en todos los labels --}}
    <div x-show="openModal" x-cloak class="inv-overlay" @click.self="openModal=false">
        <div class="am-modal" @click.stop>
            <form :action="actionUrl" method="POST">
                @csrf <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>
                <div class="inv-mhd">
                    <div>
                        {{-- FIX S6850: aria-label en h3 con contenido Alpine dinámico --}}
                        <h3 x-text="isEdit ? 'Editar Artículo' : 'Nuevo Artículo'"
                        :aria-label="isEdit ? 'Editar Artículo' : 'Nuevo Artículo'"
                        style="margin:0; font-size:18px; font-weight:800;">Artículo</h3>
                    </div>
                    <button type="button" @click="openModal=false" class="inv-mclose">×</button>
                </div>
                <div class="inv-mbody">
                    {{-- FIX S6853: for/id en todos los labels del modal --}}
                    <div>
                        <label for="inv-name" class="inv-lbl">Nombre</label>
                        <input id="inv-name" type="text" name="name" class="inv-inp" x-model="formData.name" required>
                    </div>
                    <div>
                        <label for="inv-category" class="inv-lbl">Categoría</label>
                        <input id="inv-category" type="text" name="category" class="inv-inp" x-model="formData.category" required>
                    </div>
                    <div class="inv-g2">
                        <div>
                            <label for="inv-current-stock" class="inv-lbl">Stock Actual</label>
                            <input id="inv-current-stock" type="number" step="0.01" name="current_stock" class="inv-inp" x-model="formData.current_stock" required>
                        </div>
                        <div>
                            <label for="inv-min-stock" class="inv-lbl">Mínimo</label>
                            <input id="inv-min-stock" type="number" step="0.01" name="min_stock" class="inv-inp" x-model="formData.min_stock" required>
                        </div>
                    </div>
                    <div>
                        <label for="inv-unit" class="inv-lbl">Unidad (kg, L, pz)</label>
                        <input id="inv-unit" type="text" name="unit" class="inv-inp" x-model="formData.unit" required>
                    </div>
                </div>
                <div class="inv-mfoot">
                    <button type="button" @click="openModal=false" class="inv-btn-cancel">Cancelar</button>
                    <button type="submit" class="inv-btn-save" style="background:var(--ac);color:white;padding:10px 20px;border-radius:8px;cursor:pointer;border:none;font-weight:bold;font-size:14px;">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL AJUSTE RÁPIDO STOCK --}}
    <div x-show="openAdjustModal" x-cloak class="inv-overlay" @click.self="openAdjustModal=false">
        <div class="am-modal" style="max-width:400px" @click.stop>
            <div class="inv-mhd">
                <h3 style="margin:0; font-size:18px; font-weight:800;">Ajustar Stock</h3>
                <button type="button" @click="openAdjustModal=false" class="inv-mclose">×</button>
            </div>
            <form :action="adjustUrl" method="POST">
                @csrf <input type="hidden" name="_method" value="PUT">
                <div class="inv-mbody">
                    <div class="inv-adj-toggle">
                        <div class="inv-adj-opt add" :class="adjType==='add'?'on':''" @click="adjType='add'">↑ Entró</div>
                        <div class="inv-adj-opt sub" :class="adjType==='subtract'?'on':''" @click="adjType='subtract'">↓ Salió</div>
                    </div>
                    <input type="hidden" name="type" :value="adjType">
                    <div style="text-align:center; margin-top:15px">
                        <input type="number" step="0.01" name="amount" class="inv-inp" style="font-size:24px;text-align:center;height:60px" x-model="adjAmount" placeholder="0.00" required>
                    </div>
                </div>
                <div class="inv-mfoot">
                    <button type="submit" class="inv-btn-save" style="background:var(--gn);color:white;width:100%;padding:14px;border:none;border-radius:10px;font-weight:bold;font-size:15px;cursor:pointer;">Actualizar Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL IMPORTAR — FIX S6853 + S6847: label con for, sin onmouseover/onmouseout --}}
<div id="modal-import-inv" style="display:none; position:fixed; inset:0; z-index:9999999; background:rgba(0,0,0,.6); backdrop-filter:blur(8px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:480px; box-shadow:0 32px 80px rgba(0,0,0,.18); overflow:hidden; font-family:inherit;">
        <div style="height:4px; background:linear-gradient(90deg,#2563EB,#16A34A);"></div>
        <div style="padding:24px 24px 0;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:40px;height:40px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg style="width:18px;height:18px;color:#2563EB;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </div>
                    <div>
                        <p style="margin:0 0 2px;font-size:15px;font-weight:700;color:#111827;">Importar Inventario</p>
                        <p style="margin:0;font-size:12px;color:#6b7280;">Actualiza el inventario desde un archivo CSV</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-import-inv').style.display='none'" style="width:30px;height:30px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6b7280;flex-shrink:0;font-size:18px;line-height:1;">×</button>
            </div>
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;margin-bottom:12px;">
                <p style="margin:0 0 4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#1d4ed8;">📋 Formato requerido</p>
                <code style="font-size:12px;color:#1e40af;background:#dbeafe;padding:4px 8px;border-radius:6px;display:inline-block;">Nombre;Categoría;StockActual;StockMínimo;Unidad</code>
            </div>
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;margin-bottom:20px;display:flex;gap:8px;align-items:center;">
                <svg style="width:15px;height:15px;color:#dc2626;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p style="margin:0;font-size:12px;color:#dc2626;line-height:1.5;">Solo archivos <strong>.csv</strong> — verifica la estructura antes de subir.</p>
            </div>
        </div>
        {{-- NUEVO BOTÓN DE PLANTILLA --}}
            <div style="text-align: right; margin-bottom: 20px;">
                <a href="{{ route('admin.inventory.template') }}" style="font-size:12px; color:#2563EB; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition: color 0.2s;">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Descargar plantilla de ejemplo
                </a>
            </div>
        </div>

        <form action="{{ route('admin.inventory.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="padding:0 24px 20px;">
                {{-- FIX S6853: label con for apuntando al input --}}
                <label for="inv-csv-file" style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;margin-bottom:8px;">
                    Seleccionar archivo <span style="color:#2563EB;">*</span>
                </label>
                {{-- FIX S6847: hover movido a JS sobre div, sin onmouseover en label --}}
                <div id="inv-csv-dropzone" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:28px 20px;background:#f9fafb;border:2px dashed #d1d5db;border-radius:10px;cursor:pointer;">
                    <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span style="font-size:13px;font-weight:600;color:#374151;">Haz clic para seleccionar</span>
                    <span style="font-size:11px;color:#9ca3af;" id="inv-csv-filename">Ningún archivo seleccionado</span>
                    <input type="file" id="inv-csv-file" name="csv_file" accept=".csv" required style="display:none;"
                           onchange="document.getElementById('inv-csv-filename').textContent = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado'">
                </div>
            </div>
            <div style="padding:14px 24px 20px;border-top:1px solid #f3f4f6;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-import-inv').style.display='none'" style="padding:9px 18px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:9px 20px;border-radius:8px;background:#2563EB;color:#fff;border:none;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(37,99,235,.3);">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Subir Archivo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// FIX S6847: hover del dropzone con JS en lugar de atributos inline en label
const dz = document.getElementById('inv-csv-dropzone');
if (dz) {
    dz.addEventListener('mouseenter', () => dz.style.borderColor = '#2563EB');
    dz.addEventListener('mouseleave', () => dz.style.borderColor = '#d1d5db');
    dz.addEventListener('click', () => document.getElementById('inv-csv-file').click());
}

function inventoryManager() {
    return {
        search: '', catFilter: 'todos',
        rowVisible(cat, isLow, name) {
            const mS = !this.search || name.toLowerCase().includes(this.search.toLowerCase());
            const mC = this.catFilter === 'todos' || (this.catFilter === 'low' && isLow) || this.catFilter === cat;
            return mS && mC;
        },
        openModal: false, isEdit: false, actionUrl: '', formData: {name:'',category:'',unit:'',current_stock:'',min_stock:''},
        openCreate() { this.isEdit=false; this.actionUrl='{{ route("admin.inventory.store") }}'; this.formData={name:'',category:'',unit:'',current_stock:'',min_stock:''}; this.openModal=true; },
        openEdit(item) { this.isEdit=true; this.actionUrl='{{ route("admin.inventory.update", ":id") }}'.replace(':id', item.id); this.formData={...item}; this.openModal=true; },
        openAdjustModal: false, adjustUrl: '', adjItemName: '', adjType: 'add', adjAmount: '',
        openAdjust(item) { this.adjustUrl='{{ route("admin.inventory.adjust", ":id") }}'.replace(':id', item.id); this.adjItemName=item.name; this.adjType='add'; this.adjAmount=''; this.openAdjustModal=true; },
        confirmDelete(form) { if(confirm('¿Seguro que deseas eliminar este artículo?')) form.submit(); },
        init(){}
    }
}
</script>
@endsection
