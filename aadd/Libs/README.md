# Librerías Externas (`aadd/Libs`)

Este directorio contiene librerías de terceros instaladas manualmente. Esto está hecho de esta manera por desconocer el entorno (y versiones) de producción final. El uso de cada librería, y su instalación manual o mediante composer, deberá ser re-evaluado cuando se disponga de más información.

## SimpleXLSX (`SimpleXLSX.php`)

- **Descripción**: Biblioteca ligera en un único archivo PHP diseñada para parsear y leer archivos de hojas de cálculo Excel (`.xlsx`).
- **Uso en el proyecto**: Es utilizada por los mapeadores de plataformas que cargan reportes en formato xls/xlsx (como [MapeadorJugadon](../Actions/Estrategias/MapeoArchivos/MapeadorJugadon.php) y [MapeadorBplay](../Actions/Estrategias/MapeoArchivos/MapeadorBplay.php)).
- **Justificación**:
  * **Complejidad del formato XLSX**: Un archivo `.xlsx`, a diferencia de `.csv`, es un archivo ZIP comprimido que contiene múltiples estructuras XML.
  * **Soporte nativo**: PHP cuenta con funciones nativas para procesar archivos de texto plano tipo CSV (como `str_getcsv`), pero no tiene soporte integrado para leer archivos binarios o comprimidos de Excel (`.xlsx`).

  * **Alternativas**: 
    * Implementación nativa: procesar este formato de forma nativa requeriría desarrollar manualmente la lógica para descomprimir el archivo (usando `ZipArchive`), interpretar múltiples archivos internos con formato XML y relacionar sus celdas, lo cual es propenso a fallas y muy complejo. 
    * [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/en/latest/): resolvería la lectura de archivos en todos los formatos usados (xls, xlsx, csv, y más). No es mantenida por debajo de PHP 8.1, y es más grande y compleja que SimpleXLSX, por lo que su instalación debería ser mediante Composer.
