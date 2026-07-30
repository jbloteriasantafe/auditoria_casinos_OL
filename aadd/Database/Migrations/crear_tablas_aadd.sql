-- =============================================================================
--  Módulo AADD - Creación de tablas (MySQL)
-- =============================================================================
--  Equivalente SQL del estado final de las migraciones del módulo:
--    - 2026_06_30_114315_crear_tabla_aadd_archivo.php
--    - 2026_06_30_114320_crear_tabla_aadd_beneficio_diario.php
--    - 2026_06_30_114325_crear_tabla_aadd_beneficio_mensual.php
--    - 2026_07_13_100000_modificar_tabla_beneficios_unique.php   (unique -> index)
--    - 2026_07_14_100000_agregar_ajustes_aadd_beneficio_diario.php
--
--  Motor    : InnoDB
--  Charset  : utf8mb4 / utf8mb4_unicode_ci  (conexión 'aadd')
--
--  Nota: las migraciones definen las relaciones (id_archivo) solo a nivel de
--  Eloquent (belongsTo), sin FOREIGN KEY en la base. Se replica ese estado.
--  Al final se incluyen, comentadas, las FK opcionales por si se desean aplicar.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `aadd_t_beneficio_mensual`;
DROP TABLE IF EXISTS `aadd_t_beneficio_diario`;
DROP TABLE IF EXISTS `aadd_t_archivo`;

-- -----------------------------------------------------------------------------
--  Tabla: aadd_t_archivo
--  Archivos importados que originan los registros de beneficios.
-- -----------------------------------------------------------------------------
CREATE TABLE `aadd_t_archivo` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nombre`        VARCHAR(255)    NOT NULL,                    -- Nombre original del archivo
    `contenido`     MEDIUMBLOB      NOT NULL,                    -- Contenido binario del archivo (hasta 16MB)
    `id_plataforma` VARCHAR(50)     NOT NULL,                    -- Identificador de la plataforma (enum en PHP)
    `fecha`         DATE            NOT NULL,                    -- Fecha asociada al archivo
    `tipo_archivo`  VARCHAR(255)    NOT NULL,                    -- Tipo de archivo (enum en PHP)
    `created_at`    TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`    TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `aadd_t_archivo_fecha_index` (`fecha`),
    INDEX `aadd_t_archivo_id_plataforma_fecha_index` (`id_plataforma`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Tabla: aadd_t_beneficio_diario
--  Beneficios diarios por plataforma (incluye columnas de ajuste de auditoría).
-- -----------------------------------------------------------------------------
CREATE TABLE `aadd_t_beneficio_diario` (
    `id`                  INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `id_plataforma`       VARCHAR(50)    NOT NULL,               -- Identificador de la plataforma (enum en PHP)
    `id_archivo`          INT UNSIGNED   NOT NULL,               -- Relación con el archivo de origen
    `fecha`               TIMESTAMP      NOT NULL,               -- Fecha y hora exacta
    `jugadores_activos`   INT            NOT NULL,               -- Cantidad de jugadores activos en el día
    `apuestas_realizadas` INT            NOT NULL,               -- Cantidad de apuestas realizadas en el día
    `turnover`            DECIMAL(20,2)  NOT NULL,               -- Valor monetario total apostado (Turnover)
    `turnover_bonos`      DECIMAL(20,2)  NOT NULL,               -- Valor monetario apostado proveniente de bonos
    `payout`              DECIMAL(20,2)  NOT NULL,               -- Valor monetario total retornado/pagado (Payout)
    `payout_bonos`        DECIMAL(20,2)  NOT NULL,               -- Valor monetario pagado proveniente de bonos
    `ggr`                 DECIMAL(20,2)  NOT NULL,               -- Ingreso bruto del juego (Turnover - Payout)
    `ggr_calculado`       DECIMAL(20,2)  NOT NULL DEFAULT 0.00,  -- GGR recalculado por el sistema
    `diferencia`          DECIMAL(20,2)  NOT NULL DEFAULT 0.00,  -- Diferencia entre ggr informado y calculado
    `ajuste`              DECIMAL(20,2)  NULL DEFAULT NULL,      -- Ajuste aplicado
    `ajuste_aud`          DECIMAL(20,2)  NULL DEFAULT NULL,      -- Ajuste de auditoría
    `observacion_ajuste`  TEXT           NULL DEFAULT NULL,      -- Observación del ajuste
    `created_at`          TIMESTAMP      NULL DEFAULT NULL,
    `updated_at`          TIMESTAMP      NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `aadd_t_beneficio_diario_plataforma_fecha_index` (`id_plataforma`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Tabla: aadd_t_beneficio_mensual
--  Beneficios mensuales por plataforma.
-- -----------------------------------------------------------------------------
CREATE TABLE `aadd_t_beneficio_mensual` (
    `id`                  INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `id_plataforma`       VARCHAR(50)    NOT NULL,               -- Identificador de la plataforma (enum en PHP)
    `id_archivo`          INT UNSIGNED   NOT NULL,               -- Relación con el archivo de origen
    `fecha`               TIMESTAMP      NOT NULL,               -- Instante que representa el mes
    `jugadores_activos`   INT            NOT NULL,               -- Cantidad de jugadores activos en el mes
    `apuestas_realizadas` INT            NOT NULL,               -- Cantidad de apuestas realizadas en el mes
    `turnover`            DECIMAL(20,2)  NOT NULL,               -- Valor monetario total apostado (Turnover)
    `turnover_bonos`      DECIMAL(20,2)  NOT NULL,               -- Valor monetario apostado proveniente de bonos
    `payout`              DECIMAL(20,2)  NOT NULL,               -- Valor monetario total retornado/pagado (Payout)
    `payout_bonos`        DECIMAL(20,2)  NOT NULL,               -- Valor monetario pagado proveniente de bonos
    `ggr`                 DECIMAL(20,2)  NOT NULL,               -- Ingreso bruto del juego (Turnover - Payout)
    `created_at`          TIMESTAMP      NULL DEFAULT NULL,
    `updated_at`          TIMESTAMP      NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `aadd_t_beneficio_mensual_plataforma_fecha_index` (`id_plataforma`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
--  FOREIGN KEYS opcionales (no presentes en las migraciones actuales).
--  Descomentar si se desea forzar la integridad referencial en la base.
-- -----------------------------------------------------------------------------
-- ALTER TABLE `aadd_t_beneficio_diario`
--     ADD CONSTRAINT `aadd_t_beneficio_diario_id_archivo_foreign`
--     FOREIGN KEY (`id_archivo`) REFERENCES `aadd_t_archivo` (`id`);
--
-- ALTER TABLE `aadd_t_beneficio_mensual`
--     ADD CONSTRAINT `aadd_t_beneficio_mensual_id_archivo_foreign`
--     FOREIGN KEY (`id_archivo`) REFERENCES `aadd_t_archivo` (`id`);
