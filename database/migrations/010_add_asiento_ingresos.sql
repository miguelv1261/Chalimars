-- Migracion 010: registra en el historial de migraciones la columna
-- "asiento" (numero de asiento contable, texto libre) de la tabla
-- ingresos. Esta columna ya existe en la base de datos de produccion
-- (se agrego manualmente, sin migracion), lo que dejaba schema.sql
-- desactualizado para instalaciones nuevas. El IF NOT EXISTS hace esta
-- migracion segura de correr tanto en produccion (donde ya existe) como
-- en una instalacion nueva basada en una copia vieja de schema.sql.

ALTER TABLE ingresos
    ADD COLUMN IF NOT EXISTS asiento VARCHAR(50) DEFAULT NULL;
