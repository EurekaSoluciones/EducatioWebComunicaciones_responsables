# Reinscripción por alumno — estado de trabajo

Documento de continuidad de la funcionalidad de reinscripción. Actualizado el 19 de agosto de 2026.

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
- El responsable económico es obligatorio.
- `R*` representa al responsable económico.
- `TieneFamiliaDirecto` indica si un hermano, padre u otro familiar directo asiste actualmente a Cultural.
- Los datos de colegio corresponden a la institución educativa principal del alumno; Cultural funciona como acompañamiento para muchos estudiantes.
- Las ciudades y el curso nuevo se obtendrán de tablas auxiliares.
- `Condicion` es un dato interno.
- `tieneNecesidadEspecial` es actualmente un check. En el futuro debe desplegar opciones de necesidades que Cultural puede acompañar; esa tabla auxiliar todavía no existe.
- No modificar código adicional sin autorización explícita del usuario.

## Estado actual

### Ruta

Existe una ruta GET autenticada:

```php
Route::get('/alumno/{alumno}/rematriculacion', [AlumnoController::class, 'rematriculacion'])
    ->name('alumnos.rematriculacion')
    ->middleware('auth');
```

La ruta está dentro de un grupo que ya contiene `auth` y `auth.session`; el `middleware('auth')` particular es redundante, pero no genera un problema funcional.

### Controlador

`AlumnoController::rematriculacion(Alumno $alumno)`:

- Obtiene al responsable autenticado.
- Verifica que sea responsable del alumno solicitado.
- Devuelve HTTP 403 cuando el alumno no le pertenece.
- Renderiza `alumnos.rematriculaciones.form` y actualmente envía solamente `$alumno`.

### Vista

Archivo: `resources/views/alumnos/rematriculaciones/form.blade.php`.

La vista usa AdminLTE y contiene cards para:

- Datos del alumno.
- Responsable 1.
- Responsable 2 opcional y desplegable.
- Responsable económico obligatorio, incluidos sus datos de convenio.
- Colegio principal.
- Familiares que asistieron a Cultural.
- Próximo ciclo.

Paleta actual:

- Azul: alumno.
- Celeste: responsables familiares.
- Amarillo: responsable económico.
- Azul claro: colegio principal.
- Gris: familiares que asistieron a Cultural.
- Violeta: próximo ciclo.

La vista tolera que todavía no se envíen `$rematriculacion`, `$ciudades` o `$cursos`. En ese caso, los campos no precargados quedan vacíos y los selects muestran solamente `Seleccionar`.

El botón `Confirmar reinscripción` permanece deshabilitado porque todavía no existe el endpoint de guardado.

### Botón de acceso

El botón `Completar reinscripción` se muestra en la parte inferior de la columna derecha de cada card de alumno en la pantalla principal. Tiene tamaño grande y está centrado dentro de esa columna.

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
- Próximo ciclo: `CursoNuevo`, `Observaciones`.

El nombre y apellido del alumno se leen de `Alumnos` y se muestran como información de solo lectura; no existen como columnas en `AlumnosRematriculacion`.

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

1. Crear el modelo o mecanismo de consulta para `AlumnosRematriculacion`.
2. Precargar el registro correspondiente a `codAlumno`.
3. Identificar las tablas auxiliares reales de ciudades y cursos, y confirmar sus columnas de clave y descripción.
4. Enviar `$rematriculacion`, `$ciudades` y `$cursos` desde el controlador.
5. Definir qué campos son obligatorios a nivel de negocio; la tabla permite `NULL` en todos los campos funcionales.
6. Crear la ruta y el método de guardado.
7. Implementar validación del lado del servidor.
8. Definir la tabla y las opciones para necesidades especiales.
9. Acordar con los PO cómo se habilita y cierra el período.
10. Aplicar en servidor la restricción para `culturalnqn` y `culturalcentenario`.
11. Obtener el nombre, parámetros y contrato del stored procedure de estado o leyenda.
12. Definir si una reinscripción confirmada puede editarse durante el período.
13. Reemplazar el botón por la leyenda cuando el alumno ya esté reinscripto.
14. Ocultar completamente el acceso fuera del período.
15. Agregar pruebas de autorización, visibilidad, precarga, validación y guardado.

## Consideraciones de seguridad

La futura visibilidad condicional del botón no reemplaza los controles del servidor. Tanto la apertura del formulario como el guardado deberán verificar:

- Usuario autenticado.
- Pertenencia del alumno al responsable autenticado.
- Cliente permitido.
- Período habilitado.
- Estado de reinscripción compatible con la acción solicitada.
