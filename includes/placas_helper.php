<?php

require_once __DIR__ . '/qrcode_helper.php';

class PlacasHelper
{
    /** Crea un lote independiente y retorna sus códigos de placa. */
    public static function generarPlacas(PDO $pdo, int $n): array
    {
        $lote = self::uuid();
        return self::generarLote($pdo, $n, $lote);
    }

    /** Genera códigos únicos en memoria, sin persistirlos (para la vista previa). */
    public static function generarCodigosEnMemoria(PDO $pdo, int $n): array
    {
        if ($n < 1 || $n > 500) {
            throw new InvalidArgumentException('La cantidad debe estar entre 1 y 500.');
        }

        $stmt = $pdo->prepare(
            "SELECT EXISTS(SELECT 1 FROM placas_qr WHERE codigo_placa = :codigo_qr)
              OR EXISTS(SELECT 1 FROM individuos_forestales WHERE codigo_placa = :codigo_individuo)"
        );
        $codigos = [];
        $intentos = 0;
        while (count($codigos) < $n && $intentos < $n * 100) {
            $intentos++;
            $codigo = 'EBG-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            if (isset($codigos[$codigo])) {
                continue;
            }
            $stmt->execute([':codigo_qr' => $codigo, ':codigo_individuo' => $codigo]);
            if (!(bool) $stmt->fetchColumn()) {
                $codigos[$codigo] = true;
            }
        }
        if (count($codigos) !== $n) {
            throw new RuntimeException('No fue posible reservar códigos únicos suficientes.');
        }
        return array_keys($codigos);
    }

    /** Inserta un lote completo y guarda sus SVG dentro de la transacción. */
    public static function generarLote(PDO $pdo, int $n, string $idLote, string $tamano = 'mediana', ?array $codigosVista = null): array
    {
        if (!in_array($tamano, ['pequena', 'mediana', 'grande'], true)) {
            throw new InvalidArgumentException('Tamaño de placa no válido.');
        }
        $codigos = $codigosVista ?? self::generarCodigosEnMemoria($pdo, $n);
        if (count($codigos) !== $n) {
            throw new InvalidArgumentException('La vista previa no coincide con la cantidad solicitada.');
        }
        $insert = $pdo->prepare(
            "INSERT INTO placas_qr (id_placa, id_lote, codigo_placa, estado, fecha_generacion, tamano)
             VALUES (:id, :lote, :codigo, 'pendiente', NOW(), :tamano)"
        );
        $archivos = [];
        $iniciadaAqui = !$pdo->inTransaction();
        if ($iniciadaAqui) {
            $pdo->beginTransaction();
        }
        try {
            foreach ($codigos as $codigo) {
                $ruta = QRCodeGenerator::guardarQR($codigo, QRCodeGenerator::urlFicha($codigo));
                $archivos[] = ROOT_PATH . $ruta;
                $insert->execute([
                    ':id' => self::uuid(), ':lote' => $idLote, ':codigo' => $codigo, ':tamano' => $tamano,
                ]);
            }
            if ($iniciadaAqui) {
                $pdo->commit();
            }
            return $codigos;
        } catch (Throwable $e) {
            if ($iniciadaAqui && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($archivos as $archivo) {
                if (is_file($archivo)) {
                    @unlink($archivo);
                }
            }
            throw $e;
        }
    }

    public static function marcarAsignada(PDO $pdo, string $codigo, int $idIndividuo): bool
    {
        $stmt = $pdo->prepare(
            "UPDATE placas_qr SET estado = 'asignada', asignado_a = :individuo, fecha_asignacion = NOW()
             WHERE codigo_placa = :codigo AND estado = 'pendiente'"
        );
        $stmt->execute([':individuo' => $idIndividuo, ':codigo' => $codigo]);
        return $stmt->rowCount() === 1;
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
