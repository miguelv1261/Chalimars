-- Migracion 007: stock dual (tangible / uso) en productos, precio de venta
-- por producto, y venta directa de productos en ingresos (carrito).
--
-- Antes: productos se compraba en "unidad_compra" (texto libre) y rendia
-- varias "unidad_uso" (texto libre) segun "rendimiento"; el stock solo se
-- guardaba en unidades de uso (columna "stock").
--
-- Ahora: se quitan los nombres de unidad como texto libre. El stock se
-- guarda en dos columnas numericas: stock_tangible (lo comprado, fuente de
-- verdad) y stock_uso (siempre = stock_tangible * rendimiento, recalculado
-- en cada alta/edicion/entrada). Se agrega precio_venta_uso para poder
-- vender un producto directamente en un ingreso (no solo como parte de la
-- receta de un servicio).

ALTER TABLE productos
    ADD COLUMN stock_tangible DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER costo_uso,
    ADD COLUMN precio_venta_uso DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER stock_tangible;

-- Backfill: reconstruir stock_tangible a partir del stock de uso existente,
-- y usar el costo por uso como precio de venta inicial (el admin lo ajusta
-- despues desde el formulario de producto).
UPDATE productos
    SET stock_tangible = CASE WHEN rendimiento > 0 THEN ROUND(stock / rendimiento, 2) ELSE 0 END,
        precio_venta_uso = costo_uso;

ALTER TABLE productos
    CHANGE COLUMN stock stock_uso DECIMAL(10,2) NOT NULL DEFAULT 0,
    DROP COLUMN unidad_compra,
    DROP COLUMN unidad_uso;

-- ============================================================
-- Venta directa de productos en un ingreso (paralelo a
-- ingresos_servicios, para trazabilidad y reportes del libro diario).
-- ============================================================
CREATE TABLE ingresos_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingreso_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    precio_unitario_aplicado DECIMAL(10,2) NOT NULL,
    costo_unitario_aplicado DECIMAL(10,2) NOT NULL,
    subtotal_aplicado DECIMAL(12,2) NOT NULL,
    costo_total_aplicado DECIMAL(12,2) NOT NULL,
    creado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingreso_id) REFERENCES ingresos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;
