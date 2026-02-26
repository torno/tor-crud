<?php

if (!function_exists('mostrar_imagen')) {
    /**
     * Genera y devuelve la URL de una miniatura de imagen.
     * Si no existe el archivo original, retorna null.
     * 
     * @param string $carpeta Ruta relativa de la carpeta donde está la imagen (ej: 'uploads/productos')
     * @param string|null $nombre_archivo Nombre del archivo original (puede ser null o vacío)
     * @param int $ancho Ancho deseado de la miniatura
     * @param int $alto Alto deseado de la miniatura
     * @return string|null URL de la miniatura o null si no hay archivo
     */
    function mostrar_imagen($carpeta, $nombre_archivo, $ancho = 500, $alto = 300)
    {
        // Si no hay archivo, retornar null
        if (empty($nombre_archivo)) {
            return null;
        }

        // Rutas
        $ruta_original = FCPATH . $carpeta . '/' . $nombre_archivo;
        $nombre_sin_extension = pathinfo($nombre_archivo, PATHINFO_FILENAME);
        
        // Si el archivo original no existe, retornar null
        if (!file_exists($ruta_original)) {
            return null;
        }
        
        // La miniatura siempre será JPG
        $nombre_miniatura = $nombre_sin_extension . "_{$ancho}x{$alto}.jpg";
        $ruta_miniatura = FCPATH . 'uploads/thumbs/' . $nombre_miniatura;
        $url_miniatura = base_url('uploads/thumbs/' . $nombre_miniatura);

        // Verificar si ya existe la miniatura
        if (file_exists($ruta_miniatura)) {
            return $url_miniatura;
        }

        // Crear directorio de miniaturas si no existe
        $directorio_thumbs = FCPATH . 'uploads/thumbs/';
        if (!is_dir($directorio_thumbs)) {
            mkdir($directorio_thumbs, 0777, true);
        }

        // Cargar librería de imagen de CodeIgniter
        $image = \Config\Services::image();

        try {
            // Crear miniatura (centrada y recortada)
            $image->withFile($ruta_original)
                ->fit($ancho, $alto, 'center')
                ->save($ruta_miniatura);
            
            return $url_miniatura;
        } catch (\Exception $e) {
            // Si falla la generación, retornar null
            return null;
        }
    }
}