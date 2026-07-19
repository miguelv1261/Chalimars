-- Migracion incremental:
-- 1. Mano de obra pasa a calcularse automaticamente como 30% del
--    precio de venta del servicio (en vez de elegirse de un catalogo
--    al armar la receta). Se eliminan las lineas de mano_obra que ya
--    existian en las recetas (servicios_costos), pues quedan
--    reemplazadas por el calculo automatico.
-- 2. Nueva tabla ingresos_servicios para el Libro Diario (registra
--    cada vez que se aplica un servicio a un ingreso).
--
-- Nota: el manejo de caja chica (abrir/cerrar/contar) deja de ser
-- obligatorio para registrar ingresos/egresos/depositos, pero las
-- tablas de sesiones de caja NO se eliminan (quedan disponibles por si
-- se necesita consultar el historial).
--
-- Ejemplo: mysql --default-character-set=utf8mb4 -u usuario -p chalimars < database/migrations/006_libro_diario_mano_obra_30.sql

DELETE FROM servicios_costos WHERE tipo_costo = 'mano_obra';

CREATE TABLE IF NOT EXISTS ingresos_servicios (
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
