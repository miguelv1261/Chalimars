-- Sistema de Caja y Costos - Peluqueria "Chalimars"
-- Esquema de base de datos MySQL

CREATE DATABASE IF NOT EXISTS chalimars CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chalimars;

-- ============================================================
-- Usuarios y roles
-- ============================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    rol ENUM('admin','cajero') NOT NULL DEFAULT 'cajero',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Sesiones / cierres de caja
-- ============================================================
CREATE TABLE cajas_sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_apertura DATETIME NOT NULL,
    fecha_cierre DATETIME NULL,
    monto_apertura DECIMAL(12,2) NOT NULL DEFAULT 0,
    monto_contado DECIMAL(12,2) NULL,
    monto_esperado DECIMAL(12,2) NULL,
    diferencia DECIMAL(12,2) NULL,
    estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
    abierta_por INT NOT NULL,
    cerrada_por INT NULL,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (abierta_por) REFERENCES usuarios(id),
    FOREIGN KEY (cerrada_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ============================================================
-- Proveedores de materiales
-- ============================================================
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    contacto VARCHAR(150) NULL,
    telefono VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    direccion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Inventario de materiales (control de costos)
--
-- Un producto se COMPRA en una unidad tangible (ej. una botella) a un
-- precio_compra (ej. $15) pero RINDE varias unidades de uso segun
-- "rendimiento" (ej. 20). stock_tangible es la fuente de verdad (lo
-- comprado); stock_uso siempre se recalcula como stock_tangible *
-- rendimiento. costo_uso = precio_compra / rendimiento es lo que se usa
-- para costear (ej. $15 / 20 = $0.75 por uso). precio_venta_uso es el
-- precio al que se vende cada unidad de uso cuando el producto se vende
-- directamente en un ingreso (no como parte de la receta de un servicio).
-- ============================================================
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    rendimiento DECIMAL(10,2) NOT NULL DEFAULT 1,
    precio_compra DECIMAL(10,2) NOT NULL DEFAULT 0,
    costo_uso DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_tangible DECIMAL(10,2) NOT NULL DEFAULT 0,
    precio_venta_uso DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_uso DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Historial de movimientos de inventario (entradas / salidas), en
-- unidades de uso. Las entradas registran ademas la compra original
-- (cantidad_compra, precio_compra_unitario, proveedor) para trazabilidad.
CREATE TABLE productos_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo ENUM('entrada','salida') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL,
    costo_total DECIMAL(10,2) NOT NULL,
    cantidad_compra DECIMAL(10,2) NULL,
    precio_compra_unitario DECIMAL(10,2) NULL,
    proveedor_id INT NULL,
    numero_documento VARCHAR(100) NULL,
    motivo VARCHAR(255) NOT NULL,
    ingreso_id INT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ============================================================
-- Mano de obra (catalogo de tarifas de trabajo, reutilizable
-- entre varias recetas de servicio)
-- ============================================================
CREATE TABLE mano_obra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    costo DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Gastos indirectos (luz, agua, alquiler, etc.)
-- ============================================================
CREATE TABLE gastos_indirectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    costo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Servicios: paquete vendible (ej. "Corte de Cabello") con su
-- receta de costeo predefinida (servicios_costos), para no tener
-- que volver a cargar material por material en cada ingreso.
-- ============================================================
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    precio_venta DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Receta de costeo de cada servicio: que materiales y gastos
-- indirectos consume (y en que cantidad). La mano de obra ya NO se
-- agrega aqui: se calcula automaticamente como 30% del precio_venta
-- del servicio (ver aplicar_servicio_a_ingreso() en includes/functions.php).
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

-- ============================================================
-- Ingresos (facturas de venta / servicios prestados)
-- ============================================================
CREATE TABLE ingresos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    cliente VARCHAR(150) NULL,
    descripcion VARCHAR(255) NULL,
    monto DECIMAL(12,2) NOT NULL,
    numero_factura VARCHAR(100) NULL,
    factura_pdf VARCHAR(255) NULL,
    caja_sesion_id INT NULL,
    creado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caja_sesion_id) REFERENCES cajas_sesiones(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Costeo real de cada ingreso. Se genera automaticamente al aplicar
-- un servicio (su receta se copia aqui); origen_servicio_id indica de
-- que servicio vino cada linea, para trazabilidad.
CREATE TABLE ingresos_costos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingreso_id INT NOT NULL,
    tipo_costo ENUM('material','mano_obra','gasto_indirecto') NOT NULL,
    producto_id INT NULL,
    mano_obra_id INT NULL,
    gasto_indirecto_id INT NULL,
    origen_servicio_id INT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    costo_unitario DECIMAL(10,2) NOT NULL,
    costo_total DECIMAL(10,2) NOT NULL,
    creado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingreso_id) REFERENCES ingresos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (mano_obra_id) REFERENCES mano_obra(id),
    FOREIGN KEY (gasto_indirecto_id) REFERENCES gastos_indirectos(id),
    FOREIGN KEY (origen_servicio_id) REFERENCES servicios(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Registro de cada vez que se aplica un servicio a un ingreso (para el
-- libro diario / reportes: cuantas veces se realizo cada servicio y
-- que ingresos/costos genero). Guarda una copia del precio y costo en
-- ese momento, para que el reporte no cambie si luego se edita el
-- servicio o sus costos.
CREATE TABLE ingresos_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingreso_id INT NOT NULL,
    servicio_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    precio_venta_aplicado DECIMAL(12,2) NOT NULL,
    costo_total_aplicado DECIMAL(12,2) NOT NULL,
    creado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingreso_id) REFERENCES ingresos(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Registro de cada vez que se vende un producto directamente en un
-- ingreso (sin pasar por la receta de un servicio). Paralelo a
-- ingresos_servicios: guarda copia del precio de venta y costo aplicados.
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

-- ============================================================
-- Egresos (facturas o notas de venta de compras/gastos)
-- ============================================================
CREATE TABLE egresos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    tipo_documento ENUM('factura','nota_venta') NOT NULL DEFAULT 'factura',
    numero_documento VARCHAR(100) NULL,
    documento_archivo VARCHAR(255) NULL,
    caja_sesion_id INT NULL,
    creado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caja_sesion_id) REFERENCES cajas_sesiones(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ============================================================
-- Depositos bancarios
-- ============================================================
CREATE TABLE depositos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    banco VARCHAR(100) NULL,
    numero_referencia VARCHAR(100) NULL,
    comprobante_archivo VARCHAR(255) NULL,
    caja_sesion_id INT NULL,
    creado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caja_sesion_id) REFERENCES cajas_sesiones(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ============================================================
-- Configuracion / marca del negocio (logo, nombre, color)
-- Siempre una unica fila con id = 1
-- ============================================================
CREATE TABLE configuracion (
    id INT PRIMARY KEY DEFAULT 1,
    nombre_negocio VARCHAR(150) NOT NULL DEFAULT 'Chalimars',
    logo VARCHAR(255) NULL,
    color_primario VARCHAR(7) NOT NULL DEFAULT '#8a4b6b',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO configuracion (id, nombre_negocio, color_primario) VALUES (1, 'Chalimars', '#8a4b6b');

-- ============================================================
-- Usuario administrador inicial
-- Usuario: admin  Password: admin123  (cambiar despues del primer ingreso)
-- ============================================================
INSERT INTO usuarios (username, password, nombre_completo, rol) VALUES
('admin', '$2y$12$auDkI1dgdkyy5labSQ3YaeM8OkSN1XV9dFEGNhXQgpHfiACpYw0/e', 'Administrador', 'admin');
-- El hash anterior corresponde a la contrasena: admin123
