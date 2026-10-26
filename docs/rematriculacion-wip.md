# Reinscripción por alumno — estado de trabajo

Documento de continuidad de la funcionalidad de reinscripción. Actualizado el 21 de agosto de 2026.

## Objetivo

Permitir que un responsable complete una reinscripción independiente por cada alumno a su cargo. El formulario se precargará con datos de `dbo.AlumnosRematriculacion` y, cuando se conecte el flujo completo, mostrará una leyenda obtenida mediante un stored procedure después de realizar la reinscripción.

La necesidad original corresponde a los clientes `culturalnqn` y `culturalcentenario`. La vista se mantuvo genérica para poder reutilizarla. La restricción por cliente y período todavía no está implementada.

## Decisiones confirmadas

- La reinscripción es por alumno, no por familia.
- Una familia con varios hijos debe completar el proceso una vez por cada hijo.
- El acceso principal está en la card de cada alumno en la pantalla principal.
- Por ahora, el botón se muestra sin condiciones para facilitar la revisión visual.
- Más adelante, el botón deberá depender del cliente, del período habilitado y del estado individual del alumno.
- Una vez realizada la reinscripción, el botón debería reemplazarse por una leyenda proveniente de un stored procedure.
- Fuera del período, el lugar ocupado por el botón o la leyenda debe quedar vacío.
- Los atributos `name` de los inputs conservan exactamente los nombres de las columnas de la tabla.
- Los datos funcionales son inicialmente editables. Los campos internos y de auditoría no se muestran.
- El responsable 2 es opcional.
- Si se completa cualquier dato de Responsable 2, `R2Nombre`, `R2Vinculo` y `R2DNI` pasan a ser obligatorios; si toda la ficha está vacía, puede omitirse completamente.
- En Responsable 1 son obligatorios todos los campos excepto `R1Domicilio` y `R1Telefono`. La validación se realiza en el navegador y en el servidor, y los campos faltantes se marcan en rojo al intentar enviar.
- Si se intenta confirmar con campos obligatorios incompletos, el formulario desplaza la vista al aviso superior `Faltan completar campos`; cada faltante conserva borde y leyenda de validación en rojo.
- Los campos válidos no muestran borde ni tilde verde después del intento de envío; solamente se resaltan los inválidos.
- En el responsable económico son obligatorios todos los campos excepto `RDomicilio` y `RTelefono`, incluidos el convenio y el nombre de su titular.
- `R*` representa al responsable económico.
- `TieneFamiliaDirecto` indica si un hermano, padre u otro familiar directo asiste actualmente a Cultural.
- Los datos de colegio corresponden a la institución educativa principal del alumno; Cultural funciona como acompañamiento para muchos estudiantes.
- Las ciudades y el curso nuevo se obtendrán de tablas auxiliares.
- `Condicion` es un dato interno.
- `tieneNecesidadEspecial` es actualmente un check. Al marcarlo deberá mostrarse un campo de texto multilínea, similar a observaciones, con la etiqueta `Especificar cuál`, en lugar de un select de necesidades.
- Debe agregarse, independientemente de `tieneNecesidadEspecial`, otro check con la etiqueta `El estudiante cuenta con MAI`.
- Antes de implementar el guardado de estos dos datos se deben confirmar con el equipo los nombres de las columnas y parámetros correspondientes en `SP_WEB_DatosAlumno_RematriculacionDatos`.
- No modificar código adicional sin autorización explícita del usuario.

## Estado actual

### Ruta

Existen rutas GET y POST autenticadas:

```php
Route::get('/alumno/{alumno}/rematriculacion', [AlumnoController::class, 'rematriculacion'])
    ->name('alumnos.rematriculacion')
    ->middleware('auth');

Route::post('/alumno/{alumno}/rematriculacion', [AlumnoController::class, 'guardarRematriculacion'])
    ->name('alumnos.rematriculacion.guardar')
    ->middleware('auth');
```

La ruta está dentro de un grupo que ya contiene `auth` y `auth.session`; el `middleware('auth')` particular es redundante, pero no genera un problema funcional.

### Controlador

`AlumnoController::rematriculacion(Alumno $alumno)`:

