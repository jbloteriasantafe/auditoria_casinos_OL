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

## Enrutamiento y Control de Acceso

Las rutas definidas en `aadd/routes.php` se cargan dinámicamente mediante la inclusión del archivo desde `routes/web.php`.
Dado que se incluyen en `routes/web.php`, heredan el grupo de middleware `web` principal de la aplicación.

### Rutas definidas:
- **`GET /aadd`**
  - **Acción:** `AADD\Http\Controllers\AaddController@index`
  - **Middleware:** `tiene_permiso` (fuerza la autenticación básica del usuario)
  - **Comportamiento:** Renderiza la vista visual `aadd/Views/index.blade.php` a través de la carga por ruta absoluta.

## Controladores y Vistas

### `AaddController.php`
Encargado de interceptar la petición a `/aadd` y retornar la vista.
No realiza registros transversales de secciones recientes para evitar alterar la navegación estándar del usuario.

### `index.blade.php`
La vista utiliza la directiva `@extends('includes.dashboard')` para integrarse estéticamente con el diseño del resto del sistema.
Presenta una tarjeta con la información del submódulo y el estado de la integración.
