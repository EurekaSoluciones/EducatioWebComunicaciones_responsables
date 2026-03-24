<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">



<?php
$html = $cartelera->cartelera;

$html = preg_replace_callback(
  '/<img[^>]*style=["\'][^"\'>]*width:\s*([0-9]+)px[^"\'>]*["\'][^>]*>/i', // Buscar imágenes con width en píxeles
  function ($matches) {
    // Obtener el ancho en píxeles
    $width = $matches[1];

    // Solo reemplazar si el ancho es mayor a 400px
    if ($width > 360) {
      // Reemplazar width en píxeles por width: 100% si es mayor a 400px
      return preg_replace('/width:\s*[0-9]+px/i', 'width: 100%', $matches[0]);
    }

    // Si no es mayor a 400px, dejar la imagen sin cambios
    return $matches[0];
  },
  $html
);

// También reemplazar valores de ancho en porcentaje (75%, 50%, 25%) solo si es mayor a 400px
$html = preg_replace_callback(
  '/<img[^>]*style=["\'][^"\'>]*width:\s*(75|50|25)%[^"\'>]*["\'][^>]*>/i',
  function ($matches) {
    // Reemplazar "width: 75%, 50%, o 25%" con "width: 100%" solo si es mayor a 400px
    return preg_replace('/width:\s*(75|50|25)%/i', 'width: 100%', $matches[0]);
  },
  $html
);

// Mostrar el contenido procesado
echo $html;