- Obtiene al responsable autenticado.
- Verifica que sea responsable del alumno solicitado.
- Devuelve HTTP 403 cuando el alumno no le pertenece.
- Ejecuta `SP_WEB_DatosAlumno_Rematriculacion` con el código del alumno como único parámetro.
- Usa el primer registro devuelto para precargar el formulario; si no hay resultados, la vista conserva los campos vacíos.
- Ejecuta `dbo.SP_WEB_DatosAlumno_RematriculacionCursos` con `@codAlumno` para obtener los cursos disponibles.
- Ejecuta `dbo.SP_WEB_DatosAlumno_RematriculacionConvenios` para obtener los convenios disponibles.
- Consulta la tabla `Ciudad` para obtener las ciudades disponibles.
- Renderiza `alumnos.rematriculaciones.form` y envía `$alumno`, `$rematriculacion`, `$cursos`, `$convenios` y `$ciudades`.

El selector de convenio funciona como el de ciudad: envía la clave (`CodConvenio`, `Codigo` o `id`) y muestra la descripción (`Convenio`, `Descripcion` o `Nombre`). La validación espera una clave entera y no limita la longitud de la descripción mostrada.

`AlumnoController::guardarRematriculacion(Request $request, Alumno $alumno)`:

- Vuelve a verificar que el alumno pertenezca al responsable autenticado.
- Valida tipos y longitudes según el contrato del procedimiento.
- Exige la aceptación del reglamento antes de enviar el formulario.
- Ejecuta `dbo.SP_WEB_DatosAlumno_RematriculacionDatos` con `@codAlumno` y todos los campos funcionales. La llamada usa `SET NOCOUNT ON` y recorre los conjuntos de resultados mediante PDO porque el procedimiento emite resultados intermedios sin columnas que `DB::select()` no puede procesar con el driver SQL Server.
- Obtiene del primer registro devuelto la columna `mensaje`, `leyenda`, `resultado` o `descripcion`; si el procedimiento usa otro nombre, toma el primer valor escalar no vacío.
- Redirige a la pantalla principal y reemplaza temporalmente el botón del alumno por el mensaje obtenido.

### Vista

Archivo: `resources/views/alumnos/rematriculaciones/form.blade.php`.

La vista usa AdminLTE y contiene cards para:

- Datos del alumno.
- Responsable 1.
- Responsable 2 opcional y desplegable.
- Responsable económico obligatorio, incluidos sus datos de convenio.
- Colegio principal.
- Familiares que asistieron a Cultural.
- Próximo curso.

Paleta actual:

- Azul: alumno.
- Celeste: responsables familiares.
- Amarillo: responsable económico.
- Azul claro: colegio principal.
- Gris: familiares que asistieron a Cultural.
- Violeta: próximo curso.

La vista tolera que todavía no se envíen `$rematriculacion`, `$ciudades` o `$cursos`. En ese caso, los campos no precargados quedan vacíos y los selects muestran solamente `Seleccionar`.

Los nombres de columnas recibidos desde SQL Server se normalizan para ignorar mayúsculas, espacios y guiones bajos. Esto permite precargar las ciudades aunque el procedimiento use variantes como `R1CodCiudad` o `R1Cod_Ciudad`. La selección también tolera espacios en los valores y conserva `old()` después de una validación fallida.

Nombre, apellido y DNI del alumno son de solo lectura. Al posar el cursor o enfocar cualquiera de ellos aparece el mensaje `Para cambiar este dato, acercarse a Secretaría.`.

El botón `Confirmar reinscripción` envía el formulario al endpoint de guardado. Después de un guardado exitoso vuelve a la pantalla principal.

### Botón de acceso

El botón `Completar reinscripción` se muestra en la parte inferior de la columna derecha de cada card de alumno en la pantalla principal. Tiene tamaño grande y está centrado dentro de esa columna. Inmediatamente después de guardar, el botón del alumno correspondiente se reemplaza por una alerta con la leyenda devuelta por el procedimiento.

El parcial de la card se comparte con otras pantallas, por lo que la visualización se controla mediante `mostrarBotonRematriculacion`. `home/index.blade.php` envía esta marca como `true`; la ficha individual no lo hace y, por lo tanto, no muestra el botón.

## Mapeo de `AlumnosRematriculacion`

### Campos visibles y editables

