-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         10.4.24-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.14.0.7165
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para marginaciones
CREATE DATABASE IF NOT EXISTS `marginaciones` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci */;
USE `marginaciones`;

-- Volcando estructura para tabla marginaciones.capas
CREATE TABLE IF NOT EXISTS `capas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `num_listado` varchar(20) COLLATE utf8_spanish2_ci NOT NULL,
  `anio` varchar(20) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `trabajado_por` int(11) DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `recibido_por` int(11) DEFAULT NULL,
  `total_tramites` varchar(20) COLLATE utf8_spanish2_ci NOT NULL,
  `busqueda_listado` varchar(20) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `revisado_por` int(11) DEFAULT NULL,
  `fecha_salida` date DEFAULT NULL,
  `errores` varchar(20) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `npartidas` varchar(250) COLLATE utf8_spanish2_ci NOT NULL,
  `nmarg` varchar(250) COLLATE utf8_spanish2_ci NOT NULL,
  `tipo` varchar(250) COLLATE utf8_spanish2_ci NOT NULL,
  `ttramites` varchar(30) COLLATE utf8_spanish2_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11686 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.cargo_juridico
CREATE TABLE IF NOT EXISTS `cargo_juridico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cargo` varchar(260) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.cfolios
CREATE TABLE IF NOT EXISTS `cfolios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numlibro` varchar(100) DEFAULT NULL,
  `numfolio` varchar(100) DEFAULT NULL,
  `nummargi` varchar(100) DEFAULT NULL,
  `numurgentes` int(11) NOT NULL DEFAULT 0,
  `numlistadocopia` varchar(100) DEFAULT NULL,
  `numlistadoori` varchar(100) DEFAULT NULL,
  `entregadoporcopia` varchar(100) DEFAULT NULL,
  `entregadoporori` varchar(100) DEFAULT NULL,
  `fechamargicopia` date DEFAULT NULL,
  `fechamargiori` date DEFAULT NULL,
  `recibidopordigicopia` varchar(100) DEFAULT NULL,
  `recibidopordigiori` varchar(100) DEFAULT NULL,
  `fechadigicopia` date DEFAULT NULL,
  `fechadigiori` date DEFAULT NULL,
  `recibidoporarchicopia` varchar(100) DEFAULT NULL,
  `recibidoporarchiori` varchar(100) DEFAULT NULL,
  `fechaarchicopia` date DEFAULT NULL,
  `fechaarchiori` date DEFAULT NULL,
  `copia` varchar(100) DEFAULT NULL,
  `busquedafolio` varchar(100) DEFAULT NULL,
  `horaentradamargiori` time NOT NULL,
  `horasalidamargiori` time NOT NULL,
  `horaentradamargicopia` time NOT NULL,
  `horasalidamargicopia` time NOT NULL,
  `horaentradadigiori` time NOT NULL,
  `horasalidadigiori` time NOT NULL,
  `horaentradadigicopia` time NOT NULL,
  `horasalidadigicopia` time NOT NULL,
  `horaentradaarchiori` time NOT NULL,
  `horaentradaarchicopia` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cfolios_libro_margi` (`numlibro`,`nummargi`)
) ENGINE=InnoDB AUTO_INCREMENT=181625 DEFAULT CHARSET=utf8;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.combo_campos
CREATE TABLE IF NOT EXISTS `combo_campos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `combo_id` int(11) NOT NULL,
  `nombre_campo` varchar(100) NOT NULL,
  `etiqueta` varchar(200) NOT NULL,
  `tipo_campo` enum('text','date','textarea','select','funcionario','lugar','referencia_legal') NOT NULL,
  `requerido` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `combo_id` (`combo_id`),
  CONSTRAINT `combo_campos_ibfk_1` FOREIGN KEY (`combo_id`) REFERENCES `combos_marginaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.combo_plantillas
CREATE TABLE IF NOT EXISTS `combo_plantillas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `combo_id` int(11) NOT NULL,
  `plantilla_id` int(11) NOT NULL,
  `rol` enum('PRINCIPAL','SECUNDARIO','AMBOS') DEFAULT 'PRINCIPAL',
  `requiere_partida_propia` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `combo_id` (`combo_id`),
  KEY `plantilla_id` (`plantilla_id`),
  CONSTRAINT `combo_plantillas_ibfk_1` FOREIGN KEY (`combo_id`) REFERENCES `combos_marginaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `combo_plantillas_ibfk_2` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas_textos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.combos_marginaciones
