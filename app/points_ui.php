<?php
declare(strict_types=1);

require_once __DIR__ . '/points_repository.php';

/**
 * Componentes visuales de la cartera de puntos.
 *
 * Los importes que se pintan aqui son informativos: el cobro real siempre lo
 * decide csf_puntos_coste() en el servidor. Ningun formulario de este fichero
 * envia el precio, solo la operacion que se quiere hacer.
 *
 * Estilos: prefijo `.csf-points-` en assets/css/styles.css.
 */

/**
 * Cabecera de saldo con el boton de comprar.
 *
 * Opciones: 'compacto' => bool, 'comprar' => bool (mostrar el CTA).
 */
function csf_puntos_widget_saldo(int $saldo, array $opciones = []): string
{
    $compacto = !empty($opciones['compacto']);
    $mostrarComprar = $opciones['comprar'] ?? true;
    $valor = csf_puntos_formato_euros($saldo * CSF_PUNTOS_VALOR_CENTIMOS);

    ob_start();
    ?>
    <div class="csf-points-balance<?= $compacto ? ' is-compact' : '' ?>">
        <div class="csf-points-balance-main">
            <p class="csf-points-label">Mis puntos</p>
            <p class="csf-points-amount"><strong data-puntos-saldo><?= e((string) $saldo) ?></strong> <span><?= e($saldo === 1 ? 'punto disponible' : 'puntos disponibles') ?></span></p>
            <?php if (!$compacto): ?>
                <p class="csf-points-hint">Equivalen a <?= e($valor) ?>. Un punto son <?= e(csf_puntos_formato_euros(CSF_PUNTOS_VALOR_CENTIMOS)) ?>.</p>
            <?php endif; ?>
        </div>
        <?php if ($mostrarComprar): ?>
            <button class="button button-primary csf-points-buy" type="button" data-abrir-paquetes>Comprar puntos</button>
        <?php endif; ?>
    </div>
    <?php

    return (string) ob_get_clean();
}

/**
 * Modal con los paquetes de compra.
 *
 * El boton final NO acredita puntos: crea un intento de compra en pagos_stripe
 * y avisa de que la pasarela llega despues. Se deja la interfaz terminada para
 * que conectar Stripe sea solo cambiar el destino del formulario.
 */
function csf_puntos_modal_paquetes(string $accionUrl, string $csrfToken): string
{
    ob_start();
    ?>
    <div class="csf-modal" data-paquetes-modal hidden>
        <div class="csf-modal-backdrop" data-cerrar-paquetes></div>
        <section class="csf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="csf-paquetes-title">
            <header class="csf-modal-header">
                <div>
                    <p class="section-kicker">Cartera</p>
                    <h2 id="csf-paquetes-title">Comprar puntos</h2>
                    <p>Los puntos sirven para dar visibilidad extra a tus eventos y a tus enlaces. Publicar siempre es gratis.</p>
                </div>
                <button class="modal-close" type="button" data-cerrar-paquetes aria-label="Cerrar">×</button>
            </header>

            <div class="csf-points-packs">
                <?php foreach (csf_puntos_paquetes() as $paquete): ?>
                    <form class="csf-points-pack" method="post" action="<?= e($accionUrl) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="panel_action" value="puntos_comprar">
                        <?php /* Solo viaja el numero de puntos. El precio lo
                                 pone el servidor con csf_puntos_paquete(). */ ?>
                        <input type="hidden" name="paquete" value="<?= e((string) $paquete['puntos']) ?>">
                        <span class="csf-points-pack-amount"><?= e((string) $paquete['puntos']) ?></span>
                        <span class="csf-points-pack-unit">puntos</span>
                        <span class="csf-points-pack-price"><?= e(csf_puntos_formato_euros($paquete['centimos'])) ?></span>
                        <button class="button button-secondary" type="submit">Comprar</button>
                    </form>
                <?php endforeach; ?>
            </div>

            <p class="csf-points-notice">
                <strong>Pago mediante Stripe disponible próximamente.</strong>
                Al pulsar «Comprar» guardamos tu solicitud y te avisaremos en cuanto la pasarela esté activa.
                No se realiza ningún cargo ni se añaden puntos todavía.
            </p>
        </section>
    </div>
    <?php

    return (string) ob_get_clean();
}

/**
 * Dialogo de confirmacion de un gasto.
 *
 * Se pinta ya con el saldo antes y despues para que la persona sepa exactamente
 * en que se queda antes de aceptar. El formulario solo envia la operacion y su
 * referencia; el coste lo vuelve a calcular el servidor.
 *
 * @param array{
 *     id:string, titulo:string, texto:string, coste:int, saldo:int,
 *     accion:string, csrf:string, panel_action:string, campos?:array<string,string>,
 *     confirmar?:string
 * } $config
 */
