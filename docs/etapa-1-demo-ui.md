# Etapa 1 Demo UI de Eureka para Familias

Estado al 28 de octubre de 2026: dependencias instaladas por el usuario, TypeScript y 26 pruebas aprobadas. Build bloqueado en el entorno del agente por `spawn EPERM`; revisión visual pendiente. El usuario autorizó avanzar después de una prueba rápida satisfactoria del sistema actual. Los pendientes funcionales de la etapa 0 siguen registrados por separado.

## Opciones para comparar con Mariela

El usuario pidió conservar la demo y tener variaciones de la primera para presentar a Mariela. Aclaró que la segunda debía acercarse al layout real de Pranzo. Se mantienen dos rutas públicas con selector de comparación:

- Opción 1, lateral: `http://localhost:8003/demo/eureka`. Conserva el menú lateral y su presentación; se agregó únicamente el selector de comparación y se reutilizó el componente de notificaciones de ejemplo.
- Opción 2, variante del layout Pranzo: `http://localhost:8003/demo/eureka/alternativa`. Lateral fijo de 256 px, marca centrada, navegación con iconos y opción activa destacada, institución y perfil al pie. Panel con franja superior de 8 px, esquina redondeada y encabezado de 64 px con breadcrumb, campana y avatar. En celular, menú lateral mediante diálogo nativo, cierre con Escape y fondo modal. El usuario indicó que le gustó esta estructura y luego pidió igualar el tema y el contenido principal con la primera demo.

Se revisaron en la referencia `app-layout.tsx`, `role-shell.tsx` (`RoleSidebar`, `RoleLayoutFrame`, `SidebarUserFooter`), sus consumidores por rol y la pantalla `clientes/show.tsx` con tarjetas. La estructura nueva se adapta de esos archivos; conserva la identidad Educatio y datos ficticios. El proyecto Pranzo solo se leyó.

Ambas opciones comparten la galería interactiva, los componentes, las fotos y las notificaciones de ejemplo. Los estilos de la segunda están limitados a `.eure-pranzo` en `resources/css/eureka-pranzo.css`. El layout, CSS y JSON de la primera se conservan; el selector compartido cambia el nombre de la segunda a «Pranzo».

Ambas demos leen el mismo archivo completo `resources/themes/isi.json`, seleccionado por el cliente configurado y validado sin herencia ni fallback. Se retiraron la paleta exclusiva de la segunda y su cargador. El editor de las dos indica el mismo archivo de destino. Las pruebas de colores siguen siendo temporales y locales a cada pestaña: al navegar entre opciones se vuelve al archivo del cliente. Cambiar ese JSON actualiza ambas al recargar. Se pueden abrir en pestañas distintas para comparar sus layouts con la misma identidad visual.

Feedback sobre el color: después de pedir quitar la paleta de marca de Pranzo y probar «Azul y arena», el usuario pidió aplicar al nuevo layout el mismo tema que al original. Esa es la configuración vigente; el archivo de colores original no se modificó.

Feedback sobre el contenido: ambas opciones muestran ahora la misma bienvenida («La misma familia. Una nueva experiencia.»), descripción, botón de colores, tarjeta introductoria con fotos, estadísticas y galería completa, en el mismo orden. El título se comparte mediante `DemoPageHeading` y la galería sigue siendo única en `demo/Ui.tsx`. Se quitaron la ocultación de la introducción y los estilos exclusivos de las tarjetas de la alternativa. El menú y el encabezado de cada layout conservan su estructura; el aspecto de la primera no cambia.

La verificación automatizada cubre rutas públicas, igualdad del tema recibido por ambos layouts y validación de temas. La revisión visual automatizada queda pendiente; el acceso al navegador del agente no estaba disponible. El último intento de `npm run build` falló al cargar Vite por `spawn EPERM` en este entorno. Para ver la demo, mantener `composer run dev` activo en el proyecto y abrir las rutas de Laravel en el puerto 8003.

## Devoluciones de la primera opción

Primer feedback visual del usuario: la base le gustó, pero faltaba la campana de notificaciones. Se incorporó al encabezado con contador, panel de novedades, avatares, lectura individual, lectura de todas y restablecimiento de ejemplos. El contador empieza en dos pendientes y se actualiza en memoria. Abrir el panel no marca automáticamente las notificaciones como leídas. Los colores usan el tema existente. TypeScript pasó; la comprobación visual automatizada no pudo realizarse porque el acceso al navegador no estaba disponible.

La campana de la demo no consulta `NotificationController` ni envía datos. Cuando se migre el inicio real, hay que adaptar el contrato del backend, que actualmente devuelve un contador y HTML para AdminLTE, y acordar las acciones de lectura reales.

La ruta `/demo/eureka` es pública por pedido del usuario y presenta una galería React con datos ficticios. No requiere iniciar sesión. Las demos siguen aisladas del negocio. Posteriormente, en la [etapa 1B](etapa-1b-acceso-inicio.md), se migraron login e inicio reales; el middleware Inertia también se aplica a esas rutas y el inicio conserva su autenticación. Los módulos pendientes siguen en Blade.

Se verificó acceso anónimo por HTTP en el puerto 8003 y las cuatro pruebas de la demo pasan, incluida la protección de `/home`. Se desactivó SSR en `config/inertia.php` porque esta integración renderiza React en el navegador; así Inertia no intenta consultar un servicio SSR inexistente cuando Vite está encendido.

La galería incluye menú adaptable, breadcrumb, franja superior, tarjetas, botones, avisos, etiquetas, campos, validación local, interruptores, avatares, búsqueda, filtros, orden y paginación de una tabla, diálogos y notificaciones. Los avatares ilustrados son archivos de muestra propios y tienen respaldo de iniciales. Las acciones funcionan en memoria, sin consultas de dominio, carga de archivos ni escrituras en la base o el concentrador.

