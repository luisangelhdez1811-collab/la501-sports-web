@extends('layouts.admin')

@section('content')

<style>
    .au {
        --accent:    #2563EB;
        --accent2:   #1D4ED8;
        --danger:    #EF4444;
        --warning:   #F59E0B;

        --bg:        #F8F8F8;
        --bg-card:   #FFFFFF;
        --bg-input:  #F4F4F5;
        --txt:       #18181B;
        --txt-sub:   #71717A;
        --bdr:       #E4E4E7;
    }
    .dark .au {
        --bg:        #0A0A0A;
        --bg-card:   #111111;
        --bg-input:  #1C1C1C;
        --txt:       #FAFAFA;
        --txt-sub:   #71717A;
        --bdr:       rgba(255,255,255,0.08);
    }

    .au { background: var(--bg); min-height: 100%; }

    /* header */
    .au-header {
        display: flex; align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        gap: 16px; flex-wrap: wrap;
    }
    .au-header h1 { font-size: 20px; font-weight: 700; color: var(--txt); margin: 0 0 2px; }
    .au-header p  { font-size: 13px; color: var(--txt-sub); margin: 0; }

    .au-btn-primary {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--accent); color: #fff;
        font-size: 13px; font-weight: 600;
        padding: 8px 16px; border-radius: 7px; border: none;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(37,99,235,.3);
        transition: background .15s, transform .1s;
    }
    .au-btn-primary:hover { background: var(--accent2); transform: translateY(-1px); }

    /* table card */
    .au-table-card {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 10px;
        overflow: hidden;
    }
    .au-table { width: 100%; border-collapse: collapse; }
    .au-table thead tr {
        border-bottom: 1px solid var(--bdr);
    }
    .au-table th {
        padding: 10px 14px;
        font-size: 10px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .7px;
        color: var(--txt-sub);
        text-align: left;
    }
    .au-table th:last-child { text-align: right; }
    .au-table td {
        padding: 12px 14px;
        font-size: 13px; color: var(--txt);
        vertical-align: middle;
    }
    .au-table tbody tr {
        border-bottom: 1px solid var(--bdr);
        transition: background .12s;
    }
    .au-table tbody tr:last-child { border-bottom: none; }
    .au-table tbody tr:hover { background: var(--bg-input); }
    .au-table tbody tr.suspended { opacity: .5; }

    /* avatar */
    .au-avatar {
        width: 32px; height: 32px; border-radius: 7px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
    }

    /* user cell */
    .au-user-cell { display: flex; align-items: center; gap: 10px; }
    .au-user-name { font-size: 13px; font-weight: 600; color: var(--txt); margin: 0; }
    .au-user-meta { font-size: 11px; color: var(--txt-sub); margin: 0; }

    /* role badge */
    .au-role {
        display: inline-block;
        font-size: 10px; font-weight: 700;
        letter-spacing: .5px; text-transform: uppercase;
        padding: 3px 9px; border-radius: 5px;
    }
    .au-role-admin      { background: rgba(139,92,246,.12); color: #7C3AED; }
    .au-role-cajero     { background: rgba(22,163,74,.1);   color: #15803D; }
    .au-role-cocinero   { background: rgba(249,115,22,.1);  color: #C2410C; }
    .au-role-limpieza   { background: rgba(20,184,166,.1);  color: #0F766E; }
    .au-role-empleado   { background: rgba(113,113,122,.12);color: #cf7910; }
    .dark .au-role-admin      { color: #A78BFA; }
    .dark .au-role-cajero     { color: #4ADE80; }
    .dark .au-role-cocinero   { color: #FB923C; }
    .dark .au-role-limpieza   { color: #2DD4BF; }
    .dark .au-role-empleado   { color: #eda144; }

    /* status */
    .au-status {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 600;
    }
    .au-status-dot {
        width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
    }
    .au-status.active   .au-status-dot { background: #22C55E; }
    .au-status.suspended .au-status-dot { background: var(--warning); }
    .au-status.active   { color: #16A34A; }
    .au-status.suspended { color: var(--warning); }
    .dark .au-status.active { color: #4ADE80; }

    /* owner chip */
    .au-owner-chip {
        display: inline-block;
        font-size: 10px; font-weight: 700;
        letter-spacing: .8px; text-transform: uppercase;
        color: var(--accent); background: rgba(37,99,235,.08);
        border: 1px solid rgba(37,99,235,.2);
        padding: 3px 9px; border-radius: 5px;
    }

    /* action buttons */
    .au-actions { display: flex; align-items: center; justify-content: flex-end; gap: 4px; }
    .au-act-btn {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 5px 10px; border-radius: 6px;
        font-size: 11px; font-weight: 600;
        cursor: pointer; border: 1px solid var(--bdr);
        background: transparent;
        transition: background .12s, border-color .12s;
        white-space: nowrap;
    }
    .au-act-btn svg { width: 12px; height: 12px; }
    .au-act-btn.key     { color: var(--accent); }
    .au-act-btn.key:hover     { background: rgba(37,99,235,.08); border-color: rgba(37,99,235,.3); }
    .au-act-btn.pause   { color: var(--warning); }
    .au-act-btn.pause:hover   { background: rgba(245,158,11,.08); border-color: rgba(245,158,11,.3); }
    .au-act-btn.resume  { color: #16A34A; }
    .au-act-btn.resume:hover  { background: rgba(22,163,74,.08); border-color: rgba(22,163,74,.3); }
    .au-act-btn.fire    { color: var(--danger); }
    .au-act-btn.fire:hover    { background: rgba(239,68,68,.07); border-color: rgba(239,68,68,.25); }

    /* ── MODALS ── */
    .au-overlay {
        position: fixed; inset: 0; z-index: 50;
        background: rgba(0,0,0,.5); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; padding: 16px;
    }
    .au-modal {
        background: var(--bg-card);
        border: 1px solid var(--bdr);
        border-radius: 12px;
        width: 100%; max-width: 480px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
    }
    .au-modal-sm { max-width: 360px; }
    .au-modal-head {
        display: flex; align-items: center;
        justify-content: space-between;
        padding: 16px 18px; border-bottom: 1px solid var(--bdr);
        position: sticky; top: 0; background: var(--bg-card); z-index: 1;
    }
    .au-modal-head h3 { font-size: 15px; font-weight: 700; color: var(--txt); margin: 0; }
    .au-modal-head p  { font-size: 12px; color: var(--txt-sub); margin: 4px 0 0; }
    .au-modal-close {
        width: 26px; height: 26px; border-radius: 6px;
        background: var(--bg-input); border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: var(--txt-sub); transition: background .15s; flex-shrink: 0;
    }
    .au-modal-close:hover { background: var(--bdr); }
    .au-modal-body { padding: 18px; display: flex; flex-direction: column; gap: 14px; }
    .au-modal-foot {
        display: flex; gap: 8px;
        padding: 14px 18px; border-top: 1px solid var(--bdr);
    }

    /* form */
    .au-label {
        display: block; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .5px;
        color: var(--txt-sub); margin-bottom: 4px;
    }
    .au-input {
        width: 100%; padding: 8px 10px;
        background: var(--bg-input); border: 1px solid var(--bdr);
        border-radius: 7px; font-size: 13px; color: var(--txt);
        outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .au-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .au-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    /* password row */
    .au-pass-wrap { position: relative; }
    .au-pass-wrap .au-input { padding-right: 36px; font-family: monospace; }
    .au-dice-btn {
        position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        width: 24px; height: 24px; border-radius: 5px;
        background: var(--bdr); border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; transition: background .15s;
    }
    .au-dice-btn:hover { background: rgba(37,99,235,.15); }

    /* modal buttons */
    .au-btn-cancel {
        flex: 1; padding: 9px; border-radius: 7px;
        background: var(--bg-input); border: 1px solid var(--bdr);
        font-size: 13px; font-weight: 600; color: var(--txt-sub);
        cursor: pointer; transition: background .15s;
    }
    .au-btn-cancel:hover { background: var(--bdr); }
    .au-btn-save {
        flex: 2; padding: 9px; border-radius: 7px;
        background: var(--accent); color: #fff;
        border: none; font-size: 13px; font-weight: 600;
        cursor: pointer; transition: background .15s;
        box-shadow: 0 1px 3px rgba(37,99,235,.3);
    }
    .au-btn-save:hover { background: var(--accent2); }

    @media (max-width: 640px) {
        .au-row-2 { grid-template-columns: 1fr; }
        .au-table td, .au-table th { padding: 10px 10px; }
    }
</style>

<div class="au"
     x-data="{
        showModal: false,
        showPassModal: false,
        passUserId: '',
        passUserName: '',
        password: '',
        newPassword: '',

        generatePassword(target) {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%*';
            let pass = '';
            for (let i = 0; i < 10; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
            if (target === 'new')  this.password = pass;
            if (target === 'edit') this.newPassword = pass;
        },

        openPasswordModal(id, name) {
            this.passUserId = id;
            this.passUserName = name;
            this.newPassword = '';
            this.showPassModal = true;
        }
     }">

    {{-- HEADER --}}
    <div class="au-header">
        <div>
            <h1>Personal y Permisos</h1>
            <p>Gestiona los accesos de tu equipo de trabajo.</p>
        </div>
        <button @click="showModal = true" class="au-btn-primary">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Empleado
        </button>
    </div>

    {{-- TABLE --}}
    <div class="au-table-card">
        <div class="overflow-x-auto">
            <table class="au-table">
                <thead>
                    <tr>
                        <th scope="col">Empleado</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users->where('role', '!=', 'cliente') as $user)
                    @php
                        $initials = strtoupper(substr($user->name, 0, 1)) . (str_contains($user->name, ' ') ? strtoupper(substr(explode(' ', $user->name)[1], 0, 1)) : '');
                        $avatarColors = [
                            'admin'      => '#7C3AED',
                            'cajero'     => '#16A34A',
                            'cocinero'   => '#EA580C',
                            'limpieza'   => '#0D9488',
                            'mesero'   => '#cf7910',
                        ];
                        $avatarColor = $avatarColors[$user->role] ?? '#cf7910';
                    @endphp
                    <tr class="{{ $user->is_active ? '' : 'suspended' }}">

                        {{-- Empleado --}}
                        <td>
                            <div class="au-user-cell">
                                <div class="au-avatar" style="background: {{ $avatarColor }}" aria-hidden="true">{{ $initials }}</div>
                                <div>
                                    <p class="au-user-name">{{ $user->name }}</p>
                                    <p class="au-user-meta">{{ $user->telefono ?? '—' }} · {{ $user->email }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Rol --}}
                        <td>
                            <span class="au-role au-role-{{ $user->role }}">{{ $user->role === 'empleado' ? 'mesero' : $user->role }}</span>
                        </td>

                        {{-- Estado --}}
                        <td>
                            <span class="au-status {{ $user->is_active ? 'active' : 'suspended' }}">
                                <span class="au-status-dot" aria-hidden="true"></span>
                                {{ $user->is_active ? 'Activo' : 'Suspendido' }}
                            </span>
                        </td>

                        {{-- Acciones --}}
                        <td>
                            @if($user->id !== 2)
                            <div class="au-actions">
                                {{-- Cambiar clave --}}
                                <button type="button"
                                        @click="openPasswordModal({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                        class="au-act-btn key">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    Clave
                                </button>

                                {{-- Pausar / Reactivar --}}
                                <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST">
                                    @csrf @method('PUT')
                                    <button type="submit" class="au-act-btn {{ $user->is_active ? 'pause' : 'resume' }}">
                                        @if($user->is_active)
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Pausar
                                        @else
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Reactivar
                                        @endif
                                    </button>
                                </form>

                                {{-- Eliminar --}}
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                      onsubmit="return confirm('¿Eliminar a {{ addslashes($user->name) }} permanentemente?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="au-act-btn fire">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                            @else
                                <div style="text-align:right;">
                                    <span class="au-owner-chip">Dueño del Sistema</span>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL 1: Nuevo Empleado --}}
    <div x-show="showModal" x-cloak class="au-overlay" @click.self="showModal = false">
        <div class="au-modal" @click.stop>
            <div class="au-modal-head">
                <div>
                    <h3>Agregar Empleado</h3>
                </div>
                <button @click="showModal = false" class="au-modal-close" aria-label="Cerrar modal">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="au-modal-body">

                    <div>
                        {{-- FIX Web:S6853 L414: label for="emp-name" --}}
                        <label class="au-label" for="emp-name">Nombre completo *</label>
                        <input type="text" id="emp-name" name="name" required
                               placeholder="Ej: Juan Pérez" class="au-input"
                               autocomplete="name">
                    </div>

                    <div class="au-row-2">
                        <div>
                            {{-- FIX Web:S6853 L421: label for="emp-email" --}}
                            <label class="au-label" for="emp-email">Correo *</label>
                            <input type="email" id="emp-email" name="email" required
                                   placeholder="correo@ejemplo.com" class="au-input"
                                   autocomplete="email">
                        </div>
                        <div>
                            {{-- FIX Web:S6853 L426: label for="emp-telefono" --}}
                            <label class="au-label" for="emp-telefono">Teléfono</label>
                            <input type="text" id="emp-telefono" name="telefono"
                                   placeholder="Opcional" class="au-input"
                                   autocomplete="tel">
                        </div>
                    </div>

                    <div class="au-row-2">
                        <div>
                            {{-- FIX Web:S6853 L434: label for="emp-password" --}}
                            <label class="au-label" for="emp-password">Contraseña *</label>
                            <div class="au-pass-wrap">
                                <input type="text" id="emp-password" name="password" x-model="password"
                                       required placeholder="••••••••" class="au-input"
                                       autocomplete="new-password">
                                <button type="button" @click="generatePassword('new')"
                                        class="au-dice-btn" aria-label="Generar contraseña aleatoria">🎲</button>
                            </div>
                        </div>
                        <div>
                            {{-- FIX Web:S6853 L443: label for="emp-role" --}}
                            <label class="au-label" for="emp-role">Rol / Puesto *</label>
                           <select id="emp-role" name="role" required class="au-input">
                                <option value="admin">Administrador</option>
                                {{--<option value="cajero">Cajero</option>--}}
                                <option value="cocinero">Cocinero</option>
                                <option value="empleado">Mesero</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="au-modal-foot">
                    <button type="button" @click="showModal = false" class="au-btn-cancel">Cancelar</button>
                    <button type="submit" class="au-btn-save">Guardar Empleado</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: Cambiar Contraseña --}}
    <div x-show="showPassModal" x-cloak class="au-overlay" @click.self="showPassModal = false">
        <div class="au-modal au-modal-sm" @click.stop>
            <div class="au-modal-head">
                <div>
                    <h3>Actualizar Contraseña</h3>
                    <p>Para: <strong x-text="passUserName"></strong></p>
                </div>
                <button @click="showPassModal = false" class="au-modal-close" aria-label="Cerrar modal">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form x-bind:action="'/usuarios/' + passUserId" method="POST">
                @csrf @method('PUT')
                <div class="au-modal-body">
                    <div>
                        {{-- FIX Web:S6853 L482: label for="pass-new" --}}
                        <label class="au-label" for="pass-new">Nueva Contraseña *</label>
                        <div class="au-pass-wrap">
                            <input type="text" id="pass-new" name="password" x-model="newPassword"
                                   required placeholder="••••••••" class="au-input"
                                   autocomplete="new-password">
                            <button type="button" @click="generatePassword('edit')"
                                    class="au-dice-btn" aria-label="Generar contraseña aleatoria">🎲</button>
                        </div>
                    </div>
                </div>
                <div class="au-modal-foot">
                    <button type="button" @click="showPassModal = false" class="au-btn-cancel">Cancelar</button>
                    <button type="submit" class="au-btn-save">Actualizar</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
