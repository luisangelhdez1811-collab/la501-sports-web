<?php

use Illuminate\Support\Facades\Route;

// 1. Públicos y Autenticación
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ReservationController;

// 2. Empleados
use App\Http\Controllers\WaiterController;
use App\Http\Controllers\Admin\DashboardExportController;

// 3. Administrador
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReservationAdminController;
use App\Http\Controllers\Admin\DatabaseController;

$promoPrefix = 'promociones';

/*
|--------------------
| 1. RUTAS PÚBLICAS
|--------------------
*/
Route::get('/', function () {
    return response()
        ->view('quienes-somos')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0')
        ->header('X-LiteSpeed-Cache-Control', 'no-cache')
        ->header('X-LiteSpeed-Purge', '*');
})->name('nosotros')->middleware('non-client');
Route::get('/contacto', function () { return view('contacto'); })->name('contacto')->middleware('non-client');
Route::get('/reservaciones', function () { return view('reservaciones'); })->name('reservaciones')->middleware('non-client');
Route::post('/reservaciones/store', [ReservationController::class, 'store'])->name('reservations.store');
Route::get('/ubicacion', function () { return view('ubicacion'); })->name('ubicacion')->middleware('non-client');

Route::get('/a-domicilio', [PageController::class, 'index'])->name('pedido')->middleware('non-client');
Route::get('/novedades', [PageController::class, 'novedades'])->name('novedades')->middleware('non-client');
Route::get("/{$promoPrefix}", [PageController::class, 'promociones'])->name('promociones')->middleware('non-client');
Route::post('/contacto/enviar', [PageController::class, 'enviarContacto'])->name('contacto.enviar');

Route::post('/newsletter/suscribirse', [App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/baja/{token}', [App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/menu', [MenuController::class, 'index'])->name('menu')->middleware('non-client');
Route::get('/api/menu/products', function() {
    return response()->json(\App\Models\Product::where('available', 1)->get());
})->name('api.menu.products');

// Carrito de Compras
Route::controller(CartController::class)->group(function () {
    Route::get('/carrito', 'index')->name('cart.index')->middleware('non-client');
    Route::post('/carrito/agregar', 'add')->name('cart.add');
    Route::post('/carrito/agregar-promocion', 'addPromotion')->name('cart.addPromotion');
    Route::post('/carrito/actualizar', 'update')->name('cart.update');
    Route::delete('/carrito/eliminar', 'remove')->name('cart.remove');
    Route::post('/carrito/vaciar', 'clear')->name('cart.clear');
});

// Pagos y Checkout
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index')->middleware('non-client');
Route::post('/checkout/procesar', [CheckoutController::class, 'process'])->name('checkout.process')->middleware('non-client');
Route::get('/pago/exitoso', [CheckoutController::class, 'success'])->name('payment.success');
Route::get('/pago/fallido', [CheckoutController::class, 'failure'])->name('payment.failure');
Route::get('/pedido/confirmacion/{id}', [CheckoutController::class, 'confirmation'])->name('payment.confirmation');
Route::get('/api/pedido/{id}/status', [CheckoutController::class, 'apiStatus'])->name('api.order.status');

Route::get('/pedido/seguir/{token}', [App\Http\Controllers\OrderTrackingController::class, 'show'])->name('order.track');
Route::get('/api/pedido/seguir/{token}/status', [App\Http\Controllers\OrderTrackingController::class, 'apiStatus'])->name('order.track.status');
Route::post('/pedido/seguir/{token}/recibido', [App\Http\Controllers\OrderTrackingController::class, 'confirmarRecibido'])->name('order.track.receive');
Route::get('/pedido/mis-pedidos', [App\Http\Controllers\OrderTrackingController::class, 'lookup'])->name('order.lookup');
Route::post('/pedido/mis-pedidos/enviar-pin', [App\Http\Controllers\OrderTrackingController::class, 'requestOtp'])->middleware('throttle:5,10')->name('order.lookup.request-otp');
Route::get('/pedido/mis-pedidos/verificar', [App\Http\Controllers\OrderTrackingController::class, 'showVerify'])->name('order.lookup.verify');
Route::post('/pedido/mis-pedidos/verificar', [App\Http\Controllers\OrderTrackingController::class, 'verifyOtp'])->middleware('throttle:10,10')->name('order.lookup.verify.post');
Route::get('/pedido/mis-pedidos/resultados', [App\Http\Controllers\OrderTrackingController::class, 'results'])->name('order.lookup.results');

// Final de rutas públicas
Route::get('/clear-laravel-cache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        return 'Caché de Laravel limpia con éxito: ' . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        return 'Error al limpiar: ' . $e->getMessage();
    }
});



