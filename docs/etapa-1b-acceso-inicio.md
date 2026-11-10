# Etapa 1B: acceso e inicio de Familias

Actualización del 2 de noviembre: Home2 fue descartada por el usuario y retirada del código y las rutas, junto con los accesos de comparación. El inicio vigente es `/home` (también `/`). Las menciones a Home2 más abajo son historial de las pruebas de diseño; las demos UI siguen disponibles.

Implementación preparada el 28 de octubre de 2026, después de que el usuario eligiera la alternativa y autorizara avanzar. Su devolución adicional fue mejorar especialmente el menú izquierdo de AdminLTE.

## Qué se puede ver

Ficha del alumno migrada a React en `/alumno/{alumno}`: datos escolares y foto, responsables, cinco comunicaciones más recientes del destinatario actual y legajo. Mantiene foto/recorte y carga de adjuntos en Blade por acuerdo con el usuario. El menú resalta la ficha y su selector navega entre alumnos. Se conserva el acceso directo de responsables vinculados a alumnos de baja, como en el controlador anterior; el home sigue mostrando solo activos. La consulta no crea usuarios/fotos ni marca lecturas. Verificación de esta entrega: TypeScript, Pint y 44 pruebas con 391 comprobaciones aprobadas. Build de producción bloqueado por `spawn EPERM`; pendiente ejecutarlo desde la terminal del usuario y revisar visualmente la ficha con datos reales.

1366×768 se toma como referencia mínima de escritorio. En `/home`, las tarjetas de hasta 540 px agrupan ficha, documentación, inasistencias, cuenta y pagos en «Más opciones», un desplegable nativo operable con teclado y con los mismos iconos y enlaces. En tarjetas mayores siguen como botones visibles. El corte responde al ancho real de la tarjeta; comunicaciones permanece visible.

Corrección tras revisar la captura: en `/home` las comunicaciones reemplazan los accesos secundarios al costado de la foto y los datos. Ficha, documentación, inasistencias, cuenta y pagos vuelven debajo, en una fila que se adapta al espacio. Se conserva el aumento de foto del 30%.

Distribución posterior en `/home`: accesos secundarios alineados arriba, a la derecha de foto y datos; comunicaciones debajo. Foto del alumno ampliada de 55 a 71,5 px (+30%). En tarjetas estrechas los accesos pasan debajo de los datos en dos columnas para conservar legibilidad.

Nuevo ajuste solicitado para `/home`: dos alumnos por fila cuando el área de contenido alcanza 790 px, una columna en espacios menores y cartelera debajo. Curso, división, turno y acción de foto se separan en líneas. Las reglas se limitan a `family-home-current`, conservando la variante Home2.

Referencia institucional aportada por el usuario: rojo oscuro y gris. Primer ajuste aplicado solo en `/home` (también `/`): la barra del breadcrumb y la campana pasa de rosa a gris suave, mediante el color `toolbar` de `resources/themes/isi.json`. Se conserva la franja superior bordó; `/home2` mantiene su encabezado anterior para comparación.

Devolución sobre la tarjeta de `/home2`: foto del alumno un 30% mayor que la variante de 83 px (ahora 108 px). Curso, división, turno y enlace de foto se muestran en líneas separadas. El ajuste queda aislado en la variante; no cambia las fotos de responsables ni el home original.

Revisión del trabajo del otro agente: `/home2` ya dispone de una acción del controlador que comparte la preparación de datos y las reglas del inicio, además de enlaces de comparación y pruebas de acceso. Se sincronizó la eliminación de las tres tarjetas de resumen; logo institucional y lateral oscuro ya se comparten. El home original se conserva. Propuestas pendientes de evaluar: cabecera de alumno con foto y nombre juntos, mayor énfasis en comunicaciones pendientes y accesos secundarios más compactos. No se aplicaron todavía esas propuestas.

Devolución posterior: se retiraron las tres tarjetas superiores de resumen (alumnos a cargo, comunicaciones sin leer y respuestas sin leer). El inicio conserva las tarjetas de alumnos, la cartelera y los pendientes en la campana y en cada alumno.

