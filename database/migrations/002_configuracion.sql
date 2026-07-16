-- Migracion incremental: agrega personalizacion de marca (logo, nombre, color)
-- Ejecutar una sola vez sobre una base de datos ya creada con schema.sql
-- Ejemplo: mysql -u usuario -p chalimars < database/migrations/002_configuracion.sql

CREATE TABLE IF NOT EXISTS configuracion (
    id INT PRIMARY KEY DEFAULT 1,
    nombre_negocio VARCHAR(150) NOT NULL DEFAULT 'Chalimars',
    logo VARCHAR(255) NULL,
    color_primario VARCHAR(7) NOT NULL DEFAULT '#8a4b6b',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO configuracion (id, nombre_negocio, color_primario) VALUES (1, 'Chalimars', '#8a4b6b');
