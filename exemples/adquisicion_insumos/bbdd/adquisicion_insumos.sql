
DROP DATABASE `adquisicion_insumos`;

CREATE DATABASE `adquisicion_insumos`;

USE `adquisicion_insumos`;

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `proveedor_nombre` varchar(190) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`);

ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT;
