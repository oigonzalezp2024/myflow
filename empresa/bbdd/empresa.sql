
DROP DATABASE `empresa`; 

CREATE DATABASE `empresa`;

USE `empresa`;

CREATE TABLE `analisis_cotizaciones` (
  `id_analisis` int(11) NOT NULL,
  `cotizacion_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `analisis_cotizaciones` (`id_analisis`, `cotizacion_id`) VALUES
(1, 1);

CREATE TABLE `cotizaciones` (
  `id_cotizacion` int(11) NOT NULL,
  `producto` varchar(190) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cotizaciones` (`id_cotizacion`, `producto`) VALUES
(1, 'vitrina panadera');

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pedidos` (`id_pedido`, `proveedor_id`) VALUES
(1, 1);

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `proveedor_nombre` varchar(190) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `proveedores` (`id_proveedor`, `proveedor_nombre`) VALUES
(1, 'oscar gonzalez');

ALTER TABLE `analisis_cotizaciones`
  ADD PRIMARY KEY (`id_analisis`);

ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id_cotizacion`);

ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`);

ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`);

ALTER TABLE `analisis_cotizaciones`
  MODIFY `id_analisis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `cotizaciones`
  MODIFY `id_cotizacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
