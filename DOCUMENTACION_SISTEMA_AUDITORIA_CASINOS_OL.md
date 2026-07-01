# DOCUMENTACIÓN EXHAUSTIVA DEL SISTEMA — AUDITORÍA DE CASINOS ONLINE (OL)
## Lotería de Santa Fe — Plataforma de Auditoría, Fiscalización y Control de Juegos de Azar Online

> **Ruta analizada:** `/home/desa04/Desktop/auditoria_casinos_OL`
> **Framework:** Laravel 5.4
> **PHP mínimo:** 5.6.4
> **Base de datos principal:** MySQL (`bdMTM_OL`)
> **Base de datos secundaria:** MySQL (`gestion_notas_casinos` / `eventos_casinos`)
> **Fecha del análisis:** 2026-04-22

---

## TABLA DE CONTENIDOS

0.  [Contexto y propósito del sistema](#0-contexto-y-proposito-del-sistema)
1.  [Stack tecnológico completo](#1-stack-tecnologico-completo)
2.  [Estructura general del proyecto](#2-estructura-general-del-proyecto)
3.  [Configuración y entorno (`.env`, `config/`)](#3-configuracion-y-entorno)
4.  [Modelo de datos — Base de datos completa](#4-modelo-de-datos--base-de-datos-completa)
5.  [Autenticación y autorización](#5-autenticacion-y-autorizacion)
6.  [Middleware](#6-middleware)
7.  [Rutas (web, API, console)](#7-rutas)
8.  [Controladores por dominio funcional](#8-controladores-por-dominio-funcional)
9.  [Servicios, helpers y componentes de soporte](#9-servicios-helpers-y-componentes-de-soporte)
10. [Parseo de archivos CSV y ZIP](#10-parseo-de-archivos-csv-y-zip)
11. [Generación paralela de PDFs](#11-generacion-paralela-de-pdfs)
12. [Sistema de caché custom en BD](#12-sistema-de-cache-custom-en-bd)
13. [Sistema de auditoría (Logs + Observers)](#13-sistema-de-auditoria-logs--observers)
14. [Notificaciones y correos](#14-notificaciones-y-correos)
15. [Integraciones externas](#15-integraciones-externas)
16. [Frontend — Vistas Blade, Vue, jQuery, Assets](#16-frontend)
17. [Plantillas PDF](#17-plantillas-pdf)
18. [Lógica de negocio y fórmulas críticas](#18-logica-de-negocio-y-formulas-criticas)
19. [Flujos operativos completos](#19-flujos-operativos-completos)
20. [Permisos, roles y secciones](#20-permisos-roles-y-secciones)
21. [Riesgos, deudas técnicas y observaciones](#21-riesgos-deudas-tecnicas-y-observaciones)
22. [Glosario de términos del dominio](#22-glosario)
23. [Guía para reimplementar desde cero](#23-guia-para-reimplementar)

---

## 0. CONTEXTO Y PROPÓSITO DEL SISTEMA

El sistema **"Auditoría Casinos OL"** (a veces llamado internamente **"CASOnline"**) es una aplicación web desarrollada por la **Lotería de Santa Fe (Argentina)** para **auditar, controlar y fiscalizar** la operación de las **plataformas de casinos online** autorizadas en la provincia. El dominio `@santafe.gov.ar` en la configuración de mail confirma que es un sistema interno de gobierno.

### 0.1 Objetivos funcionales

El sistema permite a los auditores y fiscalizadores de la Lotería:

1. **Registrar y administrar** los casinos, plataformas de juego online y sus catálogos de juegos (máquinas tragamonedas virtuales, poker, ruleta, etc.).
2. **Gestionar certificaciones GLI** (Gaming Labs International) — los certificados de software homologados por laboratorios para cada juego.
3. **Tramitar expedientes administrativos**, **resoluciones**, **disposiciones** y **notas** relacionadas con las autorizaciones, altas, bajas y modificaciones.
4. **Importar diariamente** los archivos de producción (CSV/ZIP) generados por cada plataforma:
   - **Producido por juegos**: apuestas, premios, beneficio por juego y día.
   - **Producido por jugadores**: mismo detalle pero segmentado por jugador individual.
   - **Producido de Poker**: droop, utilidad.
   - **Beneficio mensual**: depósitos, retiros, puntos club, ajustes.
   - **Estados de juegos**: catálogo de máquinas disponibles y su estado operativo.
   - **Estados de jugadores**: datos demográficos, autoexclusiones, movimientos.
5. **Validar y reconciliar** los datos importados contra los beneficios declarados por el operador, detectando discrepancias automáticamente.
6. **Aplicar ajustes de auditoría** y registrar todas las diferencias.
7. **Generar informes contables y estadísticos**:
   - Informes por juego y por jugador (histórico diario).
   - Estadísticas generales: PDEV, HOLD, ARPU, distribución geográfica.
   - Gráficos comparativos anuales e interanuales.
   - Planillas PDF completas por mes y plataforma.
8. **Auditar eventos publicitarios** de los casinos (notas de publicidad, promociones, contratos).
9. **Registrar notas técnicas** asociadas a eventos, con archivos adjuntos (pautas, diseños, bases y condiciones).
10. **Generar informes técnicos** que salen firmados por los auditores.
11. **Gestión centralizada de usuarios**, con roles y permisos granulares, y soporte para autenticación federada (CAS).
12. **Calendario de eventos** de la Dirección.
13. **Sistema de tickets integrado con osTicket** para soporte interno.

### 0.2 Contexto regulatorio y negocio

En Argentina, los casinos online son operados por **concesionarios privados** bajo control del **estado provincial**. La Lotería de Santa Fe cobra un **canon** y necesita verificar que los operadores declaran correctamente su **producido bruto** (ingreso total - premios pagados). Este sistema es la herramienta central para ese control.

Las plataformas operadoras identificadas en el código incluyen (por códigos/nombres):
- **CCOL** (probablemente "City Center Online")
- **BPLAY** (Boldt Play)
- Otras plataformas multi-casino (CSF, CME, CRO...)

### 0.3 Usuarios tipo del sistema

El sistema define 6 roles concretos:

| ID rol | Descripción    | Propósito típico                                            |
|-------:|----------------|-------------------------------------------------------------|
| 1      | Superusuario   | Acceso total, mantenimiento, regenerar resúmenes            |
| 2      | Administrador  | Gestión de usuarios y configuración                         |
| 3      | Fiscalizador   | Validación de producidos y beneficios                       |
| 4      | Control        | Control operativo diario                                    |
| 6      | Despacho       | Manejo de expedientes, resoluciones y notas                 |
| 9      | Casino AE      | Usuario del casino para autoexclusión (externo)             |

---

## 1. STACK TECNOLÓGICO COMPLETO

### 1.1 Backend
- **PHP ≥ 5.6.4** (código real parece escrito para PHP 7.x).
- **Laravel 5.4** (framework MVC).
- **Composer** con los paquetes:
  - `laravel/framework` 5.4.*
  - `laravel/tinker` ~1.0
  - `barryvdh/laravel-dompdf` ^0.8.0 (generación de PDF con DOMPDF)
  - `chumper/zipper` 1.0.x (manejo de archivos ZIP)
  - `guzzlehttp/guzzle` ~6.0 (HTTP client, probablemente para CAS y osTicket)
  - `nothingworks/blade-svg` ^0.2.2 (inclusión de SVGs en Blade)
  - `phpoffice/phpword` ^0.18 (generación de documentos Word)
  - `pusher/pusher-php-server` ^3.0 (Pusher para broadcasting, aparentemente sin usar activamente)
  - `fzaninotto/faker` ~1.4 (dev)
  - `mockery/mockery` 0.9.* (dev)
  - `phpunit/phpunit` ~5.7 (dev)

### 1.2 Base de datos
- **MySQL** (principal y secundaria).
- BD principal: `bdMTM_OL` (host `10.1.121.22`).
- BD secundaria: `gestion_notas_casinos` / `eventos_casinos` (host `10.1.121.195` en producción).
- Conjunto de caracteres: **`utf8mb4`**, collation `utf8mb4_unicode_ci`.
- Modo estricto MySQL: `STRICT_TRANS_TABLES`, `ERROR_FOR_DIVISION_BY_ZERO`, `NO_ENGINE_SUBSTITUTION`.
- **No usa migraciones** — la BD vive directamente en MySQL, lo que significa que el esquema está implícito en los modelos Eloquent y consultas raw.

### 1.3 Frontend
- **Blade** (motor de plantillas de Laravel).
- **Bootstrap 3** + `bootstrap-sass` 3.3.7.
- **jQuery 3.1+** (trabajo pesado, DOM manipulation).
- **Vue.js 2.0** (**instalado pero prácticamente no utilizado** — solo hay un componente `Example.vue` dummy; el código real es jQuery + vanilla JS).
- **Axios** 0.15 (peticiones HTTP desde el cliente).
- **Lodash** 4.16 (utilitarios JS).
- **Laravel Mix** 0.6 sobre Webpack (compilación de assets).
- **Highcharts 11.3.0** (gráficos — incluye 3D, export, accessibility).
- **FullCalendar** (calendario de eventos).
- **Moment.js** + `bootstrap-datetimepicker` (manejo de fechas).
- **Croppie** (recorte de imágenes de perfil).
- **Fabric.js** (canvas).
- **Anime.js** (animaciones).
- **iCheck** (checkboxes estilizados).
- **Perfect Scrollbar** (scroll custom).
- **jQuery UI** (con temas `flick` y `smoothness`).
- **jQuery qTip 1.0** (tooltips).
- **Font Awesome** + **Ionicons** + **Flaticon** + **Glyphicons** (iconografía).
- **Roboto** (tipografía primaria, todos los pesos, WOFF/WOFF2/TTF/SVG).

### 1.4 Compilación de assets
```js
// webpack.mix.js
mix.js('resources/assets/js/app.js', 'public/js')
   .sass('resources/assets/sass/app.scss', 'public/css');
```

### 1.5 Herramientas externas en el servidor
- **`pdfunite`** (de `poppler-utils`): unir PDFs parciales en generación paralela.
- **`cron`**: para el `schedule:run` de Laravel (aunque el schedule en `Kernel.php` está vacío).

---

## 2. ESTRUCTURA GENERAL DEL PROYECTO

```
auditoria_casinos_OL/
├── app/                       # Lógica de la aplicación
│   ├── APIToken.php           # Modelo API-Token
│   ├── Archivo.php            # Modelo de archivos (GLI)
│   ├── Beneficio*.php         # Modelos de beneficios (mensual, diario, poker)
│   ├── Cache.php              # Modelo de la tabla cache custom
│   ├── Casino.php             # Modelo Casino
│   ├── CategoriaJuego.php     # Categorías de juego
│   ├── Channels/              # Canales de notificación custom
│   │   └── VoiceChannel.php   # (stub, no implementado)
│   ├── Clima.php              # Modelo auxiliar (sin uso visible)
│   ├── Console/               # Kernel de consola
│   │   └── Kernel.php
│   ├── Cotizacion.php         # Cotizaciones de moneda
│   ├── DatosJuegoImportado.php
│   ├── DetalleLog.php
│   ├── DetalleProducido*.php  # Detalles de producido (juegos, jugadores, poker)
│   ├── Disposicion.php
│   ├── EstadoJuego.php
│   ├── EstadoJuegoImportado.php
│   ├── Evento.php             # Eventos de calendario
│   ├── Events/                # Eventos de broadcast (sin uso real)
│   │   └── SomeEvent.php
│   ├── Exceptions/
│   │   └── Handler.php        # Manejador de excepciones
│   ├── Expediente.php
│   ├── GliSoft.php            # Certificados de software
│   ├── Http/
│   │   ├── Controllers/       # 37 controladores
│   │   ├── Kernel.php         # Kernel HTTP
│   │   └── Middleware/        # 10+ middleware custom
│   ├── ImportacionEstadoJuego.php
│   ├── ImportacionEstadoJugador.php
│   ├── Juego.php
│   ├── Jugador.php
│   ├── Laboratorio.php
│   ├── Listeners/
│   │   └── EventListener.php  # (stub)
│   ├── Log.php
│   ├── LogJuego.php           # Historial de cambios en juegos
│   ├── Mail/
│   │   └── RecuperarContrasena.php
│   ├── Nota.php
│   ├── Notifications/
│   │   └── CalendarioEvento.php
│   ├── Observers/
│   │   ├── EntityObserver.php
│   │   ├── FullObserver.php
│   │   ├── ParametrizedObserver.php
│   │   └── ArchivoObserver.php
│   ├── PdfParalelo.php        # Sistema de PDFs paralelos
│   ├── Permiso.php
│   ├── Plataforma.php
│   ├── Producido*.php         # Producido (juegos/jugadores/poker)
│   ├── Providers/             # Service providers (Laravel)
│   ├── Resolucion.php
│   ├── Rol.php
│   ├── SecRecientes.php       # Últimas secciones visitadas por usuario
│   ├── Services/
│   │   └── LengthPager.php    # Paginación custom
│   ├── TipoEvento.php
│   ├── TipoMoneda.php
│   └── Usuario.php
├── bootstrap/                 # Bootstrap de Laravel (cache, app.php)
├── config/                    # Configuración
├── database/
│   ├── factories/
│   │   └── ModelFactory.php  # Factory de User (default de Laravel)
│   └── seeds/
│       └── DatabaseSeeder.php # Vacío, sin seeds reales
├── public/                    # Document root
│   ├── index.php
│   ├── .htaccess
│   ├── css/                   # 58+ archivos CSS
│   ├── js/                    # JS (incluyendo Highcharts, seccion*.js, etc.)
│   ├── img/                   # Logos, banners, iconos
│   ├── font-awesome/
│   ├── fonts/                 # Roboto, Flaticon, Glyphicons
│   └── storage/               # Link simbólico a storage/app/public
├── resources/
│   ├── assets/
│   │   ├── js/
│   │   │   ├── app.js
│   │   │   ├── bootstrap.js
│   │   │   └── components/
│   │   │       └── Example.vue # (único .vue, sin uso real)
│   │   ├── sass/
│   │   └── svg/               # SVGs embedidos en Blade (home, usuario, casinos, etc.)
│   ├── lang/                  # Traducciones
│   └── views/                 # ~60 vistas Blade
├── routes/
│   ├── api.php                # Rutas de API
│   ├── channels.php           # Broadcasting
│   ├── console.php            # Comando artisan crear-pdf
│   └── web.php                # ~150 rutas web
├── storage/
│   ├── app/                   # Storage de archivos (CSVs geográficos)
│   ├── framework/             # Cache, sessions, views compiladas
│   ├── fonts/                 # Fuentes para DOMPDF
│   └── logs/                  # Logs de Laravel
├── tests/                     # Tests (estructura default, probablemente sin tests escritos)
├── vendor/                    # Dependencias Composer
├── .env                       # Variables de entorno
├── artisan                    # CLI de Laravel
├── composer.json
├── composer.lock
├── package.json
├── phpunit.xml
├── readme.md                  # Readme sencillo con indicaciones
├── server.php
└── webpack.mix.js
```

**Métrica rápida:**
- **42 modelos Eloquent** (+ 1 clase auxiliar `PdfParalelo`).
- **37 controladores**.
- **10+ middleware**.
- **~60 vistas Blade**.
- **~25 secciones principales** (`seccion*.blade.php`).
- **7 plantillas PDF** (`planilla*.blade.php`).
- **~150 rutas web**.


---

## 3. CONFIGURACIÓN Y ENTORNO

### 3.1 Archivo `.env`

```ini
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:kW5v/O2ctVf0X7cEaxPi0hiW4TA8dWBfmohLZRaIGNU=
APP_DEBUG=true
APP_URL=http://10.1.121.24

# Base de datos principal (auditoría)
DB_CONNECTION=mysql
DB_HOST=10.1.121.22
DB_PORT=3306
DB_DATABASE=bdMTM_OL
DB_USERNAME=prueba
DB_PASSWORD=prueba

# Base de datos secundaria (notas de casinos / eventos de publicidad)
DB_GESTION_NOTAS_CONNECTION=mysql
DB_GESTION_NOTAS_HOST=127.0.0.1       # en prod: 10.1.121.195
DB_GESTION_NOTAS_PORT=3306
DB_GESTION_NOTAS_DATABASE=gestion_notas_casinos  # en prod: eventos_casinos
DB_GESTION_NOTAS_USERNAME=root
DB_GESTION_NOTAS_PASSWORD=

# Broadcast / Cache / Session / Queue
BROADCAST_DRIVER=log
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=sync
# QUEUE_DRIVER=database   (alternativa comentada)

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Correo (servidor SMTP del gobierno)
MAIL_DRIVER=smtp
MAIL_HOST=correo.santafe.gov.ar
MAIL_PORT=587
MAIL_USERNAME=nicolasfiore@santafe.gov.ar
MAIL_PASSWORD=sHrsNcBR
MAIL_ENCRYPTION=tls
MAIL_PRETEND=false
MAIL_FROM_ADDRESS=nicolasfiore@santafe.gov.ar
MAIL_FROM_NAME=Cami

# Pusher (vacío, no se usa)
PUSHER_APP_ID=
PUSHER_KEY=
PUSHER_SECRET=

# Cryptography y API
APP_X_API_KEY="base64:TzNlfRUDBa4hqNFm4jSHM60cW+oPhVtGHx6VFYqnrsI="
APP_CIPHER='AES-256-CBC'
APP_CRYPT=base64:1Fvopvbfi1WFsdxX30k8q5Ro3zwKKL8t3JSRtCXK2yE=

# API Geo-Ref de Argentina (para análisis demográfico)
APP_GEO_REF_URL=https://apis.datos.gob.ar/georef/api
```

### 3.2 Variables adicionales soportadas (no en el .env actual, pero referenciadas en el código)

- `CAS_ENDPOINT`: URL del servidor CAS (autenticación federada).
- `HTTP_PROXY`, `HTTP_PROXY_PORT`: proxy para validación de ticket CAS.
- `DESHABILITAR_OLVIDE_CONTRASEÑA`: flag para deshabilitar recuperación de contraseña.
- `RESTAURAR_CONTRASEÑA_LONGITUD_CODIGO` (default 6).
- `RESTAURAR_CONTRASEÑA_CARACTERES_CODIDO` (default '0123456789').
- `RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX` (default 360 segundos).
- `PERMISSIONS_DIRECTORY` (default 0777 — demasiado abierto para producción).
- `PERMISSIONS_FILE` (default 0777).
- `SESSION_SECURE_COOKIE`.

### 3.3 `config/database.php`

Dos conexiones MySQL:

| Conexión             | Descripción                                        |
|----------------------|----------------------------------------------------|
| `mysql`              | BD principal — toda la auditoría.                  |
| `gestion_notas_mysql`| BD secundaria — eventos de publicidad y notas de casinos. |

Ambas con `charset=utf8mb4`, `collation=utf8mb4_unicode_ci`, `strict=true`. En la secundaria se fuerza `modes: STRICT_TRANS_TABLES, ERROR_FOR_DIVISION_BY_ZERO, NO_ENGINE_SUBSTITUTION`.

### 3.4 `config/app.php`

- `name = 'Auditoria Casinos'`
- `timezone = 'America/Argentina/Buenos_Aires'`
- `locale = 'es'`
- `fallback_locale = 'es'`
- `cipher = AES-256-CBC`
- **Service providers custom:** `AppServiceProvider`, `AuthServiceProvider`, `BroadcastServiceProvider`, `EventServiceProvider`, `RouteServiceProvider`, `Barryvdh\DomPDF\ServiceProvider`, `Chumper\Zipper\ZipperServiceProvider`, `BladeSvg\BladeSvgServiceProvider`.

### 3.5 `config/auth.php`

```php
'defaults' => ['guard' => 'web', 'passwords' => 'users'],
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'token', 'provider' => 'users'],
],
'providers' => [
    'users' => ['driver' => 'eloquent', 'model' => App\User::class],
],
'passwords' => [
    'users' => ['provider' => 'users', 'table' => 'password_resets', 'expire' => 60],
],
```

> **Nota importante:** el guard nativo de Laravel está configurado, pero el sistema realmente usa un mecanismo propio de tokens en la tabla `usuario` y middleware custom — no usa `Auth::check()` ni `Auth::user()` de Laravel en la práctica. Todo va por `session('id_usuario')` + `session('token')`.

### 3.6 `config/session.php`

- `driver = file`
- `lifetime = 240` (minutos — 4 horas).
- `cookie` con prefijo dinámico según `SERVER_PORT`, para soportar múltiples instancias en el mismo dominio.
- `sweeping lottery = 2/100`.

### 3.7 `config/filesystems.php`

Disks configurados:

| Disk              | Driver | Root                                                                     |
|-------------------|--------|--------------------------------------------------------------------------|
| `local`           | local  | `storage/app`                                                            |
| `public`          | local  | `storage/app/public`                                                     |
| `s3`              | s3     | AWS (no usado).                                                          |
| `notas_casinos`   | local  | `/mnt/notas_casinos/wampstack-7.4.24-3/apache2/htdocs/eventos_casinos`   |

El disk `notas_casinos` apunta a una instalación WAMP separada, probablemente la aplicación legacy de gestión de notas.

### 3.8 `config/dompdf.php`

- `orientation = portrait`, `paper = A4`, `font = serif`
- `isPhpEnabled = false` (bueno para seguridad)
- `isJavascriptEnabled = true`
- `isRemoteEnabled = true` (⚠ puede cargar recursos remotos — riesgo moderado)
- `tempDir = sys_get_temp_dir()`
- `chroot = realpath(base_path())`

### 3.9 `config/cache.php`

- Driver default: `file`.
- Stores disponibles: `apc`, `array`, `database`, `file`, `memcached`, `redis`.
- Nota: además del sistema de cache de Laravel, existe un **cache custom en BD** (tabla `cache` con dependencias, ver sección 12).

### 3.10 `config/queue.php`

- Driver: `sync` (ejecución síncrona — no hay workers en producción).
- Tabla `jobs` disponible pero no usada.

### 3.11 `config/blade-svg.php`

- `svg_path = resources/assets/svg`
- `inline_rendering = true`
- `css_class = icon`

### 3.12 Scheduler

`app/Console/Kernel.php`:

```php
protected $commands = [];   // sin comandos registrados
protected function schedule(Schedule $schedule) { }  // sin tareas programadas
```

El único comando artisan registrado está en `routes/console.php`:
- **`crear-pdf {data}`**: renderiza una vista Blade de planilla y genera un PDF parcial. Se invoca desde `PdfParalelo.php` para paralelización.

El README indica que hay que agregar al crontab:
```
* * * * * php /path/to/artisan schedule:run 1>> /dev/null 2>&1
```
pero actualmente no hay tareas programadas.

### 3.13 Exceptions Handler

`app/Exceptions/Handler.php` delega al comportamiento por defecto de Laravel. No reporta excepciones de autenticación, autorización, HTTP, modelo no encontrado, CSRF ni validación. Mucho código está comentado (parece que hubo una redirección custom a `/inicio` en algún momento).

---

## 4. MODELO DE DATOS — BASE DE DATOS COMPLETA

### 4.1 Resumen cuantitativo

- **47 tablas principales**
- **11 tablas pivote** (many-to-many)
- **3 vistas SQL dinámicas** (creadas por los controladores, no son permanentes)
- **≥ 8 tablas temporales** (creadas en runtime durante importaciones)
- **≥ 4 tablas en la BD secundaria** (`gestion_notas_mysql`)

### 4.2 Dominio: Autenticación y Autorización

#### Tabla `usuario`
| Columna              | Tipo           | Descripción                                   |
|----------------------|----------------|-----------------------------------------------|
| id_usuario           | INT PK AI      | Clave primaria                                |
| user_name            | VARCHAR        | Login único                                   |
| nombre               | VARCHAR        | Nombre completo                               |
| email                | VARCHAR        | Correo institucional                          |
| dni                  | VARCHAR/INT    | DNI (utilizado para matching CAS)             |
| password             | VARCHAR        | **Texto plano** (!) — deuda técnica grave     |
| imagen               | BLOB           | Imagen de perfil (base64 en BD)               |
| token                | VARCHAR(64)    | Token de sesión activa                        |
| ultimos_visitados    | JSON/TEXT      | Últimas secciones navegadas                   |
| deleted_at           | TIMESTAMP      | Soft delete                                   |

**Modelo `Usuario.php`** usa `SoftDeletes`, `Notifiable`, `$timestamps = false`, observador `ParametrizedObserver`.

**Atributos virtuales (appends):**
- `es_superusuario` — rol 1.
- `es_administrador` — rol 2.
- `es_fiscalizador` — rol 3.
- `es_control` — rol 4.
- `es_despacho` — rol 6.
- `es_casino_ae` — rol 9.
- `es_controlador` — administrador OR superusuario OR control.
- `elimina_cya` — permiso `m_eliminar_cierres_y_aperturas`.

**Relaciones:**
- `roles()` — belongsToMany vía `usuario_tiene_rol`.
- `plataformas()` — belongsToMany vía `usuario_tiene_plataforma`.
- `casinos()` — belongsToMany vía `usuario_tiene_casino`.
- `logs()` — hasMany a `log`.
- `secciones_recientes()` — hasMany a `sec_recientes`.

#### Tabla `rol`
| Columna     | Tipo      | Descripción           |
|-------------|-----------|-----------------------|
| id_rol      | INT PK AI |                       |
| descripcion | VARCHAR   | Nombre del rol        |

Observador: `FullObserver`.

#### Tabla `permiso`
| Columna     | Tipo      | Descripción                            |
|-------------|-----------|----------------------------------------|
| id_permiso  | INT PK AI |                                        |
| descripcion | VARCHAR   | Nombre del permiso (string usado en middleware `tiene_permiso:xxx`) |

Observador: `FullObserver`.

#### Tabla `API_token`
| Columna       | Tipo          | Descripción                                |
|---------------|---------------|--------------------------------------------|
| id_api_token  | INT PK AI     |                                            |
| token         | VARCHAR       | Token (se compara con header `API-Token`)   |
| ip            | VARCHAR       | IP permitida                               |
| descripcion   | VARCHAR       | Descripción del cliente/servicio           |
| metadata      | JSON (cast)   | Flags: `puede_obtener_bruto`, `puede_post_user_name`, `id_usuario` |

#### Tabla `recuperar_contrasena`
(Creada dinámicamente en el constructor de `OlvideMiContrasenaController`.)

| Columna                  | Tipo         | Descripción                                    |
|--------------------------|--------------|------------------------------------------------|
| id_recuperar_contrasena  | INT PK AI    |                                                |
| email                    | VARCHAR(128) | Email ingresado                                |
| email_simplificado       | VARCHAR(128) | Email normalizado (para matching)              |
| codigo                   | VARCHAR(16)  | Código enviado por mail                        |
| token_recuperacion       | VARCHAR(64)  | Token tras verificar código                    |
| usuario_id_usuario       | INT          | FK a `usuario` (snapshot)                      |
| usuario_user_name        | VARCHAR(64)  | snapshot                                       |
| usuario_email            | VARCHAR(128) | snapshot                                       |
| created_at               | TIMESTAMP    |                                                |
| verified_at              | TIMESTAMP    | Marcado tras verificar código                  |
| resetted_at              | TIMESTAMP    | Marcado al completar reset                     |
| expired_at               | TIMESTAMP    | Fecha de expiración                            |
| invalidated_at           | TIMESTAMP    | Cuando se invalida                             |

#### Tablas pivote de auth
- `usuario_tiene_rol (id_usuario, id_rol)`
- `rol_tiene_permiso (id_rol, id_permiso)`
- `usuario_tiene_plataforma (id_usuario, id_plataforma)`
- `usuario_tiene_casino (id_usuario, id_casino)`

### 4.3 Dominio: Casinos y Plataformas

#### Tabla `casino`
| Columna                           | Tipo       |
|-----------------------------------|------------|
| id_casino                         | INT PK AI  |
| nombre                            | VARCHAR    |
| codigo                            | VARCHAR    |
| fecha_inicio                      | DATE       |
| porcentaje_sorteo_mesas           | DECIMAL    |
| minimo_relevamiento_progresivo    | DECIMAL    |

#### Tabla `plataforma`
| Columna       | Tipo      |
|---------------|-----------|
| id_plataforma | INT PK AI |
| nombre        | VARCHAR   |
| codigo        | VARCHAR   |

#### Pivote `plataforma_tiene_casino`
Relaciona plataformas con casinos (una plataforma puede operar en varios casinos físicos).

### 4.4 Dominio: Juegos

#### Tabla `juego`
Catálogo maestro de juegos disponibles.

| Columna              | Tipo         | Descripción                                |
|----------------------|--------------|--------------------------------------------|
| id_juego             | INT PK AI    |                                            |
| nombre_juego         | VARCHAR      |                                            |
| cod_juego            | VARCHAR      | Código del juego (usado en cruces)         |
| codigo_operador      | VARCHAR      | Código interno del operador                |
| proveedor            | VARCHAR      | Proveedor del software                     |
| denominacion_contable| VARCHAR      |                                            |
| denominacion_juego   | VARCHAR      |                                            |
| porcentaje_devolucion| DECIMAL      | RTP (Return to Player)                     |
| escritorio           | BOOLEAN      | Disponible en escritorio                   |
| movil                | BOOLEAN      | Disponible en móvil                        |
| id_unidad_medida     | INT FK       |                                            |
| id_tipo_moneda       | INT FK       |                                            |
| id_categoria_juego   | INT FK       |                                            |
| created_at/updated_at/deleted_at| TIMESTAMP | (SoftDeletes, timestamps activos)  |

**Observador:** `ParametrizedObserver`.

#### Tabla `categoria_juego`
| Columna              | Tipo       |
|----------------------|------------|
| id_categoria_juego   | INT PK AI  |
| nombre               | VARCHAR    |
| nombre_lower         | VARCHAR    | Versión lowercase para JOIN sin collation |

#### Tabla `estado_juego`
| Columna         | Tipo       |
|-----------------|------------|
| id_estado_juego | INT PK AI  |
| nombre          | VARCHAR    |
| codigo          | VARCHAR    |

#### Tabla `juego_log_norm`
Historial versionado de cambios a juegos. Cada vez que se modifica un juego, se inserta una fila aquí con todos los campos + `motivo` + `id_usuario`.

| Columna               | Tipo          |
|-----------------------|---------------|
| id_juego_log_norm     | INT PK AI     |
| id_juego              | INT FK        |
| (mismos campos que juego) |           |
| motivo                | TEXT          |
| id_usuario            | INT FK        |
| created_at, updated_at, deleted_at | |

#### Pivotes de juegos
- `plataforma_tiene_juego (id_plataforma, id_juego, id_estado_juego)` — **con pivot data**: el estado del juego en cada plataforma.
- `plataforma_tiene_juego_log_norm (id_plataforma, id_juego_log_norm, id_estado_juego)` — historial del pivot anterior.
- `juego_glisoft (id_juego, id_gli_soft)` — qué certificados GLI aplican a cada juego.
- `juego_log_norm_glisoft (id_juego_log_norm, id_gli_soft)` — historial.

### 4.5 Dominio: GLI Soft (Certificaciones de Software)

#### Tabla `gli_soft`
| Columna         | Tipo      |
|-----------------|-----------|
| id_gli_soft     | INT PK AI |
| observaciones   | TEXT      |
| nro_archivo     | VARCHAR   |
| id_archivo      | INT FK    |
| id_laboratorio  | INT FK    |
| created_at, updated_at, deleted_at | (SoftDeletes + timestamps) |

#### Tabla `archivo`
| Columna         | Tipo      |
|-----------------|-----------|
| id_archivo      | INT PK AI |
| nombre_archivo  | VARCHAR   |
| archivo         | LONGBLOB  | Binario del PDF del certificado |

Observador: `ArchivoObserver`.

#### Tabla `laboratorio`
| Columna        | Tipo       |
|----------------|------------|
| id_laboratorio | INT PK AI  |
| codigo         | VARCHAR    |
| denominacion   | VARCHAR    |
| pais           | VARCHAR    |
| url            | VARCHAR    |
| nota           | TEXT       |

### 4.6 Dominio: Expedientes y Disposiciones

#### Tabla `expediente`
| Columna             | Tipo       | Descripción                                    |
|---------------------|------------|------------------------------------------------|
| id_expediente       | INT PK AI  |                                                |
| nro_exp_org         | VARCHAR    | Número del organismo                           |
| nro_exp_interno     | VARCHAR    | Número interno                                 |
| nro_exp_control     | VARCHAR    | Número de control                              |
| fecha_iniciacion    | DATE       |                                                |
| iniciador           | VARCHAR    | Quién inicia                                   |
| concepto            | TEXT       |                                                |
| ubicacion_fisica    | VARCHAR    | Lugar físico                                   |
| fecha_pase          | DATE       |                                                |
| remitente           | VARCHAR    |                                                |
| destino             | VARCHAR    |                                                |
| nro_folios          | INT        |                                                |
| tema                | VARCHAR    |                                                |
| anexo               | VARCHAR    |                                                |
| nro_cuerpos         | INT        |                                                |

**Atributo virtual `concatenacion`** = `nro_exp_org-nro_exp_interno-nro_exp_control # codigos_plataformas`.

#### Tabla `resolucion`
| Columna               | Tipo       |
|-----------------------|------------|
| id_resolucion         | INT PK AI  |
| nro_resolucion        | VARCHAR    |
| nro_resolucion_anio   | VARCHAR    |
| id_expediente         | INT FK     |

#### Tabla `disposicion`
| Columna                  | Tipo       |
|--------------------------|------------|
| id_disposicion           | INT PK AI  |
| nro_disposicion          | VARCHAR    |
| nro_disposicion_anio     | VARCHAR    |
| descripcion              | TEXT       |
| id_nota                  | INT FK     |
| id_expediente            | INT FK     |

#### Tabla `nota`
| Columna          | Tipo       | Descripción                              |
|------------------|------------|------------------------------------------|
| id_nota          | INT PK AI  |                                          |
| fecha            | DATE       |                                          |
| detalle          | TEXT       | Contenido                                |
| identificacion   | VARCHAR    |                                          |
| id_estado_juego  | INT FK     |                                          |
| id_expediente    | INT FK     |                                          |
| id_plataforma    | INT FK     |                                          |
| id_casino        | INT FK     |                                          |
| es_disposicion   | BOOLEAN    |                                          |

#### Pivotes
- `expediente_tiene_plataforma (id_expediente, id_plataforma)`.
- `expediente_tiene_gli_sw (id_expediente, id_gli_soft)`.

### 4.7 Dominio: Producido (ingresos por juego y por jugador)

#### Tabla `producido`
Resumen diario de producción por plataforma/moneda.

| Columna              | Tipo          | Descripción                                    |
|----------------------|---------------|------------------------------------------------|
| id_producido         | INT PK AI     |                                                |
| fecha                | DATE          | Día de reporte                                 |
| id_plataforma        | INT FK        |                                                |
| id_tipo_moneda       | INT FK        |                                                |
| apuesta_efectivo     | DECIMAL(20,2) | Apuesta en dinero real                         |
| apuesta_bono         | DECIMAL(20,2) | Apuesta en bonos (cortesía)                    |
| apuesta              | DECIMAL(20,2) | = efectivo + bono                              |
| premio_efectivo      | DECIMAL(20,2) |                                                |
| premio_bono          | DECIMAL(20,2) |                                                |
| premio               | DECIMAL(20,2) |                                                |
| beneficio_efectivo   | DECIMAL(20,2) | = apuesta_efectivo − premio_efectivo           |
| beneficio_bono       | DECIMAL(20,2) | = apuesta_bono − premio_bono                   |
| beneficio            | DECIMAL(20,2) | = apuesta − premio                             |
| diferencia_montos    | BIT           | Flag: hay inconsistencias aritméticas          |
| md5                  | VARCHAR(32)   | Hash del archivo CSV fuente (anti-duplicados)  |

#### Tabla `detalle_producido`
Detalle por juego dentro del producido diario.

| Columna                  | Tipo          |
|--------------------------|---------------|
| id_detalle_producido     | INT PK AI     |
| id_producido             | INT FK        |
| cod_juego                | VARCHAR       |
| categoria                | VARCHAR       |
| jugadores                | INT           |
| apuesta_efectivo/bono/total  | DECIMAL   |
| premio_efectivo/bono/total   | DECIMAL   |
| beneficio_efectivo/bono/total| DECIMAL   |
| diferencia_montos        | BIT           |

#### Tablas `producido_jugadores` + `detalle_producido_jugadores`
Idénticas pero **agrupadas por jugador** en lugar de por juego. En el detalle el campo es `jugador` (código/ID del jugador en la plataforma) en vez de `cod_juego`.

#### Tablas `producido_poker` + `detalle_producido_poker`
Para poker:
- `producido_poker.jugadores`, `droop` (drop/rake total), `utilidad`.
- `detalle_producido_poker.cod_juego`, `categoria`, `jugadores`, `droop`, `utilidad`.

#### Tabla `resumen_mensual_producido_jugadores`
Tabla **desnormalizada** (para performance) que se regenera por `ResumenController`:
- `id_plataforma`, `id_tipo_moneda`, `fecha` (primer día del mes), `jugador`, **SUM** de todos los campos monetarios del mes.

### 4.8 Dominio: Beneficio (reconciliación mensual)

#### Tabla `beneficio_mensual`
Resumen mensual por plataforma/moneda.

| Columna                | Tipo          |
|------------------------|---------------|
| id_beneficio_mensual   | INT PK AI     |
| id_plataforma          | INT FK        |
| id_tipo_moneda         | INT FK        |
| fecha                  | DATE (YYYY-MM-01) |
| depositos              | DECIMAL       |
| retiros                | DECIMAL       |
| apuesta                | DECIMAL       |
| premio                 | DECIMAL       |
| beneficio              | DECIMAL       |
| ajuste                 | DECIMAL       |
| ajuste_auditoria       | DECIMAL       | Ajuste introducido por los auditores |
| puntos_club_jugadores  | DECIMAL       |
| validado               | BOOLEAN       |
| canon                  | DECIMAL       | Canon a pagar al Estado              |
| md5                    | VARCHAR(32)   |

#### Tabla `beneficio`
Detalle diario.

| Columna                | Tipo           |
|------------------------|----------------|
| id_beneficio           | INT PK AI      |
| id_beneficio_mensual   | INT FK         |
| fecha                  | DATE           |
| jugadores              | INT            |
| depositos              | DECIMAL        |
| retiros                | DECIMAL        |
| apuesta                | DECIMAL        |
| premio                 | DECIMAL        |
| beneficio              | DECIMAL        |
| ajuste                 | DECIMAL        |
| ajuste_auditoria       | DECIMAL        |
| puntos_club_jugadores  | DECIMAL        |
| observacion            | TEXT           |

**Atributos virtuales en el modelo:**
- `producido` — busca el `Producido` correspondiente (mismo día/plataforma/moneda).
- `calculado` — `producido->beneficio` o 0.
- `diferencia` — `calculado − (beneficio + ajuste + ajuste_auditoria)`.

#### Tablas `beneficio_mensual_poker` y `beneficio_poker`
Análogas pero para poker, con campos `mesas`, `buy`, `rebuy`, `total_buy`, `cash_out`, `otros_pagos`, `total_bonus`, `utilidad`. El diferencia se calcula contra `producido_poker.utilidad`.

### 4.9 Dominio: Jugadores y su historial

#### Tabla `jugador`
| Columna                     | Tipo       | Descripción                                   |
|-----------------------------|------------|-----------------------------------------------|
| id_jugador                  | INT PK AI  |                                               |
| id_plataforma               | INT FK     |                                               |
| fecha_importacion           | DATE       | Fecha en que se importó el registro           |
| valido_hasta                | DATE       | Fin de vigencia (NULL = actual)               |
| localidad                   | VARCHAR    |                                               |
| provincia                   | VARCHAR    |                                               |
| fecha_alta                  | DATE       |                                               |
| codigo                      | VARCHAR    | ID del jugador en la plataforma (único)       |
| estado                      | VARCHAR    | ACTIVO/INACTIVO/AUTOEXCLUIDO                  |
| fecha_autoexclusion         | DATE       |                                               |
| fecha_nacimiento            | DATE       |                                               |
| fecha_ultimo_movimiento     | DATE       |                                               |
| sexo                        | CHAR       | 'H', 'M', 'X', etc.                           |

**Versionado temporal:** cada importación genera snapshots de jugadores. Si un jugador desaparece de una importación, se marca `valido_hasta` con la fecha anterior a la nueva importación. Si reaparece idéntico, se mueve de una importación futura al presente.

#### Tabla `importacion_estado_jugador`
| Columna                         | Tipo          |
|---------------------------------|---------------|
| id_importacion_estado_jugador   | INT PK AI     |
| id_plataforma                   | INT FK        |
| fecha_importacion               | DATETIME      |
| md5                             | VARCHAR(32)   |

### 4.10 Dominio: Importación de estados de juegos

#### Tabla `importacion_estado_juego`
| Columna                        | Tipo          |
|--------------------------------|---------------|
| id_importacion_estado_juego    | INT PK AI     |
| id_plataforma                  | INT FK        |
| fecha_importacion              | DATETIME      |
| md5                            | VARCHAR(32)   |

#### Tabla `estado_juego_importado`
| Columna                        | Tipo          |
|--------------------------------|---------------|
| id_estado_juego_importado      | INT PK AI     |
| id_importacion_estado_juego    | INT FK        |
| id_datos_juego_importado       | INT FK        |
| estado                         | VARCHAR       |
| es_ultimo_estado_del_juego     | BIT           | Flag para el último estado conocido |

#### Tabla `datos_juego_importado`
Catálogo de juegos **tal como los declara el operador** (puede diferir del catálogo interno `juego`).

| Columna                     | Tipo          |
|-----------------------------|---------------|
| id_datos_juego_importado    | INT PK AI     |
| codigo                      | VARCHAR       |
| nombre                      | VARCHAR       |
| categoria                   | VARCHAR       |
| tecnologia                  | VARCHAR       |

### 4.11 Dominio: Cotizaciones

#### Tabla `cotizacion`
| Columna          | Tipo         |
|------------------|--------------|
| fecha            | DATE **PK**  | No autoincremental |
| valor            | DECIMAL      | Cotización por día |
| id_tipo_moneda   | INT FK       |                    |

### 4.12 Dominio: Calendario de eventos

#### Tabla `evento`
| Columna           | Tipo         |
|-------------------|--------------|
| id_evento         | INT PK AI    |
| fecha_inicio      | DATE         |
| fecha_fin         | DATE         |
| hora_inicio       | TIME         |
| hora_fin          | TIME         |
| titulo            | VARCHAR      |
| descripcion       | TEXT         |
| id_plataforma     | INT FK       |
| id_tipo_evento    | INT FK       |
| realizado         | BOOLEAN      |

#### Tabla `tipo_evento`
| Columna         | Tipo       |
|-----------------|------------|
| id_tipo_evento  | INT PK AI  |
| descripcion     | VARCHAR    |
| color_text      | VARCHAR(7) |
| color_back      | VARCHAR(7) |

### 4.13 Dominio: Auditoría / Logs

#### Tabla `log`
| Columna     | Tipo       | Descripción                           |
|-------------|------------|---------------------------------------|
| id_log      | INT PK AI  |                                       |
| id_usuario  | INT FK     | Autor de la acción                    |
| fecha       | TIMESTAMP  |                                       |
| accion      | VARCHAR    | created / updated / deleted           |
| tabla       | VARCHAR    | Tabla impactada                       |
| id_entidad  | INT        | PK del registro afectado              |

#### Tabla `detalle_log`
| Columna       | Tipo       |
|---------------|------------|
| id_detalle_log| INT PK AI  |
| id_log        | INT FK     |
| campo         | VARCHAR    |
| valor         | TEXT       |

### 4.14 Dominio: Caché custom

#### Tabla `cache`
| Columna       | Tipo       | Descripción                                    |
|---------------|------------|------------------------------------------------|
| id_cache      | INT PK AI  |                                                |
| codigo        | VARCHAR    | Categoría principal                            |
| subcodigo     | VARCHAR    | Sub-categoría                                  |
| data          | LONGTEXT   | Datos (JSON usualmente)                        |
| dependencias  | VARCHAR    | Formato `|dep1|dep2|` — permite invalidación   |
| creado        | TIMESTAMP  |                                                |

### 4.15 Dominio: Últimas secciones visitadas

#### Tabla `sec_recientes`
| Columna           | Tipo       |
|-------------------|------------|
| id_sec_reciente   | INT PK AI  |
| id_usuario        | INT FK     |
| orden             | INT        |
| seccion           | VARCHAR    |
| ruta              | VARCHAR    |

### 4.16 Dominio: Misceláneos

- **`clima`** (`id_clima`, `descripcion`) — tabla auxiliar sin uso evidente en controladores.
- **`notifications`** (estándar de Laravel, UUID) — guarda notificaciones de `CalendarioEvento`.

### 4.17 Base de datos secundaria `gestion_notas_mysql`

(Nombre en .env: `gestion_notas_casinos` local / `eventos_casinos` producción.)

Tablas usadas por `NotasCasinoController` y `AuditoriaEventosController`:

- **`categorias`** — categorías de notas.
- **`tipo_eventos`** — tipos de eventos (promoción, concurso, etc.).
- **`juegos`** — catálogo de juegos de esa BD (probablemente sincronizado).
- **`tipos_nota`** — Común, Publicidad (Bis), Marketing (MKT), Poker (PK).
- **`eventos`** — las notas/eventos en sí, con campos:
  - `idnota`, `nronota_ev`, `origen`, `fecha_evento`, `fecha_finalizacion`, `estado`, `url_promo`, etc.
- **`juegos_nota`** — pivote evento ↔ juegos.
- **`evento_publicidad_casino`** — auditoría de eventos publicitarios cargados desde la app de la Lotería, con fechas de carga y flag `valido`.

### 4.18 Vistas SQL dinámicas

Se crean con `CREATE OR REPLACE VIEW` en el controlador al inicializar la sección.

#### `detalle_producido_juego`
(Creada por `ProducidoController::buscarTodo`.)
```sql
CREATE OR REPLACE VIEW detalle_producido_juego AS
SELECT p.fecha, p.id_plataforma, p.id_tipo_moneda, dp.*,
       pj.id_juego,
       IF(pj.id_juego IS NULL, NULL, j.id_categoria_juego) AS id_categoria_juego,
       IF(pj.id_juego IS NULL, NULL, j.id_tipo_moneda) AS id_tipo_moneda_juego
FROM producido p
JOIN detalle_producido dp ON dp.id_producido = p.id_producido
LEFT JOIN juego j ON (j.cod_juego = dp.cod_juego AND j.deleted_at IS NULL)
LEFT JOIN plataforma_tiene_juego pj ON (pj.id_plataforma = p.id_plataforma AND pj.id_juego = j.id_juego);
```

#### `detalle_producido_diferencias`
Identifica inconsistencias aritméticas en detalles de producido (apuesta ≠ efectivo+bono, categoría difiere, moneda difiere, juego no registrado en BD).

#### Subconsulta `beneficios_diferencias_dias`
(No es CREATE VIEW sino subconsulta inline usada por `BeneficioController::buscarTodo`) — compara el beneficio del producido con el beneficio importado + ajustes.

### 4.19 Tablas temporales creadas en runtime

- `producido_temporal` — staging para LOAD DATA.
- `producido_jugadores_temporal`.
- `producido_poker_temporal`.
- `beneficio_temporal`.
- `juego_importado_temporal`.
- `temp_jugadores_validos_a_f_imp` — jugadores válidos a una fecha de importación.
- `temp_estados_dias` — matriz de estados de días (para dashboard).
- Tablas temporales con nombre dinámico en `InformeContableController`.

### 4.20 Diagrama relacional simplificado

```
usuario ─┬─ rol (N:N)
         │     └─ permiso (N:N)
         ├─ plataforma (N:N)
         ├─ casino (N:N)
         ├─ log ─ detalle_log
         └─ sec_recientes

plataforma ─┬─ casino (N:N)
            ├─ juego (N:N con estado)
            ├─ expediente (N:N)
            ├─ producido ─ detalle_producido
            ├─ producido_jugadores ─ detalle_producido_jugadores
            ├─ producido_poker ─ detalle_producido_poker
            ├─ beneficio_mensual ─ beneficio
            ├─ beneficio_mensual_poker ─ beneficio_poker
            ├─ importacion_estado_juego ─ estado_juego_importado ─ datos_juego_importado
            ├─ importacion_estado_jugador
            ├─ jugador
            ├─ evento
            └─ nota

juego ─┬─ categoria_juego
       ├─ tipo_moneda
       ├─ gli_soft (N:N) ─ archivo, laboratorio
       └─ juego_log_norm (historial)

expediente ─┬─ resolucion
            ├─ disposicion ─ nota
            ├─ nota
            └─ gli_soft (N:N)

tipo_moneda ─ cotizacion
```

---

## 5. AUTENTICACIÓN Y AUTORIZACIÓN

### 5.1 `AuthenticationController` — el núcleo

Implementa **patrón Singleton** (`getInstancia()`). Expone métodos:

#### 5.1.1 `login(Request $request)` — Login tradicional
1. Valida que vengan `user_name` y `password`.
2. Busca en `usuario` con match exacto de `user_name` + `password` + `deleted_at IS NULL`. **Las contraseñas están en texto plano en la BD**.
3. Si las credenciales son válidas:
   - Genera un token con `bin2hex(random_bytes(20))` (40 chars hex).
   - Lo guarda en `usuario.token`.
   - Coloca `id_usuario` y `token` en la sesión.
   - Redirige según `session('redirect_to')` si existe.

#### 5.1.2 `loginCASTicket($CAS_ENDPOINT, $service, $ticket, $renew)` — Login via CAS
Autenticación federada con servicio CAS (probablemente el sistema de **UID** del gobierno de Santa Fe):
1. Llama a `CASserviceValidate(...)` que hace un CURL al endpoint CAS con el ticket.
2. El servicio CAS devuelve un XML/JSON con atributos del usuario (CUIL, mail).
3. Del CUIL (11 dígitos) se extrae el DNI quitando 2 primeros y el último.
4. Se buscan usuarios en la tabla `usuario` con:
   - `dni` igual al DNI extraído, **O**
   - `email` igual al mail del CAS.
5. Para **todos** los usuarios encontrados se les asigna el **mismo token** en BD.
6. Se devuelve la lista — el usuario elige cuál usar (en caso de tener varios perfiles).

**Parámetros del CAS:**
- `CAS_ENDPOINT` — variable de entorno.
- `CAS_service` — URL de retorno tras auth.
- `CAS_renew` — si fuerza renovación.
- Usa proxy (`HTTP_PROXY`, `HTTP_PROXY_PORT`) si aplica.
- Timeout 5s, valida SSL.

#### 5.1.3 `loginUserName($user_name)` — Confirmación del usuario CAS
Tras `loginCASTicket`, el usuario selecciona un `user_name`. Esta función:
1. Busca el usuario por `user_name`.
2. Valida que su `token` en BD coincide con el token en sesión.
3. Limpia el token de los demás usuarios que compartían el token (no se selecciona ninguno de ellos).
4. Guarda `id_usuario` en la sesión.

#### 5.1.4 `logout(Request $request)`
- Borra el token en BD (`eliminarToken`).
- `$request->session()->flush()` — limpia toda la sesión.

#### 5.1.5 `obtenerIdUsuario()`
Devuelve el ID de usuario actual, con prioridad:
1. Sesión web (`session('id_usuario')`).
2. Si no, busca un `API-Token` en header + IP y toma `metadata.id_usuario`.
3. Si el API token tiene `metadata.puede_post_user_name = true`, permite override vía parámetro POST `user_name`.

#### 5.1.6 `obtenerAPIToken()`
Busca el token en la tabla `API_token` por IP + token del header.

#### 5.1.7 `verificarToken(id_usuario, token)`
Verifica match exacto en tabla `usuario`.

#### 5.1.8 `usuarioTienePermiso(id_usuario, permiso)`
Query con joins `usuario_tiene_rol → rol_tiene_permiso → permiso` validando por `descripcion`.

#### 5.1.9 `usuarioTieneAlgunPermiso(id_usuario, array_permisos)`
Igual pero con `whereIn`.

#### 5.1.10 `usuarioTieneRol(id_usuario, rol)`
Similar pero sobre roles.

### 5.2 `OlvideMiContrasenaController` — flujo completo de 5 pasos

El controlador **crea dinámicamente la tabla `recuperar_contrasena`** si no existe (en el constructor).

#### Paso 1: `ingresarUser($request)`
- Valida que no esté deshabilitado vía `.env` (`DESHABILITAR_OLVIDE_CONTRASEÑA`).
- Devuelve formulario de email.

#### Paso 2: `enviarCodigo($request)`
- Valida email requerido.
- **Normalización de email para matching**:
  - Gmail: ignora puntos (`es.un.mail@gmail.com` == `esunmail@gmail.com`).
  - Yahoo: trata `-` como `+` (sufijos).
  - Fastmail: elimina subdominios.
  - Todos: quita todo después del primer `+`.
- Busca usuarios con email equivalente.
- Genera código aleatorio:
  - Longitud: `RESTAURAR_CONTRASEÑA_LONGITUD_CODIGO` (default 6).
  - Caracteres: `RESTAURAR_CONTRASEÑA_CARACTERES_CODIDO` (default `'0123456789'`).
- TTL: `RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX` (default 360s = 6min).
- Invalida códigos previos no verificados.
- Envía mail via `RecuperarContrasena` (Mailable) con link + código.

#### Paso 3: `verificarCodigo($request)`
- Valida email + código.
- Busca registros válidos (no invalidados, no reseteados, no expirados) con código correcto.
- Genera `token_recuperacion` (`bin2hex(random_bytes(20))`).
- Marca `verified_at`.
- Extiende TTL otros 6 minutos.
- Guarda token en sesión.
- Devuelve usuarios posibles para ese email.

#### Paso 4: `verificarSeleccionUsuarios($request)`
- Valida que `token_recuperacion` exista en sesión.
- Toma array de `id_usuario` seleccionados.
- Invalida los no seleccionados.
- Extiende TTL otros 6 minutos.

#### Paso 5: `resetearPasswords($request)`
- Valida contraseña requerida + min 8 chars + confirmada.
- Actualiza password de los usuarios seleccionados.
- Marca `resetted_at`.
- Limpia token de sesión.

### 5.3 Sistema de tokens y sesiones

**Tipos de token:**

1. **Token de sesión** (`usuario.token`):
   - 40 caracteres hex (`random_bytes(20)`).
   - Se regenera en cada login.
   - Se limpia en logout.

2. **Token de recuperación** (`recuperar_contrasena.token_recuperacion`):
   - Temporal, para el flujo de recuperación.

3. **API Token** (`API_token.token`):
   - Asociado a una IP.
   - Metadata en JSON.
   - Usado por servicios externos para consumir endpoints.

**Sesión (`config/session.php`):**
- Driver: `file` en `storage/framework/sessions`.
- Lifetime: 4 horas (240 minutos).
- Cookie con prefijo dinámico por puerto (soporte multi-instancia).

**Variables clave en sesión:**
- `id_usuario`
- `token`
- `last_activity_time` (DateTime, para timeout)
- `redirect_to` (tras login)
- `token_recuperacion` (flujo de password reset)
- `redirect` (cuando expira sesión)

### 5.4 Roles definidos (según lógica del modelo `Usuario`)

| ID | Rol           | Propiedad virtual |
|----|---------------|-------------------|
| 1  | Superusuario  | `es_superusuario` |
| 2  | Administrador | `es_administrador`|
| 3  | Fiscalizador  | `es_fiscalizador` |
| 4  | Control       | `es_control`      |
| 6  | Despacho      | `es_despacho`     |
| 9  | Casino AE     | `es_casino_ae`    |

Propiedad combinada: `es_controlador` = admin ∨ superuser ∨ control.

---

## 6. MIDDLEWARE

### 6.1 Registro en `app/Http/Kernel.php`

**Middleware globales** (se ejecutan en CADA petición):
- `CheckForMaintenanceMode`
- `ValidatePostSize`
- `TrimStrings`
- `ConvertEmptyStringsToNull`

**Grupo `web`** (rutas con sesión):
1. `EncryptCookies`
2. `AddQueuedCookiesToResponse`
3. `StartSession`
4. `AuthenticateSession`
5. `ShareErrorsFromSession`
6. `VerifyCsrfToken`
7. `SubstituteBindings`
8. **`CheckUserToken`** ← custom
9. **`CheckSessionTime`** ← custom

**Grupo `api`**:
- `throttle:60,1` (60 req/min)
- `bindings`

**Middleware nombrados** (usables por ruta):
| Alias                  | Clase                          | Descripción                             |
|------------------------|--------------------------------|-----------------------------------------|
| `auth`                 | `Authenticate`                 | Auth de Laravel (no se usa mucho)       |
| `auth.basic`           | `AuthenticateWithBasicAuth`    |                                         |
| `bindings`             | `SubstituteBindings`           |                                         |
| `can`                  | `Authorize`                    |                                         |
| `guest`                | `RedirectIfAuthenticated`      |                                         |
| `throttle`             | `ThrottleRequests`             |                                         |
| **`tiene_permiso`**    | `CheckPermission`              | Todos los permisos indicados (AND)      |
| **`tiene_algun_permiso`** | `CheckAnyPermission`        | Al menos uno (OR)                       |
| **`check_API_token`**  | `CheckAPIToken`                | Valida API Token + IP                   |
| **`tiene_rol`**        | `CheckRole`                    | Todos los roles (AND)                   |

### 6.2 Middleware customs detallados

#### `CheckUserToken`
- Permite libre acceso a `/login`.
- Obtiene `id_usuario` y `token` de sesión.
- Valida que coincidan en la BD.
- Si falla:
  - AJAX → JSON con código 351 (código custom que el frontend interpreta como "relogueate").
  - Normal → redirige a `/login`, guardando `redirect_to` para volver después.

#### `CheckSessionTime`
- Timeout de **4 horas** de inactividad.
- Lee/escribe `last_activity_time` en sesión.
- Si expira: ejecuta logout y redirige.

#### `CheckPermission` (`tiene_permiso:xxx,yyy`)
- Requiere **TODOS** los permisos especificados.
- Si falta alguno: error 351 (AJAX) o redirect a `/inicio`.

#### `CheckAnyPermission` (`tiene_algun_permiso:xxx,yyy`)
- Requiere **al menos uno**.

#### `CheckRole` (`tiene_rol:xxx`)
- Requiere todos los roles indicados.

#### `CheckAPIToken` (`check_API_token`)
- Valida header `API-Token` + IP del cliente contra tabla `API_token`.
- Error 422 JSON si falla.

#### `VerifyCsrfToken`, `EncryptCookies`, `RedirectIfAuthenticated`
- Estándar de Laravel, sin modificaciones significativas.

### 6.3 Middleware inline en `routes/api.php`

**`VerificarPermisoObtenerBruto`** — inline:
- Valida que el API token tenga `metadata.puede_obtener_bruto = true`.
- Se aplica sólo al endpoint `POST /bruto`.

---

## 7. RUTAS

### 7.1 `routes/web.php` — ~150 rutas

#### Rutas de autenticación y perfil
```
GET  /login                       → index.blade.php (login / CAS / recuperación)
POST /login                       → AuthenticationController@login
POST /logout                      → AuthenticationController@logout
GET  /inicio                      → redirect /
GET  /                            → seccionInicio.blade.php
GET  /marcarComoLeidaNotif        → marca notificaciones como leídas
GET  /configCuenta                → UsuarioController@configUsuario
POST /configCuenta/modificarPassword → UsuarioController@modificarPassword
POST /configCuenta/modificarImagen   → UsuarioController@modificarImagen
POST /configCuenta/modificarDatos    → UsuarioController@modificarDatos
GET  /usuarios/quienSoy           → UsuarioController@quienSoy
GET  /usuarios/imagen             → UsuarioController@leerImagenUsuario
```

#### Logs de actividad
```
GET  /logActividades               tiene_permiso:ver_seccion_logs_actividades
GET  /logActividades/buscarLogActividades
GET  /logActividades/obtenerLogActividad/{id}
```

#### Casinos (middleware: `tiene_permiso:ver_seccion_casinos`)
```
GET    /casinos                   → buscarTodo
GET    /casinos/obtenerCasino/{id?}
POST   /casinos/guardarCasino
POST   /casinos/modificarCasino
DELETE /casinos/eliminarCasino/{id}
GET    /casinos/get
GET    /casinos/getMeses/{id_casino}
```

#### Expedientes (permiso: `ver_seccion_expedientes`)
```
GET    /expedientes
GET    /expedientes/obtenerExpediente/{id}
POST   /expedientes/guardarExpediente
POST   /expedientes/modificarExpediente
DELETE /expedientes/eliminarExpediente/{id}
POST   /expedientes/buscarExpedientes
```

#### Usuarios (permiso: `ver_seccion_usuarios`)
```
GET    /usuarios
POST   /usuarios/buscar
GET    /usuarios/buscarUsuario/{id_usuario}
POST   /usuarios/guardarUsuario
DELETE /usuarios/eliminarUsuario/{id_usuario}
GET    /usuarios/reestablecerContraseña/{id_usuario}
```

#### Roles y permisos
```
POST   /roles/buscar
POST   /permisos/buscar
GET    /roles                     tiene_permiso:ver_seccion_roles_permisos
POST   /permiso/guardar
POST   /rol/guardar
POST   /rol/modificar
POST   /permiso/modificar
POST   /permiso/buscarPermisosPorRoles
DELETE /permiso/{id}
DELETE /rol/{id}
GET    /rol/{id}
GET    /permiso/{id}
```

#### Juegos (permiso: `ver_seccion_juegos`)
```
GET  /migrarLog                           → migrar logs de juegos
GET  /juegosCSV                           → CSV export
GET  /juegos                              → buscarTodo
GET  /juegos/obtenerLogs/{id}
GET  /juegos/obtenerJuego/{id?}
POST /juegos/guardarJuego
POST /juegos/modificarJuego
DELETE /juegos/eliminarJuego/{id}
POST /juegos/buscar
POST /juegos/generarDiferenciasEstadosJuegos
GET  /juegos/{id}                         → buscarTodo con ID
POST /juegos/parsearArchivo
POST /juegos/validarCargaMasiva
POST /juegos/guardarCargaMasiva
```

#### Disposiciones, Resoluciones, Notas
```
GET  /disposiciones                tiene_permiso:ver_seccion_disposiciones
GET  /resoluciones
POST /resoluciones/buscar          tiene_permiso:ver_seccion_resoluciones
POST /disposiciones/buscar
GET  /notas                        tiene_permiso:ver_seccion_resoluciones
POST /notas/buscar                 tiene_permiso:ver_seccion_resoluciones
DELETE /notas/eliminar-nota/{id}
```

#### Notas de Casino (nueva sección) (permiso: `ver_cargar_notas`)
```
GET    /cargar-notas
GET    /cargar-notas/notas/archivo/{id}/{tipo}
POST   /cargar-notas/subir
POST   /cargar-notas/modificar
POST   /cargar-notas/paginar
GET    /cargar-notas/juegosSeleccionados/{id}
GET    /cargar-notas/juegos/buscar
GET    /cargar-notas/juegos/buscar/{id}
```

#### Informes Técnicos (permiso: `ver_seccion_informes_tecnicos`)
```
GET  /informesTecnicos
POST /informesTecnicos/paginar
GET  /informesTecnicos/notas/archivo/{id}/{tipo}
POST /informesTecnicos/guardar
POST /informesTecnicos/generar/{id}
GET  /informesTecnicos/previsualizar/{id}
```

#### Auditoría de Eventos (permiso: `ver_seccion_auditoria_eventos`)
```
GET  /auditoriaEventos
POST /auditoriaEventos/importar
POST /auditoriaEventos/paginar
GET  /auditoriaEventos/fechasCarga
```

#### GLI Soft (permiso: `ver_seccion_glisoft`)
```
GET  /certificadoSoft
GET  /certificadoSoft/{id}
POST /certificadoSoft/guardarGliSoft
GET  /certificadoSoft/pdf/{id}
GET  /certificadoSoft/obtenerGliSoft/{id}
DELETE /certificadoSoft/eliminarGliSoft/{id}
POST /certificadoSoft/buscarGliSoft
POST /certificadoSoft/modificarGliSoft
GET  /certificadoSoft/buscarLabs/{codigo?}
GET  /certificadoSoft/obtenerLab/{id_laboratorio}
GET  /certificadoSoft/buscarExpedientePorNumero/{busqueda}
```

#### Calendario
```
GET  /calendario_eventos                → vista calendar
POST /calendario_eventos/crearEvento
GET  /calendario_eventos/buscarEventos
POST /calendario_eventos/modificarEvento
GET  /calendario_eventos/eliminarEvento/{id}
GET  /calendario_eventos/verMes/{month}/{year}
GET  /calendario_eventos/getEvento/{id}
GET  /calendario_eventos/getOpciones
POST /calendario_eventos/crearTipoEvento
```

#### Importaciones (permiso: `ver_seccion_importaciones`)
```
GET  /importaciones
POST /importaciones/buscar
GET  /importaciones/{id_plataforma}/{fecha_busqueda?}/{orden?}
POST /importaciones/importarProducido
POST /importaciones/importarProducidoJugadores
POST /importaciones/importarProducidoPoker
POST /importaciones/importarBeneficio
POST /importaciones/importarBeneficioPoker
POST /importaciones/previewImportacion
DELETE /importaciones/eliminarProducidoJuegos/{id}
DELETE /importaciones/eliminarProducidoJugadores/{id}
DELETE /importaciones/eliminarProducidoPoker/{id}
DELETE /importaciones/eliminarBeneficioJuegos/{id}
DELETE /importaciones/eliminarBeneficioPoker/{id}
DELETE /importaciones/eliminarEstadoJuegos/{id}
DELETE /importaciones/eliminarEstadoJugadores/{id}
```

#### Cotizaciones
```
GET  /cotizacion/obtenerCotizaciones/{mes}
POST /cotizacion/guardarCotizacion
```

#### Producidos (permiso: `ver_seccion_producidos`)
```
GET /producidos
GET /producidos/buscarProducidos
GET /producidos/generarPlanilla/{id_producido}
GET /producidos/generarPlanillaJugadores/{id_producido_jugadores}
GET /producidos/datosDetalle/{id_detalle_producido}
GET /producidos/datosDetalleJugadores/{id_detalle_producido_jugadores}
GET /producidos/detallesProducido/{id_producido}
GET /producidos/detallesProducidoJugadores/{id_producido_jugadores}
```

#### Beneficios (permiso: `ver_seccion_beneficios`)
```
GET  /beneficios
POST /beneficios/buscarBeneficios
GET  /beneficios/obtenerBeneficios/{id_beneficio_mensual}
POST /beneficios/ajustarBeneficio
POST /beneficios/validarBeneficios
GET  /beneficios/generarPlanilla/{id_beneficio_mensual}
GET  /beneficios/informeCompleto/{id_beneficio_mensual}
```

#### Estadísticas
```
GET  /estadisticasGenerales
GET  /estadisticasPorCasino
GET  /interanuales
POST /estadisticasGenerales
POST /estadisticasPorCasino
POST /interanuales
```

#### Informes
```
# Informe de plataforma (permiso: ver_seccion_estestadoparque)
GET /informePlataforma
GET /informePlataforma/obtenerCantidadesPdevs
GET /informePlataforma/obtenerJuegoFaltantesConMovimientos
GET /informePlataforma/obtenerJugadorFaltantesConMovimientos
GET /informePlataforma/obtenerJuegoAlertasDiarias
GET /informePlataforma/obtenerJugadorAlertasDiarias
GET /informePlataforma/obtenerEvolucionCategorias

# Informe contable de juego (permiso: ver_seccion_informecontable)
GET /informeContableJuego
GET /informeContableJuego/obtenerJuegoPlataforma/{id_plataforma}/{cod_juego?}
GET /informeContableJuego/obtenerJugadorPlataforma/{id_plataforma}/{jugador?}
GET /informeContableJuego/obtenerInformeDeJuego/{id_plataforma}/{cod_juego}
GET /informeContableJuego/obtenerProducidosDeJuego/{id_plataforma}/{cod_juego}/{offset?}/{size}
GET /informeContableJuego/obtenerProducidosDeJugador/{id_plataforma}/{jugador}/{offset?}/{size}
GET /informeContableJuego/{id_plataforma}/{tipo}/{codigo}

# Informe estado jugadores (permiso: ver_seccion_informecontable)
GET /informeEstadoJugadores
POST /informeEstadoJugadores/buscarJugadores
GET /informeEstadoJugadores/historial
POST /informeEstadoJugadores/importarJugadores
GET /informeEstadoJugadores/informeDemografico

# Informe estado juegos
GET /informeEstadoJuegos
GET /informeEstadoJuegos/buscarJuegos
GET /informeEstadoJuegos/historial
POST /informeEstadoJuegos/importarEstadosJuegos
POST /informeEstadoJuegos/generarDiferenciasEstadosJuegos

# Informes por juegos (mensuales)
GET /informesJuegos
GET /informesJuegos/generarPlanilla/{year}/{mes}/{id_plataforma}/{id_tipo_moneda}/{jol}
GET /informesJuegos/informeCompleto/{year}/{mes}/{id_plataforma}/{id_tipo_moneda}
GET /informesJuegos/generarPlanillaPoker/{year}/{mes}/{id_plataforma}/{id_tipo_moneda}

# Informes generales (dashboard)
GET /informesGenerales/beneficiosMensuales
GET /informesGenerales/beneficiosAnuales
GET /informesGenerales/pdevAnualBoxplot
GET /informesGenerales/jugadoresMensuales
GET /informesGenerales/jugadoresAnuales
GET /informesGenerales/distribucionJugadores
GET /informesGenerales/arpuMensual
GET /informesGenerales/holdMensual
```

#### Back Office / MTM
```
GET  /backoffice                  tiene_permiso:informes_mtm
POST /backoffice/buscar
POST /backoffice/descargar
```

#### Auxiliares
```
POST /hashearArchivo/{tipo}                       → calcula MD5 de un archivo
GET  /actualizarTablaJugadoresNoEnBD              → mantenimiento
GET  /regenerarResumenesMensualesProducidosJugadores → superuser only
POST /enviarTicket                                → envía ticket a osTicket vía CURL
GET  /migrarLog                                   → migración de logs
```

#### MTM
```
GET /maquinas/getMoneda/{nro}
GET /maquinas/obtenerMTM/{id}
```

### 7.2 `routes/api.php`

```
# Grupo con middleware check_API_token
GET /api/auditoria/plataformasYJuegos
    → Devuelve todas las plataformas con sus juegos activos
    → JSON: [ { id_plataforma, nombre, codigo, juegos: [...] } ]

# Grupo con check_API_token + VerificarPermisoObtenerBruto
POST /api/bruto
    body: { año_mes: 'YYYY-MM', id_casino: INT }
    → devuelve la suma (bcadd) del beneficio mensual + utilidad poker para ese mes/casino
```

### 7.3 `routes/console.php`

Comando Artisan: `crear-pdf {data}`
- Recibe un archivo JSON como parámetro con: `planilla`, `compact`, `filename`, `codigo`, `pag_offset`, `pags`.
- Renderiza la vista Blade de la planilla con los datos.
- Genera un PDF con DOMPDF:
  - A4, portrait.
  - Inyecta header con "Página X de Y" y código del documento.
  - `set_base_path = public_path()`, `chroot = public_path()`.
- Lo guarda con `Storage::put($filename, $dompdf->output())`.
- Fuerza `gc_collect_cycles()` para liberar memoria.

### 7.4 `routes/channels.php`

Un solo canal:
```php
Broadcast::channel('App.User.{id}', function($user, $id) {
    return (int) $user->id === (int) $id;
});
```

(No se usa broadcasting en la práctica; `BROADCAST_DRIVER=log` en .env.)


---

## 8. CONTROLADORES POR DOMINIO FUNCIONAL

Hay **37 controladores**. Casi todos implementan Singleton con `getInstancia()`. A continuación se documenta cada uno con sus métodos, responsabilidades y reglas de negocio.

### 8.1 AuthenticationController
Ya cubierto en sección 5.

### 8.2 UsuarioController

Gestión completa de ciclo de vida de usuarios.

**Métodos principales:**
- `buscarTodo()` — vista `seccionUsuarios` con usuario actual + plataformas.
- `buscarUsuarios(Request)` — búsqueda paginada con filtros: `user_name`, `nombre`, `email`, `plataforma`.
- `buscarUsuario($id)` — devuelve usuario con roles, plataformas, casinos.
- `quienSoy()` — usuario actual basado en sesión o API token.
- `guardarUsuario(Request)` — alta o edición. Valida:
  - `user_name` único.
  - `email` único.
  - Roles/plataformas asignados deben existir.
  - Si es casino_ae, no puede ver otras plataformas.
- `eliminarUsuario($id)` — soft delete.
- `reestablecerContraseña($id)` — genera nueva pass aleatoria y la envía por mail.
- `configUsuario()` — vista `seccionConfigCuenta`.
- `modificarPassword(Request)` — requiere password actual y nueva (min 8 chars).
- `modificarImagen(Request)` — sube imagen cropeada (base64) a `usuario.imagen`.
- `modificarDatos(Request)` — cambia nombre, email, dni, etc.
- `leerImagenUsuario()` — devuelve la imagen como binary response.

**Observaciones:**
- Contraseñas se guardan en texto plano (**deuda técnica crítica**).
- Imagen se guarda base64 en BD.

### 8.3 RolController y PermisoController

Gestión CRUD de roles y permisos, asignación N:N.

**Endpoints clave:**
- `buscarTodo()` — vista `seccionRolesPermisos`.
- `buscarRoles(Request)` — paginado.
- `guardarRol(Request)` — alta/edición con array de permisos.
- `modificarRol(Request)` — actualiza relaciones.
- `eliminarRol($id)` — también limpia pivotes.
- `getRol($id)` — rol con sus permisos.
- **PermisoController** expone métodos simétricos para `permiso`.
- `buscarPermisosPorRoles` — qué permisos tienen X roles.

### 8.4 CasinoController

CRUD de casinos con validación de código único.

**Métodos:**
- `buscarTodo`, `obtenerCasino`, `guardarCasino`, `modificarCasino`, `eliminarCasino`.
- `getAll` — lista de todos los casinos (sin filtros).
- `meses($id_casino)` — retorna los meses que tienen datos para un casino.

### 8.5 JuegoController

Gestión del catálogo de juegos (MTM — Máquinas Tragamonedas).

**Funcionalidades:**
- CRUD con **historial versionado** en `juego_log_norm` (cada modificación genera un snapshot).
- **Carga masiva** vía CSV:
  - `parsearArchivo` — parsea CSV y devuelve datos para preview.
  - `validarCargaMasiva` — chequea duplicados, unicidad.
  - `guardarCargaMasiva` — inserta en bulk.
- `generarDiferenciasEstadosJuegos` — PDF comparando estados declarados vs esperados.
- `juegos_csv` — export a CSV.
- `migrarLogJuegos` — utilitario para poblar logs antiguos.

### 8.6 GliSoftController

Gestión de certificados de software (PDFs GLI).

**Métodos:**
- CRUD completo (guardar, modificar, eliminar, obtener).
- `buscarGliSofts` — búsqueda paginada.
- `leerArchivoGliSoft($id)` — sirve el PDF inline.
- `buscarLabs($codigo?)` — autocomplete de laboratorios.
- `obtenerLab($id)` — datos del laboratorio.
- Valida: archivo PDF requerido, laboratorio existente, juegos asignados.

### 8.7 ExpedienteController

**Reglas de negocio importantes:**
- Número único compuesto: `nro_exp_org-nro_exp_interno-nro_exp_control`.
- Asociación N:N con plataformas (un expediente puede ser multi-plataforma).
- Asociación N:N con GLI Softs (certificados adjuntos a expedientes).
- Asociación 1:N con notas, disposiciones, resoluciones.

### 8.8 DisposicionController y ResolucionController

CRUD simples asociados a expedientes. Búsqueda con filtros (número, año, expediente).

### 8.9 NotaController

Gestión de notas administrativas asociadas a expedientes y estado_juego.

### 8.10 OlvideMiContrasenaController
Cubierto en sección 5.2.

### 8.11 CalendarioController

Gestión del calendario de eventos de la Dirección.

**Funcionalidades:**
- `calendar()` — vista principal con FullCalendar.
- `crearEvento(Request)` — valida fechas, plataforma, tipo_evento.
- `buscarEventos()` — lista eventos en un rango.
- `modificarEvento`, `eliminarEvento`.
- `verMes($month, $year)` — eventos del mes.
- `crearTipoEvento(Request)` — CRUD de tipos de evento.
- `getOpciones()` — devuelve tipos, plataformas, colores.
- Al crear evento, **envía notificación** `CalendarioEvento` a los usuarios responsables (BD + mail).

### 8.12 LogController y DetalleLogController

Visualización de auditoría.

- `buscarTodo` — vista `seccionLogActividades`.
- `buscarLogActividades` — paginado con filtros: usuario, tabla, acción, fecha.
- `obtenerLogActividad($id)` — detalle completo con pares campo/valor.
- `guardarLog($accion, $tabla, $id)` — usado por los Observers.
- `guardarDetalleLog($detalles, $id_log)`.

### 8.13 CacheController
Cubierto en sección 12.

### 8.14 ImportacionController

Cubierto en detalle en sección 10. Coordina las importaciones de:
- Producido por juegos.
- Producido por jugadores.
- Producido de poker.
- Beneficio mensual (juegos + poker).
- Estados de juegos.
- Estados de jugadores.

Flujo general:
1. Recibe archivo CSV, TXT o ZIP.
2. Valida estructura (si ZIP: un solo archivo dentro).
3. Calcula MD5 del archivo.
4. Verifica duplicados por MD5.
5. Delega al `LectorCSVController` para parsing y carga en BD.
6. Llama a `ResumenController` para regenerar resúmenes.
7. Invalida cache (`CacheController::invalidarDependientes`).

### 8.15 ProducidoController

Gestión de producidos importados (visualización, eliminación, diferencias, PDFs).

**Métodos:**
- `buscarTodo()` — crea vistas `detalle_producido_juego` y `detalle_producido_diferencias`.
- `buscarProducidos(Request)` — filtros por rango fechas, plataforma, moneda, estado (correcto/incorrecto).
- `eliminarProducido($id)` — elimina en transacción.
- `eliminarProducidoJugadores($id)` — tras eliminar regenera resumen mensual.
- `eliminarProducidoPoker($id)`.
- `datosDetalle($id_detalle)` — diferencias calculadas.
- `datosDetalleJugadores($id_detalle)`.
- `detallesProducido($id_producido)` — lista con diferencias.
- `generarPlanilla($id_producido)` — **PDF paralelo** con 8 workers, chunks de 1360 detalles (5 páginas × 3 cols × 68 filas).
- `generarPlanillaJugadores($id)` — idem para jugadores.

### 8.16 BeneficioController

Reconciliación mensual.

**Métodos:**
- `buscarTodo()` — crea vista `beneficios_diferencias_dias`.
- `buscarBeneficios(Request)` — filtros (rango fechas, plataforma, moneda). Devuelve `id_beneficio_mensual`, `validado`, `diferencias`.
- `obtenerBeneficios($id_beneficio_mensual)` — todos los días del mes con sus diferencias.
- `ajustarBeneficio(Request)` — suma al campo `ajuste_auditoria` y actualiza acumulado mensual. Validación regex para decimales con `,` o `.`.
- `validarBeneficios(Request)` — validación completa:
  - Chequea que todos los días tengan beneficio.
  - Valida diferencia = 0 (redondeado a 2 decimales) por día.
  - Opcionalmente requiere que todos tengan producido.
  - Marca `beneficio_mensual.validado = 1`.
- `arrayInformeCompleto($id)` — array para CSV: Fecha, Usuarios, Depositos, Retiros, MontosJugados, Premio, Pdev, Resultado, ResultadoFinal, Ajustes.
- `generarPlanilla($id)` — PDF con tabla diaria.
- `informeCompleto($id)` — descarga CSV.

### 8.17 BeneficioMensualController

Estadísticas agregadas mensuales.

**Métodos clave:**
- `buscarTodoGenerales`, `buscarTodoPorCasino`, `buscarTodoInteranuales`.
- `cargarEstadisticasGenerales(Request)` — totales para Melincue, Santa Fe, Rosario (ARS/USD), con bruto + canon por mes.
- `calcularBrutoYCanon(...)` — itera mes a mes.
- `cargarSeccionEstadisticasPorCasino(Request)` — filtro por `id_actividad`: 1=MTM, 2=Mesas, 3=Bingo.
- `cargaSeccionInteranual(Request)` — comparación año contra año, con % de crecimiento.
- `eliminarBeneficioMensual($id)`, `eliminarBeneficioMensualPoker($id)`.

### 8.18 EstadoController

Estados de juegos y jugadores, informe demográfico.

**Métodos:**
- `informeEstadoJugadores()`, `informeEstadoJuegos()` — vistas.
- `buscarJugadores(Request)` — filtros: plataforma, código, estado, edad (calculada `DATEDIFF/365.25`), sexo, localidad, provincia, fechas.
- `historialJugador(Request)` — timeline de cambios.
- `buscarJuegos(Request)` — filtros: plataforma, código, nombre, estado, categoría, tecnología.
- `historialJuego(Request)` — timeline.
- `generarDiferenciasEstadosJuegos(Request)` — PDF comparando estado_recibido vs estado_esperado (soporta cambios de fecha del sistema para auditoría histórica).
- `eliminarEstadoJugadores($id)` — lógica compleja con UPDATE que restaura rangos de validez.
- `eliminarEstadoJuegos($id)`.
- `informeDemografico(Request)` — agrupa jugadores por rango etario (menores, 18-35, 36-45, 46-55, 56-65, 66-75, 76+) y sexo. PDF y alertas por menores con movimiento.

### 8.19 InformeContableController

Informes contables por juego y por jugador individual.

**Métodos:**
- `informeContableJuego()` — vista con selector.
- `obtenerJuegoPlataforma($id_plataforma, $cod_juego?)` — autocomplete (join con `plataforma_tiene_juego`, UNION con juegos en producido pero no registrados).
- `obtenerJugadorPlataforma($id_plataforma, $jugador?)` — autocomplete usando tabla temporal para JOIN.
- `obtenerInformeDeJuego($id_plataforma, $cod_juego)` — info del juego: estados por plataforma, categoría, moneda.
- `obtenerProducidosDeJuego($id, $cod, $offset, $size)` — histórico diario: fecha, moneda, categoría, jugadores, apuestas, premios, beneficios, pdev + resumen (totales + pdev promedio).
- `obtenerProducidosDeJugador($id, $jugador, $offset, $size)` — histórico del jugador.

**Chunkeado:** reciente commit menciona "chunckear busqueda de jugadores a ver si es mas rapido", indica mejoras de performance en progreso.

### 8.20 InformePlataformaController

Dashboard de alertas por plataforma.

**Métodos:**
- `informePlataforma()` — vista con selectores.
- `obtenerCantidadesPdevs()` — cálculo de PDEV teórico vs esperado vs producido:
  - **PdevTeorico** = promedio de `porcentaje_devolucion` de juegos.
  - **PdevEsperado** = promedio ponderado por apuesta.
  - **PdevProducido** = `(premio_total / apuesta_total) × 100`.
  - Agrupa por: estado, tipo (Móvil/Escritorio), categoría, categoría informada.
  - Cache a 3600s con invalidación por cambios en producido.
- `obtenerJuegoFaltantesConMovimientos()` — juegos que aparecen en producido pero NO en BD.
- `obtenerJugadorFaltantesConMovimientos()` — jugadores sin registrar.
- `obtenerJuegoAlertasDiarias()` — juegos con beneficio o pdev anómalo:
  - `|beneficio| >= beneficio_alertas`
  - `|pdev_producido - pdev_juego| >= pdev_alertas`
- `obtenerJugadorAlertasDiarias()`.
- `obtenerEvolucionCategorias()` — series temporales de PDEV por categoría.

### 8.21 informesController (minúscula, antiguo)

Genera PDFs mensuales de beneficios.

**Métodos:**
- `generarPlanilla($year, $mes, $id_plataforma, $id_tipo_moneda, $jol)`:
  - PDF mensual combinando `beneficio` + `cotizacion` + `beneficio_poker` por día.
  - Calcula total cotizado multiplicando por cotización del día.
  - Usa última cotización conocida si falta.
  - Paginación automática.
- `informeCompleto(...)` — PDF landscape con datos completos.
- `generarPlanillaPoker(...)` — para poker: Día, Jugadores, Droop, Utilidad, Rake = (utilidad/total_buy)×100.
- `obtenerBeneficiosPorPlataforma()` — vista calendario de beneficios desde 2020-10-01.

### 8.22 InformesGeneralesController

Dashboard estadístico del home.

**Métodos:**
- `beneficiosMensuales()` — últimos 365 días por plataforma/mes.
- `beneficiosAnuales()` — suma anual por plataforma.
- `jugadoresMensuales()` — count distinct por mes (usando `resumen_mensual_producido_jugadores`, cache 3600s).
- `jugadoresAnuales()`.
- `estadosDias()` — matriz temporal (tabla temporal `temp_estados_dias` con `CROSS JOIN plataforma × tipo_moneda × fechas`) para mostrar calendario de completitud.
- `distribucionJugadores()` — análisis geográfico:
  - Carga CSVs de mapping (`departamento_a_departamento.csv`, `provincia_a_provincia.csv`, etc.) de `storage/app`.
  - Usa **matching fuzzy** con `similar_text`, `Levenshtein` y `Metaphone`.
  - Agrupa por provincia/departamento.
- `pdevAnualBoxplot()` — percentiles (min, Q1, mediana, Q3, max) por plataforma/mes.
- `holdMensual()` — HOLD = (beneficio / apuesta) × 100.
- `arpuMensual()` — ARPU = apuesta_total / jugadores, con regresión lineal para tendencia.

### 8.23 ResumenController
Cubierto en sección 9.

### 8.24 CotizacionController

- `obtenerCotizaciones($mes)` — últimos 2 meses.
- `guardarCotizacion(Request)` — validación: `valor >= 25`. Upsert por fecha.

### 8.25 BackOfficeController

Tablero configurable para visualización y descarga.

**Vistas configurables:**
- `beneficio` (diario)
- `beneficio_poker`
- `producido`

Cada vista se define como:
```php
'nombre_vista' => [
  'cols' => [ [SELECT_CLAUSE, alias, tipo_formato, tipo_filtro, defaults[], valores[]] ],
  'query' => QueryBuilder,
  'default_order_by' => [...],
  'indirect_where' => ['alias' => 'columna_real']
]
```

Formatos: `string`, `numeric` (2 decimales, `,` como separador), `numeric3d` (3 dec), `integer`.
Tipos de filtro: `input_date`, `select`, `input_vals_list`.

Métodos:
- `index()` — vista principal.
- `buscar(Request)` — aplica filtros dinámicos, devuelve paginado.
- `descargar(Request)` — genera CSV.

### 8.26 LectorCSVController
Cubierto en sección 10.

### 8.27 NotasCasino\NotasCasinoController

Gestión de notas de casino en BD secundaria `gestion_notas_mysql`.

**Métodos:**
- `index()` — vista `indexNotasCasino`:
  - Conecta a gestion_notas_mysql.
  - Obtiene categorías, tipos_evento, juegos, tipos_nota.
  - Fallback hardcodeado si hay error de conexión.
- `subirNota(Request)` — alta con archivos:
  - Valida: nro_nota (regex), tipo_nota, año, evento (string), fechas, juegos.
  - Archivos: **Pautas** (PDF/ZIP, 150MB), **Diseño** (PDF/ZIP, 150MB), **Bases** (PDF/ZIP/DOC, 150MB).
  - Genera código según tipo de nota:
    - Tipo 1: `"nro-año"`
    - Tipo 2: `"nro-año Bis"` (publicidad)
    - Tipo 3: `"MKT-nro-año"` (marketing)
    - Tipo 4: `"PK-nro-año"` (poker)
  - Asigna responsable según casino: CCOL→27, BPLAY→28, otros→4.
  - Guarda archivos en disk `notas_casinos` en carpetas `Eventos_Pautas`, `Eventos_Diseño`, `Eventos_byc`.
- `modificarNota`, `paginarNotas`, `descargarArchivo($id, $tipo)`, `buscarJuegos($query)`, etc.

### 8.28 NotasCasino\InformesTecnicosController

Generación de informes técnicos a partir de las notas.

**Métodos:**
- `index()` — vista con casinos CCOL y BPLAY.
- `paginarNotas(Request)` — filtros: nroNota, nombreEvento, idCasino. Encripta `idevento` para el front.
- `descargarArchivo($id, $tipo)` — desencripta idevento y sirve el archivo (tipos: pautas, disenio, basesycond, inf_tecnico).
- `guardarInformeTecnico(Request)` — sube PDF/DOC/DOCX (max 150MB).
- `generarInformeTecnico($id)` — genera el informe automáticamente.
- `previsualizarInformeTecnico($id)` — preview.

### 8.29 AuditoriaEventos\AuditoriaEventosController

Auditoría de eventos publicitarios: cruza datos importados con la BD de notas.

**Métodos:**
- `index()` — vista con fechas de carga y selectores.
- `importarEventos(Request)` — parsea CSV:
  - Columnas: `nro_nota`, `origen`, `evento`, `fecha_evento`, `fecha_finalizacion`, `estado`, `url_promo`.
  - Detección automática de encoding (UTF-8, ISO-8859-1, WINDOWS-1252).
  - Conversión a UTF-8 si corresponde.
  - Busca `nro_nota + origen` en BD `gestion_notas`.
  - Valida fechas con `Carbon::createFromFormat('d/m/Y')`.
  - Valida estado: ACTIVO/FINALIZADO según vigencia.
  - Valida coincidencia de fechas con registro de notas.
  - Inserta en `evento_publicidad_casino` con flag `valido`.
- `paginarEventos(Request)` — lista eventos con filtros (casino, fecha_carga), formato D/M/Y.
- `fechasCarga()` — obtiene fechas distintas de carga.

### 8.30 Auth\LoginController, RegisterController, ForgotPasswordController, ResetPasswordController

Controladores **scaffold default de Laravel** — no se usan realmente porque todo el auth real pasa por `AuthenticationController`.

### 8.31 Otros controladores

- **AuditoriaEventos/** — ya cubierto.
- **NotasCasino/** — ya cubierto.

---

## 9. SERVICIOS, HELPERS Y COMPONENTES DE SOPORTE

### 9.1 `app/Services/LengthPager.php`

Servicio de paginación para colecciones (útil cuando los datos no vienen directamente de una query de BD, por ejemplo después de post-procesar en PHP).

```php
LengthPager::makeLengthAware($collection, $total, $perPage, $appends = null)
```
- Crea un `LengthAwarePaginator`.
- Renderiza HTML de paginación.
- Soporta parámetros adicionales vía `appends`.
- Limpia URLs mal formadas (`/?.` → `?.`).

### 9.2 `app/PdfParalelo.php`

Ver sección 11.

### 9.3 `app/Cache.php` + `CacheController.php`

Ver sección 12.

### 9.4 `app/Services/` (solo tiene `LengthPager.php`)

### 9.5 CSVs auxiliares en `storage/app/`

Usados para matching fuzzy en `distribucionJugadores`:
- `codigopostal_a_departamento.csv`
- `departamento_a_departamento.csv`
- `departamentos-localidades-santafe.csv`
- `distrito_a_departamento.csv`
- `localidad_a_departamento.csv`
- `miscelaneos_a_departamento.csv`
- `provincia_a_provincia.csv`

### 9.6 `ResumenController`

Gestiona la tabla desnormalizada `resumen_mensual_producido_jugadores`.

**Métodos:**
- `generarResumenMensualProducidoJugadores($id_producido_jugadores)`:
  1. Calcula `primer_dia_mes`.
  2. DELETE resumen previo para (plataforma, moneda, mes).
  3. INSERT SELECT agregando todos los `detalle_producido_jugadores` del mes con SUM() por jugador.
- `regenerarResumenesMensualesProducidosJugadores()` (solo superuser):
  1. DROP + CREATE TABLE `resumen_mensual_producido_jugadores`.
  2. Recorre todos los `producido_jugadores` y regenera.

---

## 10. PARSEO DE ARCHIVOS CSV Y ZIP

### 10.1 Estrategia general

El `ImportacionController` es el orquestador. Para cada tipo de importación:

1. **Recibe** archivo `.csv`, `.txt`, o `.zip`.
2. **Valida** extensión y MIME type.
3. **Extrae** el CSV si viene en ZIP (el ZIP debe contener **exactamente 1 archivo**).
4. **Calcula MD5** del contenido y verifica que no exista duplicado.
5. **Delega** al `LectorCSVController` que implementa el parsing real con `LOAD DATA LOCAL INFILE` para performance.

### 10.2 Técnicas de parseo utilizadas

- **`LOAD DATA LOCAL INFILE`** en una **tabla temporal** de staging (`producido_temporal`, `beneficio_temporal`, etc.).
- Transformaciones inline con MySQL: `REPLACE(columna, ',', '.')` para decimales con coma.
- Cálculos derivados en `INSERT SELECT`: `apuesta = apuesta_efectivo + apuesta_bono`, etc.
- `UPDATE` posterior para flags como `diferencia_montos = BIT_OR(...)` para detectar múltiples errores.
- Todo dentro de **transacciones** con `DB::transaction()`.

### 10.3 Importación de Producido por Juegos

**Archivo CSV esperado:**
```
DateReport, GameCode, GameCategory, Players, TotalWagerCash, TotalWagerBonus, TotalWager,
GrossRevenueCash, GrossRevenueBonus, GrossRevenue
```

Flujo:
1. DELETE registros previos para (fecha, plataforma, moneda).
2. INSERT `producido` con header.
3. `LOAD DATA` en `producido_temporal`.
4. INSERT `detalle_producido` con `apuesta = efectivo + bono`, `premio = apuesta - GrossRevenue`, `beneficio = GrossRevenue`.
5. UPDATE totales de `producido` con SUM.
6. Detecta juegos duplicados en el mismo día.
7. Calcula `diferencia_montos` comparando la aritmética.

### 10.4 Importación de Producido por Jugadores

**Columnas:** `DateReport, PlayerID, Games, TotalWagerCash, TotalWagerBonus, TotalWager, GrossRevenueCash, GrossRevenueBonus, GrossRevenue`

Proceso similar al anterior pero agrupando por `PlayerID`.

Después llama a `ResumenController::generarResumenMensualProducidoJugadores()` para regenerar la tabla desnormalizada.

### 10.5 Importación de Producido de Poker

**Columnas:** `fecha, categoria, jugadores, droop, utilidad`.

Tabla destino: `producido_poker` + `detalle_producido_poker`.

### 10.6 Importación de Beneficio Mensual

**Columnas:** `fecha, usuarios, depositos, retiros, apuesta, premio, beneficio, ajuste, puntos_club_jugadores`.

Formato de fecha: `"DD/MM/YYYY HH:MM:SS"` — se parsea con `CONCAT + SUBSTRING_INDEX` en SQL.

Estructura: `beneficio_mensual` (header) + `beneficio` (detalles diarios).

### 10.7 Importación de Beneficio Poker

Similar pero con campos específicos de poker: `mesas, buy, rebuy, total_buy, cash_out, otros_pagos, total_bonus, utilidad`.

### 10.8 Importación de Estados de Jugadores — **Algoritmo más complejo**

**Columnas:** `codigo, localidad, provincia, fecha_alta, estado, fecha_autoexclusion, fecha_nacimiento, fecha_ultimo_movimiento, sexo`.

**Algoritmo con versionado temporal:**

1. `LOAD DATA` a `jugadores_temporal` con `hash = MD5(codigo|localidad|...|sexo)` — identifica jugadores idénticos.
2. Si hay jugador duplicado en el CSV: usa el primero.
3. Busca la **próxima importación** (si existe) para esa plataforma.
4. **Invalidación de jugadores desaparecidos**: jugadores que estaban en la importación anterior pero no en la nueva → `UPDATE jugador SET valido_hasta = DATE_SUB(fecha_nueva_imp, 1 DAY)`.
5. **Reasignación**: jugadores que están en la próxima importación idénticos a los del CSV actual → se mueven a la importación actual (se actualiza su `fecha_importacion`).
6. INSERT los jugadores nuevos con `valido_hasta = DATE_SUB(prox_fecha, 1 DAY)` (o NULL si es la última).
7. DELETE `jugadores_temporal`.
8. Merge de estados idénticos consecutivos para limpiar.

### 10.9 Importación de Estados de Juegos

**Columnas:** `GameCode, GameName, IsPublished, GameCategory, Technology`.

Estructuras:
- `datos_juego_importado` — catálogo maestro (nuevos juegos).
- `estado_juego_importado` — estado por importación.

Mapeo de `IsPublished`: usa tabla `estado_juego` para traducir (p.ej. "Sí"→"Activo").

### 10.10 Hashing de archivos

Endpoint `POST /hashearArchivo/{tipo}`: calcula MD5 de cualquier archivo subido. Se usa pre-importación para verificar si es un duplicado antes de procesar.

---

## 11. GENERACIÓN PARALELA DE PDFs

### 11.1 Problema que resuelve

Los informes mensuales pueden tener **miles de juegos o jugadores**. Generar un solo PDF grande con DOMPDF en un único proceso PHP:
- Consume mucha memoria.
- Puede excederse del `max_execution_time`.
- Bloquea la petición HTTP.

**Solución**: dividir en chunks, generar PDFs parciales **en paralelo** con procesos hijos, unir al final con `pdfunite`.

### 11.2 Clases en `app/PdfParalelo.php`

#### `Command` (abstracta)
Ejecuta un comando artisan como subproceso con `proc_open`. Métodos:
- `exec()` — abre proceso, retorna descriptor.
- `join($block)` — espera/consulta estado.
- `kill()` — mata con `kill -s KILL`, espera hasta 10s.
- Abstract: `console_command()`, `args()`, `post_kill()`.

#### `CommandCrearPDF extends Command`
Ejecuta `php artisan crear-pdf {json_input}`.

Constructor: `(planilla, compact, filename, codigo, pag_offset, pags)`.

- `args()` — serializa parámetros a un JSON en archivo `.input` en storage, devuelve ruta absoluta.
- `post_kill()` — borra `.input` y (opcionalmente) el PDF generado. Llama `gc_collect_cycles()`.

#### `CommandPool`
Pool de procesos con cola FIFO.

- Constructor: `size` (máximo concurrente).
- `submit(Command)` — agrega a activos o a cola de espera.
- `start()` — lanza todos los activos con `exec()`.
- `join($max_seconds = 120, $sleep_seconds = 2)`:
  - Loop de polling cada 2s.
  - Cuando un proceso termina, mueve a `done` y toma siguiente de la cola.
  - Timeout tras 120s.

#### `PdfParalelo` (clase pública)
Método estático `crear($hilos, $planilla, $compacts, $codigo, $paginas_por_pdf, $paginas_totales)`:

1. Genera **fingerprint único**: `{user_id}:{request_path}:{microtime}`. Evita colisiones.
2. Crea `CommandPool($hilos)` (típicamente 8).
3. Para cada `compact` (chunk de datos): `new CommandCrearPDF(...)` y `submit()`.
4. `pool->start()` y `pool->join(120)`.
5. Si timeout → error.
6. Verifica que todos los PDFs parciales existen, los ordena por `pag_offset`.
7. Ejecuta `pdfunite file1.pdf file2.pdf ... output.pdf`.
8. Limpia todo (unset pool, `gc_collect_cycles`).
9. Devuelve `['error' => code, 'value' => path_o_mensaje]`.

### 11.3 Integración con el comando Artisan

El comando `crear-pdf {data}` (en `routes/console.php`):

```
php artisan crear-pdf /path/to/input.json
```

El JSON de entrada tiene:
```json
{
  "planilla": "planillaInformesJuegos",
  "compact": {...datos para la vista...},
  "filename": "storage/app/tmp/xxx-0.pdf",
  "codigo": "INF-2024-10",
  "pag_offset": 0,
  "pags": 35
}
```

El comando:
1. Valida el JSON con `Validator`.
2. Renderiza la vista Blade con `View::make(planilla, compact)`.
3. Crea `Dompdf`, setea `base_path`, `chroot`, paper A4 portrait.
4. `loadHtml(html) + render()`.
5. Agrega header dinámico con "Página X de Y" y código (usando `$PAGE_NUM + pag_offset`).
6. Guarda con `Storage::put(filename, output)`.

### 11.4 Uso típico

`ProducidoController::generarPlanilla($id_producido)`:

```php
// 1360 detalles por PDF (5 páginas × 3 cols × 68 filas)
$detalles_por_pdf = 1360;
$chunks = array_chunk($detalles, $detalles_por_pdf);
$compacts = [];
foreach ($chunks as $idx => $chunk) {
    $compacts[] = ['producido' => $p, 'detalles' => $chunk, 'idx' => $idx];
}
$res = PdfParalelo::crear(8, 'planillaProducidos', $compacts, $codigo, 5, $total_paginas);
```

---

## 12. SISTEMA DE CACHÉ CUSTOM EN BD

### 12.1 Motivación

Laravel tiene sistema de cache estándar (`Cache::remember`), pero este sistema implementa uno **custom en la tabla `cache`** con una feature adicional: **invalidación por dependencias**.

### 12.2 Tabla `cache`

```
id_cache, codigo, subcodigo, data, dependencias, creado
```

Campo `dependencias` usa formato `|dep1|dep2|dep3|` para poder hacer `LIKE '%|dep1|%'` y encontrar qué cachés dependen de qué entidad.

### 12.3 `CacheController`

Singleton. Métodos:

```php
buscar($codigo, $subcodigo, $antes_de, $dependencias, $get = true)
invalidar($codigo, $subcodigo, $antes_de, $dependencias)
invalidarDependientes($dependencias[])
agregar($codigo, $subcodigo, $data, $dependencias = [])
buscarUltimoDentroDeSegundos($codigo, $subcodigo, $segundos)
```

- `buscar` construye un WHERE dinámico y devuelve Collection o QueryBuilder.
- `invalidarDependientes(['juego', 'producido'])` elimina TODOS los cachés que tengan `juego` o `producido` en sus dependencias.
- `buscarUltimoDentroDeSegundos(codigo, sub, 3600)` borra lo viejo + devuelve el más reciente. Patrón: "cache con TTL".

### 12.4 Uso típico

En `InformePlataformaController::obtenerCantidadesPdevs`:
```php
$cc = CacheController::getInstancia();
$cache = $cc->buscarUltimoDentroDeSegundos('pdevs_' . $id_plataforma, $fecha, 3600);
if ($cache) return json_decode($cache->data);

$data = ... // cálculo pesado
$cc->agregar('pdevs_' . $id_plataforma, $fecha, json_encode($data), ['producido']);
```

Cuando se elimina o modifica un `producido`, se llama `CacheController::invalidarDependientes(['producido'])` y todos los cachés dependientes quedan invalidados.

---

## 13. SISTEMA DE AUDITORÍA (LOGS + OBSERVERS)

### 13.1 Tablas

- `log`: resumen de la acción (usuario, fecha, accion, tabla, id_entidad).
- `detalle_log`: pares `(campo, valor)` para cada cambio.

### 13.2 Observers

#### `EntityObserver` (abstracta)
Implementa `created`, `updating`, `deleted`:
- `LogController::guardarLog('Alta'|'Modificación'|'Eliminación', $tabla, $id)`.
- `DetalleLogController::guardarDetalleLog($this->getDetalles($entidad), $id_log)`.

Método abstracto `getDetalles($entidad)` a implementar en subclases.

#### `FullObserver`
Registra **todos** los atributos del modelo:
```php
foreach ($entidad->getAttributes() as $k => $v) $detalles[] = [$k, $v];
```
Aplicado a: `Rol`, `Permiso`, `Producido`, `BeneficioMensual`, `BeneficioMensualPoker`, `Expediente`, `Nota`, `Disposicion`, `Resolucion`, `Cotizacion`, `Laboratorio`.

#### `ParametrizedObserver`
Registra **solo** los atributos pasados en el constructor:
```php
Usuario::observe(ParametrizedObserver::class);
// En el boot: new ParametrizedObserver('user_name', 'email', ...)
```
Aplicado a: `Usuario`, `Juego`, `LogJuego`, `GliSoft`, `Jugador`.

#### `ArchivoObserver`
Observer custom específico para `Archivo`.

### 13.3 Visualización de logs

- `LogController::buscarLogActividades(Request)` — paginado con filtros.
- `LogController::obtenerLogActividad($id)` — detalles completos.
- Vista: `seccionLogActividades.blade.php` — tabla con usuario, fecha, acción, tabla, ID entidad.

---

## 14. NOTIFICACIONES Y CORREOS

### 14.1 Notificaciones Laravel

#### `CalendarioEvento` (`app/Notifications/`)
Canales: `['database', 'mail']`.

**En BD** (tabla `notifications`):
```json
{
  "evento": {...},
  "user": {...},
  "descripcion": "creó un evento del tipo X."
}
```

**Por mail**:
- Subject: "Tienes un nuevo evento - CAS Lotería de Santa Fe".
- Plantilla markdown: `vendor.mail.Notificacion.index`.
- Variables: titulo, main, ruta (link al calendario), boton.
- URL hardcodeada: `http://10.1.121.30:8000/calendario_eventos`.

Endpoint `GET /marcarComoLeidaNotif` marca todas como leídas.

### 14.2 Canales custom

#### `VoiceChannel` (`app/Channels/VoiceChannel.php`)
Stub no implementado — solo tiene el esqueleto.

### 14.3 Correos

#### `RecuperarContrasena` (`app/Mail/`)

```php
$this->subject('Restablecer su contraseña')
    ->view('recuperarContrasena');
```

Variables disponibles:
- `$email` — email del usuario.
- `$link` — link con ticket de reseteo.
- `$codigo` — código de verificación.

### 14.4 Configuración SMTP

```
MAIL_HOST=correo.santafe.gov.ar
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=nicolasfiore@santafe.gov.ar
```

---

## 15. INTEGRACIONES EXTERNAS

### 15.1 CAS (Central Authentication Service)
Ver sección 5.1.2. Autenticación federada del gobierno (UID Santa Fe).

### 15.2 osTicket

`POST /enviarTicket`:
- Envía ticket vía CURL a `http://10.1.121.25/osTicket/api/http.php/tickets.json`.
- Header: `X-API-Key: 14C4C2A8161F6728C74D92C58B6DF990`.
- Body JSON: nombre, email, subject, message, ip, attachments.
- Usado desde el modal "Enviar ticket" del layout principal (solo admin/auditor).

### 15.3 Sistema externo de notas de casinos

BD secundaria `gestion_notas_mysql` + disk `notas_casinos` apunta a una aplicación WAMP externa que usa los mismos archivos y BD.

### 15.4 API Geo-Ref Argentina

`APP_GEO_REF_URL=https://apis.datos.gob.ar/georef/api` — usado potencialmente para autocomplete de provincias/departamentos.

### 15.5 API de sistema de autoexclusión

Link en el menú: `http://.../autoexclusion` — aplicación externa con puerto 8000.

### 15.6 API propia de consulta

```
GET /api/auditoria/plataformasYJuegos   (requires API Token)
POST /api/bruto                          (requires API Token + permission)
```

Permite a sistemas externos (probablemente el de autoexclusión o contables) consumir datos.


---

## 16. FRONTEND

### 16.1 Layout master: `resources/views/includes/dashboard.blade.php`

Plantilla principal que extienden todas las vistas de sección.

**Estructura:**
- `<head>`:
  - Favicon: `/img/logos/faviconOnline.ico`
  - Title: "CASOnline - Lotería de Santa Fe"
  - Meta: UTF-8, viewport responsive, CSRF token.
  - **+18 hojas de estilo** (Bootstrap, custom, iconos, animaciones).
- `<body>`:
  - Header con barra de menú principal.
  - `<main>` con `@yield('contenidoVista')`.
  - Modales globales: `mensajeExito`, `mensajeError`, `modalAyuda`, `modalTicket` (admin/auditor).
  - Scripts al final: jQuery, Bootstrap, moment, datetimepicker, barraNavegacion, ajaxError, modal, AUX.

**Secciones Blade:**
- `@section('estilos')` — CSS específicos.
- `@section('headerLogo')` — logo en navbar.
- `@section('contenidoVista')` — contenido principal.
- `@section('tituloDeAyuda')`, `@section('contenidoAyuda')`.
- `@section('scripts')` — JS final.

### 16.2 Menú jerárquico dinámico

El menú se genera dinámicamente según los permisos del usuario. Está implementado con una estructura de datos anidada en PHP y una función recursiva.

**Estructura del menú:**
```
Plataformas                          (ver_seccion_casinos)

Gestión
  Usuarios
    Gestionar usuarios               (ver_seccion_usuarios)
    Roles y permisos                 (ver_seccion_roles_permisos)
    Log de actividades               (ver_seccion_logs_actividades)
  Expedientes
    Gestionar expedientes            (ver_seccion_expedientes)
    Resoluciones                     (ver_seccion_resoluciones)
    Notas
    Disposiciones                    (ver_seccion_disposiciones)
    Carga de notas                   (ver_cargar_notas)
    Generar informes técnicos        (ver_seccion_informes_tecnicos)
  Juegos
    Juegos                           (ver_seccion_juegos)
    Certificados Software            (ver_seccion_glisoft)
  Autoexclusión                      (ver_seccion_ae_alta)  [external link]

Auditoría
  Importaciones
    Importación Diaria               (ver_seccion_importaciones)
    Juegos [estado]                  (ver_seccion_informecontable)
    Jugadores [estado]               (ver_seccion_informecontable)
  Validación
    Producidos                       (ver_seccion_producidos)
    Beneficios                       (ver_seccion_beneficios)
  Informes Auditoría
    Plataforma                       (ver_seccion_estestadoparque)
    Juegos/Jugadores [contable]      (ver_seccion_informecontable)
  Eventos casinos
    Importación y Validación         (ver_seccion_auditoria_eventos)
    Exportación

Estadísticas
  Informes
    Juegos                           (informes_mtm)
  Tablero
    Generales                        (estadisticas_generales)
    Por Plataforma                   (estadisticas_por_casino)
    Interanuales                     (estadisticas_interanuales)
  Backoffice                         (informes_mtm)
```

**Algoritmo de procesamiento (4 etapas):**

1. `promover_permisos_y_asignar_defaults()` — sube los permisos de los hijos a los padres (si un padre tiene al menos 1 hijo visible, el padre es visible).
2. `filtrar_permisos()` — elimina opciones sin permiso, usando `usuarioTieneAlgunPermiso`.
3. `simplificar_submenues()` — elimina niveles con una sola opción.
4. **Hack Windows XP / Firefox 52** — si el User-Agent es WinXP, expande todos los submenus porque XP no soporta nested submenus.

### 16.3 Página de login: `index.blade.php`

**Autónoma** — no extiende layout. Es la página de login/CAS/recuperación.

Características:
- Imagen de fondo aleatoria (imgIndex1 o imgIndex2).
- Mantiene bg constante durante el flujo de recuperación.
- Forms multistep: login → recuperación (5 pasos) o CAS (selección de usuario).
- Scripts: `/js/index.js`, `/js/icheck.min.js`.

### 16.4 Dashboard: `seccionInicio.blade.php`

Página de inicio, extiende `includes.dashboard`.

**Secciones:**
1. **Últimas secciones visitadas** — grid 4 columnas con iconos SVG dinámicos (según la ruta).
2. **Gráficos (si tiene permiso `estadisticas_generales`)**:
   - Beneficios anuales por plataforma.
   - Jugadores anuales por plataforma.
   - Beneficios mensuales.
   - Jugadores mensuales.
   - PDEV a un año.
   - HOLD a un año.
   - ARPU.
   - Distribución de jugadores por provincia/departamento.
   - Calendario de actividades completadas.
3. **Calendario interactivo** con matriz de estados de importaciones por día.

Scripts:
- Suite completa de **Highcharts 11.3.0** (incluye 3D, export, export-data, accessibility).
- `/js/seccionInicio.js?9` (con cache-busting).

### 16.5 Secciones principales (~25 archivos `seccion*.blade.php`)

| Archivo                                        | Descripción                                           |
|------------------------------------------------|-------------------------------------------------------|
| `seccionCasinos.blade.php`                     | CRUD de casinos.                                      |
| `seccionUsuarios.blade.php`                    | CRUD de usuarios con filtros.                         |
| `seccionRolesPermisos.blade.php`               | Roles a la izquierda, permisos a la derecha.          |
| `seccionConfigCuenta.blade.php`                | Perfil del usuario, cambiar password, avatar (Croppie).|
| `seccionLogActividades.blade.php`              | Auditoría.                                            |
| `seccionExpedientes.blade.php`                 | CRUD con 3 campos de número (org/interno/control).    |
| `seccionResoluciones.blade.php`                | CRUD.                                                 |
| `seccionDisposiciones.blade.php`               | CRUD.                                                 |
| `seccionNotasExpediente.blade.php`             | Notas asociadas a expedientes.                        |
| `seccionJuegos.blade.php`                      | CRUD de juegos con muchos filtros.                    |
| `seccionJuegosComponenteCargaMasiva.blade.php` | Upload de CSV de juegos con preview.                  |
| `seccionGLISoft.blade.php`                     | Certificados software.                                |
| `seccionImportaciones.blade.php`               | 6 modos de importación con modales.                   |
| `seccionProducidos.blade.php`                  | Validación de producidos importados.                  |
| `seccionBeneficios.blade.php`                  | Reconciliación mensual.                               |
| `seccionInformePlataforma.blade.php`           | Informe con tabs.                                     |
| `seccionInformeEstadoJuegos.blade.php`         | Estados de juegos.                                    |
| `seccionInformeEstadoJugadores.blade.php`      | Estados de jugadores.                                 |
| `seccionInformesJuegos.blade.php`              | Informes MTM por juegos.                              |
| `seccionEstadisticasGenerales.blade.php`       | Dashboard con Highcharts.                             |
| `seccionEstadisticasPorCasino.blade.php`       | Stats por plataforma.                                 |
| `seccionEstadisticasInteranuales.blade.php`    | Comparativas año a año.                               |
| `seccionBackoffice.blade.php`                  | Descargas administrativas.                            |
| `calendar.blade.php`                           | FullCalendar de eventos.                              |

### 16.6 Componentes reutilizables (`resources/views/Components/`)

- **`modal.blade.php`** — componente de modal con ID uniqid, botones minimizar/cerrar, collapse, backdrop/keyboard deshabilitados.
- **`modalEliminar.blade.php`** — modal de confirmación.
- **`FiltroTabla.blade.php`** — componente completo filtros + tabla + paginación:
  ```php
  @component('Components/FiltroTabla', [
      'titulo' => '...',
      'target_buscar' => '#selector',
      'filtros' => '<!-- HTML -->',
      'cabecera' => '<!-- <tr><th>...</th></tr> -->',
      'molde' => '<!-- <tr><td>...</td></tr> -->'
  ])
  ```
- **`inputFecha.blade.php`** — datepicker Bootstrap.
- **`include_guard.blade.php`** — evita duplicar CSS/JS.

Además:
- `componenteBotonAbreModal.blade.php` — botón flotante para importaciones.
- `componentePaginadoInformePlataforma.blade.php` — paginación específica.

### 16.7 Includes del menú

```
resources/views/includes/
├── dashboard.blade.php                  # Layout master
├── barraMenuPrincipal.blade.php         # Top navbar
├── barraMenuPrincipal_desplegable.blade.php
├── barraMenuPrincipal_link.blade.php
├── barraMenuPrincipal_texto_con_icono.blade.php
├── menuDesplegable.blade.php
├── menuDesplegable_desplegable.blade.php
├── menuDesplegable_link.blade.php
└── notifications/
    └── calendario_evento.blade.php
```

### 16.8 JavaScript

**`resources/assets/js/app.js`** — registra el componente Vue dummy `Example`.

**`resources/assets/js/bootstrap.js`** — carga jQuery, Bootstrap, Vue, Axios (con CSRF header), Lodash.

**`public/js/` contiene ~50 archivos específicos por sección**, por ejemplo:
```
seccionCasinos.js, seccionUsuarios.js, seccionJuegos.js,
seccionRolesPermisos.js, seccionImportaciones.js, seccionProducidos.js,
seccionBeneficios.js, seccionInformePlataforma.js, seccionEstadisticasGenerales.js,
seccionInicio.js, calendar.js, ...
```

**Utilidades compartidas** (`public/js/`):
- `ajaxError.js` — manejo global de errores AJAX (códigos 351, 403, 500).
- `barraNavegacion.js`.
- `Components/modal.js`, `Components/FiltroTabla.js`, `Components/inputFecha.js`, `Components/AUX.js`.
- `anime.min.js`, `Animacion_logo2.js`.
- `CSV_parser.js`, `collapse.js`, `float.js`, `croppie.js`, `fabricjs.js`.

**Librerías externas en `public/js/`**:
- jQuery 3.1, Bootstrap, jQuery UI, jQuery qTip, jQuery frontier calendar.
- bootstrap-datetimepicker, moment.
- Highcharts 11.3.0 (suite completa).
- FullCalendar, gcal (Google Calendar integration).
- Croppie, Fabric.js, CreateJS, iCheck, FileInput.

### 16.9 Vue.js

**Configurado pero NO utilizado realmente.** Solo existe `resources/assets/js/components/Example.vue` (componente dummy).

Todo el código del front es **jQuery + vanilla JS**.

### 16.10 CSS

`public/css/` tiene **58+ archivos**. Los principales:

**Bootstrap + extensiones:**
- `bootstrap.min.css`, `bootstrap-col-xl.css`, `bootstrap-datetimepicker.css`, `bootstrap-theme.css`.

**Sistema custom:**
- `style.css`, `component.css`, `estilosBotones.css`, `estilosModal.css`, `estilosFileInput.css`, `estilosPopUp.css`, `estilosPopover.css`, `estilosSVG.css`.
- `table-fixed.css`, `paginacion.css`, `tarjetasMenues.css`, `flaticon.css`.

**Animaciones:**
- `animacionesMenu.css`, `animacionesAlta.css`, `animacionCarga.css`, `loadingAnimation.css`.
- `mensajeExito.css`, `mensajeError.css`.

**Fuentes:**
- `importacionFuentes.css` — importa Roboto y Google Fonts.

**PDF:**
- `estiloPlanillaLandscape.css`.

**Otros:**
- `blue.css` (iCheck), `ionicons.min.css`, `colorpicker/`, `perfect-scrollbar.css`, `croppie.css`.

### 16.11 Assets gráficos

**Logos 2024:**
- `/public/img/logos/logo_2024_loteria.png`
- `/public/img/logos/logo_2024_loteria_bn.png`
- `/public/img/logos/banner_2024_portrait.png`
- `/public/img/logos/banner_2024_landscape.png`

**Iconos SVG (en `resources/assets/svg/`):**
- `home.svg`, `usuario.svg`, `casinos.svg`, `maquinas.svg`, `expedientes.svg`, `informes.svg`, `tablero_control.svg`, `tablero_modif.svg`, `bingos.svg`, `mesa.svg`, `bolsa_pesos.svg`, `camara.svg`, `salida.svg`.

**Banners e imágenes de sección** en `/public/img/`.

**Fuentes:**
- Roboto (Thin, Light, Regular, Medium, Bold, Black + Italic).
- Flaticon.
- Glyphicons.

---

## 17. PLANTILLAS PDF

Siete plantillas Blade que se renderizan con DOMPDF:

### 17.1 `planillaBeneficios.blade.php`
Control de beneficio diario mensual.

Columnas: Fecha | Usuarios | Apostado | Premios | Ajuste | Beneficio | Devolución | Poker | Cotización | Total.
- Cotización múltiple (maneja ARS + USD).
- Total = (beneficio + ajuste + poker) × cotización.

### 17.2 `planillaInformesJuegos.blade.php`
Informe mensual de beneficios por juegos.

Columnas: Juego | Apuesta (Efectivo + Bono + Total) | Premio (Efectivo + Bono + Total) | Beneficio (Efectivo + Bono + Total).

### 17.3 `planillaCompletaInformesJuegos.blade.php`
Informe completo por mes (landscape).

### 17.4 `planillaProducidos.blade.php`
Resumen de producidos en período.

Usado en `ProducidoController::generarPlanilla`.

### 17.5 `planillaDiferenciasEstadosJuegos.blade.php`
Diferencias entre estado declarado y estado esperado.

### 17.6 `planillaInformesPoker.blade.php`
Informe poker: Día | Jugadores | Droop | Utilidad | Rake | Cotización.

### 17.7 `planillaInformeDemografico.blade.php`
Datos demográficos:
- Agrupa por provincia/departamento y rango etario (menores, 18-35, 36-45, 46-55, 56-65, 66-75, 76+).
- Alertas para menores con movimiento.

### 17.8 Formato común

- Header con banner 2024 de la Lotería.
- Fuente Arial/sans-serif inline.
- Paginación automática con `$PAGE_NUM + pag_offset`.
- Código del documento en header.

---

## 18. LÓGICA DE NEGOCIO Y FÓRMULAS CRÍTICAS

### 18.1 Producido (ingresos brutos)

```
Apuesta = ApuestaEfectivo + ApuestaBono
Premio  = PremioEfectivo  + PremioBono
Beneficio = Apuesta - Premio = GrossRevenue
                              = GrossRevenueEfectivo + GrossRevenueBono

Validaciones (flag diferencia_montos):
  (apuesta_efectivo + apuesta_bono) = apuesta
  (premio_efectivo  + premio_bono)  = premio
  (beneficio_efectivo + beneficio_bono) = beneficio
  (apuesta_efectivo - premio_efectivo) = beneficio_efectivo
  (apuesta_bono - premio_bono) = beneficio_bono
```

### 18.2 Beneficio mensual (reconciliación)

```
BeneficioCalculado   = Producido.beneficio (suma aritmética real)
BeneficioRegistrado  = Beneficio.beneficio + Beneficio.ajuste + Beneficio.ajuste_auditoria

Diferencia = BeneficioCalculado - BeneficioRegistrado
```

Si `|Diferencia| < 0.01` tras redondeo → validado.

### 18.3 Indicadores estadísticos

```
PDEV (Porcentaje de Devolución) = (Premio / Apuesta) × 100

PdevTeorico = promedio(juego.porcentaje_devolucion)
PdevEsperado = sum(apuesta × porcentaje_devolucion) / sum(apuesta)
PdevProducido = (premio_total / apuesta_total) × 100

HOLD = (Beneficio / Apuesta) × 100
     = 100 - PDEV

ARPU (Average Revenue per User) = apuesta_total / jugadores_distintos

Rake (en poker) = (utilidad / total_buy) × 100
```

### 18.4 Jugador (versionado temporal)

```
hash_jugador = MD5(codigo|localidad|provincia|fecha_alta|estado|
                   fecha_autoexclusion|fecha_nacimiento|
                   fecha_ultimo_movimiento|sexo)

Vigente si: valido_hasta IS NULL OR valido_hasta >= hoy
Último estado del juego: es_ultimo_estado_del_juego = 1
```

### 18.5 Casino, plataforma, actividades

Tipos de actividad (en estadísticas):
- 1 = MTM (Máquinas Tragamonedas)
- 2 = Mesas
- 3 = Bingo

### 18.6 Canon

El canon se almacena en `beneficio_mensual.canon`. Se calcula (probablemente) como un porcentaje del beneficio, pero la fórmula específica no está hardcoded visible — se ingresa manualmente o por el operador.

### 18.7 Demografía

```
edad = DATEDIFF(NOW(), fecha_nacimiento) / 365.25

Rangos:
  Menores: edad < 18     ⚠ Alerta si tiene movimiento
  18-35
  36-45
  46-55
  56-65
  66-75
  76+
```

### 18.8 Cotización

Cotización por día (típicamente ARS→USD u otra). Si falta el día se usa **la última cotización conocida** para no cortar los reportes.

```
apuesta_cotizada = apuesta × cotizacion.valor(fecha)
```

---

## 19. FLUJOS OPERATIVOS COMPLETOS

### 19.1 Flujo de alta de un casino y plataforma

1. **Superusuario** crea el casino en `/casinos`.
2. Crea la **plataforma** asociada (código, nombre).
3. Vincula plataforma ↔ casino (pivote `plataforma_tiene_casino`).
4. Crea los **usuarios** con rol apropiado y les asigna las plataformas y casinos que pueden ver.

### 19.2 Flujo de alta de un juego

1. **Fiscalizador** o Administrador crea el juego en `/juegos`.
2. Asigna categoría, tipo_moneda, porcentaje de devolución.
3. Sube o asocia los **certificados GLI** en `/certificadoSoft`.
4. Vincula el juego a una o varias plataformas con un `estado_juego` por plataforma (pivote `plataforma_tiene_juego`).
5. Cada modificación al juego genera una entrada en `juego_log_norm` con el motivo.

### 19.3 Flujo diario de importación

1. El **operador** (casino) envía el archivo CSV/ZIP a la Lotería (manualmente o por API).
2. El **Auditor** navega a `/importaciones`.
3. Selecciona tipo de importación (Producido juegos / jugadores / poker / Beneficio / Estados).
4. Sube archivo.
5. El sistema:
   - Calcula MD5, chequea duplicados.
   - Parsea con `LectorCSVController` (LOAD DATA en tabla temporal).
   - Inserta en tablas finales.
   - Regenera resumen mensual si aplica.
   - Invalida cachés dependientes.
6. La importación aparece en el historial con su MD5 y fecha.

### 19.4 Flujo de validación de beneficio mensual

1. El auditor entra a `/beneficios`.
2. Filtra por plataforma, moneda, mes.
3. Ve la lista de `beneficio_mensual` con sus diferencias.
4. Selecciona uno y entra al detalle.
5. Por cada día ve: beneficio calculado, registrado, ajuste, ajuste_auditoria, diferencia.
6. Si hay diferencia: aplica `ajuste_auditoria` con explicación.
7. Cuando todas las diferencias son 0 (redondeado a 2 decimales) y todos los días tienen producido:
   - Click en **Validar**.
   - El sistema marca `validado = 1`.

### 19.5 Flujo de generación de informe mensual

1. Auditor entra a `/informesJuegos`.
2. Filtra por año, mes, plataforma, moneda.
3. Click en Imprimir (o JOL para Juegos Online).
4. Backend:
   - Calcula chunks (dependiendo del tamaño).
   - Invoca `PdfParalelo::crear(8, 'planillaInformesJuegos', ...)`.
   - 8 procesos hijos generan PDFs parciales.
   - `pdfunite` los une.
5. Response: descarga del PDF final.

### 19.6 Flujo de gestión de expediente

1. **Despacho** entra a `/expedientes`.
2. Crea un nuevo expediente con los 3 números (org/interno/control), concepto, tema, etc.
3. Vincula plataformas y GLI softs.
4. Agrega **notas** con fecha, detalle, identificación.
5. Crea **resoluciones** o **disposiciones** asociadas.
6. Las disposiciones pueden estar vinculadas a una nota (`es_disposicion = true`).

### 19.7 Flujo de carga de notas de casino y eventos de publicidad

1. Usuario va a `/cargar-notas`.
2. Carga una nota de evento (tipo Común, Bis, MKT, PK) con:
   - Nro, año, fecha del evento, categoría.
   - Archivos: Pautas, Diseño, Bases (PDF/ZIP/DOC).
3. El sistema genera el código (ej: "123-2024 Bis") y lo guarda en la BD secundaria.

Luego, **auditoría** carga el CSV de eventos publicitarios en `/auditoriaEventos`:
4. El sistema cruza con las notas cargadas y marca cuáles son **válidas** (fecha coincide, estado correcto, nota existe).

### 19.8 Flujo de generación de informe técnico

1. Usuario va a `/informesTecnicos`.
2. Ve lista de notas con filtros.
3. Click en "Generar informe técnico" de una nota → el sistema crea un documento.
4. Opcionalmente sube manualmente un PDF/DOC firmado.
5. El archivo se guarda en `notas_casinos` disk, carpeta `Eventos_inftec`.

### 19.9 Flujo de recuperación de contraseña (5 pasos)

Ver sección 5.2.

### 19.10 Flujo de login con CAS

1. Usuario click "Ingresar con UID".
2. Redirect a CAS_ENDPOINT?service=...
3. CAS autentica al usuario.
4. Redirect de vuelta con ticket.
5. Backend valida ticket con CAS.
6. Busca usuarios por DNI (del CUIL) o email.
7. Si hay múltiples usuarios: pantalla de selección.
8. Usuario elige, se genera sesión, redirect al home.

---

## 20. PERMISOS, ROLES Y SECCIONES

### 20.1 Permisos identificados en rutas

| Permiso                             | Sección / Funcionalidad                              |
|-------------------------------------|------------------------------------------------------|
| `ver_seccion_casinos`               | Gestión de casinos                                   |
| `ver_seccion_expedientes`           | Expedientes                                          |
| `ver_seccion_usuarios`              | Gestión de usuarios                                  |
| `ver_seccion_roles_permisos`        | Roles y permisos                                     |
| `ver_seccion_juegos`                | Juegos (CRUD + carga masiva)                         |
| `ver_seccion_logs_actividades`      | Auditoría de actividades                             |
| `ver_seccion_disposiciones`         | Disposiciones                                        |
| `ver_seccion_resoluciones`          | Resoluciones y notas                                 |
| `ver_cargar_notas`                  | Carga de notas de casino                             |
| `ver_seccion_informes_tecnicos`     | Informes técnicos                                    |
| `ver_seccion_auditoria_eventos`     | Auditoría de eventos publicitarios                   |
| `ver_seccion_glisoft`               | Certificados GLI Soft                                |
| `ver_seccion_importaciones`         | Importación de datos                                 |
| `ver_seccion_producidos`            | Visualización y validación de producidos             |
| `ver_seccion_beneficios`            | Reconciliación de beneficios                         |
| `ver_seccion_estestadoparque`       | Informe de estado del parque / plataforma            |
| `ver_seccion_informecontable`       | Informe contable, estado de juegos, estado de jugadores |
| `informes_mtm`                      | BackOffice + Informes MTM                            |
| `m_eliminar_cierres_y_aperturas`    | Permite eliminar cierres y aperturas de mesas        |
| `estadisticas_generales`            | Permite ver las estadísticas generales del dashboard |
| `estadisticas_por_casino`           | Stats por plataforma                                 |
| `estadisticas_interanuales`         | Stats interanuales                                   |
| `ver_seccion_ae_alta`               | Link externo de autoexclusión                        |

### 20.2 Roles

| ID | Nombre        | Responsable de…                                                             |
|----|---------------|------------------------------------------------------------------------------|
| 1  | Superusuario  | Todo (incluyendo regeneración de resúmenes, acciones de sistema).            |
| 2  | Administrador | Configuración, usuarios, roles, permisos.                                    |
| 3  | Fiscalizador  | Validación de producidos y beneficios, auditoría.                            |
| 4  | Control       | Control operativo diario.                                                    |
| 6  | Despacho      | Gestión de expedientes, resoluciones, disposiciones, notas.                  |
| 9  | Casino AE     | Usuario del casino externo (autoexclusión).                                  |

### 20.3 Reglas específicas

- `Usuario.elimina_cya` = tiene permiso `m_eliminar_cierres_y_aperturas`.
- `Usuario.es_controlador` = administrador OR superusuario OR control.
- `Casino AE` es un rol externo del casino, no de la Lotería, con acceso restringido.


---

## 21. RIESGOS, DEUDAS TÉCNICAS Y OBSERVACIONES

### 21.1 Problemas de seguridad (críticos)

1. **Contraseñas en texto plano en la tabla `usuario.password`**. No hay hash (bcrypt, argon, ni siquiera md5). Un leak de BD compromete todas las credenciales. **Debe corregirse urgentemente con un flow de rehash incremental**.
2. **`DB_PASSWORD=prueba`** en `.env`. Es una BD de pruebas pero el credential está committeado.
3. **`APP_DEBUG=true`** en `.env` — si se despliega así a producción expone stack traces.
4. **`PERMISSIONS_DIRECTORY=0777`** y `PERMISSIONS_FILE=0777` como defaults — demasiado abierto.
5. **DOMPDF con `isRemoteEnabled=true`** — puede descargar recursos externos, lo que abre puerta a SSRF si los datos son manipulables.
6. **No hay rate limiting** en `CheckAPIToken` ni en login (aparte del throttle default de Laravel 60/min en API).
7. **CSRF desactivado posiblemente** en rutas AJAX — verificar.
8. **Imágenes de usuario en base64 en BD** — impacta performance y tamaño de la BD.
9. **Claves MD5 como identificadores** de archivo — no hay protección anti-colisión.
10. **X-API-Key de osTicket hardcodeado** en `web.php` (línea 467): `'X-API-Key: 14C4C2A8161F6728C74D92C58B6DF990'`.

### 21.2 Problemas arquitecturales

1. **No hay migraciones** — la BD vive directamente y cualquier cambio es manual. Riesgo alto ante rollback o deployment multi-ambiente.
2. **Patrón Singleton manual** (`getInstancia()`) en lugar del contenedor de Laravel — código menos testeable.
3. **`$timestamps = false`** en casi todos los modelos — no hay created_at/updated_at de auditoría nativa (se suplanta con Observers + tabla `log`).
4. **Vistas SQL creadas en runtime** (`CREATE OR REPLACE VIEW`) desde controladores — cada request recrea la vista, con overhead.
5. **Tablas temporales creadas dentro de transacciones** — MySQL tiene limitaciones con esto.
6. **Hardcoded URLs** (`http://10.1.121.30:8000/calendario_eventos`, `http://10.1.121.25/osTicket/...`).
7. **Logger oficial de Laravel no aprovechado** — debugging complicado.
8. **Broadcast y Queue configurados pero no usados**.
9. **Uso mixto de Eloquent y DB::table raw** — los queries raw saltan observers.
10. **Autenticación tipada manualmente** — no usa `Auth::check()` ni middleware estándar de Laravel.
11. **Nombre de archivo `informesController.php` en minúscula** — no sigue convención PSR.
12. **Manejador de excepciones casi vacío** — todas las errores van al default.

### 21.3 Problemas de rendimiento

1. **Imágenes base64 en BD** (campo `usuario.imagen`).
2. **BLOBs del PDF de GLI en campo `archivo.archivo`** — escalable peor que un blob storage.
3. **Vistas SQL no materializadas** — queries complejas se re-ejecutan en cada uso.
4. **Cálculos estadísticos sin indices adecuados** — dependemos de la tabla desnormalizada `resumen_mensual_producido_jugadores`.
5. **Carga de CSVs geográficos en memoria** (en `distribucionJugadores`) en cada request.
6. **PDFs paralelos con timeout de 120s** — informes muy grandes pueden exceder.

### 21.4 Deudas de frontend

1. **Vue instalado pero no usado** — incoherencia.
2. **jQuery 3.1** — relativamente antiguo.
3. **Bootstrap 3** — end-of-life desde 2019.
4. **Montón de JS inline** en blades.
5. **Scripts por sección** — cada vista tiene `seccion*.js` sin modularización real.
6. **Sin build de producción optimizado** — webpack mix no en modo production visible.
7. **Sin TypeScript**, sin tests JS.
8. **~58 archivos CSS sin minificación integrada**.

### 21.5 Observaciones menores / miscelánea

- `routes/web.php` contiene lógica pesada inline (funciones `Route_olvideMiContrasena`, `Route_CAS`, handler de ticket) que deberían estar en controladores.
- El README es escueto y no documenta el sistema.
- `database/seeds/DatabaseSeeder.php` vacío — no hay datos de ejemplo.
- `ModelFactory.php` solo tiene Factory default de User.
- Mucho código comentado en `Exceptions/Handler.php` sugiere work in progress.
- Estructura de permisos se basa en descripciones (strings), no en IDs — cambiar el texto rompe todo.
- `PUSHER_APP_ID` vacío pero el driver `Pusher` está instalado.
- `QUEUE_DRIVER=sync` — procesos pesados bloquean la request.

### 21.6 Ejemplos de commits recientes (deuda técnica activa)

```
0b73cb49 Informe Contable: chunckear busqueda de jugadores a ver si es mas rapido
2f639ebf Informe Contable: chunckear busqueda de jugadores a ver si es mas rapido
5196cdd7 Informe Contable: homogeineizar la interface y de paso arreglar la busqueda de usuarios
3f6a97ef API listado de juegos: agregar una verificación minima de token
```

Se evidencia trabajo activo sobre performance de búsquedas y hardening de la API.

---

## 22. GLOSARIO

| Término                 | Definición                                                                              |
|-------------------------|-----------------------------------------------------------------------------------------|
| **MTM**                 | Máquinas Tragamonedas.                                                                  |
| **Producido**           | Totalización diaria de ingresos brutos de una plataforma.                               |
| **Beneficio**           | Lo equivalente al "net win" o ganancia del casino después de pagar premios.             |
| **Ajuste**              | Modificación al beneficio declarado por el operador (corrección de errores, retroactividades). |
| **Ajuste de auditoría** | Ajuste que aplica el auditor de la Lotería tras detectar una diferencia.                |
| **Canon**               | Dinero que la Lotería cobra al operador por mes.                                        |
| **PDEV (Porcentaje de Devolución)** | % del dinero apostado que se devuelve como premio. PDEV = (Premio / Apuesta) × 100. También conocido como RTP (Return to Player). |
| **HOLD**                | Retención del casino = 100 - PDEV = (Beneficio / Apuesta) × 100.                        |
| **ARPU**                | Average Revenue Per User = ingresos / jugadores distintos.                              |
| **Apuesta efectivo**    | Dinero real apostado.                                                                   |
| **Apuesta bono**        | Apuesta hecha con créditos de bonos (cortesía).                                         |
| **Droop (Drop)**        | En poker: el rake o total de fichas apostadas.                                          |
| **Utilidad (poker)**    | Ganancia del casino en poker tras pagar los pots.                                       |
| **GLI (Gaming Labs International)** | Laboratorio mundial de certificación de juegos.                                |
| **GLI Soft**            | Certificado de software emitido por un laboratorio para un juego específico.            |
| **Plataforma**          | Operador de casino online (ej. CCOL, BPLAY).                                            |
| **Casino**              | Instancia del casino (físico o legal).                                                  |
| **Expediente**          | Documento administrativo con número y trámite.                                           |
| **Resolución**          | Acto administrativo formal asociado a un expediente.                                     |
| **Disposición**         | Decisión normativa, puede ser un subtipo de nota.                                        |
| **CAS**                 | Central Authentication Service — sistema de autenticación federada del gobierno.         |
| **UID**                 | Usuario Único Digital — identidad federada del gobierno de Santa Fe.                    |
| **Autoexclusión**       | Procedimiento por el cual un jugador se excluye voluntariamente.                        |
| **Jugador válido**      | Registro con `valido_hasta IS NULL` o mayor a la fecha consultada.                      |
| **Nota (de casino)**    | Documento que el casino presenta relacionado a eventos/promociones (BD secundaria).     |
| **Evento publicidad**   | Promoción o concurso del casino, con fechas, categorías, juegos vinculados.             |

---

## 23. GUÍA PARA REIMPLEMENTAR EL SISTEMA DESDE CERO

Si querés hacer otro sistema basándote en éste, aquí hay una hoja de ruta razonable:

### 23.1 Decisiones iniciales

| Aspecto                 | Recomendación moderna                                      |
|-------------------------|------------------------------------------------------------|
| Framework backend       | **Laravel 10/11** (migrar de 5.4) o **Django 5** o **NestJS**. |
| Base de datos           | **PostgreSQL 15+** (mejor para reportes, CTEs, JSON).      |
| Manejo de esquema       | **Migraciones versionadas** obligatorias.                  |
| Auth                    | Laravel Sanctum o JWT + integración CAS reutilizable.      |
| Contraseñas             | **bcrypt o argon2** (nunca texto plano).                   |
| Frontend                | **Vue 3 + Vite + Pinia** o **React + Vite + TanStack Query**. UI: **PrimeVue** o **Ant Design**. |
| Gráficos                | **Apache ECharts** o **Recharts** (modernos, más livianos que Highcharts). |
| PDFs                    | **Puppeteer** o **WeasyPrint** (mejor layout que DOMPDF). Queue con Redis. |
| Archivos                | **S3** o **MinIO** (no blobs en BD).                       |
| Queue                   | **Laravel Horizon + Redis**.                               |
| Caché                   | **Redis** (con tags nativos para invalidación por dependencias). |
| Testing                 | **PHPUnit + Pest** (backend), **Vitest + Playwright** (front). |

### 23.2 Orden de construcción

1. **Auth + Users + Roles + Permissions** (con bcrypt, JWT/Sanctum, middleware).
2. **Entidades maestras**: Casinos, Plataformas, TipoMoneda, CategoriaJuego, EstadoJuego.
3. **Juegos** con historial versionado.
4. **GLI Soft, Laboratorios, Archivos**.
5. **Expedientes, Notas, Disposiciones, Resoluciones**.
6. **Importaciones**: crear el pipeline CSV → staging → producción, con chunking y workers.
7. **Producido** (juegos, jugadores, poker).
8. **Beneficio mensual** + reconciliación.
9. **Jugadores** con versionado temporal.
10. **Dashboard estadístico**: PDEV, HOLD, ARPU, distribución geográfica.
11. **Informes PDF**: plantillas Blade/Twig/React-PDF. Primero sincrónicos, luego paralelos con workers.
12. **Calendario y notificaciones**.
13. **Notas de casino** + eventos de publicidad.
14. **Integración CAS** y **osTicket**.
15. **API externa** para consumo por sistemas terceros.
16. **Observabilidad**: logs estructurados, métricas, tracing (OpenTelemetry).
17. **Testing**: unit + feature + e2e.
18. **CI/CD** con migraciones automáticas y rollback seguro.

### 23.3 Modelo de datos sugerido (pivote limpio)

Mantener el core tal como está, pero:

- Agregar UUID como PK secundaria donde corresponda (jugador, apitoken).
- Usar `timestamps` (created_at/updated_at) en todas las tablas.
- Usar `soft_deletes` selectivamente.
- Reemplazar el campo `dependencias` como string `|x|y|` por una tabla pivot `cache_dependencia`.
- Mover PDFs/imágenes a storage de archivos, no BLOB.
- Separar `password_hash` de `password` (nunca guardar texto plano).

### 23.4 Mejoras al algoritmo de importación

1. Usar **pipelines** (ej. Laravel Pipelines o un proceso tipo Kafka Connect) en lugar de LOAD DATA raw.
2. Validar el CSV contra un esquema (schema validation).
3. Encolar el procesamiento (no bloquear la request).
4. Mostrar progreso al usuario vía WebSockets / SSE.
5. Checkpointing para reanudar importaciones interrumpidas.
6. Generación automática del MD5 y detección de duplicados idempotente.

### 23.5 Mejoras al informe y reportes

1. Precalcular estadísticas en una tabla materializada refrescada por un cron.
2. Usar CTEs y window functions de PostgreSQL en vez de vistas SQL y tablas temporales.
3. Generación de PDFs con workers dedicados y cola (no `proc_open`).
4. Cachear PDFs generados (si los inputs no cambiaron, reusar).
5. Ofrecer export a **Excel** (xlsx) y **CSV** como formatos alternativos de forma consistente.

### 23.6 Mejoras al frontend

1. **SPA única** con router, no server-side rendering de blades por sección.
2. **Componentes reutilizables**: tabla paginada, modal, filtros, upload.
3. **TypeScript** para tipar contratos API.
4. **Storybook** para componentes.
5. **A11y**: accesibilidad básica (Highcharts ya la tiene, el resto también).
6. **i18n** si eventualmente hay soporte multi-idioma.

### 23.7 Seguridad y compliance

1. **Rotación de tokens**.
2. **Rate limiting** por IP y por usuario.
3. **2FA** para usuarios privilegiados.
4. **Audit log inmutable** (append-only).
5. **Cifrado en reposo** para campos sensibles (DNI, datos de jugadores).
6. **Políticas claras de retención** (LGPD/LFPDPPP-equivalente argentino: Ley 25.326).
7. **Penetration testing** periódico.

### 23.8 Puntos críticos a replicar tal cual

Aunque el código tiene deudas, estos aspectos del sistema son **correctos funcionalmente** y deben replicarse:

1. **Separación Producido vs Beneficio mensual** y la lógica de reconciliación con ajustes y ajuste_auditoria.
2. **Versionado temporal de jugadores** (fechas de vigencia).
3. **Diferenciación de efectivo vs bono** en las apuestas/premios/beneficios.
4. **Multi-moneda** con cotización diaria.
5. **Historial de cambios en juegos** (`juego_log_norm`).
6. **Roles y permisos granulares** por sección.
7. **Logs de auditoría** automáticos via observers.
8. **Caché con invalidación por dependencias**.
9. **Informes paralelos** para evitar timeouts.

---

## APÉNDICE A — Lista completa de vistas Blade

```
resources/views/
  calendar.blade.php
  componenteBotonAbreModal.blade.php
  componentePaginadoInformePlataforma.blade.php
  error.blade.php
  index.blade.php
  informe_juego.blade.php
  planillaBeneficios.blade.php
  planillaCompletaInformesJuegos.blade.php
  planillaDiferenciasEstadosJuegos.blade.php
  planillaInformeDemografico.blade.php
  planillaInformesJuegos.blade.php
  planillaInformesPoker.blade.php
  planillaProducidos.blade.php
  recuperarContrasena.blade.php
  seccionBackoffice.blade.php
  seccionBeneficios.blade.php
  seccionCasinos.blade.php
  seccionConfigCuenta.blade.php
  seccionDisposiciones.blade.php
  seccionEstadisticasGenerales.blade.php
  seccionEstadisticasInteranuales.blade.php
  seccionEstadisticasPorCasino.blade.php
  seccionExpedientes.blade.php
  seccionGLISoft.blade.php
  seccionImportaciones.blade.php
  seccionInformeEstadoJuegos.blade.php
  seccionInformeEstadoJugadores.blade.php
  seccionInformePlataforma.blade.php
  seccionInformesJuegos.blade.php
  seccionInicio.blade.php
  seccionJuegos.blade.php
  seccionJuegosComponenteCargaMasiva.blade.php
  seccionLogActividades.blade.php
  seccionNotasExpediente.blade.php
  seccionProducidos.blade.php
  seccionResoluciones.blade.php
  seccionRolesPermisos.blade.php
  seccionUsuarios.blade.php

  AuditoriaEventos/
    indexAuditoriaEventos.blade.php

  NotasCasino/
    indexInformesTecnicos.blade.php
    indexNotasCasino.blade.php
    previewInfTecnico.blade.php

  Components/
    FiltroTabla.blade.php
    include_guard.blade.php
    inputFecha.blade.php
    modal.blade.php
    modalEliminar.blade.php

  includes/
    barraMenuPrincipal.blade.php
    barraMenuPrincipal_desplegable.blade.php
    barraMenuPrincipal_link.blade.php
    barraMenuPrincipal_texto_con_icono.blade.php
    dashboard.blade.php
    menuDesplegable.blade.php
    menuDesplegable_desplegable.blade.php
    menuDesplegable_link.blade.php
    notifications/
      calendario_evento.blade.php

  ayudas/
    modalExpedientes.blade.php

  errors/
    404.blade.php

  lang/

  vendor/
    mail/
    notifications/
      email.blade.php
    pagination/
```

## APÉNDICE B — Lista completa de controladores

```
app/Http/Controllers/
  AuthenticationController.php
  BackOfficeController.php
  BeneficioController.php
  BeneficioMensualController.php
  CacheController.php
  CalendarioController.php
  CasinoController.php
  Controller.php (base)
  CotizacionController.php
  DetalleLogController.php
  DisposicionController.php
  EstadoController.php
  ExpedienteController.php
  GliSoftController.php
  ImportacionController.php
  InformeContableController.php
  InformePlataformaController.php
  InformesGeneralesController.php
  JuegoController.php
  LectorCSVController.php
  LogController.php
  NotaController.php
  OlvideMiContrasenaController.php
  PermisoController.php
  ProducidoController.php
  ResolucionController.php
  ResumenController.php
  RolController.php
  UsuarioController.php
  informesController.php

  AuditoriaEventos/
    AuditoriaEventosController.php

  NotasCasino/
    NotasCasinoController.php
    InformesTecnicosController.php

  Auth/
    ForgotPasswordController.php
    LoginController.php
    RegisterController.php
    ResetPasswordController.php
```

## APÉNDICE C — Lista de modelos

```
app/
  APIToken.php
  Archivo.php
  Beneficio.php
  BeneficioMensual.php
  BeneficioMensualPoker.php
  BeneficioPoker.php
  Cache.php
  Casino.php
  CategoriaJuego.php
  Clima.php
  Cotizacion.php
  DatosJuegoImportado.php
  DetalleLog.php
  DetalleProducido.php
  DetalleProducidoJugadores.php
  DetalleProducidoPoker.php
  Disposicion.php
  EstadoJuego.php
  EstadoJuegoImportado.php
  Evento.php
  Expediente.php
  GliSoft.php
  ImportacionEstadoJuego.php
  ImportacionEstadoJugador.php
  Juego.php
  Jugador.php
  Laboratorio.php
  Log.php
  LogJuego.php
  Nota.php
  PdfParalelo.php    (clase auxiliar, no modelo)
  Permiso.php
  Plataforma.php
  Producido.php
  ProducidoJugadores.php
  ProducidoPoker.php
  Resolucion.php
  Rol.php
  SecRecientes.php
  TipoEvento.php
  TipoMoneda.php
  Usuario.php
```

## APÉNDICE D — Middleware

```
app/Http/Middleware/
  CheckAPIToken.php          (check_API_token)
  CheckAnyPermission.php     (tiene_algun_permiso)
  CheckPermission.php        (tiene_permiso)
  CheckRole.php              (tiene_rol)
  CheckSessionTime.php       (timeout 4h)
  CheckUserToken.php         (validar token de sesión)
  EncryptCookies.php         (estándar Laravel)
  RedirectIfAuthenticated.php (estándar Laravel)
  TrimStrings.php            (estándar Laravel)
  VerifyCsrfToken.php        (estándar Laravel)
```

## APÉNDICE E — Observers

```
app/Observers/
  EntityObserver.php         (base abstracta)
  FullObserver.php           (registra todos los atributos)
  ParametrizedObserver.php   (registra atributos específicos)
  ArchivoObserver.php        (customizado para archivo)
```

## APÉNDICE F — Tablas de BD (resumen rápido)

### BD principal (`bdMTM_OL`)

**Autenticación (4 + 4 pivotes):**
- usuario, rol, permiso, API_token
- usuario_tiene_rol, rol_tiene_permiso, usuario_tiene_plataforma, usuario_tiene_casino

**Casinos/Plataformas (2 + 1 pivot):**
- casino, plataforma
- plataforma_tiene_casino

**Juegos (5 + 4 pivotes):**
- juego, juego_log_norm, categoria_juego, estado_juego, tipo_moneda
- plataforma_tiene_juego, plataforma_tiene_juego_log_norm, juego_glisoft, juego_log_norm_glisoft

**GLI (3):**
- gli_soft, archivo, laboratorio

**Expedientes (4 + 2 pivotes):**
- expediente, resolucion, disposicion, nota
- expediente_tiene_plataforma, expediente_tiene_gli_sw

**Producido (6):**
- producido, detalle_producido, producido_jugadores, detalle_producido_jugadores, producido_poker, detalle_producido_poker
- resumen_mensual_producido_jugadores (desnormalizada)

**Beneficios (4):**
- beneficio_mensual, beneficio, beneficio_mensual_poker, beneficio_poker

**Jugadores y estados (4):**
- jugador, importacion_estado_jugador, importacion_estado_juego, estado_juego_importado, datos_juego_importado

**Calendario (2):**
- evento, tipo_evento

**Auditoría (2):**
- log, detalle_log

**Cotizaciones (1):**
- cotizacion

**Cache custom (1):**
- cache

**Auxiliares (3):**
- sec_recientes, clima, recuperar_contrasena (creada dinámicamente)

**Tablas estándar de Laravel:**
- notifications (notifs guardadas), password_resets, jobs, failed_jobs (no usadas)

### BD secundaria (`gestion_notas_mysql` / `eventos_casinos`)

- categorias, tipo_eventos, juegos (local), tipos_nota, eventos, juegos_nota, evento_publicidad_casino

### Vistas SQL

- detalle_producido_juego
- detalle_producido_diferencias
- beneficios_diferencias_dias (subconsulta inline)

### Tablas temporales

- producido_temporal, producido_jugadores_temporal, producido_poker_temporal
- beneficio_temporal, juego_importado_temporal
- temp_jugadores_validos_a_f_imp, temp_estados_dias
- (otras con nombre dinámico según request)

---

## APÉNDICE G — Diagrama ASCII del flujo de datos

```
  ┌─────────────────────┐
  │  Operadores casino  │
  │  (CCOL, BPLAY, ...) │
  └──────────┬──────────┘
             │ CSV/ZIP diario
             ▼
  ┌─────────────────────────────────────┐
  │  ImportacionController              │
  │  → LectorCSVController              │
  │    → tablas temporales              │
  │    → producido / producido_poker /  │
  │      producido_jugadores /          │
  │      beneficio / jugador / ...      │
  └──────────┬──────────────────────────┘
             │
             ▼
  ┌─────────────────────────────────────┐
  │  ProducidoController                │
  │  → vista detalle_producido_juego    │
  │  → vista detalle_producido_diferencias │
  │                                     │
  │  BeneficioController                │
  │  → reconcilia Producido vs          │
  │    Beneficio importado              │
  │  → Ajustes (ajuste, ajuste_audit)   │
  │  → Validación marca validado=1      │
  └──────────┬──────────────────────────┘
             │
             ▼
  ┌─────────────────────────────────────┐
  │  Informes y Estadísticas            │
  │  - InformeContableController        │
  │  - InformePlataformaController      │
  │  - InformesGeneralesController      │
  │  - BeneficioMensualController       │
  │  - BackOfficeController             │
  │  - informesController (PDFs)        │
  └──────────┬──────────────────────────┘
             │
             ▼
  ┌─────────────────────────────────────┐
  │  Canal de salida                    │
  │  • Gráficos Highcharts en dashboard │
  │  • PDFs paralelos (PdfParalelo)     │
  │  • Exportes CSV                     │
  │  • API JSON                         │
  └─────────────────────────────────────┘

  Paralelamente:
  ┌─────────────────────────────────────┐
  │  Gestión administrativa             │
  │  - Casinos, Plataformas, Juegos     │
  │  - GLI Soft, Archivos               │
  │  - Expedientes, Notas, Resoluciones │
  │  - Usuarios, Roles, Permisos        │
  │  - Calendario, Notas de casino      │
  └─────────────────────────────────────┘

  Auditoría:
  ┌─────────────────────────────────────┐
  │  Observers → Logs                   │
  │  ProducidoController::validar       │
  │  AuditoriaEventosController         │
  │  (cruza CSV con BD secundaria)      │
  └─────────────────────────────────────┘
```

---

## NOTA FINAL

Este documento es el resultado de un **análisis exhaustivo del código fuente** del sistema. No se ejecutó el sistema en vivo, por lo que ciertos detalles (particularmente sobre estructura exacta de tablas sin modelos, valores por defecto, y comportamiento en condiciones de borde) son **inferencias basadas en el código**.

Si vas a reimplementar el sistema:
1. Verificá los esquemas reales con `DESCRIBE tabla` en MySQL.
2. Observá el comportamiento real con datos de prueba.
3. Revisá los commits recientes (especialmente los de `Informe Contable`) para entender las mejoras en curso.
4. Considerá hacer una auditoría de seguridad completa antes de cualquier deploy con datos sensibles reales.

**Total del documento:** ~10000+ líneas de análisis.

---
*Documento generado automáticamente tras análisis profundo del codebase — 2026-04-22.*
