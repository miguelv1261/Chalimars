# Chalimars - Sistema de Caja y Costos

Sistema web (HTML, CSS y PHP puro con MySQL/MariaDB) para llevar la contabilidad de
costos de una peluqueria: registro de ingresos (con factura en PDF), egresos y
depositos bancarios; servicios con receta de costeo predefinida (materiales y gastos
indirectos, mas mano de obra calculada automaticamente); inventario de materiales con
precio de compra vs costo por uso y proveedores; y un libro diario para contabilidad
(ventas, depositos, egresos, costos y servicios completados). Incluye control de acceso
por roles: **administrador** y **cajero**.

## Requisitos

- PHP 8.1 o superior con extensiones `pdo_mysql` y `fileinfo`
- MySQL o MariaDB
- Servidor web (Apache/Nginx) o el servidor embebido de PHP para pruebas

## Instalacion

1. Crear la base de datos e importar el esquema:

   ```bash
   mysql --default-character-set=utf8mb4 -u root -p < database/schema.sql
   ```

   **Importante:** siempre incluya `--default-character-set=utf8mb4` al importar
   cualquier archivo `.sql` de este proyecto (aqui y en las migraciones). Sin ese
   parametro, el cliente `mysql` puede interpretar mal los nombres, tildes y la letra
   ñ (ej. "PESTAÑAS" queda guardado como "PESTAÃ‘AS").

   Esto crea la base `chalimars`, todas las tablas y un usuario administrador inicial:

   - **Usuario:** `admin`
   - **Contrasena:** `admin123`

   Cambie esta contrasena despues del primer ingreso desde el modulo de Usuarios.

2. Configurar la conexion a base de datos mediante variables de entorno (o editando
   directamente `config/database.php`):

   - `DB_HOST` (por defecto `localhost`)
   - `DB_NAME` (por defecto `chalimars`)
   - `DB_USER` (por defecto `root`)
   - `DB_PASS` (por defecto vacio)

3. Verificar que las carpetas `uploads/facturas`, `uploads/egresos` y
   `uploads/depositos` tengan permisos de escritura para el usuario del servidor web.

4. Apuntar el servidor web a la raiz del proyecto. Para pruebas rapidas:

   ```bash
   DB_HOST=localhost DB_NAME=chalimars DB_USER=root DB_PASS=secreto php -S 0.0.0.0:8000
   ```

   y visitar `http://localhost:8000/login.php`.

## Roles y accesos

- **Administrador**: acceso total. Gestiona usuarios, catalogos (productos, proveedores,
  mano de obra, gastos indirectos), define la receta de costeo de cada servicio, edita
  o elimina ingresos/egresos/depositos, elimina lineas de costo (repone stock
  automaticamente si era un material), y consulta el Libro diario.
- **Cajero**: registra ingresos (con factura PDF) aplicando servicios ya definidos,
  registra egresos y depositos, y consulta los catalogos e inventario en modo solo
  lectura. No puede gestionar usuarios, proveedores, catalogos, recetas de servicio, ni
  ver el Libro diario.

## Flujo de trabajo

1. **Configurar una vez**: se cargan los productos (`Inventario materiales`, con su
   precio de compra y rendimiento), los proveedores y los gastos indirectos. Con eso se
   arma cada **Servicio** (ej. "Corte de Cabello") agregandole su receta de costeo: que
   materiales usa (y cuantos) y que gastos indirectos, ademas de su precio de venta. La
   mano de obra no se agrega a mano: se calcula automaticamente como un porcentaje fijo
   del precio de venta (30% por defecto, ver `MANO_OBRA_PORCENTAJE` en `config/config.php`).
2. Los ingresos, egresos y depositos se registran libremente en cualquier momento (no
   requieren abrir/cerrar una caja).
3. Al registrar un ingreso, se selecciona el **servicio** prestado (y cuantas veces) -
   el sistema autocompleta el monto sugerido y aplica automaticamente toda la receta de
   costeo (materiales, gastos indirectos y el 30% de mano de obra), descontando stock y
   calculando la utilidad, sin tener que cargar material por material cada vez. Se
   pueden aplicar varios servicios a un mismo ingreso desde su detalle.
4. El inventario mantiene el costeo por unidad de uso: un producto se compra en una
   unidad (ej. "botella" a $15) pero rinde varias unidades de uso (ej. 20
   "aplicaciones"), y el costo por uso ($0.75) es el que se aplica en las recetas. Las
   entradas de stock (`Inventario materiales > Entrada stock`) registran el proveedor y
   el numero de documento para trazabilidad de compras; el historial completo queda en
   `Inventario materiales > Movimientos`.
