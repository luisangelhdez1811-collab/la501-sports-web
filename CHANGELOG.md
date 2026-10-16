# Changelog

Todos los cambios importantes de La 501 Sports Web se registran aquí.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y [Versionado Semántico](https://semver.org/lang/es/).

## [1.0.0] - 2026-10-15
### Añadido
- PWA: Service Worker con caché de páginas públicas y aviso "Regresar al inicio", manifest e íconos.
- Pedidos a domicilio: ubicación GPS opcional en el checkout y QR de Google Maps en el ticket.
- Panel del empleado: pantalla Domicilios (entrega al mandadito, ticket de impresión, actualización automática).
- Ticket del cliente con los pasos Recibido, Cocinado y Entregado al repartidor.
- Predicciones (ML), newsletter y rendimiento de meseros en el panel de administración.
- Plataforma web de La 501 Sports (Laravel) subida al repositorio.
- Flujo GitFlow (main, release, develop, feature/*) y pipeline CI/CD con GitHub Actions.

### Seguridad
- Las rutas de exportación de ventas y mantenimiento de la base de datos ahora requieren sesión de administrador.
- La API ligera (`api/`) lee las credenciales de la base de datos desde el `.env`.

### Pruebas
- 38 pruebas unitarias y de integración ejecutadas automáticamente en GitHub Actions.

### Cambiado
- `npm run build` genera `public/css/tailwind.css` con Tailwind CLI.
- Se elimina la confirmación de recepción por parte del cliente.
- Los recordatorios de entrega por correo quedan desactivados.
