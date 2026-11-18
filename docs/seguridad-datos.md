# Seguridad de los datos personales

Documento interno de La 501 Sports (B-38). Lo pide la Ley Federal de Protección de Datos Personales en Posesión de los Particulares: el responsable debe tener medidas de seguridad documentadas y saber qué hacer si hay una vulneración. Revisarlo cada vez que se agregue un dato, un proveedor o una pantalla nueva.

Última revisión: 17 de noviembre de 2026.

## 1. Inventario de datos personales

| Dato | Dónde se guarda | Quién lo ve | Plazo |
| --- | --- | --- | --- |
| Cuenta de cliente (nombre, correo, teléfono, contraseña cifrada, foto) | `users` (Hostinger); foto en Cloudinary | El cliente; el administrador | Mientras la cuenta esté activa |
| Pedido (nombre, teléfono, correo, dirección) | `orders` | Cocina, meseros y administrador | 10 años; al eliminar la cuenta se anonimiza |
| Ubicación GPS de la entrega | `orders.delivery_lat`, `delivery_lng` | Mesero que entrega y administrador | 30 días después de cerrado el pedido |
| Notas del pedido (pueden traer alergias) | `order_items.notes` | Cocina y meseros | 90 días |
| Pago con tarjeta | Solo el número de operación en `orders`; la tarjeta la tiene Mercado Pago | Administrador | 10 años |
| Reservaciones | `reservations` | Personal y administrador | 1 año desde la fecha reservada |
| Mensajes de contacto | `contact_messages` | Administrador | 1 año |
| Suscriptores de promociones | `promo_subscribers` (con fecha de confirmación por correo) | Administrador | Hasta que se den de baja |
| Personal (nombre, correo, rol, PIN, actividad) | `users`, `orders`, bitácoras | Administrador | Ver el Aviso para Personal |
| Códigos de verificación | `password_reset_tokens`; los de SMS los guarda Twilio | Nadie | 1 hora |
| Registros del servidor | `storage/logs` | Desarrolladores | 14 días |
| Respaldos de la base | `storage/app/private/backups` | Solo el dueño del sistema (`users.is_owner`) | 30 días |

Los plazos salen de `config/privacy.php` y coinciden con la sección 12 del Aviso de Privacidad. `php artisan privacy:purge` los aplica todos los días a las 3:30 a. m.

## 2. Medidas aplicadas

