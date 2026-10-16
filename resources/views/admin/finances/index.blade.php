@extends('layouts.admin')

@section('content')
<div class="p-8 max-w-7xl mx-auto" x-data="financeManager()">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-zinc-900 dark:text-white">Finanzas</h1>
            <p class="text-zinc-500 mt-1">Control de ingresos y egresos del mes actual.</p>
        </div>
        <button @click="openCreate()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold transition shadow-lg shadow-blue-600/20 whitespace-nowrap flex items-center gap-2">
            <span>+</span> Registrar Movimiento
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white dark:bg-[#1a1612] p-6 rounded-[24px] border border-zinc-200 dark:border-white/5 flex flex-col gap-2 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 text-green-500/10 dark:text-green-500/5 text-8xl">📈</div>
            <p class="text-zinc-500 text-sm font-bold uppercase z-10">Ingresos del Mes</p>
            <p class="text-4xl font-extrabold text-green-600 dark:text-green-400 z-10">${{ number_format($income, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1612] p-6 rounded-[24px] border border-zinc-200 dark:border-white/5 flex flex-col gap-2 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 text-red-500/10 dark:text-red-500/5 text-8xl">📉</div>
            <p class="text-zinc-500 text-sm font-bold uppercase z-10">Gastos del Mes</p>
            <p class="text-4xl font-extrabold text-red-600 dark:text-red-400 z-10">${{ number_format($expense, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1612] p-6 rounded-[24px] border border-zinc-200 dark:border-white/5 flex flex-col gap-2 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -top-4 text-zinc-500/5 text-8xl">💰</div>
            <p class="text-zinc-500 text-sm font-bold uppercase z-10">Balance Neto</p>
            <p class="text-4xl font-extrabold z-10 {{ $balance >= 0 ? 'text-zinc-900 dark:text-white' : 'text-red-500' }}">
                ${{ number_format($balance, 2) }}
            </p>
        </div>
    </div>

    <div class="bg-white dark:bg-[#1a1612] rounded-[32px] shadow-xl border border-zinc-200 dark:border-white/5 overflow-hidden">
        <div class="p-6 border-b border-zinc-100 dark:border-white/5 flex justify-between items-center bg-zinc-50/50 dark:bg-white/[0.02]">
            <h2 class="font-bold text-lg text-zinc-900 dark:text-white">Historial de Transacciones</h2>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-zinc-400 border-b border-zinc-100 dark:border-white/5">
                        <th class="p-6 font-medium">Tipo / Categoría</th>
                        <th class="p-6 font-medium">Descripción</th>
                        <th class="p-6 font-medium">Fecha</th>
                        <th class="p-6 font-medium text-right">Monto</th>
                        <th class="p-6 font-medium text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/50">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-white/5 transition group">
                            <td class="p-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $tx->type === 'income' ? 'bg-green-100 dark:bg-green-500/20 text-green-600' : 'bg-red-100 dark:bg-red-500/20 text-red-600' }}">
                                        @if($tx->type === 'income')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-zinc-900 dark:text-white text-sm">{{ $tx->type === 'income' ? 'Ingreso' : 'Egreso' }}</p>
                                        <p class="text-xs text-zinc-500 font-medium">{{ $tx->category }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6"><p class="text-zinc-600 dark:text-zinc-300 text-sm">{{ $tx->description }}</p></td>
                            <td class="p-6"><p class="text-zinc-500 text-sm font-medium">{{ \Carbon\Carbon::parse($tx->transaction_date)->translatedFormat('d M, Y') }}</p></td>
                            <td class="p-6 text-right">
                                <p class="font-extrabold text-lg {{ $tx->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-zinc-900 dark:text-white' }}">
                                    {{ $tx->type === 'income' ? '+' : '-' }}${{ number_format($tx->amount, 2) }}
                                </p>
                            </td>
                            <td class="p-6 text-right">
                                <form action="{{ route('admin.finances.destroy', $tx->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este registro financiero?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-zinc-400 hover:text-red-500 transition opacity-0 group-hover:opacity-100 p-2">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-12 text-center text-zinc-500">No hay movimientos registrados aún.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL: NUEVO MOVIMIENTO --}}
    <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-black/60 backdrop-blur-sm" @click="openModal = false"></div>
            <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-[#1a1612] rounded-[32px] shadow-2xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-zinc-200 dark:border-white/10">
                <form action="{{ route('admin.finances.store') }}" method="POST" class="p-8">
                    @csrf
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">Registrar Movimiento</h3>
                        <button type="button" @click="openModal = false" class="text-zinc-400 hover:text-zinc-800 dark:hover:text-white text-2xl">&times;</button>
                    </div>

                    <div class="flex bg-zinc-100 dark:bg-white/5 p-1 rounded-2xl mb-6">
                        <label class="flex-1 text-center cursor-pointer">
                            <input type="radio" name="type" value="income" x-model="formData.type" class="peer sr-only">
                            <div class="py-2 rounded-xl text-sm font-bold text-zinc-500 peer-checked:bg-green-500 peer-checked:text-white transition flex justify-center items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                                Ingreso
                            </div>
                        </label>
                        <label class="flex-1 text-center cursor-pointer">
                            <input type="radio" name="type" value="expense" x-model="formData.type" class="peer sr-only">
                            <div class="py-2 rounded-xl text-sm font-bold text-zinc-500 peer-checked:bg-red-500 peer-checked:text-white transition flex justify-center items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
                                Gasto
                            </div>
                        </label>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="amount" class="block text-center text-xs font-bold uppercase text-zinc-500 mb-2">Monto Total ($)</label>
                            <input type="number" id="amount" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="w-full text-center text-4xl font-extrabold bg-transparent border-b-2 border-zinc-200 dark:border-zinc-800 focus:border-blue-500 outline-none pb-2 text-zinc-900 dark:text-white transition">
                        </div>

                        <div>
                            <label for="category" class="block text-xs font-bold uppercase text-zinc-500 mb-2">Categoría</label>
                            <select id="category" name="category" required class="w-full bg-zinc-100 dark:bg-white/5 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 text-zinc-900 dark:text-white appearance-none">
                                <option value="" disabled selected>Selecciona una categoría</option>
                                <optgroup label="Ingresos" x-show="formData.type === 'income'">
                                    <option value="Ventas en Local">Ventas en Local</option>
                                    <option value="Eventos Especiales">Eventos Especiales</option>
                                    <option value="Otros Ingresos">Otros Ingresos</option>
                                </optgroup>
                                <optgroup label="Egresos" x-show="formData.type === 'expense'">
                                    <option value="Proveedores (Insumos)">Proveedores (Insumos)</option>
                                    <option value="Nómina / Sueldos">Nómina / Sueldos</option>
                                    <option value="Servicios (Luz, Agua, Internet)">Servicios (Luz, Agua, Internet)</option>
                                    <option value="Mantenimiento">Mantenimiento</option>
                                    <option value="Gastos Generales">Gastos Generales (Otros)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div>
                            <label for="transaction_date" class="block text-xs font-bold uppercase text-zinc-500 mb-2">Fecha del Movimiento</label>
                            <input type="date" id="transaction_date" name="transaction_date" x-model="formData.date" required class="w-full bg-zinc-100 dark:bg-white/5 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 text-zinc-900 dark:text-white text-center">
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-bold uppercase text-zinc-500 mb-2">Detalles / Concepto</label>
                            <textarea id="description" name="description" rows="2" required placeholder="Ej: Pago de recibo de CFE..." class="w-full bg-zinc-100 dark:bg-white/5 border-none rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-500 text-zinc-900 dark:text-white resize-none"></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button type="button" @click="openModal = false" class="flex-1 px-6 py-3 rounded-xl font-bold text-zinc-500 hover:bg-zinc-100 dark:hover:bg-white/5 transition">Cancelar</button>
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-blue-600/20 transition">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function financeManager() {
        return {
            openModal: false,
            formData: { type: 'expense', date: new Date().toISOString().split('T')[0] },
            openCreate() {
                this.formData = { type: 'expense', date: new Date().toISOString().split('T')[0] };
                this.openModal = true;
            }
        }
    }
</script>
@endsection
