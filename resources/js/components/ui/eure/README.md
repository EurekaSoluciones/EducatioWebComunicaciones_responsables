# EureUI portado para Familias

Fuente: `D:/Eureka/Pranzo/MultivacCloudPranzo/MultivacCloudPranzo-main/resources/js/components/ui/eure`, revisada el 28 de octubre de 2026.

Esta carpeta contiene la portación de componentes de Pranzo para el login y la ficha del alumno:

- `EureInput`: control básico, referencia al input, estado de error e icono.
- `EureInputGroup`: composición de label, acción del label, campo, ayuda y error; conserva los nombres de sus props.
- `EureButtonPrimary`: botón principal, estado de carga y posición del indicador.
- `EurePasswordInputGroup`: composición local del `EureInputGroup` portado, con una acción de mostrar/ocultar accesible por teclado.
- `EureCard`: portado del archivo homónimo real de Pranzo; conserva título, icono Lucide, descripción, variantes default/plain, contenido y estilos opcionales. Se adapta el contenedor a `section` y el título a `h2` para la jerarquía de la ficha.
- `EureAlert`: portado del archivo homónimo real, con tipo, mensaje y cierre opcional; colores conectados a los estados del cliente.
- `EureHumanDate`: extensión propia del proyecto (no portado de Pranzo) para fechas relativas interactivas.
- `EureButtonSecondary`: portado desde el componente real y su variante `Button outline` de Pranzo, traduciendo estilos a CSS y conservando el contrato de botón nativo. No se exponen `asChild`, variantes o tamaños del wrapper Radix no instalado.
- `EureImageCropper`: extensión propia que encapsula los assets locales Cropper.js 1.6.2 ya existentes; no se encontró un recortador equivalente entre los componentes de Pranzo revisados. Carga script y CSS una vez, espera sus estilos, libera instancias/object URLs al cambiar de imagen o desmontar, permite mover con mouse/táctil/flechas, zoom, giros y reinicio. `exportFile()` devuelve un PNG cuadrado de 512 px; no lo sube automáticamente.

## Editor de mensajes

Emojis gráficos: `EureEmojiPicker` usa el catálogo local anterior extraído a PNG individuales, con 5 categorías, búsqueda en español/alias y recientes. `emojiBaseUrl` viene del servidor y debe pasarse también a `cleanEditorHtml`; `emojiStorageKey` separa los recientes por cliente/cuenta. Se insertan a 24 px, alineados con el texto, con descripción alternativa y sin controles de resize. `FamilyEmojiCatalog` y la limpieza del cliente/servidor autorizan únicamente las URLs exactas del catálogo; las fotos conservan su validación de borrador. Ver `resources/emoji/README.md`. Unicode escrito/pegado por el usuario se conserva; el selector inserta gráficos.

Imágenes seleccionables: `EureImageResizeControls` agrega borde y tirador inferior derecho, ajuste proporcional con Pointer Events (mouse/táctil), flechas de teclado, tamaños 25/50/100% del contenedor y restauración original. El arrastre previsualiza sin publicar cada movimiento; al terminar restaura el HTML previo y reemplaza solo esa imagen mediante un comando nativo para permitir deshacer/rehacer. Escape o cancelación del puntero descartan la previsualización. Se conserva el ancho en HTML con `height:auto` y `max-width:100%`; no se modifica ni vuelve a subir el archivo. No incluye deformación con Shift ni alineación flotante. Pendiente comprobación manual del arrastre y del historial nativo en navegador.

`EureRichTextEditor` es un componente propio de React, no una portación literal de Pranzo ni un wrapper de Summernote. Recibe `id`, `initialHtml`, `imageUrls`, `disabled`, `onChange(html)`, `uploadImage(file)` y `onBusyChange(busy)`. El padre implementa carga/autorización, cancelación de peticiones, persistencia del borrador y envío. `initialHtml` se aplica al montar; usar una `key` distinta por borrador. `imageUrls` contiene únicamente las imágenes cargadas del borrador: retirar una URL quita su imagen del texto.

Permite formato, tipografías/tamaños/colores, listas/alineación, enlaces, tablas, emojis gráficos, imágenes pegadas/arrastradas/seleccionadas, deshacer/rehacer, HTML y área ampliada. Los videos se comparten como enlaces; no se incrustan reproductores. El pegado de texto con formato conserva etiquetas permitidas, pero no importa imágenes externas: se deben pegar como archivo o subirlas. HTML pegado, código y borradores restaurados se limpian antes de entrar al DOM; `FamilyMessageHtml` vuelve a validar al enviar, también para los clientes institucionales legacy.

