<?php
declare(strict_types=1);

require_once __DIR__ . '/events_repository.php';
require_once __DIR__ . '/activity_log.php';

/**
 * Reservas simples de tablao (mega agenda, bloque 3).
 *
 * Una "funcion" de tablao no es una tabla nueva: es un evento normal de la
 * tabla `eventos` cuyo miembro_id es un tablao. Esta tabla solo guarda las
 * solicitudes de reserva que llegan sobre esos eventos. Sin pago online ni
 * control de aforo en esta fase: el tablao confirma o rechaza a mano desde su
 * panel, con el mismo espiritu que las solicitudes de informacion de
 * academias (app/academia_repository.php).
 */

const CSF_TABLAO_RESERVA_MAX_PERSONAS = 50;

/**
 * @return array<string, string>
 */
function csf_tablao_reserva_estados(): array
{
    return [
        'PENDIENTE' => 'Pendiente',
        'CONFIRMADA' => 'Confirmada',
        'RECHAZADA' => 'Rechazada',
        'CANCELADA' => 'Cancelada',
    ];
}

function csf_tablao_reserva_estado_valido(string $estado): string
{
    return array_key_exists($estado, csf_tablao_reserva_estados()) ? $estado : 'PENDIENTE';
}

/**
 * True si el evento admite reservas ahora mismo: es de un tablao, esta
 * publicado, tiene `acepta_reservas` activo y todavia no ha pasado.
 *
 * @param array<string, mixed> $evento Fila de csf_evento_select_sql() (incluye
 *        artista_tipo y acepta_reservas).
 */
function csf_tablao_evento_acepta_reservas(array $evento): bool
{
    return (string) ($evento['artista_tipo'] ?? '') === 'tablao'
        && (string) ($evento['estado'] ?? '') === 'PUBLICADO'
        && !empty($evento['acepta_reservas'])
        && !csf_evento_es_pasado($evento);
}

/**
 * Validacion del formulario publico de reserva.
 *
 * @param array<string, mixed> $datos
 * @return string[]
 */
function csf_tablao_reserva_validar(array $datos): array
{
    $errores = [];

    $nombre = clean_text((string) ($datos['nombre_solicitante'] ?? ''));
    if ($nombre === '') {
        $errores[] = 'Indica tu nombre.';
    } elseif (mb_strlen($nombre) > 160) {
        $errores[] = 'El nombre no puede superar los 160 caracteres.';
    }

    $email = trim((string) ($datos['email'] ?? ''));
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errores[] = 'Indica un email válido para confirmarte la reserva.';
    }

    $telefono = clean_text((string) ($datos['telefono'] ?? ''));
    if (mb_strlen($telefono) > 60) {
        $errores[] = 'El teléfono no puede superar los 60 caracteres.';
    }

    $numPersonas = (int) ($datos['num_personas'] ?? 0);
    if ($numPersonas < 1 || $numPersonas > CSF_TABLAO_RESERVA_MAX_PERSONAS) {
        $errores[] = 'Indica un número de personas entre 1 y ' . CSF_TABLAO_RESERVA_MAX_PERSONAS . '.';
    }

    if (mb_strlen((string) ($datos['mensaje'] ?? '')) > 500) {
        $errores[] = 'El mensaje no puede superar los 500 caracteres.';
    }

    return $errores;
}

/**
 * Crea una solicitud de reserva publica sobre un evento de tablao.
 *
 * Mismo patron anti-spam que site_public_contact_submit()
 * (app/site_content_repository.php): honeypot silencioso, CSRF y un limite de
 * intentos por sesion. No hay control de aforo en esta fase: la reserva queda
 * "pendiente" hasta que el tablao la confirme o la rechace a mano.
 *
 * @param array<string, mixed> $evento Fila de csf_evento_select_sql().
 * @param array<string, mixed> $post
 * @param array<string, mixed> $server Normalmente $_SERVER.
 * @return array{ok: bool, message: string, errors: string[]}
 */
