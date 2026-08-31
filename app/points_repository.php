<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/activity_log.php';

/**
 * Cartera de puntos.
 *
 * Dos tablas, un solo criterio: `puntos_movimientos` es el libro mayor (solo se
 * anade, nunca se edita ni se borra) y `puntos_saldos` es el saldo materializado.
 * El saldo se toca EXCLUSIVAMENTE desde csf_puntos_registrar(), que exige estar
 * dentro de una transaccion y bloquea la fila con SELECT ... FOR UPDATE. Asi el
 * saldo y su justificacion no pueden desincronizarse ni siquiera con dos
 * peticiones simultaneas del mismo usuario.
 *
 * Tres redes contra el saldo negativo:
 *   1. comprobacion explicita antes de escribir;
 *   2. la columna `saldo` es INT UNSIGNED, asi que el motor rechazaria un
 *      negativo aunque la comprobacion fallara;
 *   3. el bloqueo de fila impide que dos gastos concurrentes lean el mismo saldo.
 *
 * SEGURIDAD: los costes viven aqui y solo aqui. El formulario puede decir lo que
 * quiera; el importe que se cobra sale siempre de csf_puntos_coste().
 */

/** 1 punto = 0,50 EUR. */
const CSF_PUNTOS_VALOR_CENTIMOS = 50;

/** Puntos de bienvenida segun el tipo de membresia. */
const CSF_PUNTOS_ALTA_SIMPATIZANTE = 30;
const CSF_PUNTOS_ALTA_VIP = 100;

/** Duracion por defecto de una promocion de evento, en dias. */
const CSF_PUNTOS_PROMOCION_DIAS = 30;

/** Codigos de error de las excepciones que lanza este modulo. */
const CSF_PUNTOS_ERR_SALDO = 1001;
const CSF_PUNTOS_ERR_TRANSACCION = 1002;
const CSF_PUNTOS_ERR_OPERACION = 1003;

/**
 * Tarifa de las operaciones que consumen puntos.
 *
 * Fuente unica de verdad del backend. Cuando el panel de administracion
 * necesite editarla se movera a tabla, pero de momento una constante evita una
 * consulta por peticion y hace imposible modificarla desde fuera.
 *
 * @return array<string, int>
 */
function csf_puntos_costes(): array
{
    return [
        'promocion_evento' => 10,
        'enlace_social' => 2,
    ];
}

/**
 * Coste real de una operacion. Lanza si la operacion no existe, para que un
 * `operacion` inventado por POST no acabe costando 0 puntos.
 */
function csf_puntos_coste(string $operacion): int
{
    $costes = csf_puntos_costes();
    if (!array_key_exists($operacion, $costes)) {
        throw new RuntimeException('Operacion de puntos desconocida: ' . $operacion, CSF_PUNTOS_ERR_OPERACION);
    }

    return $costes[$operacion];
}

/**
 * Paquetes de compra. Multiplos de 10 al cambio de 0,50 EUR el punto.
 *
 * @return array<int, array{puntos:int, centimos:int}>
 */
function csf_puntos_paquetes(): array
{
    $paquetes = [];
    foreach ([10, 20, 30, 50, 100] as $puntos) {
        $paquetes[] = [
            'puntos' => $puntos,
            'centimos' => $puntos * CSF_PUNTOS_VALOR_CENTIMOS,
        ];
    }

    return $paquetes;
}

/**
 * @return array{puntos:int, centimos:int}|null
 */
function csf_puntos_paquete(int $puntos): ?array
{
    foreach (csf_puntos_paquetes() as $paquete) {
        if ($paquete['puntos'] === $puntos) {
            return $paquete;
        }
    }

    return null;
}

/**
 * "5,00 €" a partir de centimos.
 */
function csf_puntos_formato_euros(int $centimos): string
{
    return number_format($centimos / 100, 2, ',', '.') . ' €';
}

/**
 * "30 puntos" / "1 punto".
 */
