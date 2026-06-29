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

### Dependencias en otros directorios

- `routes/web.php`: Se agregó la línea `require __DIR__ . '/../aadd/routes.php';` para importar las rutas del módulo.
- `composer.json`: Se agregó `autoload.psr-4.AADD` para que el autoloader cargue el namespace `AADD/`.
- `app/Providers/AppServiceProvider.php`: Se agregó la línea `View::addNamespace('aadd', base_path('aadd/Views'));` en el método `boot()` para poder definir vistas fuera de `resources/views/` (en `aadd/Views`).
- `resources/views/includes/barraMenuPrincipal.blade.php`: Se agregó la línea `@include('aadd::menu_item')` para que el submódulo sea accesible desde la barra de menú principal mediante su propio componente.

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

## Configuración del Namespace de Vistas

Para evitar cargar las plantillas mediante rutas absolutas y permitir una integración limpia, se registra el namespace de vistas `aadd` en `app/Providers/AppServiceProvider.php` en el método `boot()`:

```php
use Illuminate\Support\Facades\View;

public function boot()
{
    View::addNamespace('aadd', base_path('aadd/Views'));
}
```

Esto permite hacer referencia a las vistas del submódulo usando el prefijo `aadd::` (por ejemplo: `@include('aadd::menu_item')` o `view('aadd::index')`).

## Enrutamiento y Control de Acceso

Las rutas definidas en `aadd/routes.php` se cargan dinámicamente mediante la inclusión del archivo desde `routes/web.php`.
Dado que se incluyen en `routes/web.php`, heredan el grupo de middleware `web` principal de la aplicación.

### Rutas definidas:
- **`GET /aadd`**
  - **Acción:** `AADD\Http\Controllers\AaddController@index`
  - **Middleware:** `tiene_permiso` (fuerza la autenticación básica del usuario)
  - **Comportamiento:** Renderiza la vista visual `index.blade.php` a través de la carga por namespace (`aadd::index`).

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
