# Pago web de ISI con Supervielle

**Recordatorio para retomar: NO ENVIAR EL EMAIL.** Generar únicamente el enlace de pago; omitir `mail` y mantener `celular` omitido (sin SMS), aunque aparezcan en ejemplos de curl o en el C# anterior. Recordar esta decisión al usuario al retomar. El webhook de pagos no implica enviar correos a las familias.

Selección actualizada el 9 de noviembre de 2026: los botones web se habilitan exclusivamente por `EURE_MERCADO_PAGO`, `EURE_PAGOS_TIC` y `EURE_SUPERVIELLE` (`S`/`N`), independientemente del colegio. Esto reemplaza la selección fija inicial de Supervielle para ISI. Pueden coexistir los tres. El receptor de notificaciones Supervielle continúa desactivado y pendiente de conectar al stored procedure de imputación.

## Recorrido

1. La familia elige un importe positivo con hasta dos decimales, sin separadores de miles.
2. Un POST autenticado prepara una confirmación con alumno, importe y vencimiento, sin llamar al banco.
3. Al confirmar se obtiene el token y se publica una deuda `Cerrada`. Se omiten `mail` y `celular`, y el banco asigna `id_publicacion`.
4. Se exige HTTP 201, un `hash` UUID y un `link_de_pago` HTTPS del host autorizado, con la ruta `/publicacion/{hash}`. Se redirige al enlace, sin dar el cobro por realizado.

El vencimiento inicial es el día siguiente en `America/Argentina/Buenos_Aires`, configurable entre 1 y 30 días. Una confirmación pendiente que llega al día de vencimiento ya no publica; hay que preparar una nueva. Un enlace ya generado puede abrirse durante su día de vencimiento.

Las referencias incluyen `cod_alumno`, `cod_responsable` y el UUID del intento. No se inventa una factura ni se ejecuta el procedimiento de barrido del C#.

## Configuración

`config/supervielle.php` lee únicamente configuración del servidor. Las credenciales se importaron al `.env` local, que no se versiona. No se entregan al navegador.

- `EURE_SUPERVIELLE=N`: oculta el botón de Supervielle y rechaza preparar, confirmar o publicar pagos mediante sus rutas web. Cambiar a `S` para habilitarlo cuando corresponda; admite minúsculas. Ausente, vacío o cualquier valor distinto de `S` queda deshabilitado. Reemplaza `EURE_SUPERVIELLE_ENABLED`, que ya no se utiliza. Las credenciales no habilitan el botón por sí solas.
- `EURE_MERCADO_PAGO` y `EURE_PAGOS_TIC`: misma convención `S`/`N`, independientes entre sí y de Supervielle. La web lee estos valores desde `config/payments.php`, compatible con caché de configuración. Sin métodos habilitados desaparece toda la sección «Pagar en línea». Los tres botones usan `POST /cc/pagar`; el servidor comprueba el parámetro del medio elegido y deriva Supervielle a su confirmación. Las rutas específicas anteriores de Supervielle se conservan. Los intentos en caché se separan por cliente.
- `EURE_SUPERVIELLE_TOKEN_URL`, `EURE_SUPERVIELLE_PUBLICACION_URL`: URLs del mismo ambiente del banco.
- `EURE_SUPERVIELLE_USERNAME`, `PASSWORD`, `CLIENT_ID`, `CLIENT_SECRET`, `IBM_CLIENT_ID`: credenciales (todos con prefijo `EURE_SUPERVIELLE_`).
- `EURE_SUPERVIELLE_CONVENIO`, `TIPO_PUBLICACION=Deuda`, `MODALIDAD=Cerrada`.
- `EURE_SUPERVIELLE_FORWARDED_HOST`, `FORWARDED_FOR`: encabezados opcionales heredados de la configuración C#.
- `EURE_SUPERVIELLE_SCOPE=boton-pago.publicar`, `TIMEOUT_SECONDS=30`, `VENCIMIENTO_DIAS=1`.
- `EURE_SUPERVIELLE_CERT_PATH`, `KEY_PATH`, `KEY_PASSWORD`: certificado mTLS y clave privada, fuera del directorio público. Si se configura uno, deben estar disponibles ambos archivos. No se desactiva la validación TLS del servidor. El manual exige mTLS: la disponibilidad/instalación del certificado del convenio sigue pendiente de verificación real.
- `EURE_SUPERVIELLE_CHECKOUT_HOSTS`: hosts exactos separados por coma; por defecto `recaudaciones.supervielle.com.ar`. Para otro ambiente, usar solo el host confirmado por el banco, sin comodines.
- `EURE_SUPERVIELLE_CA_BUNDLE`: archivo opcional de autoridades certificadoras para validar el servidor, distinto del certificado mTLS del cliente. Si está vacío, usa la configuración de confianza de PHP. El diagnóstico ejecutado por el usuario devolvió cURL 60; PHP local no tenía `curl.cainfo` ni `openssl.cafile` configurados. Se configuró en el `.env` local el archivo CA existente de Git (`C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt`), manteniendo la verificación TLS en token y publicación. Esta ruta es propia del equipo; no copiarla a producción. Falta repetir el diagnóstico desde la terminal del usuario para confirmar la conexión real.