- **Transmisión**: HTTPS en todo el sitio y la API (en producción se fuerza y la cookie de sesión solo viaja por HTTPS). Cabeceras de seguridad (`SecurityHeaders`): CSP, HSTS, X-Frame-Options, COOP y Permissions-Policy.
- **Cachés**: las páginas con sesión iniciada, el panel, el seguimiento de pedidos y el pago se mandan con `Cache-Control: no-store`, también para LiteSpeed. Nadie ve una página con datos de otra persona.
- **Acceso por rol**: la cocina y el mesero revisan el rol en la ruta (`web.role`) y no solo dentro del controlador. Las cuentas suspendidas salen en su siguiente clic (`EnsureAccountIsActive`), también de la app (Sanctum y JWT).
- **Dueño del sistema**: personal, respaldos, restauración y mantenimiento de la base son solo para la cuenta con `users.is_owner`. Antes era «el usuario con id 2», escrito en 26 lugares del código. La marca no se puede poner desde ningún formulario. Además se pide otra vez la contraseña si no se confirmó en los últimos 15 minutos (`password.recent`). Siempre hay un solo dueño, y la propiedad se cambia de dos formas: en el panel, el dueño actual entra a Personal, pulsa «Hacer dueño» junto a otro administrador y escribe su contraseña; o en el servidor, con `php artisan owner:transfer correo@ejemplo.com`, por si nadie puede entrar como dueño. Cada cambio queda en la bitácora. En una base nueva (instalación o reinicio) no hay dueño ni administradores, y registrarse en la web siempre crea un cliente: nadie se queda con el sistema por registrarse primero. El dueño se crea en el servidor con `php artisan owner:create correo@ejemplo.com`.
- **Contraseñas**: cifradas con bcrypt. Una sola regla para clientes y personal (8 caracteres o más, con mayúscula, minúscula, número y símbolo; `PasswordPolicy`). Cambiarla o restablecerla cierra todas las demás sesiones: navegadores, app y app del personal (`AccountSessions`).
- **Recuperación**: código de 8 dígitos que vence en 10 minutos y aguanta 5 intentos; al quinto fallo se anula (`PasswordResetCodes`, igual para la web y la app).
- **Abuso**: inicio de sesión con límite por IP y por cuenta (5 fallos = 15 minutos). Límites en registro, contacto, reservaciones, boletín, carrito, pago, códigos SMS y API. Filtro RASP contra ataques obvios, que responde con un 403 normal.
- **Datos de entrada**: las reservaciones solo guardan campos validados (antes se podía mandar una reservación ya «confirmada»). Teléfonos de 10 dígitos y largos máximos en todos los formularios.
- **Archivos**: el logo no acepta SVG (puede llevar código). Las imágenes por URL solo por HTTPS. CSV de 5 MB máximo y SQL de 50 MB máximo, revisando su tipo real.
- **Pagos**: la tarjeta nunca toca nuestro servidor (Mercado Pago). El pago se verifica contra la API de Mercado Pago (monto, moneda y referencia) y el webhook valida su firma.
- **Respaldos**: fuera de `public/`. La descarga solo acepta archivos `.sql` de `backups/` y queda registrada (usuario, archivo, IP). El respaldo automático lo corre el programador de tareas; la URL `/run-auto-backup` solo existe con `CRON_TOKEN`.
- **Rutas quitadas**: `/link-storage` (cualquier usuario con sesión podía correr un comando del servidor), `/run-delivery-reminders` (cualquiera podía disparar correos) y `/test-errors/*`.
- **Bitácora**: inicios de sesión fallidos, confirmaciones de contraseña fallidas, cambios al personal, ataques bloqueados y restauraciones quedan en `storage/logs`, sin contraseñas.
- **Minimización**: borrado automático por plazos. Al eliminar una cuenta se anonimizan pedidos, reservaciones y mensajes. La carta pública solo expone los datos que ya se ven en el menú.
- **Tamaño de lo que llega** (`LimitInputSize`, antes de cualquier controlador): contraseñas hasta 128 caracteres (las nuevas, de 8 a 64), nombres 120, notas del pedido 150, dirección 300, mensaje de contacto 3000 y cualquier otro texto 5000. Listas de hasta 200 elementos y 6 niveles. Sin el carácter nulo. Cada formulario tiene además su propio límite, y los campos del navegador llevan `maxlength`.
- **Inyección**: todas las consultas usan parámetros (Eloquent o consultas preparadas), así que un texto nunca se ejecuta como SQL. Todo lo que se muestra pasa por el escapado de Blade. Los valores dentro de atributos de JavaScript usan `@js()`; ahí faltaba en el panel de IA, donde el nombre de un cliente podía ejecutar código en el navegador del administrador. El filtro RASP frena además etiquetas `<script>`, `<iframe>`, `<svg>`, manejadores `on...=`, `javascript:` e inyección SQL por tiempo.
- **Sensor de Acunetix quitado**: `public/acusensor.php` se cargaba en cada petición y su contraseña estaba en el repositorio público. El despliegue lo borra del servidor.
- **Pruebas de permisos** (`MatrizDePermisosTest`): recorren todas las rutas con cada rol (visitante, cliente, cocina, mesero, administrador y dueño), en la web y en la API. Fallan si un rol entra a lo que no le toca o si una página da error 500, también con rutas nuevas.
- **CORS restringido** (`config/cors.php`): la API solo acepta llamadas de navegador desde `APP_URL` y lo que se agregue en `CORS_ALLOWED_ORIGINS`. Antes aceptaba cualquier sitio. La app móvil no se ve afectada.
- **Modo debug**: en producción se apaga solo aunque el `.env` diga `APP_DEBUG=true`, y queda aviso en el log. Tampoco se envía la versión de PHP (`X-Powered-By`).
- **Sesión cifrada** en producción por defecto (`SESSION_ENCRYPT`), además de cookie `Secure`, `HttpOnly` y `SameSite=Lax`.
- **Google Analytics solo con permiso**: con `GOOGLE_ANALYTICS_ID` el aviso de cookies pide «Aceptar / Rechazar» y `gtag.js` no se descarga hasta aceptar. Nunca mide el panel, la cocina, el mesero, al personal ni páginas con datos de una persona, y no manda los parámetros de la URL. «Preferencias de cookies» en el pie permite retirar el permiso.
- **Archivos expuestos**: después de cada despliegue, el CI comprueba que `.env`, `.git`, `composer.json`, los logs, `vendor/` y el esquema SQL no se puedan descargar.
- **Paquetes sin uso quitados**: Vite, axios, laravel-vite-plugin y el código JS que nadie cargaba, además de Laravel Sail. `npm audit --omit=dev` y `composer audit`: 0 vulnerabilidades. Quedan avisos en `picomatch` dentro de Tailwind 3, una herramienta de compilación que no llega al servidor; se resuelven al migrar a Tailwind 4.
- **Dependencias**: `composer audit` antes de cada despliegue.