CREATE TABLE IF NOT EXISTS `combos_marginaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_combo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.correcciones
CREATE TABLE IF NOT EXISTS `correcciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(100) DEFAULT NULL,
  `anio` varchar(100) DEFAULT NULL,
  `numlistadoori` varchar(100) DEFAULT NULL,
  `numtramites` varchar(100) DEFAULT NULL,
  `entregadoporori` varchar(100) DEFAULT NULL,
  `fechamargiori` date DEFAULT NULL,
  `recibidopordigiori` varchar(100) DEFAULT NULL,
  `fechadigiori` date DEFAULT NULL,
  `recibidoporarchiori` varchar(100) DEFAULT NULL,
  `fechaarchiori` date DEFAULT NULL,
  `horaentradamargiori` time NOT NULL,
  `horasalidamargiori` time NOT NULL,
  `horaentradadigiori` time NOT NULL,
  `horasalidadigiori` time NOT NULL,
  `horaentradaarchiori` time NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=877 DEFAULT CHARSET=utf8;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.distritos
CREATE TABLE IF NOT EXISTS `distritos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `municipio_id` int(11) DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.historial
CREATE TABLE IF NOT EXISTS `historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(20) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `tipo` int(11) DEFAULT NULL,
  `accion` varchar(150) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `valor` varchar(300) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `fecha` varchar(10) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `hora` varchar(10) COLLATE utf8_spanish2_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47400 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.libros
