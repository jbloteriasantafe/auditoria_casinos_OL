# Documentación del Submódulo Temporal (AADD)

Este documento detalla el diseño, la estructura y la integración del submódulo temporal `AADD` dentro del sistema de Auditoría de Casinos.

## Estructura del Submódulo

El submódulo está contenido enteramente bajo la raíz del directorio `aadd/` en el proyecto y se organiza de la siguiente manera:

- `aadd/`
  - `documentacion.md` (Este archivo de documentación)
  - `routes.php` (Definición de rutas específicas para el submódulo)
  - `Http/`
    - `Controllers/`
      - `AaddController.php` (Controlador encargado de responder a las rutas)
  - `Views/`
    - `index.blade.php` (Plantilla de presentación visual de la sección)
    - `menu_item.blade.php` (Botón de acceso directo en la barra de navegación)
  - `Providers/`
    - `AaddServiceProvider.php` (Proveedor de servicios para inicializar y desacoplar vistas y migraciones. Análogo a `app/Providers/appServiceProvider.php`)
  - `Database/`
    - `Migrations/` (Migraciones exclusivas de la base de datos del módulo)
  - `Entities/` (Entidades/Modelos Eloquent del módulo)
    - `Archivo.php`
    - `BeneficioDiario.php`
    - `BeneficioMensual.php`
  - `Actions/` (Acciones/Casos de uso del módulo)
    - `CargarArchivoAction.php` (Guarda el archivo subido en la base de datos)
    - `ProcesarArchivoAction.php` (Mapea el contenido del archivo usando la estrategia correspondiente y lo persiste)
    - `Estrategias/`
      - `EstrategiaMapeo.php` (Interfaz de estrategia de mapeo)
      - `MapeadorGenerico.php` (Mapeador placeholder para testing)
  - `Enums/` (Clases emuladoras de enums)
    - `Plataforma.php`
    - `TipoArchivo.php`

### Dependencias en otros directorios

- `routes/web.php`: Se agregó la línea `require __DIR__ . '/../aadd/routes.php';` para importar las rutas del módulo.
- `composer.json`: Se agregó `autoload.psr-4.AADD` para que el autoloader cargue el namespace `AADD/`.
- `config/app.php`: Se registró el proveedor de servicios `AADD\Providers\AaddServiceProvider::class` dentro del arreglo de `providers`.
- `resources/views/includes/barraMenuPrincipal.blade.php`: Se agregó la línea `@include('aadd::menu_item')` para que el submódulo sea accesible desde la barra de menú principal mediante su propio componente.

---

## Configuración de Autoload (Composer)

El espacio de nombres `AADD` ha sido mapeado a la carpeta `aadd/` a través de PSR-4 en el archivo `composer.json`:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "AADD\\": "aadd/"
    }
}
```

> [!IMPORTANT]
> Recuerde que para habilitar este mapeo es indispensable regenerar el autoloader de Composer ejecutando:
> ```bash
> composer dump-autoload
> ```

---

## Configuración del Proveedor de Servicios (`AaddServiceProvider`)

Para desacoplar el módulo y facilitar su posterior extracción, todas las inicializaciones se gestionan a través de `AADD\Providers\AaddServiceProvider`. En su método `boot()` se registran:

1. **El espacio de nombres de vistas (`aadd`)**: Permite referenciar las vistas con el prefijo `aadd::` (por ejemplo: `aadd::index`).
2. **Las migraciones del módulo**: Informa a Laravel dónde buscar las migraciones específicas de este módulo.

```php
public function boot()
{
    // Registrar el espacio de nombres para las vistas del módulo AADD
    View::addNamespace('aadd', base_path('aadd/Views'));

    // Cargar las migraciones del módulo de forma automática
    $this->loadMigrationsFrom(base_path('aadd/Database/Migrations'));
}
```

---

## Gestión de Base de Datos y Migraciones

El módulo cuenta con su propio directorio de migraciones bajo `aadd/Database/Migrations/`.

### Crear una nueva migración
Para crear una migración que pertenezca exclusivamente a este módulo, se debe utilizar el comando de Artisan indicando la ruta mediante el parámetro `--path`:

```bash
php artisan make:migration crear_tabla_ejemplo_aadd --path=aadd/Database/Migrations
```

### Ejecutar las migraciones
Debido a que el módulo registra su ruta de migraciones mediante el método `loadMigrationsFrom` en `AaddServiceProvider`, no es necesario especificar la ruta al ejecutar las migraciones globales:

```bash
php artisan migrate
```

Las migraciones de este módulo se ejecutarán automáticamente junto con las de la aplicación principal.

---

## Enrutamiento y Control de Acceso

Las rutas definidas en `aadd/routes.php` se cargan dinámicamente mediante la inclusión del archivo desde `routes/web.php`.
Dado que se incluyen en `routes/web.php`, heredan el grupo de middleware `web` principal de la aplicación.

### Rutas definidas:
- **`GET /aadd`**
  - **Acción:** `AADD\Http\Controllers\AaddController@index`
  - **Middleware:** `tiene_permiso` (fuerza la autenticación básica del usuario)
  - **Comportamiento:** Renderiza la vista visual `index.blade.php` a través de la carga por namespace (`aadd::index`).

---

## Controladores y Vistas

### `AaddController.php`
Encargado de interceptar la petición a `/aadd` y retornar la vista.
No realiza registros transversales de secciones recientes para evitar alterar la navegación estándar del usuario.
Usa la vista namespaced: `return view('aadd::index');`.

### `index.blade.php`
La vista utiliza la directiva `@extends('includes.dashboard')` para integrarse estéticamente con el diseño del resto del sistema.
Presenta una tarjeta con la información del submódulo y el estado de la integración.

### `menu_item.blade.php`
Componente aislado que contiene la estructura HTML de la tarjeta del menú principal para acceder al submódulo AADD. Se incluye dinámicamente en `barraMenuPrincipal.blade.php` sin ensuciar el menú principal de la aplicación.