## 2.1 Consentimientos que pide la web

| Qué | Dónde | Cómo |
| --- | --- | --- |
| Aviso de Privacidad y Términos | Registro | Casilla obligatoria, sin premarcar; se guarda la versión |
| Mayoría de edad | Registro y pago con bebidas con alcohol | Casilla obligatoria |
| Términos de compra (precios, envío, cancelación, devoluciones) | Pago | Casilla obligatoria justo antes de pagar; se guarda la fecha (`orders.purchase_terms_accepted_at`) |
| Envío no incluido | Pago | Casilla obligatoria |
| Datos de salud en notas | Pago con notas | Casilla obligatoria |
| Uso de datos para responder o reservar | Contacto y reservaciones | Casilla obligatoria; se guarda la fecha y la versión |
| Promociones | Registro (opcional), Configuración y página de promociones | Casilla separada y opcional. Sin sesión, doble confirmación por correo |
| Versión nueva de los textos | Cualquier cuenta | Pantalla «Aceptar avisos» antes de seguir |
| Cookies | Todo el sitio | Aviso informativo: solo hay cookies técnicas, que no requieren permiso. El mapa de Google se carga solo si la persona lo pide |

No aplican, porque la web no los tiene: cesión de datos a terceros (no se transfieren), licencia de contenido del usuario (la foto de perfil solo la ve su dueño), suscripciones con cobro recurrente y productos digitales.

**Si un día se agrega analítica, píxeles o publicidad**, el aviso de cookies tiene que cambiar a «Aceptar / Rechazar», y esas herramientas no deben cargarse hasta que la persona acepte.

## 3. Revisión antes de cada despliegue

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` y `SESSION_ENCRYPT=true` en el `.env` de producción.
- [ ] `MERCADOPAGO_WEBHOOK_SECRET` lleno (sin él no se valida la firma de los avisos de pago).
- [ ] Si un cron externo llama `/run-auto-backup`, `CRON_TOKEN` con 64 caracteres al azar y la URL con `?token=`.
- [ ] `https://<dominio>/.env`, `/storage/logs/laravel.log` y `/storage/app/private/backups/` responden 403 o 404.
- [ ] `composer audit` sin vulnerabilidades altas.
- [ ] El cron de Hostinger corre `php artisan schedule:run` cada minuto (sin él no corren `privacy:purge` ni el respaldo automático).
- [ ] `LOG_STACK=daily` para que los registros se separen por día.

## 4. Pendientes que no resuelve el código

- **Segundo factor para administradores** (código por SMS o app de autenticación). Recomendado; no está implementado.
- **Cifrar los respaldos** con una clave en el `.env`. Hoy están protegidos por permisos y acceso, no cifrados.
- **Contratos con los encargados** (Hostinger, Mercado Pago, Twilio, Google, Pusher, Cloudinary, Amazon): aceptar y guardar sus acuerdos de tratamiento de datos.
- **Correo del sistema**: los correos salen de una cuenta institucional de estudiante. Deben salir de una cuenta del restaurante.

## 5. Qué hacer si hay una vulneración

Una vulneración es cualquier pérdida, robo, acceso o uso no autorizado de datos personales (por ejemplo, alguien entra al panel, se filtra un respaldo o un proveedor avisa de un incidente).

1. **Contener (mismo día)**: cambiar las contraseñas y claves afectadas (`APP_KEY`, tokens de Twilio, Mercado Pago, Cloudinary, base de datos), cerrar las sesiones (`php artisan session:flush` o borrar la tabla `sessions`) y quitar el acceso que se usó.
2. **Evaluar (en 72 horas)**: qué datos, de cuántas personas, desde cuándo y si hay datos sensibles (notas con alergias) o de pago. Decide el dueño (Andrés Rivera Jiménez) con quien administre el sistema.
3. **Avisar sin demora** a las personas afectadas si la vulneración afecta de forma significativa sus derechos, por correo y en la app. El aviso dice: qué pasó, qué datos se vieron afectados, qué hicimos y qué pueden hacer (por ejemplo, cambiar su contraseña).
4. **Registrar** en este documento (o en una bitácora aparte): fecha, qué pasó, a quién se avisó y qué se cambió para que no se repita.

Texto base para el aviso:

> Te escribimos porque el [fecha] detectamos [qué pasó]. Los datos afectados fueron [cuáles]. Ya [qué hicimos]. Te recomendamos [qué hacer]. Si tienes dudas, escríbenos a Equipolapapa501@gmail.com. — La 501 Sports Restaurant