- `/login`: acceso React con logo y fondo institucional existentes, tema del cliente, usuario/DNI, contraseña con visibilidad opcional, errores y estado de envío.
- `/` y `/home`: inicio React con el layout elegido, responsable y fotos, tarjetas de todos los alumnos activos vinculados, comunicaciones y respuestas pendientes, responsables de cada alumno, enlaces a documentos, asistencia, cuenta y pagos, cartelera y estado de reinscripción.
- Las dos demos siguen disponibles. La alternativa y el inicio comparten `EurekaFrame`, que conserva el lateral de 256 px, la franja superior y el encabezado, y proporciona un menú modal accesible en celular.

## Menú nuevo

Nueva prueba de paleta: el usuario considera excesivo el contraste del lateral carbón. Se propone gris medio cálido (`#595756`), texto blanco suave y pie del mismo tono, conservando el bordó institucional en la opción activa. Se modifica el tema compartido, por lo que también se refleja en Home2 y las demos. Pendiente de aprobación visual.

Prueba visual solicitada el 29 de octubre: el usuario considera el home demasiado apagado y pastel. Se cambia el tema ISI a un lateral carbón con texto blanco, opción activa bordó y pie más oscuro. Los colores permanecen centralizados en `resources/themes/isi.json`; también se reflejan en las demos que comparten ese tema. Pendiente de su evaluación visual.

Devolución del 29 de octubre: conservar arriba a la izquierda el logo y nombre del colegio, como en el menú anterior. El inicio React usa `public/assets/C/{cliente}/institucional/Logo.png` y `EURE_CLIENTE_LEYENDA`, tanto en escritorio como en el menú móvil, con la leyenda «Módulo Familias». Si falta el archivo, conserva el nombre sin mostrar una imagen rota. Las demos se mantienen sin cambios.

El menú anterior repetía un árbol por alumno y requería hasta tres niveles para acceder a las comunicaciones. El nuevo inicio tiene:

1. Inicio familiar.
2. Selector de alumno con foto, nombre, curso y pendientes por alumno.
3. Un solo grupo de accesos directos del alumno seleccionado: recibidas, enviadas/respuestas, ficha, documentación, inasistencias, cuenta y pagos. Se conservan las leyendas institucionales de los accesos económicos.
4. Carteleras de la institución.
5. Perfil del responsable, cambio de contraseña y cierre de sesión por POST.

La selección cambia los enlaces del menú y destaca la tarjeta correspondiente, pero el inicio mantiene visibles a todos los hijos. Se recuerda en `sessionStorage`, con una clave por institución y usuario, y se valida contra los alumnos del payload actual. Si el navegador bloquea el almacenamiento, el selector funciona igualmente. La selección es una comodidad de navegación; no concede permisos ni consulta alumnos ajenos.

## Datos y comportamiento

Corrección posterior sobre EureUI: el usuario observó que el login no estaba usando los componentes de Pranzo. Los controles iniciales de `components/eureka` eran implementaciones propias simplificadas, pese a su nombre. Se sustituyeron en el login por `EureInputGroup`, `EureInput` y `EureButtonPrimary` portados desde los archivos reales de Pranzo, más una composición de contraseña sobre el mismo campo. Las reglas CSS equivalen a las utilidades de la referencia y se conectan al tema del cliente; la página ya no define un campo de contraseña independiente. La procedencia, adaptaciones y límites están en `resources/js/components/ui/eure/README.md`. Esto no convierte automáticamente el resto de los componentes provisionales en EureUI: su consolidación sigue pendiente y debe hacerse explícitamente al continuar.

`FamilyPortal` prepara un payload explícito. Usa la relación existente de alumnos activos por Responsable1/Responsable2, lee fotos de `web_alumnos` y `web_users` sin invocar getters que crean registros, y conserva iniciales cuando falta la imagen. No serializa modelos completos ni contraseñas.

La cartelera general actualizada sigue provocando la redirección previa al inicio. Su pantalla completa y su registro de visualización continúan en Blade. La vista previa del inicio muestra el HTML histórico en un iframe con sandbox y política de contenido; no ejecuta scripts ni habilita formularios o navegación superior. El enlace exterior permite abrir la cartelera completa.

