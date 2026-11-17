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
| Suscriptores de promociones | `promo_subscribers` | Administrador | Hasta que se den de baja |
| Personal (nombre, correo, rol, PIN, actividad) | `users`, `orders`, bitácoras | Administrador | Ver el Aviso para Personal |
| Códigos de verificación | `password_reset_tokens`; los de SMS los guarda Twilio | Nadie | 1 hora |
| Registros del servidor | `storage/logs` | Desarrolladores | 14 días |
| Respaldos de la base | `storage/app/private/backups` | Solo el superadministrador | 30 días |

Los plazos salen de `config/privacy.php` y coinciden con la sección 12 del Aviso de Privacidad. `php artisan privacy:purge` los aplica todos los días a las 3:30 a. m.

## 2. Medidas aplicadas

- **Transmisión**: HTTPS en todo el sitio y la API; cabeceras de seguridad (`SecurityHeaders`: CSP, HSTS, X-Frame-Options).
- **Acceso**: roles (cliente, mesero, cocina, administrador); respaldos y restauración solo para el superadministrador; el personal acepta su propio aviso antes de usar el panel.
- **Contraseñas**: cifradas con bcrypt; recuperación con código de 8 dígitos que vence en 5 minutos.
- **Abuso**: límites de intentos en inicio de sesión, códigos SMS y API pública; filtro RASP contra inyección.
- **Pagos**: la tarjeta nunca toca nuestro servidor (Mercado Pago); el pago se verifica contra Mercado Pago y el webhook valida su firma.
- **Respaldos**: fuera de `public/`; la descarga solo acepta archivos `.sql` de `backups/` y queda registrada (usuario, archivo, IP).
- **Minimización**: borrado automático por plazos; al eliminar una cuenta se anonimizan pedidos, reservaciones y mensajes.
- **Dependencias**: `composer audit` antes de cada despliegue.

## 3. Revisión antes de cada despliegue

- [ ] `APP_ENV=production` y `APP_DEBUG=false` en el `.env` de producción.
- [ ] `https://<dominio>/.env`, `/storage/logs/laravel.log` y `/storage/app/private/backups/` responden 403 o 404.
- [ ] `composer audit` sin vulnerabilidades altas.
- [ ] El cron de Hostinger corre `php artisan schedule:run` cada minuto (sin él no corre `privacy:purge`).
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