Cambiar institución no cambia base de datos, convenio ni ambiente bancario. No se modificaron esos destinos durante la implementación.

Preparación del 9 de noviembre: el `.env` local queda con los tres parámetros en `N` para publicar ISI sin pagos en línea. Configurar también esos valores en el entorno de destino; el `.env` local no se despliega con el código. Si el servidor utiliza configuración cacheada, regenerarla con `php artisan config:cache` dentro del procedimiento habitual de publicación. No se realizó ningún despliegue. El webhook sigue controlado por `EURE_SUPERVIELLE_WEBHOOK_ENABLED`, restringido a ISI, y no se habilita al mostrar el botón.

## Reenvíos y errores

Verificación de la selección por parámetros del 9 de noviembre: las ocho combinaciones de botones en ISI e IFES, bloqueo de métodos apagados, envío por el distribuidor común y aislamiento de intentos por institución. Suite PHP completa: 154 pruebas y 2225 comprobaciones aprobadas; 3 pruebas de formulario React aprobadas; Pint de los archivos revisados y sintaxis del controlador legacy aprobados. No hubo llamadas reales a proveedores ni cambios en contratos móviles.

Los intentos se almacenan en la caché del servidor hasta dos días después del vencimiento. Se comprueba el usuario propietario y su relación actual con el alumno en cada acceso. Importe y alumno de la publicación provienen del intento guardado, no de campos reenviados al confirmar.

Un bloqueo atómico protege tanto la preparación como la publicación. El mismo usuario/alumno/importe/vencimiento reutiliza el intento: una segunda confirmación abre el enlace ya obtenido. No representa una nueva cuota ni un segundo pago. La caché debe ser persistente y compartida entre trabajadores (en este proyecto, `file`; si hay varios servidores, usar una caché común con locks). No vaciarla durante operaciones pendientes. Es protección temporal para iniciar pagos, no un registro contable durable; la futura conciliación necesita persistencia propia.

Ante un 401 de publicación se renueva el token y se reintenta una vez. Ante cualquier error, el botón de continuar permanece visible pero deshabilitado, y el servidor impide reenviar el mismo intento, incluso si el error ocurrió al obtener el token. Esto también se aplica a intentos guardados antes de esta regla. Timeout, HTTP 5xx o respuesta exitosa incompleta/insegura dejan el intento indeterminado: no se repite la publicación automáticamente ni desde el mismo intento. Un proceso interrumpido después del envío queda en proceso y tampoco se reenvía. No se imprime la respuesta cruda del banco ni excepciones con credenciales.

## Evidencia y límites

