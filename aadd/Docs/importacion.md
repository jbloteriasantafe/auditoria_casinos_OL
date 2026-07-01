# Documentación del Módulo de Importación (AADD)

Este documento detalla la arquitectura, el flujo de datos y el funcionamiento del submódulo de importaciones múltiples implementado en `AADD`.

## 1. Interfaz de Usuario (Frontend)

La pantalla principal de importaciones (`importaciones.blade.php`) contiene un botón central que despliega una ventana modal con el formulario de subida. 

### El Modal (`modalImportadorAadd.blade.php`)
- **Subida Múltiple**: El `<input type="file">` cuenta con el atributo `multiple`, lo que permite al usuario seleccionar varios archivos (con extensiones válidas: `.csv, .xlsx, .xls`) al mismo tiempo.
- **Previsualización Dinámica**: Al seleccionar archivos, un script en jQuery captura el evento `change` del input y genera una tabla dinámica (`#tbodyArchivosPreview`).
- **Asignación de Plataformas**: Por cada archivo seleccionado, se renderiza una fila en la tabla que incluye el nombre del archivo y un campo `<select>` obligatorio. Este select permite vincular cada archivo específico a una de las plataformas soportadas (BPLAY, CITYCENTER, JUGADON, BETWARRIOR, BETSON, BET365).

### Flujo AJAX
Al hacer clic en el botón "IMPORTAR":
1. El script verifica que **todos** los archivos en la tabla tengan una plataforma seleccionada.
2. Se instancia un objeto `FormData`.
3. Se itera sobre las filas de la tabla para empaquetar en arrays paralelos:
   - `archivos[]`: El archivo físico capturado en memoria.
   - `plataformas[]`: El valor del `select` correspondiente.
4. Se envía una petición `POST` asíncrona hacia la ruta `/aadd/importar`.
5. Durante la espera, el botón y los campos se bloquean para prevenir dobles envíos.

---

## 2. Lógica de Servidor (Backend)

La petición es recibida por el método `importar()` en `AaddController.php`.

### Procesamiento de Lotes
El controlador recibe los arrays paralelos desde el objeto `Request`:
- `$archivos = $request->file('archivos');`
- `$plataformas = $request->input('plataformas');`

Mediante un bucle `foreach`, el backend itera archivo por archivo extrayendo su plataforma equivalente según el índice del array.

### Validación y Guardado Físico
1. **Verificación de Extensión**: Asegura que el archivo tenga una extensión permitida en el array estricto `['csv', 'xlsx', 'xls']`.
2. **Generación de Nombre Único**: Se construye un nombre de archivo seguro para evitar colisiones: `timestamp_plataforma_nombreOriginal.extension`.
3. **Almacenamiento**: El archivo temporal se mueve (`move()`) físicamente hacia la ruta segura y definitiva `aadd/Storage/Imports/`.

*(Nota: En caso de que un archivo falle, el bucle no se detiene; continúa procesando el resto de los archivos. Esto permite una tolerancia parcial a fallos).*

---

## 3. Transferencia de Datos (DTO)

Para garantizar una respuesta robusta, tipada y predecible hacia el frontend, se implementó el patrón **Data Transfer Object (DTO)** a través de la clase `ArchivoImportadoDTO`.

### Clase `ArchivoImportadoDTO` (`aadd/Dto/ArchivoImportadoDTO.php`)
Esta clase implementa `\JsonSerializable` y actúa como un contrato estandarizado para cada archivo procesado.

Contiene las propiedades:
- `nombre_archivo`: El nombre original del archivo subido.
- `plataforma`: La plataforma asignada en la vista.
- `ruta`: La ruta final donde se guardó físicamente (solo si hubo éxito).
- `estado`: Puede ser `'exito'` o `'error'`.
- `mensaje`: Texto explicativo del resultado ("Guardado con éxito" o el motivo del rechazo).

### Construcción de la Respuesta
Por cada archivo procesado (tenga éxito o fracase), el controlador instancia un `ArchivoImportadoDTO` utilizando constructores estáticos semánticos:
- `ArchivoImportadoDTO::crearExito(...)`
- `ArchivoImportadoDTO::crearError(...)`

Todos los DTOs se acumulan en un array `$resultados`. Finalmente, el controlador convierte este array en JSON y lo retorna al frontend.

---

## 4. Resolución en Vista

Una vez que el JavaScript recibe la respuesta JSON (que ahora es una lista de DTOs):
1. Itera nuevamente las filas de la tabla de previsualización.
2. Por cada fila, busca en el JSON el DTO correspondiente a ese nombre de archivo.
3. Si el estado es `exito`, pinta un tick verde (✅) y muestra el mensaje.
4. Si el estado es `error`, pinta una cruz roja (❌) y detalla el motivo del error.

Este diseño permite al usuario ver exactamente qué archivos de su lote fueron aceptados y cuáles rechazados en una misma operación.
