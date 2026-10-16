-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-10-2026 a las 16:25:09
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
-- Base de datos: `gestion_incidencias`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias`
--

CREATE TABLE `incidencias` (
  `id` int(11) NOT NULL,
  `reportado_por` varchar(100) NOT NULL,
  `laboratorio` varchar(100) NOT NULL,
  `equipo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `prioridad` enum('Baja','Media','Alta') NOT NULL,
  `estado` enum('Pendiente','En proceso','Resuelta') NOT NULL DEFAULT 'Pendiente',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `incidencias`
--

INSERT INTO `incidencias` (`id`, `reportado_por`, `laboratorio`, `equipo`, `descripcion`, `prioridad`, `estado`, `fecha_registro`) VALUES
(5, 'Gabriel', 'oficina 1', 'impresora', 'no esta imprimiendo a color, solo imprime blanco y negro con manchas y de hecho pense que era tinta pero no por que ya la rellene y sigue igual y aveces solo queda parpadeando una luz naranja', 'Media', 'En proceso', '2026-10-15 20:12:18'),
(8, 'Dylan Aaron', 'Laboratorio 3', 'Impresora hp', 'la imprresora no imprime', 'Alta', 'Pendiente', '2026-10-16 13:42:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('admin','tecnico') NOT NULL DEFAULT 'tecnico'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `contrasena`, `rol`) VALUES
(1, 'admin', '$2y$10$tzGJgkXCT2nkfi/DJ53jreeBe64iV4/Z98/n72WGq7y9YRohnRu/S', 'admin'),
(2, 'Ronald_araya', '$2y$10$NAT0.X0.5oN8/3fE56k5o.Y9ntkj7pP.EnidwdvNi4sff2Le.1RAC', 'tecnico');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