Para diagnosticar únicamente la autenticación, ejecutar `php scripts/diagnostico-supervielle.php` desde la raíz del proyecto. No publica deudas ni muestra el token o secretos. Los fallos nuevos de token registran la etapa, el estado HTTP, un código OAuth reconocido o el código numérico de cURL en el log de Laravel; no registran cuerpos de respuesta ni mensajes crudos de excepciones. El fallo original de la pantalla no conservó ese detalle. La comparación de configuración cargada con el C# confirmó coincidencia de credenciales, convenio, URLs y encabezados; eso no prueba que el banco los acepte actualmente. La solicitud de token desde la sesión del agente quedó bloqueada por su proxy local restringido (cURL 7), por lo que no determina la causa del fallo del portal.

Las pruebas aisladas del diagnóstico y el error de token aprobaron 9 casos y 93 comprobaciones.

Contrato: `docs/APIBANK Recaudaciones, Seguridad y Access Token-rendicion V.1.3.1 3.pdf`, páginas 4–12, y respuesta exitosa aportada por el usuario (`hash`, `link_de_pago`, `errores`). El C# sirve de referencia para token y publicación. No se ejecutó el C# ni se alteró su configuración.

Las pruebas del portal usan SQLite en memoria y HTTP simulado. Las del cliente HTTP usan un servidor local con credenciales ficticias; no comprueban la aceptación del convenio, certificados o credenciales reales. La CLI de Postman no arranca por una política local de Private Cloud; no se instaló ni modificó esa herramienta.

Verificación local del 4 de noviembre: suite PHP completa, **133 pruebas y 1966 comprobaciones**, aprobada; TypeScript y Pint de los archivos PHP revisados, aprobados. El build de Vite se intentó, pero el entorno bloqueó un proceso auxiliar con `spawn EPERM`; queda ejecutar `npm run build` en la terminal del usuario. No se validó visualmente el recorrido en navegador durante esta implementación.

Impacto móvil: se conservan los métodos API, tokens, vistas `appshow` y sus recursos. `POST /cc/pagar` es la ruta web del formulario Blade histórico y de React; ahora verifica los parámetros de cada medio y deriva Supervielle a su confirmación. Los métodos de descarga y notificaciones existentes no se modificaron. La ruta de webhook nueva se detalla más abajo. Esta revisión y los tests no equivalen a validación en dispositivo.

No se realizaron publicaciones reales, notificaciones, imputaciones, cambios de base compartida ni despliegues. La prueba real del convenio y el circuito de confirmación/imputación del cobro quedan fuera de esta etapa.

## Corrección del formulario de inicio

Revisión posterior: el campo oculto `metodo` se modificaba directamente pero conservaba `defaultValue=""`; React podía restaurar ese valor al mostrar el estado de carga. El formulario ahora controla el medio mediante estado React y confirma el valor antes de serializar el POST. Los botones permanecen habilitados durante ese envío nativo, con protección de reentrada por referencia y estado accesible; los errores de `metodo` también se muestran al regresar a la cuenta.

El formulario está separado en `FamilyOnlinePayment.tsx` para verificar su HTML con React sin iniciar sesiones reales ni acceder al banco. Las pruebas se ejecutan con `node tests/js/payment-form.test.mjs`. La revisión interactiva de Chrome no pudo realizarse porque Computer Use no recibió aprobación de acceso.

## Receptor de notificaciones: preparación sin activar

Contrato del manual suministrado: página 12 (consulta del estado), 16 (estados y medios), 17–18 (callback GET, hash y respuesta 200). Ruta preparada:

```text
GET /api/tercerizados-cobranza/supervielle/notificacion-pago/{hash}
```

No requiere sesión familiar. Solo funciona para ISI, con `EURE_SUPERVIELLE_WEBHOOK_ENABLED=true`. Permanece desactivado por defecto; el `.env` activo no se cambió para habilitarlo. Es independiente del flag del botón: detener nuevas publicaciones no debe impedir procesar pagos anteriores. HEAD no procesa pagos. Todas las respuestas llevan `Cache-Control: no-store`; el alojamiento/CDN también debe excluir esta ruta de caché. Conserva el límite API existente de 60 solicitudes/minuto por IP, a revisar con el volumen y reintentos acordados con el banco.

