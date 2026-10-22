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

### Con git-flow o GitKraken (opcional)

Las ramas `feature/*` también se pueden crear con [git-flow](https://github.com/petervanderdoes/gitflow-avh)
(`git flow init -d` y luego `git flow feature start reservaciones` / `git flow feature publish reservaciones`)
o con el botón **Git Flow → Start Feature** de GitKraken. **Solo para features:** no usar *release start/finish*
ni *feature finish*, porque intentan fusionar y empujar directo a `develop`/`main`, que están protegidas. Las
versiones las prepara release-please (sección 3) y todo llega a `develop` y `main` por Pull Request.

Los mensajes de commit deben seguir el formato de la sección 4: release-please calcula la versión y el CHANGELOG
a partir de ellos.

---

## 3. Publicar una versión

Automatizado con [**release-please**](https://github.com/googleapis/release-please) (Google) + GitHub Actions.
El número de versión, el `CHANGELOG.md`, el tag y el Release **no se editan a mano**: salen de los commits.

```
develop ──PR──► release ──(release-please abre su PR de versión)──► release ──PR──► main ──► Hostinger
                                 VERSION + CHANGELOG                  tag + Release   despliegue + PR release → develop
```

1. **PR develop → release** con lo que se quiere publicar. Al fusionarlo se genera el paquete candidato
   `-rc.zip` (*Actions → Artifacts*).
2. **release-please abre (o actualiza) el PR "chore: preparar versión X.Y.Z"** hacia `release`, con la versión
   calculada (`feat` → sube MENOR, `fix` → sube PARCHE, `feat!` o `BREAKING CHANGE` → sube MAYOR) y la sección
   nueva del CHANGELOG. Revisarlo y **fusionarlo**: release-please crea el tag `vX.Y.Z` y el Release, y se abre
   solo el **PR release → main**.
3. **Aprobar el PR release → main** (checks **Pruebas** y **Versión** en verde). Al fusionarlo, el pipeline:
   - adjunta el `.zip` de producción al Release `vX.Y.Z`;
   - **despliega en Hostinger**: respalda código y BD en `~/respaldos`, activa el modo mantenimiento, sube los
     archivos con rsync (sin borrar `.env`, `storage/` ni avatares), corre `php artisan migrate --force` y
     `optimize:clear`, quita el mantenimiento y verifica que el sitio y la API respondan;
   - abre el **PR release → develop** para sincronizar.
4. **Aprobar el PR release → develop**.

Si falta algún secreto (sección 5), el paso correspondiente se omite con un aviso y se hace a mano.

Versionado semántico (`MAYOR.MENOR.PARCHE`), calculado por release-please a partir de los commits:
- `PARCHE` (1.0.**1**): commits `fix:`.
- `MENOR` (1.**1**.0): commits `feat:`.
- `MAYOR` (**2**.0.0): commits `feat!:` o con `BREAKING CHANGE:` en el cuerpo.

Configuración: `release-please-config.json` (secciones del CHANGELOG en español) y
`.release-please-manifest.json` (versión actual; la actualiza release-please).

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
| Push / merge a `main` | Pruebas → **Construir** → **Publicar** tag + Release → **Desplegar** en Hostinger + **Sincronizar** develop |
| Push / merge a `release` (`release-please.yml`) | **release-please**: PR de versión hacia `release`; al fusionarlo, tag + Release + PR release → main |

- **Pruebas**: PHP 8.3, MySQL 8 de pruebas, `composer install`, `npm run build`, `php artisan migrate`, `php artisan test` (`tests/Unit` y `tests/Feature`).
- **Construir**: `composer install --no-dev --optimize-autoloader` + `npm run build`. El `.zip` incluye `vendor/` y `public/build/`, sin `.env`, `tests/` ni `node_modules/`.
- **Desplegar**: scripts `scripts/deploy/antes.sh` (respaldo + mantenimiento) y `scripts/deploy/despues.sh` (migraciones + caché), ejecutados en el servidor por SSH.

### Secretos (Settings → Secrets and variables → Actions)

| Secreto | Valor |
|---|---|
| `RELEASE_TOKEN` | Token *fine-grained* (Settings personales → Developer settings) con acceso a este repositorio y permisos **Contents** y **Pull requests** en lectura y escritura. Lo usan *release-please* y *Sincronizar*: según la documentación de release-please, lo creado con el token por defecto de Actions no dispara el pipeline ni las pruebas. |
| `HOSTINGER_SSH_HOST` | IP del servidor (hPanel → Avanzado → Acceso SSH). |
| `HOSTINGER_SSH_PORT` | Puerto SSH (normalmente `65002`). |
| `HOSTINGER_SSH_USER` | Usuario SSH (ej. `u660523345`). |
| `HOSTINGER_SSH_KEY` | Llave **privada** SSH. La pública se agrega en hPanel → Acceso SSH → Llaves SSH. |
| `HOSTINGER_APP_PATH` | Carpeta del proyecto en el servidor (donde está `artisan`). |

Crear la llave (una vez, en tu computadora):
```bash
ssh-keygen -t ed25519 -C "github-actions-la501" -f la501_deploy -N ""
# la501_deploy.pub → hPanel · la501_deploy (privada) → secreto HOSTINGER_SSH_KEY · después borrar ambos archivos
```

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
