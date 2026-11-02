# Catálogo gráfico de emojis

Los 845 PNG de `public/assets/eure/emoji/v1` se extraen, sin redibujar, de los sprites locales que ya usaba `public/vendor/tam-emoji`. Conservan sus dibujos y resolución de celda (56 × 56); el editor los muestra a 24 × 24 y el selector a 28 × 28.

Regeneración determinista, con PHP/GD ya instalado:

```sh
php scripts/extract-legacy-emojis.php
```

El script genera los PNG y `catalog.json` a partir de `Config.Emoji`, `Config.EmojiCategories` y `Config.EmojiCategorySpritesheetDimens` del archivo legacy. `labels.es.json` contiene las descripciones en español, mantenidas aparte. Los alias y Unicode originales sirven para buscar, no para dibujar.

No eliminar ni cambiar el contenido de las URLs versionadas: los mensajes guardados las referencian. Un catálogo futuro debe usar otra versión y mantener los assets anteriores. Se conserva el directorio vendor original como fuente; no se reemplaza ni transforma el HTML de las comunicaciones históricas. Los créditos/licencias del catálogo de origen siguen aplicando; esta extracción no constituye arte nuevo.

Cliente y servidor reconocen únicamente IDs presentes en el catálogo y URLs exactas de su base configurada. Normalizan tamaño, alineación y descripción; rechazan imágenes arbitrarias que se hagan pasar por emojis. No crean registros de adjuntos ni archivos en el almacenamiento compartido. Los recientes guardan hasta 24 IDs, separados por cliente/cuenta en el navegador.
