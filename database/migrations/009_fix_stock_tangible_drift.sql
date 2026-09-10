-- Migracion 009: corrige el arrastre de redondeo entre stock_tangible y
-- stock_uso, y evita que vuelva a ocurrir.
--
-- Problema: cada venta descontaba stock_uso de forma exacta, pero el
-- descuento equivalente en stock_tangible se redondeaba a 2 decimales.
-- En productos con rendimiento alto (ej. una tijera = 10000 usos), restar
-- 1 uso son 0.0001 unidades tangibles, que redondeaban a 0.00: con el
-- tiempo stock_tangible dejaba de reflejar las ventas ya hechas.
--
-- Ese desfase se volvia visible al editar un producto (productos/form.php),
-- que recalculaba stock_uso = stock_tangible * rendimiento y borraba de un
-- golpe todas las ventas ya descontadas (el stock "subia" en vez de bajar).
-- El codigo ya se corrigio para no volver a hacer ese recalculo destructivo
-- y para guardar stock_tangible con mas precision de aqui en adelante.
--
-- Esta migracion repara los datos existentes: stock_uso se llevo siempre
-- de forma exacta (fuente confiable), asi que se usa para reconstruir
-- stock_tangible en vez de al reves.

ALTER TABLE productos
    MODIFY COLUMN stock_tangible DECIMAL(14,4) NOT NULL DEFAULT 0;

UPDATE productos
    SET stock_tangible = CASE WHEN rendimiento > 0 THEN ROUND(stock_uso / rendimiento, 4) ELSE stock_tangible END;
