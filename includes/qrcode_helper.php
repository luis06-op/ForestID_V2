<?php
/**
 * ForestID / ForestSRD - Generador de Códigos QR Reales (SVG)
 * Empresa: Emincut S.A.S. | El Bagre, Antioquia
 *
 * Usa la librería MIT chillerlan/php-qrcode (incluida en vendor/) para generar
 * códigos QR conformes al estándar ISO/IEC 18004, con corrección de errores,
 * información de formato/versión y enmascaramiento. Los SVG resultantes son
 * legibles por cualquier lector (cámaras, html5-qrcode, zbar, etc.).
 */

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

require_once __DIR__ . '/../config/db.php';

class QRCodeGenerator {

    private const PATRON_COLOR = '/^#[0-9a-fA-F]{3,8}$/';

    /**
     * Valida un color CSS hexadecimal para evitar inyección en el SVG.
     */
    private static function normalizarColor(string $color, string $porDefecto): string {
        $color = trim($color);
        return preg_match(self::PATRON_COLOR, $color) === 1 ? $color : $porDefecto;
    }

    /**
     * Genera un código QR real y lo devuelve como SVG.
     *
     * @throws RuntimeException si falta la dependencia o no se puede generar.
     */
    public static function generarSvg(string $texto, int $tamanio = 260, string $colorOscuro = '#1B5E20', string $colorClaro = '#FFFFFF'): string {
        $autoload = ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Falta vendor/autoload.php: ejecute "composer install" para habilitar la generación de QR.');
        }
        require_once $autoload;

        if ($texto === '') {
            throw new InvalidArgumentException('El contenido del código QR no puede estar vacío.');
        }

        // Límites razonables para impresión (evita SVG gigantes por error).
        $tamanio = max(80, min($tamanio, 2000));
        $oscuro = self::normalizarColor($colorOscuro, '#1B5E20');
        $claro  = self::normalizarColor($colorClaro, '#FFFFFF');

        $options = new QROptions([
            'outputType'           => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64'         => false, // devolver el SVG, no un data URI
            'eccLevel'             => QRCode::ECC_M,
            'addQuietzone'         => true,
            'quietzoneSize'        => 2,
            'scale'                => 10,
            'drawLightModules'     => true,
            'svgUseFillAttributes' => true,
        ]);

        $svg = (new QRCode($options))->render($texto);

        // Tamaño explícito y bordes nítidos al imprimir.
        $svg = preg_replace(
            '/<svg\s+/',
            '<svg width="' . $tamanio . '" height="' . $tamanio . '" shape-rendering="crispEdges" ',
            $svg,
            1
        );

        // Colores institucionales (la librería usa #000/#fff por defecto).
        $svg = str_replace(['fill="#000"', 'fill="#000000"'], 'fill="' . $oscuro . '"', $svg);
        $svg = str_replace(['fill="#fff"', 'fill="#ffffff"', 'fill="#FFFFFF"'], 'fill="' . $claro . '"', $svg);

        return $svg;
    }

    /**
     * Guarda el QR en assets/qr y retorna la ruta relativa para la BD.
     *
     * @throws RuntimeException si no se puede escribir el archivo.
     */
    public static function guardarQR(string $codigoPlaca, string $urlDestino): string {
        $nombreArchivo = 'qr_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $codigoPlaca) . '.svg';
        $directorioQR = ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR . 'qr';

        if (!is_dir($directorioQR) && !mkdir($directorioQR, 0755, true) && !is_dir($directorioQR)) {
            throw new RuntimeException('No se pudo crear el directorio de códigos QR.');
        }

        $rutaCompleta = $directorioQR . DIRECTORY_SEPARATOR . $nombreArchivo;
        $svgContent = self::generarSvg($urlDestino);

        if (file_put_contents($rutaCompleta, $svgContent, LOCK_EX) === false) {
            throw new RuntimeException('No se pudo guardar el código QR en ' . $rutaCompleta);
        }

        return 'assets/qr/' . $nombreArchivo;
    }
}
