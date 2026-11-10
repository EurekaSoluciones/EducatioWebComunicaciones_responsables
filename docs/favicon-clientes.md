# Favicon por cliente

La web toma `EURE_CLIENTE_ID` a través de `config('eureka.client_id')` y busca:

`public/assets/C/{cliente_id}/institucional/favicon.png`

Usar un PNG cuadrado con el símbolo institucional, sin texto pequeño. El nombre debe ser exactamente `favicon.png`, en minúsculas, también en Linux. Para IFES Neuquén y Plottier se reutiliza el símbolo de su respectivo `Logo.png` como archivo independiente.

Si el cliente no tiene ese archivo, se usa el birrete general de Educatio en `public/assets/eure/favicon.svg`. La fecha de modificación se agrega a la URL para renovar la caché cuando cambia el archivo.

La selección se comparte mediante `resources/views/partials/favicon.blade.php` entre el documento React (incluido el login) y el layout web de AdminLTE. Las vistas móviles `appshow` y sus recursos no se modifican.

Estos archivos se publican directamente desde `public`; este cambio no requiere recompilar React. Al agregar o reemplazar un favicon, incluirlo en la entrega del cliente.