/*
|-----------------------------
| 2. RUTAS DE AUTENTICACIÓN
|-----------------------------
*/
Route::controller(AuthController::class)->group(function () {
    // Ingreso y Registro
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login');
    Route::get('/registro', 'showRegistro')->name('register');
    Route::post('/registro', 'registrar');
    Route::post('/logout', 'logout')->name('logout');

    // Recuperación
    Route::get('/recuperar', 'showRecuperar')->name('password.request');
    Route::post('/recuperar', 'enviarCodigo')->name('password.email');
    Route::get('/recuperar/verificar-codigo', 'showVerifyCode')->name('password.verify.code');
    Route::post('/recuperar/verificar-codigo', 'verifyCode')->name('password.verify.code.post');
    Route::get('/recuperar/nueva-password', 'showCustomResetForm')->name('password.reset.form');
    Route::post('/recuperar/nueva-password', 'updateCustomPassword')->name('password.update');
});

/*
|------------------------------------------------
| 3. RUTAS PRIVADAS (Solo usuarios logueados)
|------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Clientes
    Route::get('/perfil', [AuthController::class, 'showPerfil'])->name('perfil');
    Route::post('/perfil', [AuthController::class, 'updatePerfil'])->name('perfil.update');

    // Configuración de cuenta (todos los roles)
    Route::get('/configuracion', [App\Http\Controllers\AccountSettingsController::class, 'index'])->name('configuracion');
    Route::post('/configuracion/password', [App\Http\Controllers\AccountSettingsController::class, 'updatePassword'])->name('configuracion.password');
    Route::post('/configuracion/promociones', [App\Http\Controllers\AccountSettingsController::class, 'togglePromoEmail'])->name('configuracion.promo');
    Route::put('/configuracion/datos', [App\Http\Controllers\AccountSettingsController::class, 'updateProfile'])->name('configuracion.datos'); // Rectificación (ARCO)
    Route::post('/configuracion/telefono/solicitar-codigo', [App\Http\Controllers\AccountSettingsController::class, 'requestPhoneOtp'])->name('configuracion.telefono.solicitar');
    Route::post('/configuracion/telefono/confirmar', [App\Http\Controllers\AccountSettingsController::class, 'confirmPhone'])->name('configuracion.telefono.confirmar');
    Route::delete('/configuracion', [App\Http\Controllers\AccountSettingsController::class, 'destroy'])->name('configuracion.eliminar'); // Cancelación (ARCO)

    // Meseros
    Route::prefix('mesero')->group(function () {
        Route::get('/mesas', [WaiterController::class, 'index'])->name('mesero.mesas');
        Route::get('/mesas/{id}/pedido', [WaiterController::class, 'tomarPedido'])->name('mesero.pedido');
        Route::put('/mesas/{id}/cobrar', [WaiterController::class, 'cobrar'])->name('mesero.cobrar');
        Route::post('/mesas/{id}/pedido', [WaiterController::class, 'guardarPedido'])->name('mesero.guardar_pedido');
        Route::get('/api/mesas', [WaiterController::class, 'apiGetMesas'])->name('mesero.api_mesas');
        Route::post('/mesas/agregar', [WaiterController::class, 'addTable'])->name('mesero.mesas.agregar');
        Route::delete('/mesas/{id}', [WaiterController::class, 'removeTable'])->name('mesero.mesas.quitar');
        Route::post('/items/{id}/entregado', [WaiterController::class, 'servirItem'])->name('mesero.items.entregado');
        Route::post('/mesas/{id}/entregar-todo', [WaiterController::class, 'servirMesa'])->name('mesero.mesas.entregar_todo');

        // Domicilios: entrega al mandadito, ticket e historial en camino
        Route::get('/domicilios', [WaiterController::class, 'domicilios'])->name('mesero.domicilios');
        Route::get('/api/domicilios', [WaiterController::class, 'apiDomicilios'])->name('mesero.api_domicilios');
        Route::post('/domicilios/{id}/mandadito', [WaiterController::class, 'entregarAlMandadito'])->name('mesero.domicilios.mandadito');
        Route::post('/domicilios/{id}/entregado', [WaiterController::class, 'marcarEntregado'])->name('mesero.domicilios.entregado');
        Route::get('/domicilios/{id}/ticket', [WaiterController::class, 'ticketMandadito'])->name('mesero.domicilios.ticket');
    });

    // Cocina
    Route::get('/cocina', [\App\Http\Controllers\KitchenController::class, 'index'])->name('kitchen.index');
    Route::post('/cocina/orden/{id}/ready', [\App\Http\Controllers\KitchenController::class, 'markAsReady'])->name('kitchen.ready');
    Route::post('/cocina/item/{id}/listo', [\App\Http\Controllers\KitchenController::class, 'markItemReady'])->name('kitchen.item.ready');
    Route::get('/api/cocina/ordenes', [\App\Http\Controllers\KitchenController::class, 'apiActiveOrders'])->name('kitchen.api_orders');

    // Solo para activar el enlace en el servidor
    Route::get('/link-storage', function () {
        Artisan::call('storage:link');
        return "Enlace creado con éxito";
    });
});

/*
|------------------------------------------------------
| 4. RUTAS DEL ADMINISTRADOR (Protegidas y Seguras)
|------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () use ($promoPrefix) {

    Route::get('/reservaciones', [ReservationAdminController::class, 'index'])->name('admin.reservations.index');
    
    // Gestión del Carrusel
    Route::prefix('carrusel')->group(function () {
        Route::post('/store', [App\Http\Controllers\Admin\CarouselController::class, 'store'])->name('admin.carousel.store');
        Route::put('/{id}', [App\Http\Controllers\Admin\CarouselController::class, 'update'])->name('admin.carousel.update');
        Route::delete('/{id}', [App\Http\Controllers\Admin\CarouselController::class, 'destroy'])->name('admin.carousel.destroy');
    });
    
    Route::patch('/reservations/{reservation}/status/{status}', [ReservationAdminController::class, 'updateStatus'])
        ->name('admin.reservations.update-status');

    // Dashboard y API de Gráficas/Estadísticas
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/exportar-ventas', [DashboardController::class, 'exportSalesCSV'])->name('admin.sales.export.excel');
    Route::get('/api/stats', [DashboardController::class, 'apiStats'])->name('admin.api.stats');
    Route::get('/api/sales', [DashboardController::class, 'apiSales'])->name('admin.api.sales');
    
    // Rutas de AdminController
    Route::get('/ventas', [AdminController::class, 'ventas'])->name('admin.ventas');

    // Gestión de Mensajes (CRM)
    Route::get('/mensajes', [AdminController::class, 'mensajes'])->name('admin.mensajes');
    Route::put('/mensajes/{id}/marcar-leido', [AdminController::class, 'marcarRespondido'])->name('admin.mensajes.leido');

    // Gestión del Menú
    Route::get('/menu', [AdminMenuController::class, 'index'])->name('admin.menu');
    Route::get('/menu/exportar', [AdminMenuController::class, 'exportCSV'])->name('admin.menu.export');
    Route::get('/menu/plantilla', [AdminMenuController::class, 'downloadTemplate'])->name('admin.menu.template');
    Route::post('/menu/importar', [AdminMenuController::class, 'importCSV'])->name('admin.menu.import');
    Route::post('/menu/store', [AdminMenuController::class, 'store'])->name('admin.menu.store');
    Route::put('/menu/{id}', [AdminMenuController::class, 'update'])->name('admin.menu.update');
    Route::delete('/menu/{id}', [AdminMenuController::class, 'destroy'])->name('admin.menu.destroy');
    
    // Gestión de Usuarios / Empleados
    Route::get('/usuarios', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/usuarios/store', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/usuarios/{id}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/usuarios/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::put('/usuarios/{id}/toggle', [UserController::class, 'toggleStatus'])->name('admin.users.toggle');

    // Desempeño de Meseros / Empleados (ventas por mesero + auditoría de cobros forzados)
    Route::get('/meseros/desempeno', [App\Http\Controllers\Admin\WaiterPerformanceController::class, 'index'])->name('admin.waiters.performance');

    // Gestión de Novedades
    Route::get('/novedades', [NewsController::class, 'index'])->name('admin.news');
    Route::post('/novedades/store', [NewsController::class, 'store'])->name('admin.news.store');
    Route::put('/novedades/{id}', [NewsController::class, 'update'])->name('admin.news.update');
    Route::delete('/novedades/{id}', [NewsController::class, 'destroy'])->name('admin.news.destroy');

    // Gestión de Promociones
    Route::prefix($promoPrefix)->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('admin.promotions.index');
        Route::post('/', [PromotionController::class, 'store'])->name('admin.promotions.store');
        Route::put('/{id}', [PromotionController::class, 'update'])->name('admin.promotions.update');
        Route::delete('/{id}', [PromotionController::class, 'destroy'])->name('admin.promotions.destroy');
    });

    // Configuración General
    Route::controller(SettingController::class)->prefix('configuracion')->group(function () {
        Route::get('/', 'index')->name('admin.settings.index');
        Route::post('/logo', 'updateLogo')->name('admin.settings.logo');
        Route::post('/mapa', 'updateMap')->name('admin.settings.map');
        Route::post('/direccion', 'updateAddress')->name('admin.settings.address');
        Route::post('/horarios', 'updateSchedule')->name('admin.settings.schedule');
    });

    // Gestión de Finanzas
    Route::resource('finanzas', FinanceController::class)->only(['index', 'store', 'destroy'])->names('admin.finances');

    // Centro de Inteligencia Artificial y Minería de Datos (ML)
    Route::get('/ia', [\App\Http\Controllers\Admin\MlController::class, 'index'])->name('admin.ml.index');
    Route::post('/ia/cupon', [\App\Http\Controllers\Admin\MlController::class, 'sendPromotionCoupon'])->name('admin.ml.coupon');

    // Gestión de Inventario
    Route::get('/inventario/exportar', [InventoryController::class, 'exportCSV'])->name('admin.inventory.export');
    Route::get('/inventario/plantilla', [InventoryController::class, 'downloadTemplate'])->name('admin.inventory.template');
    Route::post('/inventario/importar', [InventoryController::class, 'importCSV'])->name('admin.inventory.import');
    Route::resource('inventario', InventoryController::class)->except(['create', 'show', 'edit'])->names('admin.inventory');
    Route::put('/inventario/{id}/ajustar', [InventoryController::class, 'adjust'])->name('admin.inventory.adjust');

    // ==========================================================
    // SISTEMA Y BASE DE DATOS (Módulo Avanzado)
    // ==========================================================
    Route::prefix('sistema/base-de-datos')->group(function () {
        // Vistas principales
        Route::get('/', [AdminController::class, 'database'])->name('admin.database');
        Route::get('/historial', [BackupController::class, 'databaseHistory'])->name('admin.database.history');
        
        Route::get('/monitoreo', [AdminController::class, 'monitor'])->name('admin.database.monitor');

        // Acciones de Respaldo
        Route::post('/backup', [AdminController::class, 'createBackup'])->name('admin.database.backup');
        Route::post('/descargar', [AdminController::class, 'downloadBackup'])->name('admin.database.download');

        Route::post('/auto', [AdminController::class, 'saveAuto'])->name('admin.database.saveAuto');

        // Acciones de Restauración
        Route::post('/restaurar', [AdminController::class, 'restore'])->name('admin.database.restore');
        Route::post('admin/sistema/base-de-datos/restaurar-subida', [App\Http\Controllers\BackupController::class, 'restoreUpload'])->name('admin.database.restore.upload');
    });
    
});

/*
|------------------------------------------------------
| 5. RUTAS DE MANTENIMIENTO Y TAREAS PROGRAMADAS
|------------------------------------------------------
*/