function csf_puntos_dialogo_confirmar(array $config): string
{
    $coste = (int) $config['coste'];
    $saldo = (int) $config['saldo'];
    $suficiente = $saldo >= $coste;
    $restante = max(0, $saldo - $coste);
    $campos = $config['campos'] ?? [];

    ob_start();
    ?>
    <div class="csf-modal" data-confirmar-modal="<?= e($config['id']) ?>" hidden>
        <div class="csf-modal-backdrop" data-cerrar-confirmar></div>
        <section class="csf-modal-dialog csf-modal-narrow" role="dialog" aria-modal="true"
                 aria-labelledby="csf-confirm-title-<?= e($config['id']) ?>">
            <header class="csf-modal-header">
                <div>
                    <p class="section-kicker">Confirmación</p>
                    <h2 id="csf-confirm-title-<?= e($config['id']) ?>"><?= e($config['titulo']) ?></h2>
                </div>
                <button class="modal-close" type="button" data-cerrar-confirmar aria-label="Cerrar">×</button>
            </header>

            <p class="csf-confirm-text"><?= e($config['texto']) ?></p>

            <?php if ($suficiente): ?>
                <dl class="csf-confirm-ledger">
                    <div><dt>Saldo actual</dt><dd><?= e(csf_puntos_formato($saldo)) ?></dd></div>
                    <div><dt>Coste</dt><dd class="is-cost">−<?= e(csf_puntos_formato($coste)) ?></dd></div>
                    <div class="is-total"><dt>Saldo después</dt><dd><?= e(csf_puntos_formato($restante)) ?></dd></div>
                </dl>

                <div class="csf-confirm-actions">
                    <button class="button button-secondary" type="button" data-cerrar-confirmar>Cancelar</button>
                    <form method="post" action="<?= e($config['accion']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($config['csrf']) ?>">
                        <input type="hidden" name="panel_action" value="<?= e($config['panel_action']) ?>">
                        <?php foreach ($campos as $nombre => $valor): ?>
                            <input type="hidden" name="<?= e($nombre) ?>" value="<?= e($valor) ?>">
                        <?php endforeach; ?>
                        <button class="button button-primary" type="submit">
                            <?= e($config['confirmar'] ?? ('Confirmar por ' . csf_puntos_formato($coste))) ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="csf-confirm-empty">
                    <p><strong>No tienes puntos suficientes.</strong></p>
                    <p>Necesitas <?= e(csf_puntos_formato($coste)) ?> y tienes <?= e(csf_puntos_formato($saldo)) ?>.</p>
                </div>
                <div class="csf-confirm-actions">
                    <button class="button button-secondary" type="button" data-cerrar-confirmar>Cancelar</button>
                    <button class="button button-primary" type="button" data-abrir-paquetes>Comprar puntos</button>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php

    return (string) ob_get_clean();
}

/**
 * Historial de movimientos de la cartera.
 *
 * @param array<int, array<string, mixed>> $movimientos
 */
function csf_puntos_historial(array $movimientos): string
{
    ob_start();
    if ($movimientos === []) {
        ?><p class="csf-empty">Todavía no hay movimientos en tu cartera.</p><?php
        return (string) ob_get_clean();
    }
    ?>
    <ul class="csf-points-ledger">
        <?php foreach ($movimientos as $movimiento): ?>
            <?php
            $puntos = (int) $movimiento['puntos'];
            $positivo = $puntos > 0;
            $timestamp = strtotime((string) ($movimiento['created_at'] ?? ''));
            ?>
            <li class="csf-points-entry<?= $positivo ? ' is-credit' : ' is-debit' ?>">
                <span class="csf-points-entry-amount"><?= e(($positivo ? '+' : '−') . abs($puntos)) ?></span>
                <span class="csf-points-entry-body">
                    <strong><?= e((string) $movimiento['concepto']) ?></strong>
                    <small>
                        <?= e(csf_puntos_tipo_etiqueta((string) $movimiento['tipo'])) ?>
                        <?php if ($timestamp !== false): ?> · <?= e(date('d/m/Y H:i', $timestamp)) ?><?php endif; ?>
                    </small>
                </span>
                <span class="csf-points-entry-balance"><?= e((string) $movimiento['saldo_posterior']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php

    return (string) ob_get_clean();
}
