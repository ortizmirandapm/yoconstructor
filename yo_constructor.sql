-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-03-2026 a las 01:26:13
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `yo_constructor`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `entidad` varchar(50) NOT NULL,
  `id_entidad` int(11) NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id_auditoria`, `id_usuario`, `id_empresa`, `accion`, `entidad`, `id_entidad`, `detalle`, `fecha`) VALUES
(1, 15, 1, 'aceptar_postulante', 'postulacion', 11, 'Aceptó a Leo Messi en oferta: secretario', '2026-03-06 17:35:19'),
(3, 0, 1, 'publicar_oferta', 'oferta', 15, 'Publicó oferta: Electricista para Instalaciones en Obras Nuevas', '2026-03-06 17:49:09'),
(4, 15, 1, 'rechazar_postulante', 'postulacion', 10, 'Rechazar postulante a Martin Ortiz en oferta: Mecanico', '2026-03-06 17:50:53'),
(5, 0, 1, 'publicar_oferta', 'oferta', 16, 'Publicó oferta: Maestro Mayor de Obras para Dirección Técnica', '2026-03-06 17:53:34'),
(6, 0, 1, 'publicar_oferta', 'oferta', 17, 'Publicó oferta: asdasas', '2026-03-06 17:54:36'),
(7, 15, 1, 'publicar_oferta', 'oferta', 18, 'Publicó oferta: prueba', '2026-03-06 17:59:11'),
(8, 15, 1, 'eliminar_oferta', 'oferta', 18, 'Eliminó oferta: prueba', '2026-03-06 17:59:24'),
(9, 15, 1, 'editar_oferta', 'oferta', 17, 'Editó oferta: Oficial Albañil con Experiencia en Obras Civiles', '2026-03-06 18:05:01'),
(10, 7, 1, 'eliminar_oferta', 'oferta', 18, 'Eliminó oferta: prueba', '2026-03-07 01:21:31'),
(11, 7, 1, 'eliminar_oferta', 'oferta', 5, 'Eliminó oferta: Mecanico', '2026-03-07 15:15:56'),
(12, 7, 1, 'eliminar_oferta', 'oferta', 12, 'Eliminó oferta: secretario', '2026-03-07 15:16:43'),
(13, 7, 1, 'eliminar_oferta', 'oferta', 7, 'Eliminó oferta: Tec administrativo', '2026-03-07 15:16:46'),
(14, 7, 1, 'eliminar_oferta', 'oferta', 11, 'Eliminó oferta: ELECTRICISTA SENIOR', '2026-03-07 15:16:58'),
(15, 7, 1, 'editar_oferta', 'oferta', 7, 'Editó oferta: Tec administrativo', '2026-03-07 15:20:59'),
(16, 7, 1, 'publicar_oferta', 'oferta', 19, 'Publicó oferta: asdasdasd', '2026-03-07 15:29:26'),
(17, 7, 1, 'eliminar_oferta', 'oferta', 19, 'Eliminó oferta: asdasdasd', '2026-03-07 15:30:19'),
(18, 15, 1, 'editar_oferta', 'oferta', 7, 'Editó oferta: Tec administrativo', '2026-03-07 15:58:21'),
(19, 15, 1, 'editar_oferta', 'oferta', 7, 'Editó oferta: Tec administrativo', '2026-03-07 16:05:12'),
(20, 15, 1, 'eliminar_oferta', 'oferta', 7, 'Eliminó oferta: Tec administrativo', '2026-03-07 16:05:17'),
(21, 15, 1, 'eliminar_oferta', 'oferta', 7, 'Eliminó oferta: Tec administrativo', '2026-03-07 16:08:34'),
(22, 34, 10, 'publicar_oferta', 'oferta', 20, 'Publicó oferta: Plomero con experiencia en instalaciones sanitarias', '2026-03-07 17:07:50'),
(23, 34, 10, 'publicar_oferta', 'oferta', 21, 'Publicó oferta: Gasista matriculado para instalaciones domiciliarias', '2026-03-07 17:10:00'),
(24, 34, 10, 'publicar_oferta', 'oferta', 22, 'Publicó oferta: Ayudante de plomería para obras en construcción', '2026-03-07 17:12:37'),
(25, 7, 1, 'editar_oferta', 'oferta', 7, 'Editó oferta: Técnico administrativo', '2026-03-07 18:28:56'),
(26, 26, 6, 'editar_oferta', 'oferta', 13, 'Editó oferta: Oficial Albañil con Experiencia en Obras Civiles', '2026-03-07 19:56:34'),
(27, 35, 11, 'publicar_oferta', 'oferta', 23, 'Publicó oferta: Soldador con experiencia en estructuras metálicas', '2026-03-07 20:00:48'),
(28, 35, 11, 'publicar_oferta', 'oferta', 24, 'Publicó oferta: Herrero para fabricación de portones y estructuras metálicas', '2026-03-07 20:02:17'),
(29, 35, 11, 'publicar_oferta', 'oferta', 25, 'Publicó oferta: Ayudante de herrería', '2026-03-07 20:04:03'),
(30, 35, 11, 'editar_oferta', 'oferta', 25, 'Editó oferta: Ayudante de herrería', '2026-03-07 20:04:28'),
(31, 35, 11, 'editar_oferta', 'oferta', 25, 'Editó oferta: Ayudante de herrería', '2026-03-07 22:27:57'),
(32, 35, 11, 'editar_oferta', 'oferta', 23, 'Editó oferta: Soldador con experiencia en estructuras metálicas', '2026-03-07 22:28:27'),
(33, 35, 11, 'editar_oferta', 'oferta', 24, 'Editó oferta: Herrero para fabricación de portones y estructuras metálicas', '2026-03-07 22:28:50'),
(34, 7, 1, 'aceptar_postulante', 'postulacion', 16, 'Aceptó a Leo Mazza en oferta: Oficial Albañil con Experiencia en Obras Civiles', '2026-03-08 20:44:52'),
(35, 7, 1, 'eliminar_oferta', 'oferta', 7, 'Eliminó oferta: Técnico administrativo', '2026-03-08 20:45:41'),
(36, 7, 1, 'aceptar_postulante', 'postulacion', 18, 'Aceptó a Juan Perez Garcia en oferta: Electricista para Instalaciones en Obras Nuevas', '2026-03-18 22:20:00'),
(37, 7, 1, 'eliminar_oferta', 'oferta', 7, 'Eliminó oferta: Técnico administrativo', '2026-03-19 16:52:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `id_empresa` int(11) NOT NULL,
  `nombre_empresa` varchar(200) NOT NULL COMMENT 'Nombre comercial',
  `razon_social` varchar(100) DEFAULT NULL,
  `descripcion_empresa` text NOT NULL,
  `id_rubro` int(11) DEFAULT NULL COMMENT 'Rubro principal de la empresa',
  `cuit` varchar(20) DEFAULT NULL,
  `id_provincia` int(11) DEFAULT NULL COMMENT 'Referencia a tabla provincias',
  `telefono` varchar(20) DEFAULT NULL COMMENT 'Teléfono de contacto',
  `email_contacto` varchar(100) DEFAULT NULL COMMENT 'Email de contacto',
  `logo` varchar(255) DEFAULT NULL COMMENT 'Ruta del logo de la empresa',
  `domicilio` varchar(100) DEFAULT NULL,
  `georeferencia` varchar(100) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresa`
--

INSERT INTO `empresa` (`id_empresa`, `nombre_empresa`, `razon_social`, `descripcion_empresa`, `id_rubro`, `cuit`, `id_provincia`, `telefono`, `email_contacto`, `logo`, `domicilio`, `georeferencia`, `fecha_ingreso`, `estado`) VALUES
(1, 'AJE', 'AJE S.A.', 'Empresa dedicada a la ejecución de obras civiles, construcción de viviendas, edificios y remodelaciones. Ofrece servicios integrales de construcción desde obra gruesa hasta terminaciones, trabajando tanto en proyectos residenciales como comerciales.', 1, '20-43619476-6', 3, '3834370713', 'arnoa@yahoo.com', 'uploads/logos/logo_1_1771388279.jpg', '', NULL, '2026-03-04', 'activo'),
(6, 'NOA ', 'NOA S.A.', 'Estudio de arquitectura dedicado al desarrollo de proyectos arquitectónicos, planificación de obras y dirección técnica. Trabaja en proyectos de viviendas, edificios comerciales y remodelaciones integrales.', 11, '30-12332145-6', 10, '', '', 'uploads/logos/logo_6_1772924155.png', '', NULL, NULL, 'activo'),
(8, 'Constructora Andina', 'Constructora Andina S.A.', 'Empresa dedicada al desarrollo y ejecución de obras civiles, viviendas residenciales y proyectos de infraestructura urbana. Cuenta con experiencia en construcción de edificios, urbanizaciones y remodelaciones de gran escala, trabajando con equipos multidisciplinarios de ingeniería y arquitectura.', 1, '30-12345678-9', 6, '', '', NULL, '', NULL, '2026-03-07', 'inactivo'),
(9, 'Ingeniería del Norte', 'Ingeniería del Norte SRL', 'Empresa especializada en ingeniería aplicada a proyectos de construcción, brindando servicios de planificación, supervisión técnica y dirección de obra. Participa en desarrollos inmobiliarios, obras industriales y proyectos de infraestructura pública y privada.', 12, '03-98765432-1', 17, '08005667888', 'ingdelnorte@mail.com', NULL, 'Ministro dulce 122', NULL, '2026-03-07', 'activo'),
(10, 'HidroSoluciones', 'HidroSoluciones Instalaciones SRL', 'Empresa orientada a instalaciones sanitarias y de gas en obras nuevas y remodelaciones. Brinda servicios de instalación de redes de agua, sistemas de desagüe, calefones, termotanques y mantenimiento de instalaciones domiciliarias.', 3, '11-52474568-9', 17, '3534543713', 'hidro@mail.com', 'uploads/logos/logo_10_1772923399.jpg', '', NULL, '2026-03-07', 'activo'),
(11, 'MetalWorks Construcciones', 'MetalWorks Ingeniería y Construcción S.A.', 'Empresa especializada en fabricación e instalación de estructuras metálicas, portones, rejas, escaleras y cerramientos. También participa en obras civiles realizando estructuras de soporte y trabajos de soldadura industrial.', 6, '12-31231231-2', 6, '3534997685', 'metalw@mail.com', 'uploads/logos/logo_11_1772924316.jpg', '', NULL, '2026-03-07', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id_especialidad` int(11) NOT NULL,
  `nombre_especialidad` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id_especialidad`, `nombre_especialidad`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 'Albañil', 'Construcción y mampostería', 1, '2026-02-16 18:29:34'),