Route::get('/run-auto-backup', [BackupController::class, 'runAutoBackup']);

Route::get('/run-delivery-reminders', function () {
    \Illuminate\Support\Facades\Artisan::call('orders:send-delivery-reminders');
    return \Illuminate\Support\Facades\Artisan::output();
});

/*
|------------------------------------------------------
| RUTAS TEMPORALES PARA PROBAR VISTAS DE ERROR
|------------------------------------------------------
*/
Route::prefix('test-errors')->group(function () {
    Route::get('/403', function () { abort(403); }); // RASP / Prohibido
    Route::get('/404', function () { abort(404); }); // No encontrado
    Route::get('/500', function () { abort(500); }); // Error de servidor
    Route::get('/419', function () { abort(419); }); // Sesión expirada
    Route::get('/401', function () { abort(401); }); // No autorizado
});

// routes/web.php

// Rutas de administración que estaban fuera del grupo protegido: solo administradores.
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/database/api/metrics', [AdminController::class, 'getMetricsApi'])->name('admin.database.api.metrics');
    Route::post('admin/sistema/base-de-datos/optimizar', [DatabaseController::class, 'optimize'])->name('admin.database.optimize');
    Route::post('admin/sistema/base-de-datos/reindexar', [DatabaseController::class, 'reindex'])->name('admin.database.reindex');
    Route::post('admin/sistema/base-de-datos/no9', [DatabaseController::class, 'cleanup'])->name('admin.database.cleanup');
    Route::get('admin/sistema/base-de-datos/reportes', [DatabaseController::class, 'reports'])->name('admin.database.reports');
    Route::post('admin/sistema/base-de-datos/descargar-reporte', [DatabaseController::class, 'downloadReport'])->name('admin.database.report.download');
    Route::get('/admin/api/category-sales', [App\Http\Controllers\Admin\DashboardController::class, 'apiCategoryRotation'])->name('admin.api.category_sales');
    Route::get('/admin/export/ganancias', [DashboardExportController::class, 'exportGanancias'])->name('admin.export.ganancias');
    Route::get('/admin/export/rotacion', [DashboardExportController::class, 'exportRotacion'])->name('admin.export.rotacion');
    Route::get('/admin/export/cortediario', [DashboardExportController::class, 'exportCorteDiario'])->name('admin.export.cortediario');
});


/*
|------------------------------------------------------
| 6. RUTAS DE POLÍTICAS Y TÉRMINOS
|------------------------------------------------------
*/
Route::get('/aviso-de-privacidad', function () {
    return view('aviso-privacidad');
})->name('aviso-privacidad');

Route::get('/terminos-y-condiciones', function () {
    return view('terminos-condiciones');
})->name('terminos-condiciones');