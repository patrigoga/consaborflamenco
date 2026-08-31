<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Auditoria de las operaciones importantes: alta, edicion y baja de eventos,
 * promociones, consumo de puntos y activacion de enlaces sociales.
 *
 * La tabla `registro_actividad` solo se anade: no se actualiza ni se borra. Es
 * el respaldo de "que paso aqui" cuando un artista reclama unos puntos.
 *
 * Nunca lanza excepcion: un fallo del log no puede tumbar la operacion que se
 * estaba auditando. Si algo va mal se escribe en error_log y se sigue.
 */
function csf_log_actividad(
    ?PDO $pdo,
    ?int $usuarioId,
    string $entidad,
    ?int $entidadId,
    string $accion,
    array $detalle = []
): void {
    if (!$pdo) {
        return;
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO registro_actividad (usuario_id, entidad, entidad_id, accion, detalle_json, ip)
             VALUES (:usuario_id, :entidad, :entidad_id, :accion, :detalle_json, :ip)'
        );
        $statement->execute([
            'usuario_id' => $usuarioId !== null && $usuarioId > 0 ? $usuarioId : null,
            'entidad' => mb_substr($entidad, 0, 40),
            'entidad_id' => $entidadId !== null && $entidadId > 0 ? $entidadId : null,
            'accion' => mb_substr($accion, 0, 40),
            'detalle_json' => $detalle === [] ? null : json_encode($detalle, JSON_UNESCAPED_UNICODE),
            'ip' => csf_log_client_ip(),
        ]);
    } catch (Throwable $exception) {
        error_log('[registro_actividad] ' . $exception->getMessage());
    }
}

/**
 * IP del cliente, recortada al ancho de la columna. No se consultan cabeceras
 * de proxy (X-Forwarded-For): son falsificables y aqui solo sirven de pista.
 */
function csf_log_client_ip(): ?string
{
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

    return $ip !== '' ? mb_substr($ip, 0, 45) : null;
}

/**
 * Ultimas entradas de auditoria de una entidad concreta. Lo usa el panel de
 * administracion; no se expone en el area publica.
 *
 * @return array<int, array<string, mixed>>
 */
function csf_log_historial(PDO $pdo, string $entidad, int $entidadId, int $limite = 20): array
{
    $statement = $pdo->prepare(
        'SELECT r.*, u.nombre AS usuario_nombre
         FROM registro_actividad r
         LEFT JOIN usuarios u ON u.id = r.usuario_id
         WHERE r.entidad = :entidad AND r.entidad_id = :entidad_id
         ORDER BY r.created_at DESC, r.id DESC
         LIMIT ' . max(1, min(100, $limite))
    );
    $statement->execute(['entidad' => $entidad, 'entidad_id' => $entidadId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
