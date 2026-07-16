-- Migracion incremental: proveedores, productos por rendimiento/costo de uso,
-- catalogo de mano de obra separado, y "servicios" como receta de costeo
-- reutilizable (en vez de cargar material por material en cada ingreso).
--
-- IMPORTANTE: si su base de datos aun no tiene informacion real de
-- produccion (solo pruebas), es mas simple y seguro recrear la base
-- desde cero con database/schema.sql en vez de correr esta migracion.
-- Use esta migracion solo si necesita conservar datos existentes.
--
-- Ejemplo: mysql -u usuario -p chalimars < database/migrations/003_servicios_costeo_proveedores.sql

-- ---------- Proveedores ----------
CREATE TABLE IF NOT EXISTS proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    contacto VARCHAR(150) NULL,
    telefono VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    direccion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Productos: separar precio de compra vs costo por uso ----------
ALTER TABLE productos
    ADD COLUMN unidad_uso VARCHAR(20) NOT NULL DEFAULT 'unidad' AFTER unidad,
    ADD COLUMN rendimiento DECIMAL(10,2) NOT NULL DEFAULT 1 AFTER unidad_uso,
    ADD COLUMN precio_compra DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER rendimiento;

UPDATE productos SET precio_compra = costo_unitario, unidad_uso = unidad;

ALTER TABLE productos CHANGE COLUMN unidad unidad_compra VARCHAR(20) NOT NULL DEFAULT 'unidad';
ALTER TABLE productos CHANGE COLUMN costo_unitario costo_uso DECIMAL(10,2) NOT NULL DEFAULT 0;

-- ---------- Movimientos: trazabilidad de compra/proveedor ----------
ALTER TABLE productos_movimientos
    ADD COLUMN cantidad_compra DECIMAL(10,2) NULL AFTER cantidad,
    ADD COLUMN precio_compra_unitario DECIMAL(10,2) NULL AFTER costo_total,
    ADD COLUMN proveedor_id INT NULL AFTER precio_compra_unitario,
    ADD COLUMN numero_documento VARCHAR(100) NULL AFTER proveedor_id,
    ADD CONSTRAINT fk_movimiento_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id);

-- ---------- Renombrar el catalogo de mano de obra (antes "servicios") ----------
RENAME TABLE servicios TO mano_obra;
ALTER TABLE mano_obra CHANGE COLUMN costo_mano_obra costo DECIMAL(10,2) NOT NULL DEFAULT 0;

-- ---------- Nueva tabla de servicios: paquete vendible con costeo predefinido ----------
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio_venta DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE servicios_costos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_id INT NOT NULL,
    tipo_costo ENUM('material','mano_obra','gasto_indirecto') NOT NULL,
    producto_id INT NULL,
    mano_obra_id INT NULL,
    gasto_indirecto_id INT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (mano_obra_id) REFERENCES mano_obra(id),
    FOREIGN KEY (gasto_indirecto_id) REFERENCES gastos_indirectos(id)
) ENGINE=InnoDB;

-- ---------- Ajustar ingresos_costos: servicio_id ahora es mano_obra_id ----------
ALTER TABLE ingresos_costos
    CHANGE COLUMN servicio_id mano_obra_id INT NULL;

ALTER TABLE ingresos_costos
    ADD COLUMN origen_servicio_id INT NULL AFTER mano_obra_id,
    ADD CONSTRAINT fk_ingreso_costo_origen_servicio FOREIGN KEY (origen_servicio_id) REFERENCES servicios(id);