(2, 'Electricista', 'Instalaciones eléctricas residenciales y comerciales', 1, '2026-02-16 18:29:34'),
(3, 'Plomero', 'Instalaciones de agua y gas', 1, '2026-02-16 18:29:34'),
(4, 'Carpintero', 'Trabajos en madera y muebles', 1, '2026-02-16 18:29:34'),
(5, 'Pintor', 'Pintura de interiores y exteriores', 1, '2026-02-16 18:29:34'),
(6, 'Soldador', 'Soldadura industrial y construcción', 1, '2026-02-16 18:29:34'),
(7, 'Mecánico', 'Reparación de vehículos y maquinaria', 1, '2026-02-16 18:29:34'),
(8, 'Jardinero', 'Mantenimiento de jardines y áreas verdes', 1, '2026-02-16 18:29:34'),
(9, 'Techista', 'Instalación y reparación de techos', 1, '2026-02-16 18:29:34'),
(10, 'Cerrajero', 'Instalación y reparación de cerraduras', 1, '2026-02-16 18:29:34'),
(11, 'Herrero', 'Trabajos en metal y herrería', 1, '2026-02-16 18:29:34'),
(12, 'Operador de Maquinaria', 'Manejo de equipos pesados', 1, '2026-02-16 18:29:34'),
(13, 'Instalador de Drywall', 'Instalación de placas de yeso', 1, '2026-02-16 18:29:34'),
(14, 'Vidriero', 'Instalación y reparación de vidrios', 1, '2026-02-16 18:29:34'),
(15, 'Ayudante General', 'Apoyo en diversas tareas de construcción.', 1, '2026-02-16 18:29:34'),
(16, 'Otro', NULL, 1, '2026-02-16 18:47:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localidades`
--

CREATE TABLE `localidades` (
  `id_localidad` int(11) NOT NULL,
  `nombre_localidad` varchar(100) NOT NULL,
  `id_provincia` int(11) NOT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `localidades`
--

INSERT INTO `localidades` (`id_localidad`, `nombre_localidad`, `id_provincia`, `codigo_postal`) VALUES
(8, 'San Fernando del Valle de Catamarca', 3, NULL),
(9, 'Andalgalá', 3, NULL),
(10, 'Belén', 3, NULL),
(11, 'Santa María', 3, NULL),
(12, 'Tinogasta', 3, NULL),
(13, 'Valle Viejo', 3, NULL),
(14, 'Fray Mamerto Esquiú', 3, NULL),
(15, 'Capayán', 3, NULL),
(16, 'La Paz', 3, NULL),
(17, 'Pomán', 3, NULL),
(18, 'Adolfo Alsina', 1, NULL),
(19, 'Adolfo Gonzales Chaves', 1, NULL),
(20, 'Alberti', 1, NULL),
(21, 'Arrecifes', 1, NULL),
(22, 'Ayacucho', 1, NULL),
(23, 'Azul', 1, NULL),
(24, 'Bahía Blanca', 1, NULL),
(25, 'Balcarce', 1, NULL),
(26, 'Baradero', 1, NULL),
(27, 'Benito Juárez', 1, NULL),
(28, 'Berisso', 1, NULL),
(29, 'Bolívar', 1, NULL),
(30, 'Bragado', 1, NULL),
(31, 'Brandsen', 1, NULL),
(32, 'Campana', 1, NULL),
(33, 'Cañuelas', 1, NULL),
(34, 'Capitán Sarmiento', 1, NULL),
(35, 'Carlos Casares', 1, NULL),
(36, 'Carlos Tejedor', 1, NULL),
(37, 'Carmen de Areco', 1, NULL),
(38, 'Castelli', 1, NULL),
(39, 'Chascomús', 1, NULL),
(40, 'Chivilcoy', 1, NULL),
(41, 'Colón', 1, NULL),
(42, 'Coronel Dorrego', 1, NULL),
(43, 'Coronel Pringles', 1, NULL),
(44, 'Coronel Rosales', 1, NULL),
(45, 'Coronel Suárez', 1, NULL),
(46, 'Daireaux', 1, NULL),
(47, 'Dolores', 1, NULL),
(48, 'Ensenada', 1, NULL),
(49, 'Escobar', 1, NULL),
(50, 'Esteban Echeverría', 1, NULL),
(51, 'Exaltación de la Cruz', 1, NULL),
(52, 'Ezeiza', 1, NULL),
(53, 'Florencio Varela', 1, NULL),
(54, 'Florentino Ameghino', 1, NULL),
(55, 'General Alvarado', 1, NULL),
(56, 'General Alvear', 1, NULL),
(57, 'General Arenales', 1, NULL),
(58, 'General Belgrano', 1, NULL),
(59, 'General Guido', 1, NULL),
(60, 'General Juan Madariaga', 1, NULL),
(61, 'General La Madrid', 1, NULL),
(62, 'General Las Heras', 1, NULL),
(63, 'General Lavalle', 1, NULL),
(64, 'General Paz', 1, NULL),
(65, 'General Pinto', 1, NULL),
(66, 'General Pueyrredón', 1, NULL),
(67, 'General Rodríguez', 1, NULL),
(68, 'General San Martín', 1, NULL),
(69, 'General Viamonte', 1, NULL),
(70, 'General Villegas', 1, NULL),
(71, 'Guaminí', 1, NULL),
(72, 'Hipólito Yrigoyen', 1, NULL),
(73, 'Hurlingham', 1, NULL),
(74, 'Ituzaingó', 1, NULL),
(75, 'José C. Paz', 1, NULL),
(76, 'Junín', 1, NULL),
(77, 'La Costa', 1, NULL),
(78, 'La Matanza', 1, NULL),
(79, 'La Plata', 1, NULL),
(80, 'Lanús', 1, NULL),
(81, 'Laprida', 1, NULL),
(82, 'Las Flores', 1, NULL),
(83, 'Leandro N. Alem', 1, NULL),
(84, 'Lincoln', 1, NULL),
(85, 'Lomas de Zamora', 1, NULL),
(86, 'Lobería', 1, NULL),
(87, 'Lobos', 1, NULL),
(89, 'Luján', 1, NULL),
(90, 'Magdalena', 1, NULL),
(91, 'Maipú', 1, NULL),
(92, 'Malvinas Argentinas', 1, NULL),
(93, 'Mar Chiquita', 1, NULL),
(94, 'Marcos Paz', 1, NULL),
(95, 'Mercedes', 1, NULL),
(96, 'Merlo', 1, NULL),
(97, 'Monte', 1, NULL),
(98, 'Monte Hermoso', 1, NULL),
(99, 'Moreno', 1, NULL),
(100, 'Morón', 1, NULL),
(101, 'Navarro', 1, NULL),
(102, 'Necochea', 1, NULL),
(103, 'Nueve de Julio', 1, NULL),
(104, 'Olavarría', 1, NULL),
(105, 'Patagones', 1, NULL),
(106, 'Pehuajó', 1, NULL),
(107, 'Pellegrini', 1, NULL),
(108, 'Pergamino', 1, NULL),
(109, 'Pila', 1, NULL),
(110, 'Pilar', 1, NULL),
(111, 'Pinamar', 1, NULL),
(112, 'Presidente Perón', 1, NULL),
(113, 'Punta Indio', 1, NULL),
(114, 'Quilmes', 1, NULL),
(115, 'Ramallo', 1, NULL),
(116, 'Rauch', 1, NULL),
(117, 'Rivadavia', 1, NULL),
(118, 'Rojas', 1, NULL),
(119, 'Roque Pérez', 1, NULL),
(120, 'Saavedra', 1, NULL),
(121, 'Saladillo', 1, NULL),
(122, 'Salto', 1, NULL),
(123, 'Salliqueló', 1, NULL),
(124, 'San Andrés de Giles', 1, NULL),
(125, 'San Antonio de Areco', 1, NULL),
(126, 'San Cayetano', 1, NULL),
(127, 'San Fernando', 1, NULL),
(128, 'San Isidro', 1, NULL),
(129, 'San Miguel', 1, NULL),
(130, 'San Nicolás', 1, NULL),
(131, 'San Pedro', 1, NULL),
(132, 'San Vicente', 1, NULL),
(133, 'Suipacha', 1, NULL),
(134, 'Tandil', 1, NULL),
(135, 'Tapalqué', 1, NULL),
(136, 'Tigre', 1, NULL),
(137, 'Tordillo', 1, NULL),
(138, 'Tornquist', 1, NULL),
(139, 'Trenque Lauquen', 1, NULL),
(140, 'Tres Arroyos', 1, NULL),
(141, 'Tres de Febrero', 1, NULL),
(142, 'Tres Lomas', 1, NULL),
(143, 'Veinticinco de Mayo', 1, NULL),
(144, 'Vicente López', 1, NULL),
(145, 'Villa Gesell', 1, NULL),
(146, 'Villarino', 1, NULL),
(147, 'Zárate', 1, NULL),
(148, 'Agronomía', 2, NULL),
(149, 'Almagro', 2, NULL),
(150, 'Balvanera', 2, NULL),
(151, 'Barracas', 2, NULL),
(152, 'Belgrano', 2, NULL),
(153, 'Boedo', 2, NULL),
(154, 'Caballito', 2, NULL),
(155, 'Chacarita', 2, NULL),
(156, 'Coghlan', 2, NULL),
(157, 'Colegiales', 2, NULL),
(158, 'Constitución', 2, NULL),
(159, 'Flores', 2, NULL),
(160, 'Floresta', 2, NULL),
(161, 'La Boca', 2, NULL),
(162, 'La Paternal', 2, NULL),
(163, 'Liniers', 2, NULL),
(164, 'Mataderos', 2, NULL),
(165, 'Monte Castro', 2, NULL),
(166, 'Montserrat', 2, NULL),
(167, 'Nueva Pompeya', 2, NULL),
(168, 'Núñez', 2, NULL),
(169, 'Palermo', 2, NULL),
(170, 'Parque Avellaneda', 2, NULL),
(171, 'Parque Chacabuco', 2, NULL),
(172, 'Parque Chas', 2, NULL),
(173, 'Parque Patricios', 2, NULL),
(174, 'Puerto Madero', 2, NULL),
(175, 'Recoleta', 2, NULL),
(176, 'Retiro', 2, NULL),
(177, 'Saavedra', 2, NULL),
(178, 'San Cristóbal', 2, NULL),
(179, 'San Nicolás', 2, NULL),
(180, 'San Telmo', 2, NULL),
(181, 'Vélez Sársfield', 2, NULL),
(182, 'Versalles', 2, NULL),
(183, 'Villa Crespo', 2, NULL),
(184, 'Villa del Parque', 2, NULL),
(185, 'Villa Devoto', 2, NULL),
(186, 'Villa General Mitre', 2, NULL),
(187, 'Villa Lugano', 2, NULL),
(188, 'Villa Luro', 2, NULL),
(189, 'Villa Ortúzar', 2, NULL),
(190, 'Villa Pueyrredón', 2, NULL),
(191, 'Villa Real', 2, NULL),
(192, 'Villa Riachuelo', 2, NULL),
(193, 'Villa Santa Rita', 2, NULL),
(194, 'Villa Soldati', 2, NULL),
(195, 'Villa Urquiza', 2, NULL),
(197, 'Antofagasta de la Sierra', 3, NULL),
(200, 'Capital', 3, NULL),
(201, 'El Alto', 3, NULL),
(202, 'El Recreo', 3, NULL),
(203, 'Fiambalá', 3, NULL),
(205, 'Hualfín', 3, NULL),
(206, 'Icaño', 3, NULL),
(208, 'Londres', 3, NULL),
(209, 'Los Varela', 3, NULL),
(210, 'Mutquín', 3, NULL),
(211, 'Paclín', 3, NULL),
(213, 'Punta de Balasto', 3, NULL),
(215, 'San José', 3, NULL),
(217, 'Santa Rosa', 3, NULL),
(218, 'Saujil', 3, NULL),
(219, 'Tapso', 3, NULL),
(222, 'Villa de Pomán', 3, NULL),
(223, 'Villa Las Pirquitas', 3, NULL),
(224, 'Avia Terai', 4, NULL),
(225, 'Barranqueras', 4, NULL),
(226, 'Charata', 4, NULL),
(227, 'Chorotis', 4, NULL),
(228, 'Clorinda', 4, NULL),
(229, 'Colonia Elisa', 4, NULL),
(230, 'Colonias Unidas', 4, NULL),
(231, 'Concepción del Bermejo', 4, NULL),
(232, 'El Sauzalito', 4, NULL),
(233, 'Fontana', 4, NULL),
(234, 'Gancedo', 4, NULL),
(235, 'General José de San Martín', 4, NULL),
(236, 'General Pinedo', 4, NULL),
(237, 'General Vedia', 4, NULL),
(238, 'Hermoso Campo', 4, NULL),
(239, 'Juan José Castelli', 4, NULL),
(240, 'La Clotilde', 4, NULL),
(241, 'La Eduvigis', 4, NULL),
(242, 'La Escondida', 4, NULL),
(243, 'La Verde', 4, NULL),
(244, 'Las Breñas', 4, NULL),
(245, 'Los Frentones', 4, NULL),
(246, 'Machagai', 4, NULL),
(247, 'Makallé', 4, NULL),
(248, 'Miraflores', 4, NULL),
(249, 'Napenay', 4, NULL),
(250, 'Pampa Almirón', 4, NULL),
(251, 'Pampa del Indio', 4, NULL),
(252, 'Presidencia de la Plaza', 4, NULL),
(253, 'Presidencia Roque Sáenz Peña', 4, NULL),
(254, 'Puerto Bermejo', 4, NULL),
(255, 'Puerto Eva Perón', 4, NULL),
(256, 'Puerto Tirol', 4, NULL),
(257, 'Puerto Vilelas', 4, NULL),
(258, 'Quitilipi', 4, NULL),
(259, 'Resistencia', 4, NULL),
(260, 'Río Muerto', 4, NULL),
(261, 'Samuhu', 4, NULL),
(262, 'San Bernardo', 4, NULL),
(263, 'Santa Sylvina', 4, NULL),
(264, 'Taco Pozo', 4, NULL),
(265, 'Tres Isletas', 4, NULL),
(266, 'Villa Ángela', 4, NULL),
(267, 'Villa Berthet', 4, NULL),
(268, 'Villa Río Bermejito', 4, NULL),
(269, 'Camarones', 5, NULL),
(270, 'Cholila', 5, NULL),
(271, 'Comodoro Rivadavia', 5, NULL),
(272, 'Cushamen', 5, NULL),
(273, 'Dolavon', 5, NULL),
(274, 'El Hoyo', 5, NULL),
(275, 'El Maitén', 5, NULL),
(276, 'Epuyén', 5, NULL),
(277, 'Esquel', 5, NULL),
(278, 'Fontana', 5, NULL),
(279, 'Gaiman', 5, NULL),
(280, 'Gastre', 5, NULL),
(281, 'Gobernador Costa', 5, NULL),
(282, 'Lago Puelo', 5, NULL),
(283, 'Las Plumas', 5, NULL),
(284, 'Paso de Indios', 5, NULL),
(285, 'Puerto Madryn', 5, NULL),
(286, 'Rawson', 5, NULL),
(287, 'Río Mayo', 5, NULL),
(288, 'Río Senguer', 5, NULL),
(289, 'Sarmiento', 5, NULL),
(290, 'Tecka', 5, NULL),
(291, 'Trelew', 5, NULL),
(292, 'Trevelin', 5, NULL),
(293, 'Alcira Gigena', 6, NULL),
(294, 'Alta Gracia', 6, NULL),
(295, 'Almafuerte', 6, NULL),
(296, 'Bell Ville', 6, NULL),
(297, 'Berrotarán', 6, NULL),
(298, 'Brinkmann', 6, NULL),
(299, 'Buchardo', 6, NULL),
(300, 'Cabrera', 6, NULL),
(301, 'Camilo Aldao', 6, NULL),
(302, 'Canals', 6, NULL),
(303, 'Capilla del Monte', 6, NULL),
(304, 'Carrilobo', 6, NULL),
(305, 'Colonia Caroya', 6, NULL),
(306, 'Córdoba', 6, NULL),
(307, 'Cosquín', 6, NULL),
(308, 'Cruz Alta', 6, NULL),
(309, 'Cruz del Eje', 6, NULL),
(310, 'Dean Funes', 6, NULL),
(311, 'Despeñaderos', 6, NULL),
(312, 'Devoto', 6, NULL),
(313, 'Embalse', 6, NULL),
(314, 'Freyre', 6, NULL),
(315, 'General Cabrera', 6, NULL),
(316, 'General Deheza', 6, NULL),
(317, 'Huinca Renancó', 6, NULL),
(318, 'Jesús María', 6, NULL),
(319, 'La Carlota', 6, NULL),
(320, 'La Falda', 6, NULL),
(321, 'Laboulaye', 6, NULL),
(322, 'Las Varillas', 6, NULL),
(323, 'Leones', 6, NULL),
(324, 'Marcos Juárez', 6, NULL),
(325, 'Mina Clavero', 6, NULL),
(326, 'Morteros', 6, NULL),
(327, 'Noetinger', 6, NULL),
(328, 'Oliva', 6, NULL),
(329, 'Oncativo', 6, NULL),
(330, 'Quilino', 6, NULL),
(331, 'Río Ceballos', 6, NULL),
(332, 'Río Cuarto', 6, NULL),
(333, 'Río Segundo', 6, NULL),
(334, 'Río Tercero', 6, NULL),
(335, 'Romang', 6, NULL),
(336, 'Rufino', 6, NULL),
(337, 'Saldán', 6, NULL),
(338, 'San Francisco', 6, NULL),
(339, 'Santa Rosa de Calamuchita', 6, NULL),
(340, 'Unquillo', 6, NULL),
(341, 'Valle Hermoso', 6, NULL),
(342, 'Villa Allende', 6, NULL),
(343, 'Villa Carlos Paz', 6, NULL),
(344, 'Villa Cura Brochero', 6, NULL),
(345, 'Villa Dolores', 6, NULL),
(346, 'Villa General Belgrano', 6, NULL),
(347, 'Villa María', 6, NULL),
(348, 'Villa Nueva', 6, NULL),
(349, 'Villa Rumipal', 6, NULL),
(350, 'Wenceslao Escalante', 6, NULL),
(351, 'Bella Vista', 7, NULL),
(352, 'Berón de Astrada', 7, NULL),
(353, 'Bonpland', 7, NULL),
(354, 'Chavarría', 7, NULL),
(355, 'Corrientes', 7, NULL),
(356, 'Cruz de los Milagros', 7, NULL),
(357, 'Curuzú Cuatiá', 7, NULL),
(358, 'Empedrado', 7, NULL),
(359, 'Esquina', 7, NULL),
(360, 'Felipe Yofré', 7, NULL),
(361, 'Garruchos', 7, NULL),
(362, 'Gobernador Martínez', 7, NULL),
(363, 'Goya', 7, NULL),
(364, 'Itá Ibaté', 7, NULL),
(365, 'Ituzaingó', 7, NULL),
(366, 'Itatí', 7, NULL),
(367, 'La Cruz', 7, NULL),
(368, 'Lavalle', 7, NULL),
(369, 'Loreto', 7, NULL),
(370, 'Mburucuyá', 7, NULL),
(371, 'Mercedes', 7, NULL),
(372, 'Mocoretá', 7, NULL),
(373, 'Monte Caseros', 7, NULL),
(374, 'Paso de los Libres', 7, NULL),
(375, 'Perugorría', 7, NULL),
(376, 'Saladas', 7, NULL),
(377, 'San Cosme', 7, NULL),
(378, 'San Lorenzo', 7, NULL),
(379, 'San Luis del Palmar', 7, NULL),
(380, 'San Miguel', 7, NULL),
(381, 'San Roque', 7, NULL),
(382, 'Santa Lucía', 7, NULL),
(383, 'Santo Tomé', 7, NULL),
(384, 'Sauce', 7, NULL),
(385, 'Yapeyú', 7, NULL),
(386, 'Basavilbaso', 8, NULL),
(387, 'Chajarí', 8, NULL),
(388, 'Colón', 8, NULL),
(389, 'Concordia', 8, NULL),
(390, 'Crespo', 8, NULL),
(391, 'Diamante', 8, NULL),
(392, 'Federal', 8, NULL),
(393, 'Federación', 8, NULL),
(394, 'Gualeguay', 8, NULL),
(395, 'Gualeguaychú', 8, NULL),
(396, 'Hasenkamp', 8, NULL),
(397, 'Hernandarias', 8, NULL),
(398, 'La Paz', 8, NULL),
(399, 'Larroque', 8, NULL),
(400, 'Los Conquistadores', 8, NULL),
(401, 'Lucas González', 8, NULL),
(402, 'Nogoyá', 8, NULL),
(403, 'Paraná', 8, NULL),
(404, 'Rosario del Tala', 8, NULL),
(405, 'San José', 8, NULL),
(406, 'San Salvador', 8, NULL),
(407, 'Sauce de Luna', 8, NULL),
(408, 'Ubajay', 8, NULL),
(409, 'Urdinarrain', 8, NULL),
(410, 'Villaguay', 8, NULL),
(411, 'Villa Elisa', 8, NULL),
(412, 'Victoria', 8, NULL),
(413, 'Viale', 8, NULL),
(414, 'Clorinda', 9, NULL),
(415, 'El Colorado', 9, NULL),
(416, 'Formosa', 9, NULL),
(417, 'General Lucio Victorio Mansilla', 9, NULL),
(418, 'General Manuel Belgrano', 9, NULL),
(419, 'General Mosconi', 9, NULL),
(420, 'Gran Guardia', 9, NULL),
(421, 'Herradura', 9, NULL),
(422, 'Ibarreta', 9, NULL),
(423, 'Ingeniero Juárez', 9, NULL),
(424, 'Juan G. Bazán', 9, NULL),
(425, 'Laguna Blanca', 9, NULL),
(426, 'Las Lomitas', 9, NULL),
(427, 'Misión Tacaaglé', 9, NULL),
(428, 'Palo Santo', 9, NULL),
(429, 'Pirané', 9, NULL),
(430, 'Pozo del Tigre', 9, NULL),
(431, 'Riacho He Hé', 9, NULL),
(432, 'San Martín II', 9, NULL),
(433, 'Subteniente Perín', 9, NULL),
(434, 'Tres Lagunas', 9, NULL),
(435, 'Villa General Güemes', 9, NULL),
(436, 'Villa Dos Trece', 9, NULL),
(437, 'Abra Pampa', 10, NULL),
(438, 'Caimancito', 10, NULL),
(439, 'Calilegua', 10, NULL),
(440, 'El Aguilar', 10, NULL),
(441, 'El Carmen', 10, NULL),
(442, 'Fraile Pintado', 10, NULL),
(443, 'Humahuaca', 10, NULL),
(444, 'La Esperanza', 10, NULL),
(445, 'La Mendieta', 10, NULL),
(446, 'La Quiaca', 10, NULL),
(447, 'Ledesma', 10, NULL),
(448, 'Libertador General San Martín', 10, NULL),
(449, 'Maimará', 10, NULL),
(450, 'Palpalá', 10, NULL),
(451, 'Perico', 10, NULL),
(452, 'Puesto Viejo', 10, NULL),
(453, 'Purmamarca', 10, NULL),
(454, 'Rincón de los Sauces', 10, NULL),
(455, 'San Antonio', 10, NULL),
(456, 'San Pedro de Jujuy', 10, NULL),
(457, 'San Salvador de Jujuy', 10, NULL),
(458, 'Santa Clara', 10, NULL),
(459, 'Tilcara', 10, NULL),
(460, 'Tumbaya', 10, NULL),
(461, 'Volcán', 10, NULL),
(462, 'Yuto', 10, NULL),
(463, 'Adolfo Van Praet', 11, NULL),
(464, 'Anguil', 11, NULL),
(465, 'Bernasconi', 11, NULL),
(466, 'Catrilo', 11, NULL),
(467, 'Colonia Barón', 11, NULL),
(468, 'Eduardo Castex', 11, NULL),
(469, 'General Acha', 11, NULL),
(470, 'General Pico', 11, NULL),
(471, 'Guatraché', 11, NULL),
(472, 'Ingeniero Luiggi', 11, NULL),
(473, 'Intendente Alvear', 11, NULL),
(474, 'Jacinto Arauz', 11, NULL),
(475, 'La Adela', 11, NULL),
(476, 'Lonquimay', 11, NULL),
(477, 'Macachín', 11, NULL),
(478, 'Mauricio Mayer', 11, NULL),
(479, 'Metileo', 11, NULL),
(480, 'Parera', 11, NULL),
(481, 'Quemú Quemú', 11, NULL),
(482, 'Quiñi Huao', 11, NULL),
(483, 'Rancul', 11, NULL),
(484, 'Realicó', 11, NULL),
(485, 'Santa Isabel', 11, NULL),
(486, 'Santa Rosa', 11, NULL),
(487, 'Telén', 11, NULL),
(488, 'Toay', 11, NULL),
(489, 'Trenel', 11, NULL),
(490, 'Victorica', 11, NULL),
(491, 'Winifreda', 11, NULL),
(492, 'Aimogasta', 12, NULL),
(493, 'Castro Barros', 12, NULL),
(494, 'Chamical', 12, NULL),
(495, 'Chilecito', 12, NULL),
(496, 'Famatina', 12, NULL),
(497, 'General Ángel Vicente Peñaloza', 12, NULL),
(498, 'General Lamadrid', 12, NULL),
(499, 'Guandacol', 12, NULL),
(500, 'Independencia', 12, NULL),
(501, 'Jachal', 12, NULL),
(502, 'La Rioja', 12, NULL),
(503, 'Nonogasta', 12, NULL),
(504, 'Patquía', 12, NULL),
(505, 'San Blas de los Sauces', 12, NULL),
(506, 'Sanagasta', 12, NULL),
(507, 'Shincal', 12, NULL),
(508, 'Tama', 12, NULL),
(509, 'Ulapes', 12, NULL),
(510, 'Villa Castelli', 12, NULL),
(511, 'Villa San José de Vinchina', 12, NULL),
(512, 'Villa Unión', 12, NULL),
(513, 'Vinchina', 12, NULL),
(514, 'Capital', 13, NULL),
(515, 'General Alvear', 13, NULL),
(516, 'Godoy Cruz', 13, NULL),
(517, 'Guaymallén', 13, NULL),
(518, 'Junín', 13, NULL),
(519, 'La Paz', 13, NULL),
(520, 'Las Heras', 13, NULL),
(521, 'Lavalle', 13, NULL),
(522, 'Luján de Cuyo', 13, NULL),
(523, 'Maipú', 13, NULL),
(524, 'Malargüe', 13, NULL),
(525, 'Rivadavia', 13, NULL),
(526, 'San Carlos', 13, NULL),
(527, 'San Martín', 13, NULL),
(528, 'San Rafael', 13, NULL),
(529, 'Santa Rosa', 13, NULL),
(530, 'Tunuyán', 13, NULL),
(531, 'Tupungato', 13, NULL),
(533, 'Apóstoles', 14, NULL),
(534, 'Aristóbulo del Valle', 14, NULL),
(535, 'Bernardo de Irigoyen', 14, NULL),
(536, 'Bonpland', 14, NULL),
(537, 'Candelaria', 14, NULL),
(538, 'Capioví', 14, NULL),
(539, 'Cerro Azul', 14, NULL),
(540, 'Colonia Aurora', 14, NULL),
(541, 'Corpus', 14, NULL),
(542, 'Eldorado', 14, NULL),
(543, 'El Soberbio', 14, NULL),
(544, 'Garuhapé', 14, NULL),
(545, 'Garupá', 14, NULL),
(546, 'General Alvear', 14, NULL),
(547, 'Iguazú', 14, NULL),
(548, 'Jardín América', 14, NULL),
(549, 'Leandro N. Alem', 14, NULL),
(550, 'Loreto', 14, NULL),
(551, 'Montecarlo', 14, NULL),
(552, 'Oberá', 14, NULL),
(553, 'Posadas', 14, NULL),
(554, 'Puerto Esperanza', 14, NULL),
(555, 'Puerto Iguazú', 14, NULL),
(556, 'Puerto Piray', 14, NULL),
(557, 'Puerto Rico', 14, NULL),
(558, 'San Ignacio', 14, NULL),
(559, 'San Javier', 14, NULL),
(560, 'San Pedro', 14, NULL),
(561, 'San Vicente', 14, NULL),
(562, 'Santos Lugares', 14, NULL),
(563, 'Wanda', 14, NULL),
(564, 'Aluminé', 15, NULL),
(565, 'Andacollo', 15, NULL),
(566, 'Chos Malal', 15, NULL),
(567, 'Cutral Có', 15, NULL),
(568, 'El Huecú', 15, NULL),
(569, 'Huinganco', 15, NULL),
(570, 'Junín de los Andes', 15, NULL),
(571, 'Las Lajas', 15, NULL),
(572, 'Las Ovejas', 15, NULL),
(573, 'Loncopué', 15, NULL),
(574, 'Manzano Amargo', 15, NULL),
(575, 'Neuquén', 15, NULL),
(576, 'Piedra del Águila', 15, NULL),
(577, 'Picún Leufú', 15, NULL),
(578, 'Plottier', 15, NULL),
(579, 'Rincón de los Sauces', 15, NULL),
(580, 'San Martín de los Andes', 15, NULL),
(581, 'San Patricio del Chañar', 15, NULL),
(582, 'Senillosa', 15, NULL),
(583, 'Taquimilán', 15, NULL),
(584, 'Villa El Chocón', 15, NULL),
(585, 'Villa La Angostura', 15, NULL),
(586, 'Villa Pehuenia', 15, NULL),
(587, 'Vista Alegre', 15, NULL),
(588, 'Zapala', 15, NULL),
(589, 'Allen', 16, NULL),
(590, 'Bariloche', 16, NULL),
(591, 'Catriel', 16, NULL),
(592, 'Cervantes', 16, NULL),
(593, 'Choele Choel', 16, NULL),
(594, 'Cinco Saltos', 16, NULL),
(595, 'Cipolletti', 16, NULL),
(596, 'Comallo', 16, NULL),
(597, 'Conesa', 16, NULL),
(598, 'Darwin', 16, NULL),
(599, 'El Bolsón', 16, NULL),
(600, 'El Cuy', 16, NULL),
(601, 'General Conesa', 16, NULL),
(602, 'General Enrique Godoy', 16, NULL),
(603, 'General Fernández Oro', 16, NULL),
(604, 'General Roca', 16, NULL),
(605, 'Guardia Mitre', 16, NULL),
(606, 'Ingeniero Jacobacci', 16, NULL),
(607, 'Lamarque', 16, NULL),
(608, 'Las Grutas', 16, NULL),
(609, 'Los Menucos', 16, NULL),
(610, 'Maquinchao', 16, NULL),
(611, 'Mencué', 16, NULL),
(612, 'Ñorquinco', 16, NULL),
(613, 'Pilcaniyeu', 16, NULL),
(614, 'Río Colorado', 16, NULL),
(615, 'San Antonio Oeste', 16, NULL),
(616, 'San Carlos de Bariloche', 16, NULL),
(617, 'Sierra Colorada', 16, NULL),
(618, 'Sierra Grande', 16, NULL),
(619, 'Valcheta', 16, NULL),
(620, 'Viedma', 16, NULL),
(621, 'Villa Mascardi', 16, NULL),
(622, 'Villa Regina', 16, NULL),
(623, 'Aguaray', 17, NULL),
(624, 'Cafayate', 17, NULL),
(625, 'Campo Quijano', 17, NULL),
(626, 'Cachi', 17, NULL),
(627, 'Cerrillos', 17, NULL),
(628, 'Chicoana', 17, NULL),
(629, 'Embarcación', 17, NULL),
(630, 'General Güemes', 17, NULL),
(631, 'General José de San Martín', 17, NULL),
(632, 'General Mosconi', 17, NULL),
(633, 'Hipólito Yrigoyen', 17, NULL),
(634, 'Iruya', 17, NULL),
(635, 'La Caldera', 17, NULL),
(636, 'La Candelaria', 17, NULL),
(637, 'La Poma', 17, NULL),
(638, 'Las Lajitas', 17, NULL),
(639, 'Metán', 17, NULL),
(640, 'Molinos', 17, NULL),
(641, 'Nazareno', 17, NULL),
(642, 'Orán', 17, NULL),
(643, 'Pichanal', 17, NULL),
(644, 'Rivadavia', 17, NULL),
(645, 'Rosario de la Frontera', 17, NULL),
(646, 'Rosario de Lerma', 17, NULL),
(647, 'Salta', 17, NULL),
(648, 'San Antonio de los Cobres', 17, NULL),
(649, 'Santa Victoria Este', 17, NULL),
(650, 'Santa Victoria Oeste', 17, NULL),
(651, 'Tartagal', 17, NULL),
(652, 'Tolar Grande', 17, NULL),
(653, 'Urundel', 17, NULL),
(654, 'Albardón', 18, NULL),
(655, 'Angaco', 18, NULL),
(656, 'Calingasta', 18, NULL),
(657, 'Caucete', 18, NULL),
(658, 'Chimbas', 18, NULL),
(659, 'Iglesia', 18, NULL),
(660, 'Jáchal', 18, NULL),
(661, 'Pocito', 18, NULL),
(662, 'Rawson', 18, NULL),
(663, 'Rivadavia', 18, NULL),
(664, 'Sarmiento', 18, NULL),
(665, 'San Juan', 18, NULL),
(666, 'San Martín', 18, NULL),
(667, 'Santa Lucía', 18, NULL),
(668, 'Ullum', 18, NULL),
(669, 'Valle Fértil', 18, NULL),
(670, 'Zonda', 18, NULL),
(671, '25 de Mayo', 18, NULL),
(672, 'Achiras', 19, NULL),
(673, 'Arizona', 19, NULL),
(674, 'Buena Esperanza', 19, NULL),
(675, 'Candelaria', 19, NULL),
(676, 'Concarán', 19, NULL),
(677, 'Justo Daract', 19, NULL),
(678, 'La Toma', 19, NULL),
(679, 'Luján', 19, NULL),
(680, 'Mercedes', 19, NULL),
(681, 'Merlo', 19, NULL),
(682, 'Navia', 19, NULL),
(683, 'Nogolí', 19, NULL),
(684, 'Papagayos', 19, NULL),
(685, 'Paso Grande', 19, NULL),
(686, 'Quines', 19, NULL),
(687, 'San Francisco del Monte de Oro', 19, NULL),
(688, 'San Luis', 19, NULL),
(689, 'Santa Rosa del Conlara', 19, NULL),
(690, 'Tilisarao', 19, NULL),
(691, 'Villa de la Quebrada', 19, NULL),
(692, 'Villa del Carmen', 19, NULL),
(693, 'Villa General Roca', 19, NULL),
(694, 'Villa Mercedes', 19, NULL),
(695, 'Caleta Olivia', 20, NULL),
(696, 'Cañadón Seco', 20, NULL),
(697, 'Comandante Luis Piedra Buena', 20, NULL),
(698, 'El Calafate', 20, NULL),
(699, 'El Chaltén', 20, NULL),
(700, 'Gobernador Gregores', 20, NULL),
(701, 'Hipólito Yrigoyen', 20, NULL),
(702, 'Jaramillo', 20, NULL),
(703, 'Las Heras', 20, NULL),
(704, 'Los Antiguos', 20, NULL),
(705, 'Perito Moreno', 20, NULL),
(706, 'Puerto Deseado', 20, NULL),
(707, 'Puerto Madryn', 20, NULL),
(708, 'Puerto San Julián', 20, NULL),
(709, 'Puerto Santa Cruz', 20, NULL),
(710, 'Río Gallegos', 20, NULL),
(711, 'Río Turbio', 20, NULL),
(712, '28 de Noviembre', 20, NULL),
(713, 'Avellaneda', 21, NULL),
(714, 'Cañada de Gómez', 21, NULL),
(715, 'Casilda', 21, NULL),
(716, 'Ceres', 21, NULL),
(717, 'Coronda', 21, NULL),
(718, 'Esperanza', 21, NULL),
(719, 'Firmat', 21, NULL),
(720, 'Fray Luis Beltrán', 21, NULL),
(721, 'Gálvez', 21, NULL),
(722, 'Helvecia', 21, NULL),
(723, 'Las Rosas', 21, NULL),
(724, 'Las Toscas', 21, NULL),
(725, 'Laspiur', 21, NULL),
(726, 'Los Molinos', 21, NULL),
(727, 'Malabrigo', 21, NULL),
(728, 'Reconquista', 21, NULL),
(729, 'Rafaela', 21, NULL),
(730, 'Recreo', 21, NULL),
(731, 'Rosario', 21, NULL),
(732, 'Rufino', 21, NULL),
(733, 'San Carlos Centro', 21, NULL),
(734, 'San Cristóbal', 21, NULL),
(735, 'San Francisco', 21, NULL),
(736, 'San Jorge', 21, NULL),
(737, 'San Javier', 21, NULL),
(738, 'San Justo', 21, NULL),
(739, 'Santa Fe', 21, NULL),
(740, 'Santo Tomé', 21, NULL),
(741, 'Sunchales', 21, NULL),
(742, 'Tostado', 21, NULL),
(743, 'Venado Tuerto', 21, NULL),
(744, 'Vera', 21, NULL),
(745, 'Villa Constitución', 21, NULL),
(746, 'Villa Gobernador Gálvez', 21, NULL),
(747, 'Villa Ocampo', 21, NULL),
(748, 'Añatuya', 22, NULL),
(749, 'Atamisqui', 22, NULL),
(750, 'Bandera', 22, NULL),
(751, 'Beltrán', 22, NULL),
(752, 'Campo Gallo', 22, NULL),
(753, 'Colonia El Simbolar', 22, NULL),
(754, 'Clodomira', 22, NULL),
(755, 'El Bobadal', 22, NULL),
(756, 'Fernández', 22, NULL),
(757, 'Frías', 22, NULL),
(758, 'Garza', 22, NULL),
(759, 'Gramilla', 22, NULL),
(760, 'Herrera', 22, NULL),
(761, 'La Banda', 22, NULL),
(762, 'Las Termas de Río Hondo', 22, NULL),
(763, 'Loreto', 22, NULL),
(764, 'Los Juríes', 22, NULL),
(765, 'Los Telares', 22, NULL),
(766, 'Lugones', 22, NULL),
(767, 'Malbrán', 22, NULL),
(768, 'Monte Quemado', 22, NULL),
(769, 'Nueva Esperanza', 22, NULL),
(770, 'Pinto', 22, NULL),
(771, 'Pozo Hondo', 22, NULL),
(772, 'Quimilí', 22, NULL),
(773, 'Río Hondo', 22, NULL),
(774, 'Rodeo', 22, NULL),
(775, 'San Pedro', 22, NULL),
(776, 'Santiago del Estero', 22, NULL),
(777, 'Suncho Corral', 22, NULL),
(778, 'Sumampa', 22, NULL),
(779, 'Tintina', 22, NULL),
(780, 'Udpinango', 22, NULL),
(781, 'Villa Atamisqui', 22, NULL),
(782, 'Villa Ojo de Agua', 22, NULL),
(783, 'Villa Salavina', 22, NULL),
(784, 'Villa Unión', 22, NULL),
(785, 'Vilmer', 22, NULL),
(786, 'Río Grande', 23, NULL),
(787, 'Tolhuin', 23, NULL),
(788, 'Ushuaia', 23, NULL),
(789, 'Aguilares', 24, NULL),
(790, 'Alderetes', 24, NULL),
(791, 'Banda del Río Salí', 24, NULL),
(792, 'Bella Vista', 24, NULL),
(793, 'Burruyacú', 24, NULL),
(794, 'Capilla del Monte', 24, NULL),
(795, 'Chicligasta', 24, NULL),
(796, 'Concepción', 24, NULL),
(797, 'El Bracho', 24, NULL),
(798, 'Famaillá', 24, NULL),
(799, 'Graneros', 24, NULL),
(800, 'Juan Bautista Alberdi', 24, NULL),
(801, 'La Cocha', 24, NULL),
(802, 'Leales', 24, NULL),
(803, 'Lules', 24, NULL),
(804, 'Monteros', 24, NULL),
(805, 'Pozuelos', 24, NULL),
(806, 'Río Chico', 24, NULL),
(807, 'San Miguel de Tucumán', 24, NULL),
(808, 'Simoca', 24, NULL),
(809, 'Tafí del Valle', 24, NULL),
(810, 'Tafí Viejo', 24, NULL),
(811, 'Trancas', 24, NULL),
(812, 'Yerba Buena', 24, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('postulacion','oferta','sistema') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `url_accion` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `tipo`, `titulo`, `mensaje`, `leida`, `url_accion`, `fecha_creacion`) VALUES
(2, 3, 'postulacion', '¡Tu postulación fue aceptada!', 'Felicitaciones, tu postulación para \"Mecanico\" fue aceptada. La empresa se pondrá en contacto pronto.', 1, 'mis-postulaciones.php', '2026-03-03 19:45:54'),
(3, 5, 'sistema', 'Completá tu perfil para mejorar tus chances', 'Falta completar: Localidad preferida. Un perfil completo tiene más visibilidad ante las empresas.', 1, 'perfil-trabajador.php', '2026-03-03 19:48:12'),
(4, 25, 'sistema', 'Completá tu perfil para mejorar tus chances', 'Faltan completar: Teléfono, Fecha de nacimiento, Título / Oficio y 4 campo(s) más. Un perfil completo tiene más visibilidad ante las empresas.', 0, 'perfil-trabajador.php', '2026-03-03 22:56:13'),
(5, 25, 'oferta', 'Nueva oferta compatible: Oficial Albañil con Experiencia en Obras Civiles', 'Hay una nueva oferta de Albañil que coincide con tu perfil.', 0, 'ofertas-laborales.php?ver=13', '2026-03-03 23:47:54'),
(7, 3, 'oferta', 'Nueva oferta compatible: Oficial Albañil con Experiencia en Obras Civiles', 'Hay una nueva oferta de Albañil que coincide con tu perfil.', 1, 'ofertas-laborales.php?ver=13', '2026-03-04 00:08:36'),
(8, 25, 'postulacion', '¡Tu postulación fue aceptada!', 'Felicitaciones, tu postulación para \"secretario\" fue aceptada. La empresa se pondrá en contacto pronto.', 0, 'mis-postulaciones.php', '2026-03-04 02:23:06'),
(9, 25, 'sistema', 'Completá tu perfil para mejorar tus chances', 'Faltan completar: Teléfono, Fecha de nacimiento, Título / Oficio y 4 campo(s) más. Un perfil completo tiene más visibilidad ante las empresas.', 0, 'perfil-trabajador.php', '2026-03-04 02:42:25'),
(11, 3, 'postulacion', 'Tu postulación no fue seleccionada', 'Tu postulación para \"secretario\" no fue seleccionada esta vez. ¡Seguí intentando!', 1, 'mis-postulaciones.php', '2026-03-06 17:29:22'),
(12, 3, 'postulacion', '¡Tu postulación fue aceptada!', 'Felicitaciones, tu postulación para \"secretario\" fue aceptada. La empresa se pondrá en contacto pronto.', 1, 'mis-postulaciones.php', '2026-03-06 17:30:05'),
(13, 25, 'postulacion', '¡Tu postulación fue aceptada!', 'Felicitaciones, tu postulación para \"secretario\" fue aceptada. La empresa se pondrá en contacto pronto.', 0, 'mis-postulaciones.php', '2026-03-06 17:31:18'),
(14, 5, 'postulacion', 'Tu postulación no fue seleccionada', 'Tu postulación para \"Mecanico\" no fue seleccionada esta vez. ¡Seguí intentando!', 0, 'mis-postulaciones.php', '2026-03-06 17:50:53'),
(15, 3, 'oferta', 'Nueva oferta compatible: Oficial Albañil con Experiencia en Obras Civiles', 'Hay una nueva oferta de Albañil que coincide con tu perfil.', 1, 'ofertas-laborales.php?ver=17', '2026-03-06 23:48:37'),
(16, 3, 'oferta', 'Nueva oferta compatible: Ayudante de plomería para obras en construcción', 'Hay una nueva oferta de Plomero que coincide con tu perfil.', 1, 'ofertas-laborales.php?ver=22', '2026-03-07 17:53:35'),
(17, 3, 'oferta', 'Nueva oferta compatible: Gasista matriculado para instalaciones domiciliarias', 'Hay una nueva oferta de Plomero que coincide con tu perfil.', 1, 'ofertas-laborales.php?ver=21', '2026-03-07 17:53:35'),
(18, 3, 'oferta', 'Nueva oferta compatible: Plomero con experiencia en instalaciones sanitarias', 'Hay una nueva oferta de Plomero que coincide con tu perfil.', 1, 'ofertas-laborales.php?ver=20', '2026-03-07 17:53:35'),
(20, 40, 'sistema', 'Completá tu perfil para mejorar tus chances', 'Faltan completar: Teléfono, Fecha de nacimiento, Título / Oficio y 4 campo(s) más. Un perfil completo tiene más visibilidad ante las empresas.', 1, 'perfil-trabajador.php', '2026-03-08 20:25:32'),
(21, 40, 'oferta', 'Nueva oferta compatible: Ayudante de plomería para obras en construcción', 'Hay una nueva oferta de Plomero que coincide con tu perfil.', 0, 'ofertas-laborales.php?ver=22', '2026-03-08 20:25:32'),
(22, 40, 'oferta', 'Nueva oferta compatible: Gasista matriculado para instalaciones domiciliarias', 'Hay una nueva oferta de Plomero que coincide con tu perfil.', 0, 'ofertas-laborales.php?ver=21', '2026-03-08 20:25:32'),
(23, 40, 'oferta', 'Nueva oferta compatible: Plomero con experiencia en instalaciones sanitarias', 'Hay una nueva oferta de Plomero que coincide con tu perfil.', 0, 'ofertas-laborales.php?ver=20', '2026-03-08 20:25:32'),
(24, 3, 'postulacion', '¡Tu postulación fue aceptada!', 'Felicitaciones, tu postulación para \"Oficial Albañil con Experiencia en Obras Civiles\" fue aceptada. La empresa se pondrá en contacto pronto.', 1, 'mis-postulaciones.php', '2026-03-08 20:44:52'),
(25, 40, 'sistema', 'Completá tu perfil para mejorar tus chances', 'Faltan completar: Teléfono, Título / Oficio, Domicilio y 2 campo(s) más. Un perfil completo tiene más visibilidad ante las empresas.', 0, 'perfil-trabajador.php', '2026-03-18 20:37:47'),
(26, 40, 'postulacion', '¡Tu postulación fue aceptada!', 'Felicitaciones, tu postulación para \"Electricista para Instalaciones en Obras Nuevas\" fue aceptada. La empresa se pondrá en contacto pronto.', 0, 'mis-postulaciones.php', '2026-03-18 22:20:00'),
(27, 40, 'sistema', 'Completá tu perfil para mejorar tus chances', 'Faltan completar: Teléfono, Título / Oficio, Domicilio y 2 campo(s) más. Un perfil completo tiene más visibilidad ante las empresas.', 0, 'perfil-trabajador.php', '2026-03-19 01:12:14'),
(28, 5, 'sistema', 'Completá tu perfil para mejorar tus chances', 'Falta completar: Localidad preferida. Un perfil completo tiene más visibilidad ante las empresas.', 0, 'perfil-trabajador.php', '2026-03-19 01:14:11'),
(29, 5, 'postulacion', 'Tu postulación no fue seleccionada', 'Tu postulación para \"Electricista para Instalaciones en Obras Nuevas\" no fue seleccionada esta vez. ¡Seguí intentando!', 0, 'mis-postulaciones.php', '2026-03-19 17:05:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ofertas_laborales`
--

CREATE TABLE `ofertas_laborales` (
  `id_oferta` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `id_rubro` int(11) DEFAULT NULL COMMENT 'Rubro de la oferta',
  `titulo` varchar(200) NOT NULL,
  `descripcion` text NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `requisitos` text DEFAULT NULL,
  `salario_min` decimal(10,2) DEFAULT NULL,
  `salario_max` decimal(10,2) DEFAULT NULL,
  `tipo_contrato` enum('Tiempo completo','Medio tiempo','Por proyecto','Pasantía') DEFAULT 'Tiempo completo',
  `modalidad` enum('Presencial','Remoto','Híbrido') DEFAULT 'Presencial',
  `id_provincia` int(11) DEFAULT NULL,
  `id_localidad` int(11) DEFAULT NULL,
  `experiencia_requerida` int(11) DEFAULT NULL COMMENT 'Años de experiencia',
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('Activa','Pausada','Cerrada','Borrador') DEFAULT 'Activa',
  `visitas` int(11) DEFAULT 0,
  `postulaciones` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ofertas_laborales`
--

INSERT INTO `ofertas_laborales` (`id_oferta`, `id_empresa`, `id_rubro`, `titulo`, `descripcion`, `id_especialidad`, `requisitos`, `salario_min`, `salario_max`, `tipo_contrato`, `modalidad`, `id_provincia`, `id_localidad`, `experiencia_requerida`, `fecha_publicacion`, `fecha_vencimiento`, `estado`, `visitas`, `postulaciones`) VALUES
(7, 1, NULL, 'Técnico administrativo', 'Se busca técnico en informática o administración publica para el puesto de administrativo', 16, 'Excelente manejo de paquete office', 50000.00, 100000.00, 'Por proyecto', 'Híbrido', 3, NULL, 1, '2026-02-22 05:02:51', '2026-12-22', 'Borrador', 0, 0),
(13, 6, NULL, 'Oficial Albañil con Experiencia en Obras Civiles', 'Empresa constructora se encuentra en la búsqueda de Oficial Albañil para obras en ejecución. Sus principales tareas serán levantamiento de muros, revoques, colocación de cerámicos, contrapiso y trabajos generales de albañilería.\r\nSe ofrece incorporación inmediata, continuidad laboral según desempeño y buen clima de trabajo en equipo. Obras en zona urbana.\r\nJornada completa de lunes a viernes.', 1, 'Experiencia mínima de 3 años en obras.\r\nConocimiento en lectura básica de planos.\r\nManejo de herramientas manuales y eléctricas.\r\nResponsabilidad y puntualidad.\r\nDisponibilidad', NULL, NULL, 'Tiempo completo', 'Presencial', 10, 446, 3, '2026-03-04 02:41:09', '2026-05-12', 'Activa', 0, 0),
(15, 1, NULL, 'Electricista para Instalaciones en Obras Nuevas', 'Empresa del rubro construcción busca electricista para instalaciones domiciliarias e industriales en obras en ejecución. Tareas: cableado, montaje de tableros, canalizaciones, instalación de artefactos y pruebas de funcionamiento.\r\nTrabajo en equipo con arquitectos y contratistas.', 2, '*Experiencia mínima de 2 años en instalaciones eléctricas.\r\n*Conocimiento de normativa eléctrica vigente.\r\n*Herramientas propias (preferentemente).\r\n*Matrícula habilitante (si corresponde).', 500000.00, 1000000.00, 'Por proyecto', 'Presencial', 2, 154, 3, '2026-03-06 20:49:09', '2026-03-20', 'Activa', 0, 0),
(16, 1, NULL, 'Maestro Mayor de Obras para Dirección Técnica', 'Nos encontramos en la búsqueda de Maestro Mayor de Obras para supervisión y control de proyectos residenciales. Será responsable del seguimiento de obra, coordinación de gremios, control de materiales y cumplimiento de plazos.\r\n\r\nModalidad presencial en obra. Posibilidad de crecimiento dentro de la empresa.', 16, 'Título habilitante de Maestro Mayor de Obras.\r\n\r\nExperiencia comprobable en dirección o supervisión de obra.\r\n\r\nConocimientos en cómputo y presupuesto.\r\n\r\nManejo de AutoCAD (deseable).\r\n\r\nMatrícula profesional habilitada.', NULL, NULL, 'Tiempo completo', 'Presencial', 13, 514, 4, '2026-03-06 20:53:34', '2026-04-12', 'Activa', 0, 0),
(17, 1, NULL, 'Oficial Albañil con Experiencia en Obras Civiles', 'Empresa constructora se encuentra en la búsqueda de Oficial Albañil para obras en ejecución. Sus principales tareas serán levantamiento de muros, revoques, colocación de cerámicos, contrapiso y trabajos generales de albañilería.\r\n\r\nSe ofrece incorporación inmediata, continuidad laboral según desempeño y buen clima de trabajo en equipo. Obras en zona urbana.\r\n\r\nJornada completa de lunes a viernes.', 1, 'Experiencia mínima de 3 años en obras.\r\n\r\nConocimiento en lectura básica de planos.\r\n\r\nManejo de herramientas manuales y eléctricas.\r\n\r\nResponsabilidad y puntualidad.\r\n\r\nDisponibilidad horaria.', NULL, NULL, 'Tiempo completo', 'Presencial', 22, 783, 0, '2026-03-06 20:54:36', '2026-05-21', 'Activa', 0, 0),
(20, 10, NULL, 'Plomero con experiencia en instalaciones sanitarias', 'HidroSoluciones Instalaciones SRL se encuentra en la búsqueda de un plomero con experiencia para incorporarse a su equipo de trabajo. Las tareas incluyen instalación y mantenimiento de sistemas de agua potable, desagües cloacales y pluviales en obras residenciales y comerciales.\r\n\r\nEl puesto requiere trabajo en obra, interpretación básica de planos y coordinación con otros rubros de construcción.', 3, 'Experiencia mínima de 2 años en plomería de obra\r\nConocimiento en instalación de cañerías PVC, PPR y termofusión\r\nManejo de herramientas manuales del oficio\r\nResponsabilidad y compromiso con el trabajo\r\nDisponibilidad para jornada completa', 800000.00, 1200000.00, 'Tiempo completo', 'Presencial', 3, 197, 2, '2026-03-07 20:07:50', '2026-03-20', 'Activa', 0, 0),
(21, 10, NULL, 'Gasista matriculado para instalaciones domiciliarias', 'Empresa del rubro instalaciones sanitarias busca gasista matriculado para realizar instalaciones y mantenimiento de redes de gas en viviendas y locales comerciales.\r\n\r\nLas tareas incluyen instalación de cañerías de gas, conexión de artefactos, pruebas de hermeticidad y cumplimiento de normativas de seguridad.', 3, 'Matrícula habilitante de gasista\r\nExperiencia comprobable en instalaciones de gas\r\nConocimiento de normativas de seguridad\r\nCapacidad para trabajar en equipo\r\nHerramientas propias (preferentemente)', 600.00, 1000000.00, 'Tiempo completo', 'Presencial', 12, 492, 1, '2026-03-07 20:10:00', '2026-03-21', 'Activa', 0, 0),
(22, 10, NULL, 'Ayudante de plomería para obras en construcción', 'HidroSoluciones busca ayudante de plomería para asistir en tareas generales de instalación sanitaria en obras en ejecución. El puesto incluye preparación de materiales, asistencia en instalación de cañerías y mantenimiento del área de trabajo.\r\n\r\nSe ofrece posibilidad de aprendizaje y crecimiento dentro de la empresa.', 3, 'Experiencia básica en obras o construcción\r\nGanas de aprender el oficio\r\nPuntualidad y responsabilidad\r\nBuena predisposición para trabajo en equipo', NULL, NULL, 'Tiempo completo', 'Presencial', 3, 8, 0, '2026-03-07 20:12:37', '2026-03-19', 'Cerrada', 0, 0),
(23, 11, NULL, 'Soldador con experiencia en estructuras metálicas', 'MetalWorks Ingeniería y Construcción S.A. se encuentra en la búsqueda de un soldador con experiencia para incorporarse a su equipo de trabajo. Las tareas incluyen fabricación y montaje de estructuras metálicas, soldadura de piezas en obra y en taller, y mantenimiento de estructuras existentes.\r\n\r\nSe valorará experiencia en proyectos de construcción y montaje de estructuras para obras civiles.', 6, 'Experiencia mínima de 2 años en soldadura\r\nConocimiento en soldadura eléctrica y MIG\r\nManejo de herramientas de corte y amolado\r\nResponsabilidad y compromiso con el trabajo\r\nDisponibilidad para trabajo en obra y taller', 800000.00, 1000000.00, 'Tiempo completo', 'Presencial', 16, 590, 2, '2026-03-07 23:00:48', '2026-04-12', 'Activa', 0, 0),
(24, 11, NULL, 'Herrero para fabricación de portones y estructuras metálicas', 'Empresa del rubro metalúrgico busca herrero con experiencia en fabricación de portones, rejas, estructuras metálicas y trabajos de herrería general.\r\n\r\nLas tareas incluyen corte de materiales, armado de estructuras y soldadura de piezas metálicas.', 11, 'Experiencia en herrería y metalurgia\r\nManejo de herramientas como amoladora, taladro y soldadora\r\nCapacidad para interpretar medidas y planos simples\r\nTrabajo en equipo y responsabilidad', 800000.00, NULL, 'Tiempo completo', 'Presencial', 15, 575, 1, '2026-03-07 23:02:17', '2026-04-12', 'Activa', 0, 0),
(25, 11, NULL, 'Ayudante de herrería', 'MetalWorks busca ayudante para asistir en tareas generales de herrería y soldadura en taller y obras. El puesto incluye preparación de materiales, asistencia en armado de estructuras y mantenimiento del área de trabajo.\r\n\r\nSe ofrece posibilidad de aprendizaje y crecimiento dentro de la empresa.', 11, 'Experiencia básica en construcción o metalurgia (no excluyente)\r\nBuena predisposición para aprender\r\nResponsabilidad y puntualidad\r\nDisponibilidad para jornada completa', 600000.00, 900000.00, 'Tiempo completo', 'Presencial', 15, 575, NULL, '2026-03-07 23:04:03', '2026-04-12', 'Activa', 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oferta_especialidades`
--

CREATE TABLE `oferta_especialidades` (
  `id` int(11) NOT NULL,
  `id_oferta` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `es_principal` tinyint(1) DEFAULT 0 COMMENT '1 si es la especialidad principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfil`
--

CREATE TABLE `perfil` (
  `id_perfil` int(11) NOT NULL,
  `tipo` int(11) DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona`
--

CREATE TABLE `persona` (
  `id_persona` int(11) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `apellido` varchar(50) DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `descripcion_persona` text NOT NULL DEFAULT '' COMMENT 'Descripción breve del perfil del trabajador',
  `anios_experiencia` int(2) DEFAULT 0 COMMENT 'Años totales de experiencia en construcción',
  `curriculum_pdf` varchar(255) DEFAULT NULL COMMENT 'Ruta del archivo PDF del curriculum',
  `domicilio` varchar(100) DEFAULT NULL,
  `id_provincia_preferencia` int(11) DEFAULT NULL COMMENT 'Provincia donde busca trabajo',
  `id_localidad_preferencia` int(11) DEFAULT NULL COMMENT 'Localidad donde busca trabajo',
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `nombre_titulo` varchar(50) DEFAULT NULL,
  `imagen_perfil` varchar(255) NOT NULL,
  `georeferencia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `persona`
--

INSERT INTO `persona` (`id_persona`, `dni`, `apellido`, `nombre`, `descripcion_persona`, `anios_experiencia`, `curriculum_pdf`, `domicilio`, `id_provincia_preferencia`, `id_localidad_preferencia`, `telefono`, `fecha_nacimiento`, `nombre_titulo`, `imagen_perfil`, `georeferencia`) VALUES
(1, '20425194756', 'Ortiz ', 'Martin', '', 0, NULL, 'eessa', NULL, NULL, NULL, '2026-03-14', NULL, '', NULL),
(2, NULL, 'Ortiz', 'Pablo', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL),
(3, '10101012', 'Mazza', 'Leo', 'Albañil con experiencia en construcción y refacción de viviendas particulares. Realizo trabajos de mampostería, revoque, colocación de pisos y revestimientos, ampliaciones y arreglos generales. Me destaco por la prolijidad en terminaciones y el trato respetuoso con clientes. Trabajo tanto de manera independiente como en equipo.', 3, 'cv_3_1771479577.pdf', 'ESTANISLAO MALDONES 167', 3, 8, '+543834285017', '1986-06-26', 'Albañil', 'perfil_3_1773012009.jpg', ''),
(5, '41205949', 'Ortiz', 'Martin', 'Electricista con experiencia en automotores', 1, '', 'San martin 156', 10, NULL, '+543834285017', '2000-02-13', 'Tec. en motores', '', ''),
(7, '40519789', 'Lqk', 'Lucas', '', 0, NULL, NULL, 18, NULL, '3834507968', NULL, 'panadero', '', NULL),
(9, '12345678', 'Kontos', 'Eric', 'Albañil con experiencia en construcción y refacción de viviendas particulares. Realizo trabajos de mampostería, revoque, colocación de pisos y revestimientos, ampliaciones y arreglos generales. Me destaco por la prolijidad en terminaciones y el trato respetuoso con clientes. Trabajo tanto de manera independiente como en equipo.', 5, '', 'Av. Illia 111 ', 3, NULL, '+543834285017', '1996-03-10', 'Tecnico Maestro mayor de obra', 'perfil_9_1772603100.webp', ''),
(13, '98776554', 'Perez Garcia', 'Juan', '', 0, 'cv_13_1773012954.pdf', '', 2, NULL, '', '2000-12-12', '', 'perfil_13_1773012954.jpg', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona_especialidades`
--

CREATE TABLE `persona_especialidades` (
  `id_persona_especialidad` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `nivel_experiencia` enum('Básico','Intermedio','Avanzado','Experto') DEFAULT 'Básico',
  `fecha_agregado` datetime DEFAULT current_timestamp(),
  `es_principal` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `persona_especialidades`
--

INSERT INTO `persona_especialidades` (`id_persona_especialidad`, `id_persona`, `id_especialidad`, `nivel_experiencia`, `fecha_agregado`, `es_principal`) VALUES
(14, 5, 7, 'Experto', '2026-02-25 23:03:56', 1),
(60, 9, 1, 'Experto', '2026-03-04 02:51:50', 1),
(61, 9, 15, 'Básico', '2026-03-04 02:51:50', 0),
(62, 9, 10, 'Básico', '2026-03-04 02:51:50', 0),
(63, 9, 7, 'Avanzado', '2026-03-04 02:51:50', 0),
(64, 9, 5, 'Básico', '2026-03-04 02:51:50', 0),
(65, 9, 6, 'Básico', '2026-03-04 02:51:50', 0),
(79, 3, 1, 'Avanzado', '2026-03-08 20:20:09', 1),
(80, 3, 15, 'Experto', '2026-03-08 20:20:09', 0),
(81, 3, 4, 'Básico', '2026-03-08 20:20:09', 0),
(82, 3, 3, 'Básico', '2026-03-08 20:20:09', 0),
(87, 13, 4, 'Básico', '2026-03-08 20:35:54', 0),
(88, 13, 3, 'Básico', '2026-03-08 20:35:54', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `postulaciones`
--

CREATE TABLE `postulaciones` (
  `id_postulacion` int(11) NOT NULL,
  `id_oferta` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `mensaje` text DEFAULT NULL COMMENT 'Carta de presentación',
  `cv_adjunto` varchar(255) DEFAULT NULL,
  `estado` enum('Pendiente','Revisada','Entrevista','Aceptada','Rechazada') DEFAULT 'Pendiente',
  `fecha_postulacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notas_empresa` text DEFAULT NULL COMMENT 'Notas internas de la empresa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `postulaciones`
--

INSERT INTO `postulaciones` (`id_postulacion`, `id_oferta`, `id_persona`, `mensaje`, `cv_adjunto`, `estado`, `fecha_postulacion`, `fecha_actualizacion`, `notas_empresa`) VALUES
(6, 7, 3, NULL, NULL, 'Rechazada', '2026-02-25 05:11:54', '2026-02-26 01:59:41', NULL),
(15, 22, 3, NULL, NULL, 'Pendiente', '2026-03-07 22:06:05', '2026-03-07 22:06:05', NULL),
(16, 17, 3, NULL, NULL, 'Aceptada', '2026-03-07 22:06:16', '2026-03-08 23:45:16', 'buen perfil'),
(17, 20, 3, NULL, NULL, 'Pendiente', '2026-03-08 23:17:09', '2026-03-08 23:17:09', NULL),
(18, 15, 13, NULL, NULL, 'Revisada', '2026-03-08 23:36:57', '2026-03-19 20:05:41', NULL),
(19, 25, 13, NULL, NULL, 'Pendiente', '2026-03-19 00:15:48', '2026-03-19 00:15:48', NULL),
(20, 15, 5, NULL, NULL, 'Rechazada', '2026-03-19 04:14:27', '2026-03-19 20:05:32', 'Perfil para otro sector.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provincias`
--

CREATE TABLE `provincias` (
  `id_provincia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(5) DEFAULT NULL COMMENT 'Código ISO o postal',
  `region` varchar(50) DEFAULT NULL COMMENT 'NOA, NEA, Cuyo, Centro, Patagonia',
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `provincias`
--

INSERT INTO `provincias` (`id_provincia`, `nombre`, `codigo`, `region`, `estado`) VALUES
(1, 'Buenos Aires', 'BA', 'Centro', 1),
(2, 'Ciudad Autónoma de Buenos Aires', 'CABA', 'Centro', 1),
(3, 'Catamarca', 'CT', 'NOA', 1),
(4, 'Chaco', 'CC', 'NEA', 1),
(5, 'Chubut', 'CH', 'Patagonia', 1),
(6, 'Córdoba', 'CB', 'Centro', 1),
(7, 'Corrientes', 'CR', 'NEA', 1),
(8, 'Entre Ríos', 'ER', 'Centro', 1),
(9, 'Formosa', 'FO', 'NEA', 1),
(10, 'Jujuy', 'JY', 'NOA', 1),
(11, 'La Pampa', 'LP', 'Centro', 1),
(12, 'La Rioja', 'LR', 'NOA', 1),
(13, 'Mendoza', 'MZ', 'Cuyo', 1),
(14, 'Misiones', 'MI', 'NEA', 1),
(15, 'Neuquén', 'NQ', 'Patagonia', 1),
(16, 'Río Negro', 'RN', 'Patagonia', 1),
(17, 'Salta', 'SA', 'NOA', 1),
(18, 'San Juan', 'SJ', 'Cuyo', 1),
(19, 'San Luis', 'SL', 'Cuyo', 1),
(20, 'Santa Cruz', 'SC', 'Patagonia', 1),
(21, 'Santa Fe', 'SF', 'Centro', 1),
(22, 'Santiago del Estero', 'SE', 'NOA', 1),
(23, 'Tierra del Fuego', 'TF', 'Patagonia', 1),
(24, 'Tucumán', 'TM', 'NOA', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reclutadores`
--

CREATE TABLE `reclutadores` (
  `id_reclutador` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `apellido` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_alta` date DEFAULT curdate(),
  `estado` varchar(20) NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reclutadores`
--

INSERT INTO `reclutadores` (`id_reclutador`, `id_usuario`, `id_empresa`, `nombre`, `apellido`, `telefono`, `fecha_alta`, `estado`) VALUES
(1, 15, 1, 'Juan Ignacio', 'Perez', '03834285018', '2026-02-26', 'activo'),
(2, 16, 1, 'Leandro', 'Paredes', '3834123456', '2026-02-26', 'activo'),
(9, 41, 1, 'Pablo', 'Perez', '03834285017', '2026-03-24', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id_reporte` int(11) NOT NULL,
  `tipo` enum('empresa','trabajador','oferta') NOT NULL,
  `id_referencia` int(11) NOT NULL,
  `motivo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_usuario_reporta` int(11) DEFAULT NULL,
  `estado` enum('pendiente','revisado','resuelto','descartado') DEFAULT 'pendiente',
  `accion_tomada` text DEFAULT NULL,
  `fecha_reporte` datetime DEFAULT current_timestamp(),
  `fecha_revision` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rubros`
--

CREATE TABLE `rubros` (
  `id_rubro` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL COMMENT 'Nombre del icono para UI',
  `estado` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0 COMMENT 'Para ordenar en el frontend'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rubros`
--

INSERT INTO `rubros` (`id_rubro`, `nombre`, `descripcion`, `icono`, `estado`, `orden`) VALUES
(1, 'Construcción General', 'Obras civiles, edificación, reformas', 'building', 1, 1),
(2, 'Electricidad', 'Instalaciones eléctricas residenciales y comerciales', 'bolt', 1, 2),
(3, 'Plomería y Gas', 'Instalaciones sanitarias, agua, gas', 'wrench', 1, 3),
(4, 'Carpintería', 'Trabajos en madera, muebles, aberturas', 'hammer', 1, 4),
(5, 'Pintura y Revestimientos', 'Pintura, durlock, yeso', 'paint-brush', 1, 5),
(6, 'Herrería y Soldadura', 'Trabajos en metal, rejas, portones', 'shield', 1, 6),
(7, 'Paisajismo y Jardinería', 'Diseño de jardines, mantenimiento', 'leaf', 1, 7),
(8, 'Climatización', 'Aire acondicionado, calefacción, ventilación', 'snowflake', 1, 8),
(9, 'Techado e Impermeabilización', 'Techos, membranas, impermeabilización', 'house', 1, 9),
(10, 'Demolición y Excavación', 'Movimiento de suelos, demoliciones', 'truck', 1, 10),
(11, 'Arquitectura y Diseño', 'Diseño, planos, dirección de obra', 'compass', 1, 11),
(12, 'Administración de Obras', 'Gestión de proyectos, supervisión', 'clipboard', 1, 12);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_usuario`
--

CREATE TABLE `tipo_usuario` (
  `id_tipo` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_usuario`
--

INSERT INTO `tipo_usuario` (`id_tipo`, `nombre`, `estado`) VALUES
(1, 'Administrador', 'activo'),
(2, 'Trabajador', 'activo'),
(3, 'Empresa', 'activo'),
(4, 'Reclutador', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id_usuario` int(11) NOT NULL,
  `id_persona` int(11) DEFAULT NULL,
  `id_empresa` int(11) DEFAULT NULL,
  `usuario` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `tipo` int(11) DEFAULT NULL,
  `estado` varchar(20) DEFAULT NULL,
  `visible_busqueda` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Si el trabajador aparece en búsquedas de empresas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id_usuario`, `id_persona`, `id_empresa`, `usuario`, `email`, `contrasena`, `fecha_creacion`, `tipo`, `estado`, `visible_busqueda`) VALUES
(1, 1, NULL, 'martinom', 'martin@mail.com', '$2y$10$.gN9ulZ4SPwPfX2zwPV6mO3jO03Tiav95ZGi1qroth6bhlfiSTdvW', '2025-07-07', 1, 'activo', 1),
(2, 2, NULL, NULL, 'pablo@mail.com', '$2y$10$e0tdGK6hxT5m55TVRa7Z3u03dnAPSMig8JHxSQSoGiNEBQlehdI9i', NULL, 2, 'inactivo', 1),
(3, 3, NULL, NULL, 'mazza@mail.com', '$2y$10$8WBVPpxw3b4sjUxmAkIdtuhZb1A3WS0AdUe3XBcpBzAnFAr1nMI1S', NULL, 2, 'activo', 1),
(5, 5, NULL, NULL, 'martinortiz@hotmail.com', '$2y$10$tk7x77mR23AcWlRqmBJZHeMaO785FsS3QU6P1QEAShQkaOx5plTre', NULL, 2, 'activo', 1),
(7, NULL, 1, NULL, 'arnoa@arnoa.com', '$2y$10$AF/YAX2WQRuxxQ/KKr7ImuTm2t9KTq0MK.iS7iYpjfVmJ7vJQLjXm', NULL, 3, 'activo', 1),
(15, NULL, 1, 'juan@mail.com', 'juan@mail.com', '$2y$10$JuMJTVZ3Q5ZsVpeGaiY9xuTdUfMgvStdQ1T54giukL9lfCNn8Ivs.', NULL, 4, 'activo', 1),
(16, NULL, 1, 'lea@mail.com', 'leaparedes@mail.com', '$2y$10$upizgRZ.4wpOrU.aOZOUZO/udV9klk3oTUc2aijaaZBbFUDsd0qpa', NULL, 4, 'activo', 1),
(25, 9, NULL, NULL, 'eric@gmail.com', '$2y$10$9znuMZwtq//It5cMn7CcBur2xumcvUnHBKAPeU/gUwO/TTkqQmJkC', NULL, 2, 'activo', 1),
(26, NULL, 6, NULL, 'noa@noa.com', '$2y$10$6H.rIcEuBa9iGs5uEiKcmOBG7pUUbtL.rwXOHHBd3TlRODhgV45Ai', NULL, 3, 'activo', 1),
(27, NULL, NULL, 'pablo', 'pabloadmin@mail.com', '$2y$10$B90Fz4AMGv.7N/PWi6jEje1UfELxVrEN8W8vMfEP/3nUEG2zSeIkO', '2026-03-05', 1, 'activo', 1),
(32, NULL, 8, NULL, 'candina@mail.com', '$2y$10$f2PJpigPWoKel0HanSGJDuTSSpoaWbMjA5lLbX5QKowDHvZnePWFm', NULL, 3, 'inactivo', 1),
(33, NULL, 9, NULL, 'ingdelnorte@mail.com', '$2y$10$IhbMjRHjGYzbHom19rq.GeR7JqM/bXKUbClT7HLxlwYmY6yEN2VtG', NULL, 3, 'activo', 1),
(34, NULL, 10, NULL, 'hidro@mail.com', '$2y$10$si.4kxIdM1MwYBabuPZEs.a5a9IhtIVSRWVRtMega/JAi1vetxrJa', NULL, 3, 'activo', 1),
(35, NULL, 11, NULL, 'metalw@mail.com', '$2y$10$n8YwU5JztWovFbnkBwJgUOpBKsvzfIiXKxay/vvx8twKd0yksbobO', NULL, 3, 'activo', 1),
(40, 13, NULL, NULL, 'juanpg@mail.com', '$2y$10$CUHkzomar0WJ2qp.iHIhHOq.aPT8.AhJTYijj/ewTLbLtMuFrxWpO', NULL, 2, 'activo', 1),
(41, NULL, 1, NULL, 'pabloperez@mail.com', '$2y$10$22cxXkZ7OF1oQha0mUZLnOmmPHndu7nT7IS20yRik/iKzkdIFPEGG', NULL, 4, 'activo', 1),
(42, NULL, NULL, 'terpo', 'terpo@mail.com', '$2y$10$DnnXAUSOBSTO1liuR/iRwe.YGZfd3nCNYdeNlH8cbh4OaFmSCqUQu', '2026-03-24', 1, 'activo', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `id_empresa` (`id_empresa`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`id_empresa`),
  ADD KEY `idx_id_provincia` (`id_provincia`),
  ADD KEY `idx_nombre_empresa` (`nombre_empresa`),
  ADD KEY `idx_id_rubro` (`id_rubro`);

--
-- Indices de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id_especialidad`),
  ADD UNIQUE KEY `nombre_especialidad` (`nombre_especialidad`);

--
-- Indices de la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD PRIMARY KEY (`id_localidad`),
  ADD KEY `idx_provincia` (`id_provincia`),
  ADD KEY `idx_nombre` (`nombre_localidad`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `ofertas_laborales`
--
ALTER TABLE `ofertas_laborales`
  ADD PRIMARY KEY (`id_oferta`),
  ADD KEY `id_empresa` (`id_empresa`),
  ADD KEY `id_provincia` (`id_provincia`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha_publicacion` (`fecha_publicacion`),
  ADD KEY `idx_id_rubro` (`id_rubro`),
  ADD KEY `fk_oferta_especialidad` (`id_especialidad`);

--
-- Indices de la tabla `oferta_especialidades`
--
ALTER TABLE `oferta_especialidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_oferta_especialidad` (`id_oferta`,`id_especialidad`),
  ADD KEY `id_especialidad` (`id_especialidad`);

--
-- Indices de la tabla `perfil`
--
ALTER TABLE `perfil`
  ADD PRIMARY KEY (`id_perfil`);

--
-- Indices de la tabla `persona`
--
ALTER TABLE `persona`
  ADD PRIMARY KEY (`id_persona`),
  ADD KEY `fk_persona_provincia_pref` (`id_provincia_preferencia`),
  ADD KEY `fk_persona_localidad_pref` (`id_localidad_preferencia`);

--
-- Indices de la tabla `persona_especialidades`
--
ALTER TABLE `persona_especialidades`
  ADD PRIMARY KEY (`id_persona_especialidad`),
  ADD UNIQUE KEY `unique_persona_especialidad` (`id_persona`,`id_especialidad`),
  ADD KEY `id_especialidad` (`id_especialidad`);

--
-- Indices de la tabla `postulaciones`
--
ALTER TABLE `postulaciones`
  ADD PRIMARY KEY (`id_postulacion`),
  ADD UNIQUE KEY `unique_postulacion` (`id_oferta`,`id_persona`),
  ADD KEY `id_persona` (`id_persona`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha` (`fecha_postulacion`);

--
-- Indices de la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD PRIMARY KEY (`id_provincia`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `reclutadores`
--
ALTER TABLE `reclutadores`
  ADD PRIMARY KEY (`id_reclutador`),
  ADD UNIQUE KEY `uk_usuario` (`id_usuario`),
  ADD KEY `id_empresa` (`id_empresa`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id_reporte`);

--
-- Indices de la tabla `rubros`
--
ALTER TABLE `rubros`
  ADD PRIMARY KEY (`id_rubro`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  ADD PRIMARY KEY (`id_tipo`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`,`email`),
  ADD KEY `id_persona` (`id_persona`),
  ADD KEY `tipo` (`tipo`),
  ADD KEY `idx_id_empresa` (`id_empresa`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id_empresa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id_especialidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id_localidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=813;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `ofertas_laborales`
--
ALTER TABLE `ofertas_laborales`
  MODIFY `id_oferta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `oferta_especialidades`
--
ALTER TABLE `oferta_especialidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `perfil`
--
ALTER TABLE `perfil`
  MODIFY `id_perfil` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `persona`
--
ALTER TABLE `persona`
  MODIFY `id_persona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `persona_especialidades`
--
ALTER TABLE `persona_especialidades`
  MODIFY `id_persona_especialidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de la tabla `postulaciones`
--
ALTER TABLE `postulaciones`
  MODIFY `id_postulacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `provincias`
--
ALTER TABLE `provincias`
  MODIFY `id_provincia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `reclutadores`
--
ALTER TABLE `reclutadores`
  MODIFY `id_reclutador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id_reporte` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rubros`
--
ALTER TABLE `rubros`
  MODIFY `id_rubro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  MODIFY `id_tipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `auditoria_ibfk_2` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`);

--
-- Filtros para la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD CONSTRAINT `fk_empresa_provincia` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_empresa_rubro` FOREIGN KEY (`id_rubro`) REFERENCES `rubros` (`id_rubro`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD CONSTRAINT `localidades_ibfk_1` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id_usuario`);

--
-- Filtros para la tabla `ofertas_laborales`
--
ALTER TABLE `ofertas_laborales`
  ADD CONSTRAINT `fk_oferta_especialidad` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`),
  ADD CONSTRAINT `ofertas_laborales_ibfk_1` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON DELETE CASCADE,
  ADD CONSTRAINT `ofertas_laborales_ibfk_2` FOREIGN KEY (`id_rubro`) REFERENCES `rubros` (`id_rubro`) ON DELETE SET NULL,
  ADD CONSTRAINT `ofertas_laborales_ibfk_3` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`) ON DELETE SET NULL;

--
-- Filtros para la tabla `oferta_especialidades`
--
ALTER TABLE `oferta_especialidades`
  ADD CONSTRAINT `oferta_especialidades_ibfk_1` FOREIGN KEY (`id_oferta`) REFERENCES `ofertas_laborales` (`id_oferta`) ON DELETE CASCADE,
  ADD CONSTRAINT `oferta_especialidades_ibfk_2` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`) ON DELETE CASCADE;

--
-- Filtros para la tabla `persona`
--
ALTER TABLE `persona`
  ADD CONSTRAINT `fk_persona_localidad_pref` FOREIGN KEY (`id_localidad_preferencia`) REFERENCES `localidades` (`id_localidad`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_persona_provincia_pref` FOREIGN KEY (`id_provincia_preferencia`) REFERENCES `provincias` (`id_provincia`) ON DELETE SET NULL;

--
-- Filtros para la tabla `persona_especialidades`
--
ALTER TABLE `persona_especialidades`
  ADD CONSTRAINT `persona_especialidades_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON DELETE CASCADE,
  ADD CONSTRAINT `persona_especialidades_ibfk_2` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`) ON DELETE CASCADE;

--
-- Filtros para la tabla `postulaciones`
--
ALTER TABLE `postulaciones`
  ADD CONSTRAINT `postulaciones_ibfk_1` FOREIGN KEY (`id_oferta`) REFERENCES `ofertas_laborales` (`id_oferta`) ON DELETE CASCADE,
  ADD CONSTRAINT `postulaciones_ibfk_2` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reclutadores`
--
ALTER TABLE `reclutadores`
  ADD CONSTRAINT `reclutadores_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclutadores_ibfk_2` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON DELETE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id_empresa`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`tipo`) REFERENCES `tipo_usuario` (`id_tipo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
