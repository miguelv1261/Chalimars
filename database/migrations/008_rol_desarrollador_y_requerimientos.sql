-- Migracion 008: rol "desarrollador" y modulo de Requerimientos.
--
-- El rol "cajero" ya no se usa: la operacion diaria del sistema (contador)
-- usa "admin", y se agrega "desarrollador" para quien da soporte y
-- desarrollo (mismo acceso completo que "admin", ver is_admin() en
-- includes/auth.php). No se elimina "cajero" del ENUM para no romper
-- filas existentes; simplemente ya no aparece como opcion al crear o
-- editar usuarios.
ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('admin','desarrollador','cajero') NOT NULL DEFAULT 'admin';

-- Modulo de Requerimientos: para que el contador (u otro usuario) pueda
-- enviar pedidos de soporte/programacion, y el desarrollador los vaya
-- atendiendo (estado + respuesta).
CREATE TABLE requerimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('pendiente','en_progreso','completado','rechazado') NOT NULL DEFAULT 'pendiente',
    respuesta TEXT NULL,
    creado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;
