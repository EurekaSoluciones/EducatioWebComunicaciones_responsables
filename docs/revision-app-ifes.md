# Revisión de compatibilidad móvil — entrega IFES

Fecha: 2 de noviembre de 2026.

## Alcance acordado

La entrega alcanza solamente IFES Neuquén (`ifesnqn`). La app móvil debe conservar su comportamiento; su interfaz, APIs y vistas embebidas no se portan a React. Rematriculación de Cultural queda fuera de esta entrega. La regla está incorporada en `AGENTS.md`, el resumen, el plan y el prompt reutilizable.

Esta revisión compara el workspace con el commit local `f53d049` (base previa a la portación); no se comparó contra una copia del código actualmente desplegado ni se inspeccionó el código de la app instalada. `.env` y `.env.example` identifican IFES. Esto no constituye un despliegue ni modifica otros clientes.

## Código y rutas revisados

- `routes/api.php` no tiene diferencias respecto de la base local. Conserva autenticación, información del responsable, comunicaciones/respuestas, recibos/documentos, fotos, adjuntos, carteleras y notificaciones Expo.
- Sin diferencias en controladores `API/*`, modelos, controladores legacy de comunicaciones, alumnos, responsables, cuenta corriente, informes y notificaciones Expo.
- Las secciones API de `EureAuthController` y `AdjuntoController` coinciden con la base local. El nuevo control de borrado en `destroyAdjunto` pertenece al endpoint web; los métodos móviles de carga/borrado siguen separados.
- Se conservaron las tres rutas web embebidas `/comunicaciones/app/{comunicacion}/{responsable}/{token}`, `/comunicaciones/e/app/{comunicacionE}/{responsable}/{token}` y `/carteleras/app/{cartelera}`. Sus controladores y plantillas `appshow` no cambiaron. Siguen entregando HTML Blade independiente, con la adaptación de imágenes existente, sin Inertia ni el layout React.
- Los filtros y permisos nuevos de las comunicaciones web no se trasladaron a la API móvil. Se conservan sus scopes y contratos anteriores.
- La modificación del menú legacy en `EureFunctions` solo agrega el período al enlace web de recibidas; las vistas embebidas no construyen ese menú.

## Cambios compartidos que sí alcanzan a la app

1. **Framework y Sanctum:** la actualización a Laravel 13 y los ajustes de configuración son compartidos. Las pruebas de tokens descritas abajo pasan, pero no permiten afirmar que todos los recorridos de una app instalada fueron validados.
2. **Cuenta corriente:** `APIController::info_responsable` también llama a `EducatioCommFunctions::CC_Obtener`. La corrección de conversión explícita de fecha alcanza por tanto esa consulta móvil. Conserva firma, estructura de salida, procedimiento, límite calculado y redondeo; cambia el transporte/conversión del parámetro. La afirmación anterior de que el helper solo tenía consumidores web era incompleta. Falta verificar la respuesta real de `/api/info-responsable` con SQL Server.
3. **Contenido y archivos compartidos:** mensajes, lecturas, fotos y adjuntos ingresados desde la web serán visibles en la app como datos compartidos. Los nuevos emojis requieren desplegar `public/assets/eure/emoji/v1` junto con la web; los recursos y vistas históricos se conservan. Las pruebas verifican preservación de rutas relativas en el HTML, no la descarga visual en un dispositivo.

El endpoint heredado `/api/passw` referencia `apipasswordUpdate`, método inexistente tanto en la base local como en el workspace. `/api/password-update` sí existe y se probó. No se modificó esta inconsistencia previa; sin el código del cliente no se puede confirmar si alguien consume la ruta antigua.

## Verificación ejecutada

Se añadieron cinco pruebas de regresión con configuración IFES, SQLite en memoria y disco de archivos simulado:

- Login móvil y emisión de token; acceso real con Bearer, sin recurrir al usuario cacheado del guard o sesión web; formato JSON y ausencia de contraseña/token de recuerdo en `/api/user`.
- Cambio de contraseña móvil: contraseña incorrecta, actualización válida, permanencia del token actual y revocación de los demás; logout y rechazo del token revocado.
- Cambio de contraseña web: el token móvil permanece válido.
- Vistas embebidas de recibidas, enviadas y carteleras: Blade, imágenes, acceso autorizado/rechazado, registro de lectura existente y cartelera inactiva. Las rutas no se convierten en páginas React.
- Carga y borrado móvil de adjuntos de enviadas y respuestas: permisos de alumno, estados HTTP, claves JSON, entidades y URLs existentes. Solo archivos simulados.

Resultado: cinco pruebas nuevas aprobadas (99 comprobaciones); suite completa de **118 pruebas PHP y 1769 comprobaciones**, aprobada. Pint de pruebas aprobado. No se enviaron comunicaciones reales, no se cambiaron contraseñas reales ni se escribieron archivos en el concentrador.

## Límite de la conclusión

No se detectaron cambios de interfaz o contrato en los recorridos móviles inspeccionados y probados. No es una certificación completa en dispositivo: quedan pendientes login con una cuenta real IFES, actualización de `/api/info-responsable`, apertura de comunicaciones/carteleras con imágenes y emojis, adjuntos, documentos/recibos y notificaciones push en la app instalada contra el backend preparado. La conexión SQL Server de la consola sigue bloqueada por cifrado ODBC. No se ejecutaron proveedores externos ni notificaciones reales. Tampoco se validó el bundle de producción en esta revisión.

Se debe conservar Blade y los recursos consumidos por la app durante la limpieza posterior; cualquier retiro requiere volver a revisar consumidores.
