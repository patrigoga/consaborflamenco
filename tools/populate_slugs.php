<?php
require_once __DIR__ . '/../app/auth.php';

$pdo = db();
if (!$pdo) {
    echo "No database available.\n";
    exit(1);
}

$stmt = $pdo->query('SELECT id, nombre_publico, usuario_id FROM miembros');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $base = trim($row['nombre_publico'] ?: 'miembro-' . $row['usuario_id']);
    $slug = slugify($base);
    $candidate = $slug;
    $i = 1;
    // ensure uniqueness
    while (true) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM miembros WHERE slug = :slug AND id != :id');
        $check->execute(['slug' => $candidate, 'id' => $row['id']]);
        if ((int) $check->fetchColumn() === 0) {
            break;
        }
        $i++;
        $candidate = $slug . '-' . $i;
    }

    $update = $pdo->prepare('UPDATE miembros SET slug = :slug WHERE id = :id');
    $update->execute(['slug' => $candidate, 'id' => $row['id']]);
    echo "Updated member {$row['id']} -> $candidate\n";
}

echo "Done\n";