function csf_puntos_formato(int $puntos): string
{
    $absoluto = abs($puntos);

    return $absoluto . ' ' . ($absoluto === 1 ? 'punto' : 'puntos');
}

/**
 * Etiquetas de los tipos de movimiento, para el historial de la cartera.
 *
 * @return array<string, string>
 */
function csf_puntos_tipos(): array
{
    return [
        'INICIAL' => 'Puntos iniciales',
        'COMPRA' => 'Compra de puntos',
        'CONSUMO' => 'Consumo',
        'DEVOLUCION' => 'Devolución',
        'PROMOCION' => 'Promoción de evento',
        'ENLACE_SOCIAL' => 'Enlace social',
        'ADMINISTRACION' => 'Ajuste de administración',
        'PROMOCIONAL' => 'Puntos promocionales',
    ];
}

function csf_puntos_tipo_etiqueta(string $tipo): string
{
    return csf_puntos_tipos()[strtoupper($tipo)] ?? 'Movimiento';
}

/**
 * Saldo disponible. Devuelve 0 si el usuario aun no tiene cartera.
 */
function csf_puntos_saldo(PDO $pdo, int $usuarioId): int
{
    if ($usuarioId <= 0) {
        return 0;
    }

    $statement = $pdo->prepare('SELECT saldo FROM puntos_saldos WHERE usuario_id = :usuario_id');
    $statement->execute(['usuario_id' => $usuarioId]);
    $saldo = $statement->fetchColumn();

    return $saldo === false ? 0 : (int) $saldo;
}

/**
 * Saldo mas acumulados, para la pantalla "Mis puntos".
 *
 * @return array{saldo:int, total_ingresado:int, total_gastado:int}
 */