Implementación experimental sin dependencias nuevas: utiliza `contentEditable` y comandos nativos para conservar el historial de deshacer. `execCommand` está obsoleto y necesita comprobación por navegador ([documentación MDN](https://developer.mozilla.org/en-US/docs/Web/API/Document/execCommand)); antes de dar esta pieza por definitiva, validar Chrome/Edge y móvil o reemplazar su motor por una biblioteca mantenida, conservando el contrato React. Pendiente recorrido visual/real de selección, pegar imágenes, diálogos, deshacer/rehacer y restauración. TypeScript y pruebas PHP de HTML/autorización no sustituyen esos controles.

## Convención obligatoria: fechas para humanos

Cuando se pida «fecha para humanos», «hace…» o una fecha relativa en React, usar `EureHumanDate`; no presentar una cadena relativa suelta ni duplicar la lógica en cada pantalla.

```tsx
<EureHumanDate value={communication.dateTime} />
// Excepción explícita al umbral general:
<EureHumanDate value={communication.dateTime} exactAfterDays={7} />
```

- Recibe fecha ISO 8601 con zona horaria explícita. El servidor debe enviarla con `toIso8601String()`.
- Subrayado fino para indicar interacción; clic, Enter o Espacio alternan relativa y exacta, en ambos sentidos.
- Desde 30 días transcurridos muestra inicialmente la fecha exacta. Es el valor inicial adoptado mientras se evalúa el umbral; se configura por uso con `exactAfterDays` o globalmente en `HUMAN_DATE_EXACT_AFTER_DAYS`.
- La fecha exacta usa `dd/mm/aaaa HH:mm` y por defecto la zona `America/Argentina/Buenos_Aires`, configurable con `timeZone`.
- Actualiza la fecha relativa cada minuto y al recuperar foco; respeta el formato que eligió el usuario. Una fecha diferente restablece el criterio inicial.
- Fechas ausentes o no válidas muestran `—` sin interacción (personalizable con `fallback`).
- Es un botón con apariencia de enlace, porque alterna una visualización. Nunca anidarlo dentro de un enlace o botón. En una fila navegable, separar el enlace de apertura y el control de fecha: alternar la fecha no debe navegar, enviar formularios ni marcar lecturas.
- La fecha exacta sigue disponible en el título cuando se ve la relativa. Mantener foco visible y colores del tema.

Ejemplo aplicado: últimas comunicaciones de `family/StudentProfile`. Validación de fechas: `npm run test:dates`.

Adaptaciones explícitas:

- Las clases utilitarias de Tailwind se expresan en `resources/css/eureui.css`, porque este proyecto no tiene Tailwind instalado. Se mantienen las dimensiones, jerarquías y estados de los controles de referencia, con input de 16 px en celular para evitar el zoom al enfocarlo.
- Los tokens de Pranzo se conectan al archivo completo del cliente mediante alias CSS. No se incorporan los colores de marca de Pranzo ni otra paleta.
- La rama de máscaras de `EureInput`, que depende de `@react-input/mask`, no está portada ni se ofrece como prop. El login no usa máscaras. Antes de usar campos enmascarados debe resolverse esa dependencia; no se simula su comportamiento.
- Se añadieron asociaciones `aria-describedby`, mensajes de error y visibilidad de contraseña accesible por teclado. El input oculto CSRF permanece en el formulario.
- Se conservó el envío HTML del login para respetar sus destinos Blade y la sesión existente. No se copió el backend de autenticación de Pranzo.

No es una instalación del catálogo completo de EureUI. `components/eureka` contiene todavía los componentes provisionales de la demo y parte del portal; no debe confundirse con esta portación. Las próximas migraciones deben partir de los componentes reales de la referencia y consolidarse aquí, evitando mantener dos implementaciones equivalentes a largo plazo.

La ficha y la edición de foto usan las tarjetas, alertas y botones portados; reutilizan el avatar existente y el menú/campana del portal. Esos componentes compartidos mantienen su implementación anterior. «Cambiar foto» ya abre React. Los enlaces a pantallas Blade conservan navegación HTML normal, y el acceso a «Nuevo adjunto» usa el estilo del botón primario con semántica de enlace.