Se adaptaron los patrones de `EureCard`, botones y campos de Pranzo a componentes locales. Esta primera base utiliza CSS con variables y React, sin importar la aplicación de Pranzo ni toda su biblioteca de controles. Todavía no incluye editor enriquecido, recorte de imágenes ni los controles especializados de Pranzo.

## Instalación necesaria

Desde la raíz de este proyecto, el usuario debe ejecutar:

```powershell
composer update inertiajs/inertia-laravel --with-all-dependencies
npm install
```

Los manifiestos declaran Inertia 3, React 19, TypeScript y Vite 8. Node local es 22.18.0, dentro del rango declarado. Las instrucciones del workspace impiden al agente instalar dependencias; no se copiaron paquetes ni se usaron los de Pranzo para ejecutar la demo. Los locks se actualizarán con estos comandos, no manualmente.

Después de instalar, verificar:

```powershell
composer test
npm run types:check
npm run build
```

Para trabajar con cambios en vivo, usar `composer run dev`, como en Pranzo. Arranca Laravel y Vite juntos mediante `npm run dev:all` y `concurrently`, sin iniciar un procesador de colas. Se declaró `concurrently` como dependencia de desarrollo; después de este cambio hace falta ejecutar `npm install` una vez. El agente no puede instalarlo por las restricciones del workspace. El arranque conjunto queda pendiente de verificar después de esa instalación.

Cerrar antes los procesos independientes de `php artisan serve` y `npm run dev` para evitar ocupar los mismos puertos. El usuario eligió el puerto 8003: `.env` y `.env.example` definen `SERVER_PORT=8003` y `APP_URL=http://localhost:8003`. Laravel 13 lee `SERVER_PORT` al ejecutar `artisan serve`, también desde `composer run dev`. Abrir directamente `http://localhost:8003/demo/eureka`, sin login. Vite sirve los assets; la pantalla se abre desde Laravel. Los comandos separados siguen disponibles para diagnóstico.

No publicar assets ni ejecutar migraciones para esta demo. Los puntos de entrada anteriores de Vite se conservan junto al nuevo `resources/js/eureka.tsx`.

## Cómo cambiar el tema

El cliente activo se obtiene de `EURE_CLIENTE_ID` mediante `config/eureka.php`. Inicialmente se implementó `isi`, que es el cliente local configurado.

El archivo completo es `resources/themes/isi.json`. Contiene el nombre y los 33 colores del tema. Cada futuro cliente tendrá su propio archivo completo, sin herencia ni sobrescrituras entre instituciones. El selector rechaza identificadores inválidos, archivos ausentes, paletas incompletas y valores que no sean colores hexadecimales. No asigna otro cliente silenciosamente.

Por feedback del usuario, el bloque inferior del menú (volver al sistema y perfil) tiene colores independientes del resto del sidebar. La sección «Pie del menú» permite cambiar fondo, texto/iconos, texto secundario y separadores, mediante los cuatro tokens `sidebar-footer*`. Se mantienen los colores anteriores como valores iniciales. Los temas descargados antes de este cambio necesitan incorporar estos cuatro tokens; descargar nuevamente desde la demo produce el contrato completo.

Hay dos formas de probar los colores:

1. Editar el JSON. Con Vite en desarrollo, el watcher recarga la pantalla; con assets compilados basta recargar la página, porque Laravel lee el tema en cada petición. No se necesita compilar por un cambio de valores en el tema.
2. Usar los selectores y códigos hexadecimales del panel lateral. Los cambios afectan la vista previa completa, sin persistencia. «Restablecer archivo» recupera los colores recibidos del servidor. «Descargar tema completo» genera un JSON que se puede usar para reemplazar el archivo del cliente.

El panel muestra relaciones de contraste para texto sobre tarjeta, botón principal, franja superior y los dos textos del pie del menú. Son comprobaciones de esos pares, no una certificación de accesibilidad del tema completo. Los colores de las ilustraciones de muestra son contenido, no colores de interfaz.

## Verificación y pendientes

Antes de instalar las dependencias: 22 pruebas pasaron; 4 pruebas específicas de Inertia quedaron omitidas explícitamente porque falta el paquete. La ejecución informa 26 pruebas, 143 comprobaciones y 4 omitidas. La sintaxis PHP y la configuración JavaScript se verificaron; los archivos PHP nuevos se formatearon con Pint.

Después de instalar: `npm run types:check` pasó y `composer test` pasó con 26 pruebas y 170 comprobaciones, sin omisiones. La prueba de la respuesta Inertia se corrigió para enviar `X-Inertia-Version` con la versión actual de los assets; sin esa cabecera el protocolo devuelve 409 para solicitar una recarga.

`npm run build` quedó bloqueado al cargar Vite por `spawn EPERM` en el entorno restringido del agente. Debe ejecutarse desde la terminal del usuario. Para abrir la demo en desarrollo, usar `npm run dev` junto a `php artisan serve`. Falta revisar en navegador escritorio y celular, navegación con teclado, foco de diálogos, comportamiento del menú y actualización/exportación/restablecimiento de colores. No se presenta esta implementación como una demo ya comprobada visualmente.

Después de la revisión del usuario, aplicar las decisiones al layout y a los componentes comunes. La migración de login e inicio reales comienza después de validar esta demo.

## Referencias de integración

- [Inertia 3 para Laravel](https://inertiajs.com/docs/v3/installation/server-side-setup).
- [Configuración manual de React con Inertia](https://inertiajs.com/docs/v3/installation/client-side-setup#manual-setup).
- [Vite](https://vite.dev/guide/).
