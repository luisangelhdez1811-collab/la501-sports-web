# Changelog

Todos los cambios importantes de La 501 Sports Web se registran aquí.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y [Versionado Semántico](https://semver.org/lang/es/).

## [1.1.0](https://github.com/luisangelhdez1811-collab/la501-sports-web/compare/v1.0.0...v1.1.0) (2026-10-27)


### Añadido

* **admin:** categorías, salsas y extras, y promociones con renglones a elegir ([d3ea3b7](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/d3ea3b790677bb5643776b460e1bf3abefe02e93))
* API del mesero con comensales, cobro por separado y cobros sin conexión ([4b47e5c](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/4b47e5c206bfc6a48589856a12a5f217d0b4dfe5))
* **cliente:** opciones en el pedido y promociones con platillos a elegir ([a7de1b9](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/a7de1b9ed23ee965fe6988aa95c22cca1ee5484d))
* cobros sin conexión y cobros por revisar en el desempeño de meseros ([6b86f14](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/6b86f146cb5a96cf4e478eccb1814c56da5eb948))
* **cocina, mesero:** pantalla de cocina y barra, entregas y mapa de mesas ([a276cc5](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/a276cc59c90a4eef2fba60b03d173dd34b53e18d))
* derechos ARCO en /profile y preferencias de Oposición ([f81bbae](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/f81bbaead1d6267c5787d624b540233dd1112f96))
* derechos ARCO en /profile y preferencias de Oposición ([e79f1fd](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/e79f1fd64c0f96d6dc06ad55c5dfbc21c54f18d3))
* integra API movil y verificacion por SMS (Twilio) con el proyec… ([dd15da0](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/dd15da00a3b388c9c2c2be37ff001e5459147ce2))
* integra API movil y verificacion por SMS (Twilio) con el proyecto base ([72bf64c](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/72bf64c94106b3cd1f014d1ebfd021fecd402d86))
* menú nuevo, cocina y barra separadas, entregas del mesero y promociones a elegir ([6c1d78e](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/6c1d78e62db136e71cccf5d7737429033c0fcfa7))
* **menu:** estructura del menú nuevo e importador del Excel ([1b9210a](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/1b9210a5a4cff67f9afee946089178c99f50f5a8))
* pedidos por comensal, cobro por separado y cobros sin conexión en la API del mesero ([836a98f](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/836a98ff9bd77df4a6efdf49765949b34f5ac11d))
* sesiones de la app con JWT Bearer y refresh token con rotación ([131890b](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/131890b70d30d1d343f284b9043eb512b2bab6b1))
* sesiones de la app con JWT Bearer y refresh token con rotación ([5357192](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/53571925d92a68c84248d4fbe1cc622a4fb7fc82))


### Corregido

* **deps:** actualizar dependencias de PHP con vulnerabilidades conocidas ([097b22f](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/097b22fb747becac5b2ce6174f6c9029fc8ebf45))
* responder JSON 403 en la API cuando el filtro de seguridad bloquea una petición ([be425d6](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/be425d6309ca728ebc247fab4568d28c5a1f65ca))
* **seguridad:** consultas preparadas en la API ligera para evitar inyección SQL ([d25deba](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/d25debafde6db8dfba8fdb5ce8f3eaed149bb147))
* **seguridad:** consultas preparadas en la API ligera para evitar inyección SQL ([3083b9b](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/3083b9b90a86cd2e2bfed71bfee867cfa9783f6c))
* **seguridad:** resolver hallazgos de Semgrep ([2d1851d](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/2d1851da566985b464a8c3bff91b668ea0895d53))


### Pruebas

* borrar con delete los platillos de prueba de la API ligera ([5464332](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/5464332914ba306159e6d1f2400f4f1c247c6e05))
* limpiar productos al terminar las pruebas de la API ligera ([ab1dc7e](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/ab1dc7eaec3a1cde85458c42fb363918267a1bdd))
* pruebas de la API del mesero (comensales, cobros por separado y sin conexión) ([05422eb](https://github.com/luisangelhdez1811-collab/la501-sports-web/commit/05422eba2f06a7760965c42915f3023c84025620))

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
