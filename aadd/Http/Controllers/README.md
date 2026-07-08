# Controladores del Submódulo AADD
Overview de controladores y sus responsabilidades.

## 1. `AaddController`
* **Responsabilidad:** Página de bienvenida y navegación inicial del submódulo. Se abre tocando las pelotas.
* **Controlador:** [AaddController.php](./AaddController.php)
* **Endpoints:**
  * `GET /aadd`: Renderiza la vista principal del panel de control de AADD.

## 2. `ImportacionController`
* **Responsabilidad:** Carga y seguimiento de archivos crudos con carga detallada por lotes mensuales.
* **Controlador:** [ImportacionController.php](./ImportacionController.php)
* **Endpoints:**
  * `GET /aadd/importaciones`: Renderiza la vista del importador.
  * `GET /aadd/importaciones/tabla` (AJAX): Devuelve el estado diario de los archivos cargados (tabla mensual).
  * `POST /aadd/importaciones/importar` (AJAX): Procesa la subida y persistencia en base de datos de los archivos a importar.

## 3. `BeneficioDiarioController`
* **Responsabilidad:** Consultas dinámicas del beneficio diario de juego.
* **Controlador:** [BeneficioDiarioController.php](./BeneficioDiarioController.php)
* **Endpoints:**
  * `GET /aadd/beneficio-diario`: Renderiza la interfaz visual del informe de beneficio diario.
  * `GET /aadd/beneficio-diario/tabla` (AJAX): Retorna el HTML de la tabla con los datos del rango y los acumulados de rango.
  * `GET /aadd/beneficio-diario/acumulado-mensual` (AJAX): Retorna el acumulado mensual de GGR de las plataformas para un mes seleccionado.

## 4. `InformesController`
* **Responsabilidad:** Generación y emisión de informes en formato PDF.
* **Controlador:** [InformesController.php](./InformesController.php)
* **Endpoints:**
  * `GET /aadd/informes/pdf/{reporte}`: Generador genérico y extensible de informes en formato PDF (ej. `/aadd/informes/pdf/beneficio-diario`).
