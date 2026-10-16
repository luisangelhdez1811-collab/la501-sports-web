# La 501 Sports Web

Plataforma web (Laravel) del **Sistema Integral Multiplataforma para el Restaurante La 501 Sports**.
La app móvil vive en otro repositorio.

[![CI/CD](https://github.com/luisangelhdez1811-collab/la501-sports-web/actions/workflows/ci-cd.yml/badge.svg?branch=develop)](https://github.com/luisangelhdez1811-collab/la501-sports-web/actions/workflows/ci-cd.yml)

---

## 1. Ramas (GitFlow)

| Rama | Propósito | ¿Se puede hacer push directo? |
|---|---|---|
| `main` | Código de producción estable. Cada cambio queda con un tag `vX.Y.Z`. | ❌ Solo por Pull Request desde `release` |
| `release` | Preparación para producción: pruebas finales y ajuste de versión. | ✅ Solo ajustes de versión / correcciones menores |
| `develop` | Integración de todas las funcionalidades nuevas. | ❌ Solo por Pull Request desde `feature/*` |
| `feature/*` | Ramas temporales para cada funcionalidad (`feature/login`, `feature/reservaciones`). | ✅ Es tu rama de trabajo |

Todo Pull Request hacia `develop` o `main` necesita **1 aprobación del compañero** y que el pipeline esté en verde.

```
feature/login ──PR──► develop ──PR──► release ──PR──► main ──► tag v1.0.0 + Release
```

---

## 2. Flujo de trabajo diario

```bash
# 1. Actualizar develop
git checkout develop
git pull origin develop

# 2. Crear la rama de la funcionalidad
git checkout -b feature/reservaciones

# 3. Trabajar y hacer commits pequeños y claros
git add .
git commit -m "feat: agregar formulario de reservaciones"

# 4. Subir la rama
git push -u origin feature/reservaciones
```

5. En GitHub: **Compare & pull request** → base `develop` ← compare `feature/reservaciones`.
6. El compañero revisa y aprueba. Cuando el check **Pruebas** está en verde → **Merge**.
7. Borrar la rama `feature/...` (GitHub ofrece el botón al fusionar).

---

## 3. Publicar una versión

1. **develop → release**: abrir PR con base `release`. Al fusionar, el pipeline prueba y genera el paquete candidato `la501-sports-web-vX.Y.Z-rc.zip` (en *Actions → Artifacts*).
2. **Ajustar versión en `release`**:
   ```bash
   git checkout release
   git pull origin release
   # editar VERSION (ej. 1.1.0) y CHANGELOG.md
   git commit -am "chore: preparar versión 1.1.0"
   git push origin release
   ```
3. **release → main**: abrir PR con base `main`. El check **Versión** confirma que el número es nuevo.
4. Al fusionar a `main`, el pipeline crea automáticamente el tag `v1.1.0` y el **Release** con el `.zip` de producción listo para subir a Hostinger.
5. **Sincronizar**: abrir PR `release → develop` para que develop tenga la nueva versión.

Versionado semántico (`MAYOR.MENOR.PARCHE`):
- `PARCHE` (1.0.**1**): correcciones.
- `MENOR` (1.**1**.0): funcionalidades nuevas compatibles.
- `MAYOR` (**2**.0.0): cambios grandes o incompatibles.

---

## 4. Mensajes de commit

Formato: `tipo: descripción en minúsculas y en presente`

| Tipo | Uso | Ejemplo |
|---|---|---|
| `feat` | Funcionalidad nueva | `feat: agregar carrito de pedidos` |
| `fix` | Corrección de error | `fix: corregir subida de avatar en hostinger` |
| `refactor` | Mejora de código sin cambiar comportamiento | `refactor: separar lógica de reservaciones en servicio` |
| `style` | Estilos / formato | `style: ajustar colores del dashboard` |
| `test` | Pruebas | `test: agregar prueba de login de mesero` |
| `docs` | Documentación | `docs: actualizar readme` |
| `chore` | Configuración, versiones, dependencias | `chore: preparar versión 1.1.0` |

Evitar: `cambios`, `avance`, `ya quedó`, `asdf`.

---

## 5. CI/CD (GitHub Actions)

Archivo: `.github/workflows/ci-cd.yml`

| Evento | Jobs que se ejecutan |
|---|---|
| Push o PR a `develop` | Verificar proyecto → **Pruebas** |
| PR a `release` | Verificar proyecto → **Pruebas** |
| PR a `main` | Verificar proyecto → **Pruebas** + **Versión** |
| Push / merge a `release` | Pruebas → **Construir** paquete candidato (`-rc.zip`) |
| Push / merge a `main` | Pruebas → **Construir** → **Publicar** tag + Release |

- **Pruebas**: PHP 8.3, MySQL 8 de pruebas, `composer install`, `npm run build`, `php artisan migrate`, `php artisan test` (`tests/Unit` y `tests/Feature`).
- **Construir**: `composer install --no-dev --optimize-autoloader` + `npm run build`. El `.zip` incluye `vendor/` y `public/build/`, sin `.env`, `tests/` ni `node_modules/`.

### Base de datos para las pruebas
La base se administra con SQLyog (sin migraciones), así que las pruebas que consulten tablas necesitan la estructura. Exportar **solo la estructura** (sin datos) de la base y guardarla en:

```
database/schema/mysql-schema.sql
```

Laravel la carga automáticamente al ejecutar `php artisan migrate` en el pipeline.

---

## 6. Primera subida del proyecto (una sola vez)

```bash
git clone https://github.com/luisangelhdez1811-collab/la501-sports-web.git
cd la501-sports-web
git checkout develop
git checkout -b feature/proyecto-base
```

1. Copiar **el contenido** de la carpeta del proyecto Laravel dentro de `la501-sports-web/` (si la carpeta original tiene una carpeta oculta `.git`, **no** copiarla).
2. **No** copiar `.env`, `vendor/` ni `node_modules/` (el `.gitignore` ya los excluye).
3. Conservar `README.md`, `VERSION`, `CHANGELOG.md` y la carpeta `.github/` del repositorio.

```bash
git add .
git status            # revisar que NO aparezca .env ni vendor/
git commit -m "feat: agregar proyecto base de la plataforma web"
git push -u origin feature/proyecto-base
```

4. Abrir PR `feature/proyecto-base → develop`, esperar el check **Pruebas** en verde y la aprobación del compañero.

---

## 7. Instalación local

```bash
git clone https://github.com/luisangelhdez1811-collab/la501-sports-web.git
cd la501-sports-web
git checkout develop
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run dev
php artisan serve
```

---

**Equipo:** Luis Angel Hernández Hernández (@luisangelhdez1811-collab) · @AlanGomez0605
