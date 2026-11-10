# IFES Plottier

Identidad visual preparada el 10 de noviembre de 2026, con los parámetros informados por el usuario para producción:

```dotenv
EURE_CLIENTE_ID=ifesplottier
EURE_CLIENTE_LEYENDA='IFES Plottier'
```

El tema completo `resources/themes/ifesplottier.json` tiene inicialmente los mismos colores de IFES Neuquén, pero es independiente. Los logos, la foto del login (`LoginCarrusel03.jpg`) y el favicon provienen de `public/assets/C/ifesplottier/institucional/`.

Estos cambios no requieren recompilar React. La selección se toma de la configuración del entorno; si se cambia el `.env`, limpiar la caché de configuración con `php artisan config:clear`.

No se modificó el `.env` activo, la conexión a datos ni los medios de pago. Estos últimos siguen dependiendo de sus tres indicadores `EURE_MERCADO_PAGO`, `EURE_PAGOS_TIC` y `EURE_SUPERVIELLE`, sin selección fija por colegio. Tampoco se modificaron APIs ni vistas móviles. La preparación visual no acredita pruebas funcionales en producción.