`GET /familia/notificaciones` entrega datos estructurados para React, con autenticación de sesión y sin caché. El endpoint anterior de AdminLTE sigue intacto. La campana nueva suma comunicaciones recibidas únicas y respuestas sin leer; el desglose por alumno conserva los scopes existentes. Una misma comunicación para hermanos cuenta una vez en el total familiar. Se actualiza cada 30 segundos mientras la página está visible y al recuperar foco. Ante errores mantiene el último dato e indica que no se pudo actualizar; una sesión vencida vuelve al login. Abrir la campana no marca lecturas y no existe una acción ficticia de «marcar todas».

El login conserva la autenticación de responsables y el destino solicitado. Los formularios de acceso y salida hacen navegación completa para convivir con destinos Blade. Al fallar la autenticación solo se conserva el usuario, no la contraseña. El cierre web invalida la sesión y regenera el token CSRF. No se modificaron los endpoints de autenticación móvil.

Los módulos pendientes se abren con enlaces HTML normales. Allí todavía se verá el layout de AdminLTE hasta migrarlos. Los Blade anteriores de login e inicio se conservan temporalmente para comparar mientras se valida esta entrega; los parciales compartidos siguen usados por otros módulos.

## Verificación

- TypeScript correcto.
- Suite completa: 40 pruebas y 320 comprobaciones aprobadas.
- Pruebas nuevas con esquema legado mínimo en SQLite en memoria: alumnos propios/ajenos, bajas, fotos ausentes, ausencia de escrituras al consultar el inicio, contadores por responsable, deduplicación de hermanos, lectura sin efectos secundarios, cartelera pendiente/vista, familia sin alumnos, responsable ausente, tipo de usuario incorrecto y acceso anónimo.
- Pruebas de autenticación: credenciales válidas/incorrectas, CSRF, destino solicitado, retorno de errores sin contraseña y cierre de sesión. Prueba de continuidad de una pantalla Blade y pruebas móviles existentes aprobadas.
- Sintaxis PHP y Pint en los archivos nuevos/refactorizados correctos.
- HTTP local: login 200 con `auth/Login` y recursos institucionales; Vite devuelve 200 al transformar las nuevas páginas, layout, componentes y CSS.
- `npm run build` ya se completa correctamente y genera los recursos de producción con el último ajuste del inicio. El error previo de `spawn EPERM` no se repitió.
- No se verificó el renderizado visual ni el recorrido autenticado real contra SQL Server. El intento de control de aplicaciones fue rechazado porque Google Chrome no estaba autorizado para esa herramienta. No se probaron acciones con efectos sobre la base real o el concentrador de adjuntos.

## Revisión con el usuario

Con `composer run dev` activo, abrir `http://localhost:8003/login` e ingresar con la cuenta habitual. Revisar el cambio de alumno y sus enlaces, fotos, contador y cartelera, tanto en escritorio como en celular. Si corresponde una cartelera obligatoria, se mostrará antes del inicio, como hasta ahora. Comprobar también un acceso incorrecto y la salida.

Después del feedback de esta entrega, continuar con el editor y el tratamiento de imágenes previstos en la etapa 2.

## Aviso discreto de saldo vencido — 9 de noviembre de 2026

El acceso a cuenta corriente de cada alumno en la home muestra únicamente un triángulo amarillo cuando su saldo vencido neto es positivo, sin leyenda adicional visible. El tooltip «Hay saldo vencido en esta cuenta. Entrá para consultar el detalle.» aparece al pasar el mouse o con foco de teclado y se cierra con Escape. Se conserva la navegación del enlace, también dentro de «Más opciones» en pantallas pequeñas.

La home consulta en segundo plano `GET /familia/saldos-vencidos`, con sesión de responsable y respuesta sin caché. Solo devuelve identificador y presencia de saldo vencido para alumnos activos vinculados. Reutiliza `CC_Obtener`, incluidas las reglas de fin de mes y compensación de saldos; no expone importes ni modifica datos. Si la consulta falla, no se inventa un aviso de deuda ni un estado «al día». No se cambiaron contratos móviles ni vistas `appshow`.

Verificación aislada: 6 pruebas PHP y 95 comprobaciones aprobadas, TypeScript y Pint aprobados. El build se intentó pero el entorno bloqueó un proceso con `spawn EPERM`; queda ejecutarlo desde la terminal del usuario. No se validó visualmente en navegador ni contra SQL Server real.
