# Convenciones del proyecto

## Alcance de la portación y entrega IFES

La app móvil debe conservar su comportamiento. No portar ni rediseñar sus vistas Blade, ni cambiar contratos de API, autenticación/tokens, permisos, respuestas, cargas de archivos o enlaces consumidos por la app como parte de esta modernización web. Antes de modificar o retirar código compartido, rutas, vistas o assets, verificar sus consumidores móviles. Mantener las vistas `appshow` y sus recursos mientras la app los utilice.

La entrega prevista para el 2 de noviembre de 2026 alcanza únicamente IFES Neuquén (`ifesnqn`). No desplegar ni habilitar funcionalidades para otros clientes. Rematriculación de Cultural y portación de pantallas móviles quedan fuera de esta entrega. Registrar cualquier impacto móvil detectado y distinguir revisión de código/pruebas aisladas de una validación real en el dispositivo.

Ampliación autorizada el 4 de noviembre de 2026: preparar la web de ISI College (`isi`) y su pago con Supervielle, exclusivo para ese cliente. Esta etapa incluye confirmación del importe, publicación individual sin email/SMS y apertura del enlace del banco; no incluye imputación automática de cobros, despliegue ni pruebas reales con el banco. Mantener intactos los medios de pago de otros colegios y los contratos móviles.

Ampliación posterior del mismo día: preparar el receptor de notificaciones Supervielle, consulta de estado y persistencia para imputación mediante stored procedure. Mantenerlo desactivado hasta definir el procedimiento y registrar la URL con el banco. Esta preparación no autoriza aplicar migraciones a la base compartida, imputar cobros reales ni desplegar. Las pruebas deben usar base aislada y banco simulado.

Ampliación autorizada el 10 de noviembre de 2026: preparar la identidad visual web de IFES Plottier (`ifesplottier`, leyenda `IFES Plottier`) con la misma estética de IFES Neuquén. Mantener un tema completo e independiente y recursos en la carpeta del cliente. Esta preparación no cambia la base de datos, los medios de pago ni el entorno activo, y no implica despliegue.

## Recordatorio al retomar Supervielle

Actualización autorizada el 9 de noviembre de 2026: la disponibilidad de botones de pago web depende exclusivamente de `EURE_MERCADO_PAGO`, `EURE_PAGOS_TIC` y `EURE_SUPERVIELLE` (`S`/`N`), sin selección fija por cliente. Pueden coexistir varios medios. Para preparar ISI sin botones, mantener los tres en `N` en su entorno. Esto reemplaza la selección exclusiva por colegio; no habilita el webhook, despliegues ni cobros reales.

**NO ENVIAR EL EMAIL.** Recordar esta decisión al usuario cuando se retome la integración de ISI/Supervielle. La publicación debe generar únicamente el enlace de pago: omitir el campo `mail` para no solicitar al banco el envío de correo. Mantener también la omisión de `celular` (sin SMS). No copiar esos campos de los ejemplos de curl ni reintroducir el envío de la integración C# anterior. El webhook entrante de pagos es independiente y no autoriza enviar emails a las familias.

## Fechas para humanos en React

Siempre que se solicite una fecha para humanos o relativa, usar el componente compartido `EureHumanDate` de `resources/js/components/ui/eure`.

Debe llevar un subrayado fino y alternar entre fecha relativa y exacta con clic o teclado. Desde el umbral configurable debe mostrar la exacta inicialmente (valor inicial: 30 días). Enviar fechas ISO 8601 con zona horaria explícita. No anidar el componente dentro de enlaces o botones: alternar el formato no debe abrir una comunicación ni marcarla como leída.

El contrato completo y los ejemplos están en `resources/js/components/ui/eure/README.md`. Mantener esta convención en las próximas pantallas y portaciones; no duplicar la lógica por pantalla.