- Alumno: `Domicilio`, `Telefono`, `email`, `CodCiudad`, `nopermitefoto`, `tieneNecesidadEspecial`.
- Responsable 1: `R1Nombre`, `R1Apellido`, `R1DNI`, `R1Domicilio`, `R1Telefono`, `R1Celular`, `R1Email`, `R1CodCiudad`, `R1Ocupacion`, `R1Vinculo`.
- Responsable 2: `R2Nombre`, `R2Apellido`, `R2DNI`, `R2Domicilio`, `R2Telefono`, `R2Celular`, `R2Email`, `R2CodCiudad`, `R2Ocupacion`, `R2Vinculo`.
- Responsable económico: `RNombre`, `RApellido`, `RDNI`, `RDomicilio`, `RTelefono`, `RCelular`, `REmail`, `RCodCiudad`.
- Convenio: `Convenio`, `NombreTitularConvenio`.
- Colegio principal: `Colegio`, `GradoColegio`, `TurnoColegio`.
- Familiares que asisten actualmente: `TieneFamiliaDirecto`, `TieneFamiliaDirectoQuienes`.
- Próximo curso: `CursoNuevo`, `Observaciones`.

El nombre, apellido y DNI del alumno se leen de `Alumnos` y se muestran como información de solo lectura; no existen como columnas en `AlumnosRematriculacion`.

`nopermitefoto` se muestra con una etiqueta no invertida: `No autorizo el uso de fotografías del alumno`.

### Campos internos no visibles

- `Codigo`
- `codAlumno`
- `Condicion`
- `fecha_baja`
- `motivobaja`
- `cod_grupoActual`
- `fecha_alta`
- `fecha_modif`
- `usuarioModif`

## Archivos involucrados actualmente

- `app/Http/Controllers/AlumnoController.php`
- `routes/web.php`
- `resources/views/alumnos/rematriculaciones/form.blade.php`
- `resources/views/alumnos/partials/alumnoCard.blade.php`
- `resources/views/home/index.blade.php`

## Pendientes funcionales

1. Confirmar las columnas de clave y descripción devueltas por `SP_WEB_DatosAlumno_RematriculacionCursos`.
2. Continuar definiendo los campos obligatorios del resto del formulario. Ya quedaron implementadas las reglas de Responsable 1, la obligatoriedad condicional de Responsable 2 y las reglas del responsable económico descritas arriba.
3. Confirmar con el equipo los nombres de columna y parámetro para el detalle `Especificar cuál` de la necesidad especial y para el check `El estudiante cuenta con MAI`; luego incorporarlos al formulario, la precarga, la validación y `SP_WEB_DatosAlumno_RematriculacionDatos`.
4. Acordar con los PO cómo se habilita y cierra el período.
5. Aplicar en servidor la restricción para `culturalnqn` y `culturalcentenario`.
6. Obtener el nombre, parámetros y contrato del stored procedure de estado o leyenda para conservar el estado al recargar la página o iniciar una sesión nueva. Actualmente se muestra el resultado del procedimiento de guardado mediante la sesión flash.
7. Definir si una reinscripción confirmada puede editarse durante el período.
8. Reemplazar el botón por la leyenda cuando el alumno ya esté reinscripto.
9. Ocultar completamente el acceso fuera del período.
10. Agregar pruebas de autorización, visibilidad, precarga, validación y guardado.

## Punto de continuidad al 21 de agosto de 2026

- Los cambios de reinscripción permanecen sin commit en el árbol de trabajo.
- `AlumnoController.php` pasa la verificación de sintaxis con `php -l`.
- Las plantillas Blade compilan correctamente con `php artisan view:cache`.
- Las rutas GET y POST de reinscripción aparecen correctamente en `php artisan route:list`.
- No existen todavía pruebas automatizadas específicas de reinscripción.
- El próximo bloque acordado es continuar con la definición de campos obligatorios del formulario.

## Consideraciones de seguridad

La futura visibilidad condicional del botón no reemplaza los controles del servidor. Tanto la apertura del formulario como el guardado deberán verificar:

- Usuario autenticado.
- Pertenencia del alumno al responsable autenticado.
- Cliente permitido.
- Período habilitado.
- Estado de reinscripción compatible con la acción solicitada.
