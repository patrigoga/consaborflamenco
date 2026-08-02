<?php
declare(strict_types=1);

/**
 * Id numerico del usuario en la tabla `usuarios`.
 *
 * Ojo: `$user['id']` NO es ese numero, es el uuid (ver db_user_from_row()).
 * El id numerico, que es al que apuntan todas las claves foraneas, va en `db_id`.
 */
function academia_user_id(array $user): int
{
    return (int) ($user['db_id'] ?? 0);
}

function academia_require_role(PDO $pdo, array $user, array $roles): array
{
    $userId = academia_user_id($user);
    $memberships = academia_memberships_for_user($pdo, $userId, $roles);

    if (!$memberships) {
        redirect_to('panel-usuario.php');
    }

    $requestedAcademiaId = isset($_GET['academia_id']) ? (int) $_GET['academia_id'] : 0;
    if ($requestedAcademiaId > 0) {
        foreach ($memberships as $membership) {
            if ((int) $membership['academia_id'] === $requestedAcademiaId) {
                return $membership;
            }
        }
        redirect_to('panel-academia.php');
    }

    return $memberships[0];
}

function academia_require_alumno(PDO $pdo, array $user): array
{
    $userId = academia_user_id($user);
    $statement = $pdo->prepare(
        'SELECT al.*, a.miembro_id AS academia_id, m.nombre_publico AS academia_nombre
         FROM academia_alumnos al
         INNER JOIN academias a ON a.miembro_id = al.academia_id
         INNER JOIN miembros m ON m.id = a.miembro_id
         WHERE al.usuario_id = :usuario_id AND al.estado = "ACTIVO"
         ORDER BY al.created_at ASC'
    );
    $statement->execute(['usuario_id' => $userId]);
    $alumnos = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (!$alumnos) {
        redirect_to('panel-usuario.php');
    }

    return $alumnos[0];
}

function academia_verify_alumno_ownership(PDO $pdo, int $academiaId, int $alumnoId): bool
{
    $statement = $pdo->prepare('SELECT id FROM academia_alumnos WHERE id = :id AND academia_id = :academia_id LIMIT 1');
    $statement->execute(['id' => $alumnoId, 'academia_id' => $academiaId]);

    return $statement->fetchColumn() !== false;
}

function academia_verify_curso_ownership(PDO $pdo, int $academiaId, int $cursoId): bool
{
    $statement = $pdo->prepare('SELECT id FROM academia_cursos WHERE id = :id AND academia_id = :academia_id LIMIT 1');
    $statement->execute(['id' => $cursoId, 'academia_id' => $academiaId]);

    return $statement->fetchColumn() !== false;
}

function academia_verify_grupo_ownership(PDO $pdo, int $academiaId, int $grupoId): bool
{
    $statement = $pdo->prepare('SELECT id FROM academia_grupos WHERE id = :id AND academia_id = :academia_id LIMIT 1');
    $statement->execute(['id' => $grupoId, 'academia_id' => $academiaId]);

    return $statement->fetchColumn() !== false;
}
