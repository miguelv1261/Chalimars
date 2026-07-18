-- Corrige nombres con caracteres especiales (n, tildes) que quedaron mal
-- codificados al importar database/migrations/004_import_inventario_inicial.sql
-- con el cliente "mysql" sin indicar el charset UTF-8 (ej. "PESTAÃ‘AS" en vez
-- de "PESTAÑAS").
--
-- Es seguro correr esta migracion aunque sus datos ya esten correctos: para
-- texto sin caracteres especiales no cambia nada.
--
-- IMPORTANTE: ejecutar este archivo indicando el charset del cliente, o el
-- problema se repite al aplicar la propia correccion:
--   mysql --default-character-set=utf8mb4 -u usuario -p chalimars < database/migrations/005_fix_encoding_utf8.sql

UPDATE productos
SET nombre = CONVERT(CAST(CONVERT(nombre USING latin1) AS BINARY) USING utf8mb4);

UPDATE gastos_indirectos
SET nombre = CONVERT(CAST(CONVERT(nombre USING latin1) AS BINARY) USING utf8mb4),
    descripcion = CONVERT(CAST(CONVERT(descripcion USING latin1) AS BINARY) USING utf8mb4);
