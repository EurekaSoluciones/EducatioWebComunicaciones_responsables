# Etapa 0 Actualización de Familias a Laravel 13

Estado al 27 de octubre de 2026: Laravel 13.38.0 instalado y validación automatizada local aprobada. Falta comprobar los recorridos de negocio con SQL Server y la app móvil en un entorno de prueba antes de cerrar la etapa.

## Alcance

Actualizar el framework antes de cambiar la interfaz. Conservar las reglas de negocio, la autenticación de responsables, los contratos de la API móvil, las tablas y los procedimientos almacenados. Blade y AdminLTE 3 continúan durante esta etapa.

## Cambios preparados

- `composer.json`: PHP 8.3, Laravel 13, Sanctum 4, Tinker 3, Carbon 3, Collision 8 y PHPUnit 12. AdminLTE permanece limitado a la rama 3. La resolución exacta corresponde a Composer.
- `config/sanctum.php`: claves de middleware de Sanctum 4, incluyendo autenticación de sesión y validación CSRF.
- `config/session.php`: formato de serialización PHP explícito para preservar el comportamiento previo. Se conservan nombre y driver de sesión.
- `phpunit.xml`: configuración compatible con PHPUnit moderno y aislamiento del `.env` real mediante SQLite en memoria, sesión/cache en memoria, URLs y cliente de prueba, clave de cifrado de prueba y rutas de caché de configuración/rutas separadas.
- Pruebas de login, tipo de usuario, credenciales incorrectas, logout, CSRF, redirección de invitados, autenticación Sanctum, datos sensibles omitidos, render de AdminLTE y formatos de fechas e importes.

Se conserva el arranque mediante los kernels y providers existentes. El middleware local `VerifyCsrfToken` utiliza el alias que Laravel 13 todavía mantiene: no se introduce una dependencia de clases nuevas antes de instalar el framework. No se copian los archivos de configuración de Pranzo sobre los de Familias.

## Actualización de dependencias realizada

El usuario ejecutó la actualización de Composer. Las instrucciones del workspace impiden al agente instalar, actualizar o eliminar dependencias; no se editaron `vendor` ni `composer.lock` manualmente.

Comando ejecutado desde la raíz de este repositorio experimental:

```powershell
composer update --with-all-dependencies
```

La salida compartida el 27 de octubre muestra una actualización de PHPUnit 12.5.40 a 12.5.42 y el descubrimiento de paquetes correcto. La inspección local posterior confirma Laravel 13.38.0 y AdminLTE 3.16.0. Composer no informó avisos de vulnerabilidades en esa ejecución.

El manifiesto y el lock ya están alineados: `composer validate --strict` pasó. No hace falta repetir la actualización para continuar con las verificaciones funcionales.

## Verificación local realizada

Comandos ejecutados en el mismo proyecto:

```powershell
php artisan view:clear
composer validate --strict
composer check-platform-reqs
php artisan --version
composer test
```

Las verificaciones confirmaron Laravel 13.38.0, PHPUnit 12.5.42 y los requisitos de plataforma satisfechos en PHP 8.3.31. La primera ejecución falló al renderizar AdminLTE porque una vista compilada antigua llamaba a `LayoutHelper::isPreloaderEnabled()`, que ahora corresponde a `PreloaderHelper`. Limpiar las vistas compiladas resolvió el error sin cambiar plantillas ni dependencias; la segunda ejecución pasó completa.

No había archivos de caché de configuración ni rutas en `bootstrap/cache`. No se ejecutó `optimize:clear`, que puede limpiar un cache compartido según el entorno.

La aplicación usa una base externa ya existente y no tiene migraciones de dominio en este checkout. No ejecutar `migrate`, `migrate:fresh`, seeders ni publicar/aplicar automáticamente migraciones de Sanctum. Revisar el esquema de tokens existente si aparece un problema concreto.

## Evidencia y límites

El usuario aclaró después que el concentrador compartido es `D:\Eureka\Educatio\2023\NivelInicial\WebDocentes\WebDocentes-main\storage\app\public`. Se configuró `storage/app/public` de este experimental como junction a ese directorio; `public/storage` continúa enlazando al almacenamiento interno. Así las URLs y las cargas del disco `public` usan el mismo concentrador. Se comprobó por HTTP un archivo existente del concentrador: respuesta 200 y 153.279 bytes. No se escribieron archivos en el concentrador.

La única foto local se conservó en `storage/app/public-local-before-shared/AVResp_261027083636_189.png`. No existe todavía en el concentrador y queda pendiente copiarla allí por el usuario, porque el agente tiene permiso de escritura únicamente en el workspace experimental. El almacenamiento compartido y el respaldo local se excluyeron de Git. Estas junctions son configuración local de Windows y deben recrearse en otros entornos.

En la prueba manual del 27 de octubre, una foto existente fallaba bajo `/storage/`. `public/storage` era una carpeta vacía independiente de `storage/app/public`. Se reemplazó únicamente esa carpeta vacía por una junction de Windows al destino configurado. La URL exacta reportada respondió HTTP 200, `image/png`, con 99.630 bytes, coincidentes con el archivo local. También se verificó un archivo temporal por HTTP y se eliminó al terminar. Es una corrección local del enlace de almacenamiento, no un cambio de rutas ni de base de datos; no demuestra que los adjuntos históricos estén copiados en este entorno.

Comparación antes y después de la actualización:

- PHP de consola: 8.3.31; extensiones `sqlsrv` y `pdo_sqlsrv` disponibles.
- Referencia inicial: Laravel 10.12.0 y PHPUnit 10.1.3; 17 pruebas correctas y 117 comprobaciones.
- Instalación actual: Laravel 13.38.0 y PHPUnit 12.5.42; las mismas 17 pruebas correctas y 117 comprobaciones.
- `composer validate --strict` y `composer check-platform-reqs`: correctos.

Las pruebas usan usuarios en memoria y no crean tablas ni registros. La prueba de AdminLTE omite únicamente la construcción del menú que consulta la base. La prueba Sanctum valida el guard y la respuesta autenticada; todavía no demuestra emisión, persistencia ni revocación de tokens reales.

Para cerrar la etapa falta comprobar, en un entorno de prueba autorizado, SQL Server y sus procedimientos, lectura y respuesta de comunicaciones, adjuntos, fotos, pagos, documentos, reinscripción y contratos de la app móvil. El PHP del servidor web y del hosting debe verificarse por separado del PHP de consola. Las operaciones con escrituras y servicios externos no se ejecutan contra producción por inferir que la carpeta es experimental.

## Referencias de compatibilidad

- [Actualización de Laravel 10 a 11](https://laravel.com/docs/11.x/upgrade): estructura de aplicación, Sanctum y autenticación.
- [Actualización a Laravel 12](https://laravel.com/docs/12.x/upgrade): Carbon 3 y archivos.
- [Actualización a Laravel 13](https://laravel.com/docs/13.x/upgrade): CSRF y serialización de sesiones.
- [Requisitos para SQL Server](https://laravel.com/docs/13.x/database): SQL Server 2017 o superior y drivers PHP/ODBC compatibles.