function csf_tablao_reserva_enviar(PDO $pdo, array $evento, array $post, array $server): array
{
    if (!csf_tablao_evento_acepta_reservas($evento)) {
        return ['ok' => false, 'message' => '', 'errors' => ['Este evento no admite reservas.']];
    }

    if (!verify_csrf($post['csrf_token'] ?? null)) {
        return ['ok' => false, 'message' => '', 'errors' => ['La sesión ha caducado. Vuelve a intentarlo.']];
    }

    // Honeypot: si el campo trampa viene relleno, se finge exito sin escribir nada.
    if (trim((string) ($post['website'] ?? '')) !== '') {
        return ['ok' => true, 'message' => 'Gracias. Hemos recibido tu solicitud de reserva.', 'errors' => []];
    }

    $now = time();
    $_SESSION['tablao_reserva_intentos'] = array_values(array_filter(
        is_array($_SESSION['tablao_reserva_intentos'] ?? null) ? $_SESSION['tablao_reserva_intentos'] : [],
        static fn ($timestamp): bool => is_numeric($timestamp) && (int) $timestamp > $now - 60
    ));
    if (count($_SESSION['tablao_reserva_intentos']) >= 3) {
        return ['ok' => false, 'message' => '', 'errors' => ['Has enviado varias solicitudes seguidas. Espera un minuto antes de intentarlo de nuevo.']];
    }

    $errores = csf_tablao_reserva_validar($post);
    if ($errores) {
        return ['ok' => false, 'message' => '', 'errors' => $errores];
    }

    $ipHash = hash('sha256', (string) ($server['REMOTE_ADDR'] ?? '') . '|' . APP_NAME);

    $statement = $pdo->prepare(
        'INSERT INTO tablao_reservas
            (evento_id, miembro_id, nombre_solicitante, email, telefono, num_personas, mensaje, ip_hash)
         VALUES
            (:evento_id, :miembro_id, :nombre_solicitante, :email, :telefono, :num_personas, :mensaje, :ip_hash)'
    );
    $statement->execute([
        'evento_id' => (int) $evento['id'],
        'miembro_id' => (int) $evento['miembro_id'],
        'nombre_solicitante' => mb_substr(clean_text((string) $post['nombre_solicitante']), 0, 160),
        'email' => mb_substr(trim((string) $post['email']), 0, 190),
        'telefono' => mb_substr(clean_text((string) ($post['telefono'] ?? '')), 0, 60) ?: null,
        'num_personas' => max(1, min(CSF_TABLAO_RESERVA_MAX_PERSONAS, (int) $post['num_personas'])),
        'mensaje' => mb_substr(clean_text((string) ($post['mensaje'] ?? '')), 0, 500) ?: null,
        'ip_hash' => $ipHash,
    ]);

    $_SESSION['tablao_reserva_intentos'][] = $now;

    csf_log_actividad($pdo, null, 'tablao_reserva', (int) $pdo->lastInsertId(), 'creada', [
        'evento_id' => (int) $evento['id'],
    ]);

    return [
        'ok' => true,
        'message' => 'Gracias. Hemos recibido tu solicitud de reserva; el tablao te confirmará por email.',
        'errors' => [],
    ];
}

/**
 * Bandeja de reservas de un tablao (todas sus funciones), para el panel.
 * Pendientes primero, luego por fecha de la funcion.
 *
 * @return array<int, array<string, mixed>>
 */
function csf_tablao_reservas_de_miembro(PDO $pdo, int $miembroId, int $limite = 100): array
{
    $statement = $pdo->prepare(
        'SELECT r.*, e.titulo AS evento_titulo, e.fecha AS evento_fecha, e.slug AS evento_slug
         FROM tablao_reservas r
         INNER JOIN eventos e ON e.id = r.evento_id
         WHERE r.miembro_id = :miembro_id
         ORDER BY (r.estado = "PENDIENTE") DESC, e.fecha ASC, r.created_at DESC
         LIMIT ' . max(1, min(300, $limite))
    );
    $statement->execute(['miembro_id' => $miembroId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function csf_tablao_reservas_contar_pendientes(PDO $pdo, int $miembroId): int
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM tablao_reservas WHERE miembro_id = :miembro_id AND estado = "PENDIENTE"'
    );
    $statement->execute(['miembro_id' => $miembroId]);

    return (int) $statement->fetchColumn();
}

/**
 * Cambia el estado de una reserva, comprobando antes que pertenece al tablao
 * que la gestiona (no basta con que el id exista): mismo principio que
 * academia_verify_*_ownership() en app/academia_security.php.
 */
function csf_tablao_reserva_cambiar_estado(
    PDO $pdo,
    int $reservaId,
    int $miembroId,
    int $usuarioGestorId,
    string $nuevoEstado
): bool {
    $estado = csf_tablao_reserva_estado_valido($nuevoEstado);

    $statement = $pdo->prepare(
        'UPDATE tablao_reservas
         SET estado = :estado, gestionado_por = :gestor, gestionado_at = NOW()
         WHERE id = :id AND miembro_id = :miembro_id'
    );
    $statement->execute([
        'estado' => $estado,
        'gestor' => $usuarioGestorId,
        'id' => $reservaId,
        'miembro_id' => $miembroId,
    ]);

    if ($statement->rowCount() > 0) {
        csf_log_actividad($pdo, $usuarioGestorId, 'tablao_reserva', $reservaId, 'estado_' . strtolower($estado));

        return true;
    }

    return false;
}