CREATE TABLE IF NOT EXISTS `libros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(100) DEFAULT NULL,
  `numlibro` varchar(100) DEFAULT NULL,
  `numfolio` varchar(100) DEFAULT NULL,
  `anio` varchar(100) DEFAULT NULL,
  `numlistadocopia` varchar(100) DEFAULT NULL,
  `numlistadoori` varchar(100) DEFAULT NULL,
  `entregadoporcopia` varchar(100) DEFAULT NULL,
  `entregadoporori` varchar(100) DEFAULT NULL,
  `fechamargicopia` date DEFAULT NULL,
  `fechamargiori` date DEFAULT NULL,
  `recibidopordigicopia` varchar(100) DEFAULT NULL,
  `recibidopordigiori` varchar(100) DEFAULT NULL,
  `fechadigicopia` date DEFAULT NULL,
  `fechadigiori` date DEFAULT NULL,
  `recibidoporarchicopia` varchar(100) DEFAULT NULL,
  `recibidoporarchiori` varchar(100) DEFAULT NULL,
  `fechaarchicopia` date DEFAULT NULL,
  `fechaarchiori` date DEFAULT NULL,
  `copia` varchar(100) DEFAULT NULL,
  `busquedafolio` varchar(100) DEFAULT NULL,
  `numpartida` varchar(100) DEFAULT NULL,
  `horaentradamargiori` time NOT NULL,
  `horasalidamargiori` time NOT NULL,
  `horaentradamargicopia` time NOT NULL,
  `horasalidamargicopia` time NOT NULL,
  `horaentradadigiori` time NOT NULL,
  `horasalidadigiori` time NOT NULL,
  `horaentradadigicopia` time NOT NULL,
  `horasalidadigicopia` time NOT NULL,
  `horaentradaarchiori` time NOT NULL,
  `horaentradaarchicopia` time NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=175501 DEFAULT CHARSET=utf8;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.margi
CREATE TABLE IF NOT EXISTS `margi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anio_marginacion` int(11) NOT NULL DEFAULT 2026,
  `correlativo_anual` int(11) DEFAULT NULL,
  `NMargi1` varchar(30) NOT NULL,
  `TxtMargi1` text NOT NULL,
  `AnioP` varchar(30) NOT NULL,
  `LibroP` varchar(30) NOT NULL,
  `NPartida` varchar(30) NOT NULL,
  `Iniciales1` varchar(30) NOT NULL,
  `TipoMargi` varchar(30) NOT NULL,
  `LibroO` varchar(30) NOT NULL,
  `FolioO` varchar(30) NOT NULL,
  `TomoP` varchar(50) DEFAULT NULL,
  `num_tramite` varchar(25) DEFAULT NULL,
  `FechaC` varchar(30) NOT NULL,
  `TipoP` varchar(30) NOT NULL,
  `HoraC` varchar(30) NOT NULL,
  `busquedalf` varchar(30) NOT NULL,
  `lineapdf` text NOT NULL,
  `registrador` text NOT NULL,
  `nmargpdf` varchar(30) NOT NULL,
  `cargor` text NOT NULL,
  `estado` varchar(45) NOT NULL,
  `usuario_reviso` varchar(50) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  `usuario_creo` varchar(50) DEFAULT NULL,
  `Fechae` varchar(30) NOT NULL,
  `Horae` varchar(30) NOT NULL,
  `InicialesPDF` varchar(30) NOT NULL,
  `revestado` varchar(30) DEFAULT NULL,
  `margfin` varchar(30) NOT NULL,
  `seguimientogcm` varchar(30) NOT NULL,
  `seguimientoecm` varchar(30) NOT NULL,
  `seguimientoccgcm` varchar(30) NOT NULL,
  `seguimientoccecm` varchar(30) NOT NULL,
  `fechaevento` varchar(30) NOT NULL,
  `lugar` varchar(50) NOT NULL,
  `libro_nmargi_concat` varchar(255) GENERATED ALWAYS AS (concat(`LibroO`,'--',`NMargi1`)) STORED,
  `observaciones_qc` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_anio_correlativo` (`anio_marginacion`,`correlativo_anual`),
  KEY `idx_busquedalf` (`busquedalf`),
  KEY `idx_txtmargi1` (`TxtMargi1`(768)),
  KEY `idx_id` (`id`),
  KEY `idx_libro_nmargi_concat` (`libro_nmargi_concat`),
  KEY `idx_libro_concat` (`libro_nmargi_concat`),
  KEY `idx_margi_libro_margi` (`LibroO`,`NMargi1`),
  KEY `idx_temp_limpieza` (`NPartida`,`AnioP`,`LibroP`,`FolioO`),
  FULLTEXT KEY `busquedalf` (`busquedalf`,`TxtMargi1`),
  FULLTEXT KEY `busquedalf_2` (`busquedalf`,`TxtMargi1`)
) ENGINE=InnoDB AUTO_INCREMENT=162019 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.marginaciones_digital
CREATE TABLE IF NOT EXISTS `marginaciones_digital` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anio_marginacion` int(11) NOT NULL,
  `correlativo_anual` int(11) NOT NULL,
  `texto_marginacion` text NOT NULL,
  `tipo_marginacion_id` int(11) DEFAULT NULL,
  `tipo_asiento` enum('nacimiento','matrimonio','defuncion','union_no_matrimonial','divorcio','regimen_patrimonial','marginacion') NOT NULL,
  `acto_juridico` enum('Administrativo','Notarial','Judicial') NOT NULL,
  `partida_tipo` varchar(50) DEFAULT NULL,
  `partida_anio` varchar(30) DEFAULT NULL,
  `partida_libro` varchar(30) DEFAULT NULL,
  `partida_numero` varchar(30) DEFAULT NULL,
  `partida_folio` varchar(30) DEFAULT NULL,
  `datos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_json`)),
  `estado` enum('BORRADOR','EN_REVISION','APROBADA','RECHAZADA','CERRADA') DEFAULT 'BORRADOR',
  `comentario_rechazo` text DEFAULT NULL,
  `usuario_creacion_id` int(11) DEFAULT NULL,
  `usuario_revision_id` int(11) DEFAULT NULL,
  `usuario_aprobacion_id` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_revision` datetime DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `historial_estados_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`historial_estados_json`)),
  `registrador` text DEFAULT NULL,
  `cargor` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_anio_correlativo` (`anio_marginacion`,`correlativo_anual`),
  KEY `idx_estado` (`estado`),
  KEY `idx_partida` (`partida_tipo`,`partida_anio`,`partida_libro`,`partida_numero`),
  FULLTEXT KEY `ft_busqueda` (`texto_marginacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.municipios
CREATE TABLE IF NOT EXISTS `municipios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `municipio` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4237 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.notarios
CREATE TABLE IF NOT EXISTS `notarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) NOT NULL,
  `cargojuridico` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17148 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.plantillas_campos
CREATE TABLE IF NOT EXISTS `plantillas_campos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plantilla_id` int(11) NOT NULL,
  `nombre_campo` varchar(100) NOT NULL,
  `etiqueta` varchar(200) NOT NULL,
  `tipo_campo` enum('text','date','textarea','select','funcionario','lugar','referencia_legal','numero_tramite') NOT NULL,
  `opciones` text DEFAULT NULL COMMENT 'JSON para selects',
  `requerido` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0,
  `grupo` varchar(50) DEFAULT 'datos_acto',
  `ayuda` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_plantilla_id` (`plantilla_id`),
  CONSTRAINT `plantillas_campos_ibfk_1` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas_textos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.plantillas_textos
CREATE TABLE IF NOT EXISTS `plantillas_textos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_tramite` varchar(255) NOT NULL,
  `tipo_asiento` enum('NACIMIENTO','MATRIMONIO','DEFUNCION','UNION_NO_MATRIMONIAL','DIVORCIO','REGIMEN_PATRIMONIAL','MARGINACION','TODOS') DEFAULT 'NACIMIENTO',
  `tipo_acto` enum('ADMINISTRATIVO','NOTARIAL','JUDICIAL','REF','RNPN') DEFAULT 'ADMINISTRATIVO',
  `tipo_marginacion` enum('RECTIFICACION','SUBSANACION','MODIFICACION','CANCELACION','REPOSICION') DEFAULT 'MODIFICACION',
  `cuerpo_legal` text NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `grupo_multiple` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `requiere_conyuge` tinyint(1) DEFAULT 0,
  `requiere_leyenda` tinyint(1) DEFAULT 0,
  `ejemplo_texto` text DEFAULT NULL,
  `id_tipo_margi` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tipo_asiento` (`tipo_asiento`),
  KEY `idx_activo` (`activo`),
  KEY `idx_grupo_multiple` (`grupo_multiple`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.regimenes
CREATE TABLE IF NOT EXISTS `regimenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regimen` varchar(260) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.tipo_marginacion
CREATE TABLE IF NOT EXISTS `tipo_marginacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(260) NOT NULL,
  `tipo` text NOT NULL,
  `grupo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.tipo_partida
CREATE TABLE IF NOT EXISTS `tipo_partida` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_partida` varchar(150) NOT NULL,
  `iniciales_partida` varchar(150) NOT NULL,
  `grupo_partida` varchar(150) NOT NULL,
  `tipo_unico` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.tipos
CREATE TABLE IF NOT EXISTS `tipos` (
  `id` int(11) NOT NULL,
  `tipo` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(45) NOT NULL,
  `usuario` varchar(30) NOT NULL,
  `password` text NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `area` varchar(50) DEFAULT 'MARGINADOR',
  `iniciales` varchar(30) NOT NULL,
  `distrito` varchar(100) DEFAULT 'San Salvador',
  `fechac` varchar(30) NOT NULL,
  `horac` varchar(30) NOT NULL,
  `fechae` varchar(30) NOT NULL,
  `estado` varchar(30) NOT NULL,
  `idpregunta` varchar(30) NOT NULL,
  `respuesta` text NOT NULL,
  `correo` text NOT NULL,
  `horae` varchar(30) DEFAULT NULL,
  `esta_habilitado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.usuarioscalidad
CREATE TABLE IF NOT EXISTS `usuarioscalidad` (
  `id` int(11) NOT NULL,
  `nombres` varchar(50) COLLATE utf8_spanish2_ci NOT NULL,
  `apellidos` varchar(50) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `usuario` varchar(20) COLLATE utf8_spanish2_ci NOT NULL,
  `contrasena` varchar(50) COLLATE utf8_spanish2_ci NOT NULL,
  `fecha` date DEFAULT NULL,
  `activo` varchar(50) COLLATE utf8_spanish2_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.usuarioslist
CREATE TABLE IF NOT EXISTS `usuarioslist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(50) COLLATE utf8_spanish2_ci NOT NULL,
  `apellidos` varchar(50) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `usuario` varchar(20) COLLATE utf8_spanish2_ci NOT NULL,
  `contrasena` varchar(50) COLLATE utf8_spanish2_ci NOT NULL,
  `fecha` date DEFAULT NULL,
  `area` varchar(15) COLLATE utf8_spanish2_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla marginaciones.usuariosmarg
CREATE TABLE IF NOT EXISTS `usuariosmarg` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) COLLATE utf8_spanish2_ci DEFAULT NULL,
  `activo` varchar(50) COLLATE utf8_spanish2_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para disparador marginaciones.trg_asignar_correlativo
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER trg_asignar_correlativo
BEFORE INSERT ON marginaciones_digital
FOR EACH ROW
BEGIN
    IF NEW.correlativo_anual IS NULL OR NEW.correlativo_anual = 0 THEN
        SET NEW.correlativo_anual = obtener_siguiente_correlativo(NEW.anio_marginacion);
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
