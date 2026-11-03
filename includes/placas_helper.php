<?php

require_once __DIR__ . '/funciones.php';
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

        // Serie numérica consecutiva compartida con los individuos registrados.
        return reservar_codigos_placa($pdo, $n);
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

    /**
     * Elimina un lote completo de placas vacías y sus archivos SVG.
     * Por seguridad no permite borrar lotes con placas ya asignadas a individuos.
     *
     * @return int Cantidad de placas eliminadas.
     */
    public static function eliminarLote(PDO $pdo, string $idLote): int
    {
        $idLote = trim($idLote);
        if ($idLote === '') {
            throw new RuntimeException('El lote indicado no es válido.');
        }

        $stmt = $pdo->prepare("SELECT codigo_placa, estado FROM `placas_qr` WHERE id_lote = :lote");
        $stmt->execute([':lote' => $idLote]);
        $placas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$placas) {
            throw new RuntimeException('El lote no existe o ya fue eliminado.');
        }

        $asignadas = 0;
        foreach ($placas as $placa) {
            if (($placa['estado'] ?? '') === 'asignada') {
                $asignadas++;
            }
        }
        if ($asignadas > 0) {
            throw new RuntimeException(
                'Este lote tiene ' . $asignadas . ' placa(s) asignada(s) a individuos y no se puede eliminar. '
                . 'Solo se pueden borrar lotes con placas pendientes.'
            );
        }

        $iniciadaAqui = !$pdo->inTransaction();
        if ($iniciadaAqui) {
            $pdo->beginTransaction();
        }
        try {
            $del = $pdo->prepare("DELETE FROM `placas_qr` WHERE id_lote = :lote");
            $del->execute([':lote' => $idLote]);
            if ($iniciadaAqui) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($iniciadaAqui && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        // Los archivos se borran después de confirmar la transacción.
        foreach ($placas as $placa) {
            $archivo = ROOT_PATH . 'assets/qr/qr_'
                . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $placa['codigo_placa']) . '.svg';
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }

        return count($placas);
    }

    public static function marcarAsignada(PDO $pdo, string $codigo, string $idIndividuo): bool
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
