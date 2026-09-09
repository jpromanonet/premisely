# Premisely

Plataforma para administrar propiedades, espacios, inventario, stock, compras, tareas, rutinas, limpieza, mantenimiento, reparaciones, servicios, gastos y documentos.

## Stack

- PHP 8.1+
- MySQL 8 / MariaDB
- HTML / CSS / JavaScript (SSR)
- Apache (compatible con hosting compartido)

## Desarrollo local / servidor W

En este entorno el despliegue apunta a `W:\premisely` → `http://192.168.100.50/premisely`

Credenciales MySQL del servidor:

- Host: `127.0.0.1` (desde el propio servidor)
- User: `root`
- Pass: `local_admin`
- Database: `premisely`

## Instalación

1. Copiar el proyecto a `W:\premisely` (o al document root deseado).
2. Asegurar que `.env` tenga la configuración correcta.
3. Abrir `http://192.168.100.50/premisely/index.php?r=/install`
4. Completar el asistente (crea DB, migraciones, seeds y usuario admin).
5. El instalador escribe `storage/installed` y queda bloqueado.

## Cron

```bash
php /ruta/a/premisely/cron/scheduler.php
```

## Estructura

Modular monolith bajo `app/Modules/*` con núcleo en `app/Core/*`.

## Licencia

Propietaria — ver `LICENSE`.