function csf_puntos_resumen(PDO $pdo, int $usuarioId): array
{
    $vacio = ['saldo' => 0, 'total_ingresado' => 0, 'total_gastado' => 0];
    if ($usuarioId <= 0) {
        return $vacio;
    }

    $statement = $pdo->prepare(
        'SELECT saldo, total_ingresado, total_gastado FROM puntos_saldos WHERE usuario_id = :usuario_id'
    );
    $statement->execute(['usuario_id' => $usuarioId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return $vacio;
    }

    return [
        'saldo' => (int) $row['saldo'],
        'total_ingresado' => (int) $row['total_ingresado'],
        'total_gastado' => (int) $row['total_gastado'],
    ];
}

/**
 * Historial de movimientos, del mas reciente al mas antiguo.
 *
 * @return array<int, array<string, mixed>>
 */
function csf_puntos_movimientos(PDO $pdo, int $usuarioId, int $limite = 30): array
{
    if ($usuarioId <= 0) {
        return [];
    }

    $statement = $pdo->prepare(
        'SELECT id, puntos, tipo, concepto, referencia_tipo, referencia_id, saldo_posterior, created_at
         FROM puntos_movimientos
         WHERE usuario_id = :usuario_id
         ORDER BY created_at DESC, id DESC
         LIMIT ' . max(1, min(200, $limite))
    );
    $statement->execute(['usuario_id' => $usuarioId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ejecuta una operacion dentro de una transaccion, reutilizando la que ya
 * hubiera abierta.
 *
 * Es lo que permite que "promocionar evento" descuente los puntos y marque el
 * evento como destacado de forma atomica: el repositorio de eventos abre la
 * transaccion y csf_puntos_gastar() se suma a ella en vez de abrir otra.
 */
function csf_puntos_ejecutar(PDO $pdo, callable $operacion): mixed
{
    if ($pdo->inTransaction()) {
        return $operacion();
    }

    $pdo->beginTransaction();
    try {
        $resultado = $operacion();
        $pdo->commit();

        return $resultado;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/**
 * Anota un movimiento y actualiza el saldo. Nucleo de todo el modulo.
 *
 * $puntos positivo abona, negativo gasta. Exige transaccion abierta: quien
 * llama es responsable de que el efecto asociado (marcar el evento como
 * promocionado, activar el enlace) viaje en la misma transaccion, para que o se
 * guarda todo o no se guarda nada.
 *
 * Opciones admitidas: referencia_tipo, referencia_id, pago_id,
 * clave_idempotencia, creado_por_usuario_id.
 *
 * @return array{movimiento_id:int, saldo:int, puntos:int, duplicado:bool}
 */
function csf_puntos_registrar(
    PDO $pdo,
    int $usuarioId,
    int $puntos,
    string $tipo,
    string $concepto,
    array $opciones = []
): array {
    if (!$pdo->inTransaction()) {
        throw new RuntimeException(
            'csf_puntos_registrar() debe ejecutarse dentro de una transaccion.',
            CSF_PUNTOS_ERR_TRANSACCION
        );
    }

    if ($usuarioId <= 0) {
        throw new RuntimeException('Usuario invalido para un movimiento de puntos.', CSF_PUNTOS_ERR_OPERACION);
    }

    if ($puntos === 0) {
        throw new RuntimeException('Un movimiento de puntos no puede ser de 0.', CSF_PUNTOS_ERR_OPERACION);
    }

    $tipo = strtoupper(trim($tipo));
    if (!array_key_exists($tipo, csf_puntos_tipos())) {
        throw new RuntimeException('Tipo de movimiento desconocido: ' . $tipo, CSF_PUNTOS_ERR_OPERACION);
    }

    $clave = clean_text((string) ($opciones['clave_idempotencia'] ?? ''));
    $clave = $clave !== '' ? mb_substr($clave, 0, 120) : null;

    // La cartera se crea al vuelo la primera vez que el usuario mueve puntos.
    $pdo->prepare('INSERT IGNORE INTO puntos_saldos (usuario_id) VALUES (:usuario_id)')
        ->execute(['usuario_id' => $usuarioId]);

    // A partir de aqui la fila queda bloqueada hasta el commit: dos peticiones
    // simultaneas del mismo usuario se serializan en vez de leer el mismo saldo.
    $bloqueo = $pdo->prepare(
        'SELECT saldo, total_ingresado, total_gastado FROM puntos_saldos WHERE usuario_id = :usuario_id FOR UPDATE'
    );
    $bloqueo->execute(['usuario_id' => $usuarioId]);
    $cartera = $bloqueo->fetch(PDO::FETCH_ASSOC) ?: ['saldo' => 0, 'total_ingresado' => 0, 'total_gastado' => 0];

    // Idempotencia: se comprueba con la fila ya bloqueada, para que un doble
    // envio del formulario no llegue a duplicar el cargo.
    if ($clave !== null) {
        $repetido = $pdo->prepare('SELECT id, saldo_posterior, puntos FROM puntos_movimientos WHERE clave_idempotencia = :clave');
        $repetido->execute(['clave' => $clave]);
        $previo = $repetido->fetch(PDO::FETCH_ASSOC);
        if ($previo) {
            return [
                'movimiento_id' => (int) $previo['id'],
                'saldo' => (int) $cartera['saldo'],
                'puntos' => (int) $previo['puntos'],
                'duplicado' => true,
            ];
        }
    }

    $saldoActual = (int) $cartera['saldo'];
    $saldoNuevo = $saldoActual + $puntos;

    if ($saldoNuevo < 0) {
        throw new RuntimeException(
            'Saldo insuficiente: hacen falta ' . csf_puntos_formato(abs($puntos))
                . ' y solo hay ' . csf_puntos_formato($saldoActual) . '.',
            CSF_PUNTOS_ERR_SALDO
        );
    }

    $ingresado = (int) $cartera['total_ingresado'] + ($puntos > 0 ? $puntos : 0);
    $gastado = (int) $cartera['total_gastado'] + ($puntos < 0 ? abs($puntos) : 0);

    $actualizar = $pdo->prepare(
        'UPDATE puntos_saldos
            SET saldo = :saldo, total_ingresado = :ingresado, total_gastado = :gastado
          WHERE usuario_id = :usuario_id'
    );
    $actualizar->execute([
        'saldo' => $saldoNuevo,
        'ingresado' => $ingresado,
        'gastado' => $gastado,
        'usuario_id' => $usuarioId,
    ]);

    $insertar = $pdo->prepare(
        'INSERT INTO puntos_movimientos
            (usuario_id, puntos, tipo, concepto, referencia_tipo, referencia_id,
             saldo_posterior, pago_id, clave_idempotencia, creado_por_usuario_id)
         VALUES
            (:usuario_id, :puntos, :tipo, :concepto, :referencia_tipo, :referencia_id,
             :saldo_posterior, :pago_id, :clave_idempotencia, :creado_por)'
    );
    $insertar->execute([
        'usuario_id' => $usuarioId,
        'puntos' => $puntos,
        'tipo' => $tipo,
        'concepto' => mb_substr(clean_text($concepto), 0, 190),
        'referencia_tipo' => ($opciones['referencia_tipo'] ?? null) !== null
            ? mb_substr((string) $opciones['referencia_tipo'], 0, 40)
            : null,
        'referencia_id' => isset($opciones['referencia_id']) && (int) $opciones['referencia_id'] > 0
            ? (int) $opciones['referencia_id']
            : null,
        'saldo_posterior' => $saldoNuevo,
        'pago_id' => isset($opciones['pago_id']) && (int) $opciones['pago_id'] > 0
            ? (int) $opciones['pago_id']
            : null,
        'clave_idempotencia' => $clave,
        'creado_por' => isset($opciones['creado_por_usuario_id']) && (int) $opciones['creado_por_usuario_id'] > 0
            ? (int) $opciones['creado_por_usuario_id']
            : null,
    ]);

    return [
        'movimiento_id' => (int) $pdo->lastInsertId(),
        'saldo' => $saldoNuevo,
        'puntos' => $puntos,
        'duplicado' => false,
    ];
}

/**
 * Abona puntos. Abre transaccion propia si no hay ninguna en curso.
 *
 * @return array{movimiento_id:int, saldo:int, puntos:int, duplicado:bool}
 */
function csf_puntos_abonar(
    PDO $pdo,
    int $usuarioId,
    int $puntos,
    string $tipo,
    string $concepto,
    array $opciones = []
): array {
    return csf_puntos_ejecutar(
        $pdo,
        static fn (): array => csf_puntos_registrar($pdo, $usuarioId, abs($puntos), $tipo, $concepto, $opciones)
    );
}

/**
 * Gasta puntos. Abre transaccion propia si no hay ninguna en curso.
 *
 * Lanza RuntimeException con codigo CSF_PUNTOS_ERR_SALDO si no llega el saldo.
 *
 * @return array{movimiento_id:int, saldo:int, puntos:int, duplicado:bool}
 */
function csf_puntos_gastar(
    PDO $pdo,
    int $usuarioId,
    int $puntos,
    string $tipo,
    string $concepto,
    array $opciones = []
): array {
    return csf_puntos_ejecutar(
        $pdo,
        static fn (): array => csf_puntos_registrar($pdo, $usuarioId, -abs($puntos), $tipo, $concepto, $opciones)
    );
}

/**
 * Abona los puntos de bienvenida la primera vez que el usuario entra al panel.
 *
 * Idempotente por `clave_idempotencia`: se puede llamar en cada carga de pagina
 * sin miedo. El importe depende del tipo de membresia en el momento del alta;
 * una subida posterior a VIP no vuelve a abonar (seria una decision comercial
 * distinta, con su propio movimiento de tipo PROMOCIONAL).
 */
function csf_puntos_asegurar_alta(PDO $pdo, int $usuarioId, string $membresia): int
{
    if ($usuarioId <= 0) {
        return 0;
    }

    $esVip = strtolower(trim($membresia)) === 'vip';
    $puntos = $esVip ? CSF_PUNTOS_ALTA_VIP : CSF_PUNTOS_ALTA_SIMPATIZANTE;
    $concepto = $esVip ? 'Bienvenida miembro VIP' : 'Bienvenida miembro gratuito';

    try {
        $resultado = csf_puntos_abonar($pdo, $usuarioId, $puntos, 'INICIAL', $concepto, [
            'referencia_tipo' => 'alta',
            'referencia_id' => $usuarioId,
            'clave_idempotencia' => 'alta:' . $usuarioId,
        ]);

        if (!$resultado['duplicado']) {
            csf_log_actividad($pdo, $usuarioId, 'puntos', $resultado['movimiento_id'], 'alta_inicial', [
                'puntos' => $puntos,
                'membresia' => $esVip ? 'vip' : 'simpatizante',
            ]);
        }

        return $resultado['saldo'];
    } catch (Throwable $exception) {
        error_log('[puntos] alta inicial omitida: ' . $exception->getMessage());

        return csf_puntos_saldo($pdo, $usuarioId);
    }
}

/**
 * Registra la intencion de compra de un paquete de puntos.
 *
 * Crea la fila en `pagos_stripe` en estado PENDIENTE y NO abona nada: los
 * puntos solo se acreditan cuando el pago se confirme de verdad. Cuando se
 * conecte Stripe, el webhook marcara el pago como PAGADO y llamara a
 * csf_puntos_acreditar_pago(); hasta entonces la fila queda como registro de
 * interes comercial.
 *
 * @return array{pago_id:int, puntos:int, centimos:int}
 */
function csf_puntos_crear_intento_compra(PDO $pdo, int $usuarioId, int $puntos): array
{
    $paquete = csf_puntos_paquete($puntos);
    if ($paquete === null) {
        throw new RuntimeException('Ese paquete de puntos no es válido.', CSF_PUNTOS_ERR_OPERACION);
    }

    $statement = $pdo->prepare(
        'INSERT INTO pagos_stripe (usuario_id, concepto, importe_centimos, moneda, estado)
         VALUES (:usuario_id, :concepto, :importe, "EUR", "PENDIENTE")'
    );
    $statement->execute([
        'usuario_id' => $usuarioId,
        'concepto' => 'Paquete de ' . $paquete['puntos'] . ' puntos',
        'importe' => $paquete['centimos'],
    ]);

    $pagoId = (int) $pdo->lastInsertId();

    csf_log_actividad($pdo, $usuarioId, 'pago', $pagoId, 'intento_compra_puntos', [
        'puntos' => $paquete['puntos'],
        'centimos' => $paquete['centimos'],
    ]);

    return [
        'pago_id' => $pagoId,
        'puntos' => $paquete['puntos'],
        'centimos' => $paquete['centimos'],
    ];
}

/**
 * Acredita los puntos de un pago ya confirmado.
 *
 * Todavia no la llama nadie: es el punto de entrada que usara el webhook de
 * Stripe. Se deja escrita y probada para que conectar el pago sea solo invocarla
 * desde el webhook, sin volver a tocar la logica de la cartera.
 *
 * Es idempotente por pago (clave `pago:{id}`), que es justo lo que hace falta
 * para un webhook que puede llegar repetido.
 */
function csf_puntos_acreditar_pago(PDO $pdo, int $pagoId): ?array
{
    $statement = $pdo->prepare('SELECT id, usuario_id, concepto, importe_centimos, estado FROM pagos_stripe WHERE id = :id');
    $statement->execute(['id' => $pagoId]);
    $pago = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$pago || (string) $pago['estado'] !== 'PAGADO') {
        return null;
    }

    $puntos = (int) round(((int) $pago['importe_centimos']) / CSF_PUNTOS_VALOR_CENTIMOS);
    if ($puntos <= 0) {
        return null;
    }

    return csf_puntos_abonar(
        $pdo,
        (int) $pago['usuario_id'],
        $puntos,
        'COMPRA',
        (string) $pago['concepto'],
        [
            'referencia_tipo' => 'pago',
            'referencia_id' => $pagoId,
            'pago_id' => $pagoId,
            'clave_idempotencia' => 'pago:' . $pagoId,
        ]
    );
}
