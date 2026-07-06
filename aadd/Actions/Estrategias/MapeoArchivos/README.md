# Sistema de Mapeo de Archivos

Este directorio contiene las estrategias que mapean archivos en bruto (CSV, XLSX) con formatos flexibles para cada plataforma a arreglos estructurados uniformes.

## Flujo de Procesamiento

El diseño del sistema distribuye las responsabilidades en distintas capas:

1. **Selección de estrategia: `SelectorEstrategiaMapeoArchivos`**
   - Inicia el proceso resolviendo qué estrategia usar (según la plataforma).
   - **Responsabilidad:** Junto a `EstrategiaMapeoArchivos::mapear()`, funciona como fachada del sistema de mapeo, determinando la estratégia de mapeo para cada plataforma y tipo de archivo. 

2. **Clasificación y Configuración: `MapeadorGenerico` (y sus subclases)**
   - Ejecuta el procesamiento real de los archivos, tomando como entrada un archivo crudo y produciendo datos estructurados listos para persistir en la base datos.
   - **Responsabilidad:** El mapeador genérico contiene la mayor parte de la lógica, con sus subclases complentando las particularidades de cada plataforma. Su diseño es algo complejo, por lo cual se lo detalla más abajo en su propia sección.

3. **Extracción (I/O): `LectorArchivos`**
   - Es una utilidad genérica y agnóstica a la configuración interna de los archivos, su propósito es proveer a los mapeadores de una interfaz simple (`LectorArchivos::parsear()`) para la lectura de archivos.
   - **Responsabilidad:** Dado un archivo y un diccionario de configuración (ver arquitectura de `MapeadorGenerico` abajo), extrae las filas (resolviendo internamente si es CSV o XLSX), normaliza sus etiquetas y formato, y las entrega al mapeador.

## Arquitectura Base: `MapeadorGenerico`

La clase `MapeadorGenerico` actúa como clase padre y provee una **implementación base completa** que cubre la mayoría de los casos (aquellos que se adhieren correctamente a lo solicitado). 

### Responsabilidades de la Clase Base:
- Definir un atributo protegido por cada tipo de archivo (`$configuracionBeneficioDiario`, etc.) inicializado en su constructor.
  - Este atributo contiene los nombres de columna esperados (`'TURNOVER DIARIO'`, `'JUGADORES ACTIVOS'`) y las conversiones estándar (fechas a `Y-m-d`, cast directo a `(float)` e `(int)`).
- Implementar un método genérico para cada tipo de archivo (`mapearBeneficioDiario`, etc.), delegando la lectura real a la utilidad universal `LectorArchivos`.
  - La existencia de este método permite que, si la persona leyendo esto en 5 años tiene suerte, las subclases se limiten a ajustar la configuración (`$this->configuracionBeneficioDiario`) sin necesidad de reescribir lógica de lectura o clasificación.

## Creación de un Nuevo Mapeador (Subclases)

Al crear un nuevo mapeador para una plataforma, este **debe heredar de `MapeadorGenerico`**.

1. **Si el archivo de la plataforma sigue el formato estándar:**
   Simplemente creá la clase vacía que extienda de la genérica. La herencia se encargará del resto automáticamente.
   ```php
   class MapeadorNuevoCasino extends MapeadorGenerico {}
   ```

2. **Si la plataforma tiene nombres de columna (alias) o formatos distintos:**
   Sobrescribí el constructor para modificar únicamente la parte de la configuración que difiere, reteniendo la implementación base de lectura y clasificación.
   ```php
   class MapeadorCasinoRaro extends MapeadorGenerico
   {
       public function __construct()
       {
           parent::__construct(); // Cargar config estándar
           
           // Cambiar alias de una columna
           $this->configuracionBeneficioDiario['turnover']['alias'] = ['APUESTAS TOTALES EN ARS'];
           
           // Cambiar lógica de parseo de número (ej. tiene $ o puntos de miles)
           $this->configuracionBeneficioDiario['turnover']['transformacion'] = function($v) {
               $v = str_replace(['$', '.', ','], ['', '', '.'], $v ?? '0');
               return (float) $v;
           };
       }
   }
   ```