5. El **Libro diario** (`Contabilidad > Libro diario`, solo admin) muestra por rango de
   fechas: ventas, depositos bancarios, egresos, costo total y utilidad neta por dia, y
   una tabla de servicios completados en el periodo (cuantas veces se realizo cada uno,
   ingresos generados y costo).

Las pantallas de caja (`caja/`) siguen existiendo en el codigo por si se necesita
consultar el historial de sesiones antiguas, pero ya no son parte del flujo diario ni
aparecen en el menu.

## Actualizar una instalacion existente

Si ya tenia el sistema instalado con una version anterior, corra las migraciones nuevas
en orden (o recree la base con `database/schema.sql` si aun no tiene datos reales que
conservar):

```bash
mysql --default-character-set=utf8mb4 -u usuario -p chalimars < database/migrations/002_configuracion.sql
mysql --default-character-set=utf8mb4 -u usuario -p chalimars < database/migrations/003_servicios_costeo_proveedores.sql
mysql --default-character-set=utf8mb4 -u usuario -p chalimars < database/migrations/004_import_inventario_inicial.sql
mysql --default-character-set=utf8mb4 -u usuario -p chalimars < database/migrations/005_fix_encoding_utf8.sql
mysql --default-character-set=utf8mb4 -u usuario -p chalimars < database/migrations/006_libro_diario_mano_obra_30.sql
```

La migracion 003 renombra tablas y columnas (`servicios` pasa a ser `mano_obra`, entre
otros cambios) - respalde la base de datos antes de correrla si tiene datos que le
importen. La migracion 005 corrige nombres con ñ/tildes que hayan quedado mal
codificados por no usar `--default-character-set=utf8mb4` al importar (es segura de
correr aunque sus datos ya esten bien). La migracion 006 elimina las lineas de mano de
obra que hubiera en recetas de servicio (quedan reemplazadas por el calculo automatico
del 30%) y crea la tabla `ingresos_servicios` para el libro diario.

## Despliegue automatico (GitHub Actions + FTP)

El workflow `.github/workflows/deploy.yml` sube automaticamente los archivos al hosting
por FTP/FTPS cada vez que se hace `git push` a la rama `main`.

Flujo: `git push` -> GitHub -> GitHub Actions -> FTP/FTPS -> hosting (ej. Hostinger).

### Configuracion (una sola vez)

1. En el repositorio de GitHub: **Settings > Secrets and variables > Actions > New
   repository secret**, y cree estos 4 secretos con los datos FTP de su hosting
   (en Hostinger: hPanel > Archivos > Cuentas FTP):

   | Secreto | Ejemplo |
   |---|---|
   | `FTP_SERVER` | `ftp.midominio.com` |
   | `FTP_USERNAME` | `u123456789.chalimars` |
   | `FTP_PASSWORD` | la contrasena de esa cuenta FTP |
   | `FTP_SERVER_DIR` | `/public_html/` (o `/public_html/chalimars/` si va en subcarpeta) |

2. Cree manualmente en el servidor (por FTP o el Administrador de archivos del hosting,
   **no por git**, para no exponer credenciales en el repositorio) el archivo
   `config/database.local.php` con las credenciales reales de la base de datos del
   hosting. Vea `config/database.local.example.php` como plantilla.

3. Importe `database/schema.sql` en la base de datos del hosting (phpMyAdmin o
   `mysql` por SSH si el plan lo permite).

4. Haga `git push origin main`: el workflow se dispara solo y sube los archivos. Puede
   ver el progreso en la pestana **Actions** del repositorio en GitHub.

El workflow excluye de la subida (y por lo tanto nunca toca ni borra en el servidor):
`.git`, `.github`, `database/`, `README.md`, `config/database.local.php`, y las
subcarpetas de `uploads/` (facturas, egresos, depositos, logos) donde vive contenido
generado por los propios usuarios del sistema, no por el codigo fuente.

## Seguridad

- Contrasenas con `bcrypt` (`password_hash`/`password_verify`).
- Consultas parametrizadas con PDO (prepared statements) en toda la aplicacion.
- Proteccion CSRF en todos los formularios que modifican datos.
- Validacion de tipo MIME real y extension en los archivos subidos (PDF/JPG/PNG),
  limite de 5MB, nombres de archivo aleatorios.
- `.htaccess` en `uploads/`, `config/`, `includes/` y `database/` para bloquear la
  ejecucion de scripts y el acceso directo a codigo/configuracion sensible.
