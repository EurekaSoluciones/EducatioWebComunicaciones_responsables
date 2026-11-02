# Convenciones del proyecto

## Alcance de la portación y entrega IFES

La app móvil debe conservar su comportamiento. No portar ni rediseñar sus vistas Blade, ni cambiar contratos de API, autenticación/tokens, permisos, respuestas, cargas de archivos o enlaces consumidos por la app como parte de esta modernización web. Antes de modificar o retirar código compartido, rutas, vistas o assets, verificar sus consumidores móviles. Mantener las vistas `appshow` y sus recursos mientras la app los utilice.

La entrega prevista para el 2 de noviembre de 2026 alcanza únicamente IFES Neuquén (`ifesnqn`). No desplegar ni habilitar funcionalidades para otros clientes. Rematriculación de Cultural y portación de pantallas móviles quedan fuera de esta entrega. Registrar cualquier impacto móvil detectado y distinguir revisión de código/pruebas aisladas de una validación real en el dispositivo.

## Fechas para humanos en React

Siempre que se solicite una fecha para humanos o relativa, usar el componente compartido `EureHumanDate` de `resources/js/components/ui/eure`.

Debe llevar un subrayado fino y alternar entre fecha relativa y exacta con clic o teclado. Desde el umbral configurable debe mostrar la exacta inicialmente (valor inicial: 30 días). Enviar fechas ISO 8601 con zona horaria explícita. No anidar el componente dentro de enlaces o botones: alternar el formato no debe abrir una comunicación ni marcarla como leída.

El contrato completo y los ejemplos están en `resources/js/components/ui/eure/README.md`. Mantener esta convención en las próximas pantallas y portaciones; no duplicar la lógica por pantalla.
