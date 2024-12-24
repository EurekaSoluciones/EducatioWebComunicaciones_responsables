<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">



<?php
// Suponiendo que $comunicacion->msg contiene el HTML
$html = $comunicacion->msg;

// Usar preg_replace_callback para reemplazar los estilos de ancho en las imágenes
$html = preg_replace_callback(
  '/<img[^>]*style=["\'][^"\'>]*width:\s*(75|50|25)%[^"\'>]*["\'][^>]*>/i',
  function ($matches) {
    // Reemplazar "width: 75%, 50%, o 25%" con "width: 100%"
    return preg_replace('/width:\s*(75|50|25)%/i', 'width: 100%', $matches[0]);
  },
  $html
);

// Mostrar el contenido procesado
echo $html;
