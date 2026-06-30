<?php
declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/layout.php';

$availableCardBackgrounds = [
    'tarjeta-bailaora.png' => [
        'path' => 'assets/images/member-cards/tarjeta-bailaora.png',
        'figure' => 'woman',
    ],
    'tarjeta-bailaor.png' => [
        'path' => 'assets/images/member-cards/tarjeta-bailaor.png',
        'figure' => 'man',
    ],
];
$requestedCode = strtoupper(clean_text((string) ($_GET['m'] ?? '')));
$selectedCardBackground = (string) ($_GET['d'] ?? 'tarjeta-bailaora.png');
if (!isset($availableCardBackgrounds[$selectedCardBackground])) {
    $selectedCardBackground = 'tarjeta-bailaora.png';
}

$cardUser = find_user_by_member_code($requestedCode);
$cardIsAvailable = $cardUser && user_email_is_verified($cardUser);
$cardProfile = $cardIsAvailable ? default_member_profile($cardUser) : [];
$cardBackground = $availableCardBackgrounds[$selectedCardBackground]['path'];
$cardFigure = $availableCardBackgrounds[$selectedCardBackground]['figure'];
$displayName = $cardIsAvailable
    ? (($cardProfile['public_name'] ?? '') !== '' ? (string) $cardProfile['public_name'] : (string) ($cardUser['name'] ?? 'Miembro'))
    : '';
$memberTypeLabel = $cardIsAvailable ? (member_type_options()[$cardProfile['member_type'] ?? 'artista'] ?? 'Artista') : '';
$memberTier = $cardIsAvailable ? strtolower((string) ($cardUser['membership_tier'] ?? 'simpatizante')) : 'simpatizante';
$isVipMember = $memberTier === 'vip';
$memberStatus = $isVipMember ? 'Miembro VIP' : 'Miembro simpatizante';
$memberCode = $cardIsAvailable ? member_code_for_user($cardUser) : '';
$cardHeadline = $cardIsAvailable ? clean_text((string) ($cardProfile['artistic_headline'] ?? '')) : '';
?>
<!DOCTYPE html>
<html lang="es">
<?php page_head('Tarjeta de miembro | Con Sabor Flamenco', 'Tarjeta digital de miembro de Con Sabor Flamenco.', false); ?>
<body class="member-card-public-body">
    <?php page_header(); ?>
    <main class="member-card-public-page">
        <?php if (!$cardIsAvailable): ?>
            <section class="member-card-public-shell">
                <div class="member-card-public-heading">
                    <p class="section-kicker">Tarjeta digital</p>
                    <h1>Tarjeta no disponible</h1>
                    <p>El codigo no existe, ha caducado o el email del miembro aun no esta verificado.</p>
                    <a class="button button-primary" href="index.php#inicio">Ir al inicio</a>
                </div>
            </section>
        <?php else: ?>
            <section class="member-card-public-shell">
                <div class="member-card-public-heading">
                    <div>
                        <p class="section-kicker">Tarjeta digital</p>
                        <h1><?= e($displayName) ?></h1>
                        <p><?= e($memberTypeLabel) ?> - <?= e($memberStatus) ?></p>
                    </div>
                    <button class="button button-primary" type="button" onclick="window.print()">Imprimir tarjeta</button>
                </div>
                <div class="member-card-public-stage">
                    <div class="member-card-preview member-card-preview-<?= e($cardFigure) ?>">
                        <img src="<?= e($cardBackground) ?>" alt="Fondo de tarjeta de miembro" loading="eager">
                        <img class="member-card-seal" src="assets/images/member-cards/pegatina-con-sabor-flamenco.png" alt="Sello Con Sabor Flamenco" loading="eager">
                        <div class="member-card-overlay">
                            <span class="member-card-space"><?= e($memberTypeLabel) ?></span>
                            <strong><?= e($displayName) ?></strong>
                            <?php if ($cardHeadline !== ''): ?><span class="member-card-headline"><?= e($cardHeadline) ?></span><?php endif; ?>
                            <?php if ($isVipMember): ?><code><?= e($memberCode) ?></code><?php endif; ?>
                        </div>
                        <div class="member-card-footer">
                            <span><?= e($memberStatus) ?></span>
                            <strong><span>con</span><em>sabor</em><span>flamenco</span><small>.com</small></strong>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <?php page_footer(); ?>
    <?php province_modal(); ?>
</body>
</html>
