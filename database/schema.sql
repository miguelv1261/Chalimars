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
-- Inventario de materiales
-- ============================================================
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    unidad VARCHAR(20) NOT NULL DEFAULT 'unidad',
    costo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Historial de movimientos de inventario (entradas / salidas)
CREATE TABLE productos_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    tipo ENUM('entrada','salida') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL,
    costo_total DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    ingreso_id INT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ============================================================
-- Mano de obra / servicios
-- ============================================================
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    costo_mano_obra DECIMAL(10,2) NOT NULL DEFAULT 0,
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

-- Costeo de cada ingreso: materiales, mano de obra, gastos indirectos
CREATE TABLE ingresos_costos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingreso_id INT NOT NULL,
    tipo_costo ENUM('material','mano_obra','gasto_indirecto') NOT NULL,
    producto_id INT NULL,
    servicio_id INT NULL,
    gasto_indirecto_id INT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
    costo_unitario DECIMAL(10,2) NOT NULL,
    costo_total DECIMAL(10,2) NOT NULL,
    creado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingreso_id) REFERENCES ingresos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (servicio_id) REFERENCES servicios(id),
    FOREIGN KEY (gasto_indirecto_id) REFERENCES gastos_indirectos(id),
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
-- Usuario administrador inicial
-- Usuario: admin  Password: admin123  (cambiar despues del primer ingreso)
-- ============================================================
INSERT INTO usuarios (username, password, nombre_completo, rol) VALUES
('admin', '$2y$12$auDkI1dgdkyy5labSQ3YaeM8OkSN1XV9dFEGNhXQgpHfiACpYw0/e', 'Administrador', 'admin');
-- El hash anterior corresponde a la contrasena: admin123