El receptor verifica que el hash esté registrado para el convenio activo. Después consulta `GET {publication_url}/{hash}/estado`, usando un token con `EURE_SUPERVIELLE_CONSULT_SCOPE=boton-pago.consultar`. No usa importes ni estados enviados por quien invoca el callback. Comprueba convenio, alumno, UUID e importe contra el registro propio. El manual no describe una firma del callback; la verificación efectiva del pago se realiza mediante consulta autenticada al banco. La configuración de la URL y cualquier autenticación adicional quedan pendientes de respuesta de Supervielle.

Se creó la migración `database/migrations/2026_11_04_000001_create_supervielle_ledger.php` con tablas `web_supervielle_publications` y `web_supervielle_payments`. **No fue aplicada a la base compartida**. Cuando se habilite el modo webhook, el flujo del botón guarda el intento antes de llamar al banco y asocia el hash recibido antes de redirigir. Si falla la persistencia, no se reenvía automáticamente la publicación. Si el banco publicó pero no se logró guardar el hash, el intento debe reconciliarse manualmente con el banco; no debe crearse otro para resolverlo. Las publicaciones anteriores a esta función no se importan automáticamente desde la caché.

Los avisos repetidos se controlan con una clave única por cliente, convenio e `id_pago`, una transacción y bloqueo de la publicación. Estados 1 (aprobado) y 2 (acreditado) pasan a imputación una sola vez; pasar de 1 a 2 no produce otro cobro. Estado 3 (pendiente) se guarda sin imputar. Estados 4 (reversado), 5 (anulado) e importes diferentes del solicitado se guardan con `needs_review=true`, sin hacer ajustes contables automáticos. Esta revisión todavía no tiene una pantalla ni un aviso al operador; debe definirse antes de activar el circuito completo. Fechas del banco sin zona se interpretan en Buenos Aires, pendiente de confirmación con el banco.

El adaptador `SuperviellePaymentPoster::post()` recibe la publicación (alumno, convenio, hash, UUID) y el pago normalizado (ID bancario, estado, medio, importe decimal y fecha). **Todavía no ejecuta un stored procedure**: falla explícitamente hasta recibir su nombre y contrato. La futura llamada debe usar la misma conexión y transacción que el registro de pagos, informar errores y no confirmar por su cuenta la transacción exterior. Así un fallo revierte tanto el cobro como su marca de imputación. El SP también debe controlar el ID bancario para integrarse con cualquier otro proceso de cobranza.

La respuesta es 200 solo al completar el tratamiento (incluyendo pagos pendientes o guardados para revisión). Hash inválido/desconocido devuelve 400; fallo bancario, de base o de imputación devuelve 503 para que el banco reintente. No se reconoce como procesado un cobro que falló. El manual indica que los reintentos se agotan: queda pendiente una tarea de conciliación con rendiciones para recuperar notificaciones perdidas, además del alta de la URL, instalación de tablas y conexión al SP.

Pruebas aisladas: receptor con SQLite en memoria y respuestas basadas en el esquema del manual; cliente HTTP contra servidor PHP local con credenciales ficticias. Cubren repetición, transición aprobado/acreditado, pendiente/aprobado, reversos, importes discrepantes, fallo con rollback, hash desconocido, estado inesperado, persistencia de publicaciones y renovación del token de consulta. No prueban concurrencia real de SQL Server, el contrato del SP ni entregas del banco. Las rutas móviles y receptores existentes de MP/PTIC se conservaron; se agregó únicamente la ruta nueva.

Verificación de esta preparación: suite PHP completa aprobada, **152 pruebas y 2137 comprobaciones**; Pint aprobado para los archivos PHP nuevos/modificados de Supervielle. No se ejecutaron migraciones fuera de SQLite de pruebas, llamadas al banco, imputaciones reales ni despliegues.
