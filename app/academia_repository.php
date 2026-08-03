<?php
declare(strict_types=1);

/**
 * Catalogos de los ENUM del modulo.
 *
 * Fuente unica de verdad: se usan para pintar los <select> y para validar en
 * servidor lo que llega por POST. Antes los valores estaban escritos a mano en
 * el HTML de panel-academia.php y viajaban crudos hasta la consulta, asi que un
 * valor inventado reventaba el INSERT contra la columna ENUM.
 *
 * Viven aqui, y no en academia_ui.php, porque tambien los necesita esta capa
 * para validar y este es el fichero que carga siempre app/auth.php.
 *
 * Las claves deben coincidir exactamente con los ENUM de
 * database/20260802_academias_fase1.sql.
 */
function academia_dias_semana(): array
{
    return [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        0 => 'Domingo',
    ];
}

function academia_curso_estados(): array
{
    return [
        'BORRADOR' => 'Borrador',
        'PUBLICADO' => 'Publicado',
        'MATRICULA_ABIERTA' => 'Matrícula abierta',
        'COMPLETO' => 'Completo',
        'EN_CURSO' => 'En curso',
        'FINALIZADO' => 'Finalizado',
        'CANCELADO' => 'Cancelado',
    ];
}

function academia_curso_modalidades(): array
{
    return [
        'PRESENCIAL' => 'Presencial',
        'ONLINE' => 'Online',
        'MIXTA' => 'Mixta',
    ];
}

function academia_grupo_estados(): array
{
    return [
        'ACTIVO' => 'Activo',
        'CERRADO' => 'Cerrado',
    ];
}

function academia_matricula_estados(): array
{
    return [
        'SOLICITADA' => 'Solicitada',
        'PENDIENTE_DOCUMENTACION' => 'Pendiente de documentación',
        'PENDIENTE_PAGO' => 'Pendiente de pago',
        'ACTIVA' => 'Activa',
        'PAUSADA' => 'Pausada',
        'FINALIZADA' => 'Finalizada',
        'CANCELADA' => 'Cancelada',
        'RECHAZADA' => 'Rechazada',
    ];
}

/**
 * Estados de matricula que ocupan plaza en un grupo.
 */
function academia_matricula_estados_ocupan_plaza(): array
{
    return ['SOLICITADA', 'PENDIENTE_DOCUMENTACION', 'PENDIENTE_PAGO', 'ACTIVA', 'PAUSADA'];
}

function academia_miembro_estados(): array
{
    return [
        'ACTIVO' => 'Activo',
        'INACTIVO' => 'Inactivo',
        'SUSPENDIDO' => 'Suspendido',
        'PENDIENTE' => 'Pendiente',
    ];
}

function academia_alumno_estados(): array
{
    return [
        'ACTIVO' => 'Activo',
        'INACTIVO' => 'Inactivo',
        'BAJA' => 'Baja',
    ];
}

/**
 * Devuelve el valor solo si esta en el catalogo; si no, el valor por defecto.
 * Nunca deja pasar a la consulta algo que la columna ENUM no admita.
 */
function academia_enum(mixed $value, array $options, string $default): string
{
    $candidate = is_scalar($value) ? (string) $value : '';

    return array_key_exists($candidate, $options) ? $candidate : $default;
}

/**
 * Igual que academia_enum() pero admite vacio, para filtros del tipo "todos".
 */
function academia_enum_filter(mixed $value, array $options): string
{
    $candidate = is_scalar($value) ? (string) $value : '';

    return array_key_exists($candidate, $options) ? $candidate : '';
}

function academia_sync_membership(PDO $pdo, int $userId): void
{
    $statement = $pdo->prepare(
        'SELECT m.id AS miembro_id, tm.slug AS tipo_slug
         FROM miembros m
         LEFT JOIN tipos_miembro tm ON tm.id = m.tipo_miembro_id
         WHERE m.usuario_id = :usuario_id LIMIT 1'
    );
    $statement->execute(['usuario_id' => $userId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$row || (string) ($row['tipo_slug'] ?? '') !== 'academia') {
        return;
    }

    $academiaId = (int) $row['miembro_id'];

    $pdo->prepare('INSERT IGNORE INTO academias (miembro_id, estado, created_by) VALUES (:miembro_id, "PENDIENTE", :creado_por)')
        ->execute(['miembro_id' => $academiaId, 'creado_por' => $userId]);

    $pdo->prepare(
        'INSERT IGNORE INTO academia_miembros (academia_id, usuario_id, rol, estado, fecha_alta, created_by)
         VALUES (:academia_id, :usuario_id, "RESPONSABLE", "ACTIVO", CURRENT_DATE(), :creado_por)'
    )->execute(['academia_id' => $academiaId, 'usuario_id' => $userId, 'creado_por' => $userId]);
}

function academia_decode_config(?string $json): array
{
    if (!$json) {
        return [];
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Descripcion publica de la academia.
 *
 * Vive en perfil_json porque db_upsert_member_for_user() vacia la columna
 * `biografia` en cada login. La columna se usa solo como respaldo.
 */
function academia_descripcion(array $academia): string
{
    $profile = academia_decode_config((string) ($academia['perfil_json'] ?? ''));
    $descripcion = clean_text((string) ($profile['short_description'] ?? ''));

    return $descripcion !== '' ? $descripcion : clean_text((string) ($academia['biografia'] ?? ''));
}

function academia_memberships_for_user(PDO $pdo, int $userId, array $roles = []): array
{
    $sql = 'SELECT am.*, a.estado AS academia_estado, a.plan, m.nombre_publico, m.slug
            FROM academia_miembros am
            INNER JOIN academias a ON a.miembro_id = am.academia_id
            INNER JOIN miembros m ON m.id = am.academia_id
            WHERE am.usuario_id = :usuario_id AND am.estado = "ACTIVO"';
    $params = ['usuario_id' => $userId];

    if ($roles) {
        $placeholders = [];
        foreach (array_values($roles) as $index => $role) {
            $key = 'rol_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $role;
        }
        $sql .= ' AND am.rol IN (' . implode(',', $placeholders) . ')';
    }

    $sql .= ' ORDER BY am.created_at ASC';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_get(PDO $pdo, int $academiaId): ?array
{
    $statement = $pdo->prepare(
        'SELECT a.*, m.nombre_publico, m.slug, m.biografia, m.ciudad, m.provincia_texto, m.telefono,
                m.foto_principal_path, m.web_url, m.instagram_url, m.facebook_url, m.youtube_url, m.perfil_json
         FROM academias a
         INNER JOIN miembros m ON m.id = a.miembro_id
         WHERE a.miembro_id = :academia_id LIMIT 1'
    );
    $statement->execute(['academia_id' => $academiaId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function academia_get_by_slug(PDO $pdo, string $slug): ?array
{
    $statement = $pdo->prepare(
        'SELECT a.*, m.nombre_publico, m.slug, m.biografia, m.ciudad, m.provincia_texto, m.telefono,
                m.foto_principal_path, m.web_url, m.instagram_url, m.facebook_url, m.youtube_url, m.perfil_json
         FROM academias a
         INNER JOIN miembros m ON m.id = a.miembro_id
         WHERE m.slug = :slug LIMIT 1'
    );
    $statement->execute(['slug' => $slug]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function academia_update_profile(PDO $pdo, int $academiaId, array $data, int $actorUserId): void
{
    // `miembros` tiene doble fuente de verdad: las columnas y `perfil_json`.
    // db_upsert_member_for_user() reescribe las columnas desde perfil_json en cada
    // login, asi que hay que actualizar ambos o la edicion se revierte al entrar.
    // La descripcion va solo en perfil_json porque esa funcion vacia `biografia`.
    $current = $pdo->prepare('SELECT perfil_json FROM miembros WHERE id = :academia_id LIMIT 1');
    $current->execute(['academia_id' => $academiaId]);
    $profile = academia_decode_config((string) ($current->fetchColumn() ?: ''));

    $profile['member_type'] = 'academia';
    $profile['public_name'] = $data['nombre_publico'];
    $profile['short_description'] = $data['biografia'];
    $profile['city'] = $data['ciudad'];
    $profile['province'] = $data['provincia_texto'];
    $profile['phone'] = $data['telefono'];
    $profile['website_url'] = $data['web_url'];
    $profile['instagram_url'] = $data['instagram_url'];

    $pdo->prepare(
        'UPDATE miembros SET
            nombre_publico = :nombre_publico,
            biografia = :biografia,
            ciudad = :ciudad,
            provincia_texto = :provincia_texto,
            telefono = :telefono,
            web_url = :web_url,
            instagram_url = :instagram_url,
            facebook_url = :facebook_url,
            perfil_json = :perfil_json,
            updated_at = UTC_TIMESTAMP()
         WHERE id = :academia_id'
    )->execute([
        'nombre_publico' => $data['nombre_publico'],
        'biografia' => $data['biografia'],
        'ciudad' => $data['ciudad'],
        'provincia_texto' => $data['provincia_texto'],
        'telefono' => $data['telefono'],
        'web_url' => $data['web_url'],
        'instagram_url' => $data['instagram_url'],
        'facebook_url' => $data['facebook_url'],
        'perfil_json' => json_encode($profile, JSON_UNESCAPED_UNICODE),
        'academia_id' => $academiaId,
    ]);

    $pdo->prepare(
        'UPDATE academias SET
            razon_social = :razon_social,
            cif = :cif,
            configuracion_json = :configuracion_json,
            updated_by = :actor,
            updated_at = UTC_TIMESTAMP()
         WHERE miembro_id = :academia_id'
    )->execute([
        'razon_social' => $data['razon_social'],
        'cif' => $data['cif'],
        'configuracion_json' => json_encode($data['configuracion'] ?? [], JSON_UNESCAPED_UNICODE),
        'actor' => $actorUserId,
        'academia_id' => $academiaId,
    ]);
}

function academia_all_disciplinas(PDO $pdo): array
{
    return $pdo->query('SELECT id, slug, nombre FROM disciplinas WHERE estado = "ACTIVA" ORDER BY nombre ASC')
        ->fetchAll(PDO::FETCH_ASSOC);
}

function academia_list_niveles(PDO $pdo, int $academiaId): array
{
    $statement = $pdo->prepare('SELECT * FROM academia_niveles WHERE academia_id = :academia_id ORDER BY orden ASC, nombre ASC');
    $statement->execute(['academia_id' => $academiaId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_upsert_nivel(PDO $pdo, int $academiaId, array $data): void
{
    $pdo->prepare(
        'INSERT INTO academia_niveles (academia_id, nombre, orden, descripcion) VALUES (:academia_id, :nombre, :orden, :descripcion)'
    )->execute([
        'academia_id' => $academiaId,
        'nombre' => $data['nombre'],
        'orden' => $data['orden'] !== '' ? $data['orden'] : 0,
        'descripcion' => $data['descripcion'] ?: null,
    ]);
}

function academia_list_disciplinas(PDO $pdo, int $academiaId): array
{
    $statement = $pdo->prepare(
        'SELECT d.id, d.slug, d.nombre
         FROM academia_disciplinas ad
         INNER JOIN disciplinas d ON d.id = ad.disciplina_id
         WHERE ad.academia_id = :academia_id AND d.estado = "ACTIVA"
         ORDER BY d.nombre ASC'
    );
    $statement->execute(['academia_id' => $academiaId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_set_disciplinas(PDO $pdo, int $academiaId, array $disciplinaIds): void
{
    $pdo->prepare('DELETE FROM academia_disciplinas WHERE academia_id = :academia_id')
        ->execute(['academia_id' => $academiaId]);

    if (!$disciplinaIds) {
        return;
    }

    $statement = $pdo->prepare(
        'INSERT IGNORE INTO academia_disciplinas (academia_id, disciplina_id) VALUES (:academia_id, :disciplina_id)'
    );
    foreach ($disciplinaIds as $disciplinaId) {
        $statement->execute(['academia_id' => $academiaId, 'disciplina_id' => (int) $disciplinaId]);
    }
}

function academia_list_profesores(PDO $pdo, int $academiaId): array
{
    $statement = $pdo->prepare(
        'SELECT am.id AS academia_miembro_id, am.usuario_id, am.estado, am.fecha_alta,
                u.nombre, u.email,
                ap.especialidad, ap.biografia_docente
         FROM academia_miembros am
         INNER JOIN usuarios u ON u.id = am.usuario_id
         LEFT JOIN academia_profesores ap ON ap.academia_miembro_id = am.id
         WHERE am.academia_id = :academia_id AND am.rol = "PROFESOR"
         ORDER BY u.nombre ASC'
    );
    $statement->execute(['academia_id' => $academiaId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_add_profesor(PDO $pdo, int $academiaId, string $email, string $especialidad, int $actorUserId): array
{
    $userStatement = $pdo->prepare('SELECT id, nombre FROM usuarios WHERE email = :email LIMIT 1');
    $userStatement->execute(['email' => normalize_email($email)]);
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ['ok' => false, 'error' => 'No existe ninguna cuenta de Con Sabor Flamenco con ese email. Pide primero que se registre.'];
    }

    $insert = $pdo->prepare(
        'INSERT IGNORE INTO academia_miembros (academia_id, usuario_id, rol, estado, fecha_alta, created_by)
         VALUES (:academia_id, :usuario_id, "PROFESOR", "ACTIVO", CURRENT_DATE(), :creado_por)'
    );
    $insert->execute(['academia_id' => $academiaId, 'usuario_id' => (int) $user['id'], 'creado_por' => $actorUserId]);

    if ($insert->rowCount() === 0) {
        return ['ok' => false, 'error' => 'Esa persona ya es profesor de esta academia.'];
    }

    $academiaMiembroId = (int) $pdo->lastInsertId();

    $pdo->prepare(
        'INSERT INTO academia_profesores (academia_miembro_id, especialidad) VALUES (:academia_miembro_id, :especialidad)
         ON DUPLICATE KEY UPDATE especialidad = VALUES(especialidad)'
    )->execute(['academia_miembro_id' => $academiaMiembroId, 'especialidad' => $especialidad !== '' ? $especialidad : null]);

    return ['ok' => true];
}

function academia_set_profesor_estado(PDO $pdo, int $academiaId, int $academiaMiembroId, string $estado): void
{
    $pdo->prepare(
        'UPDATE academia_miembros SET estado = :estado, updated_at = UTC_TIMESTAMP()
         WHERE id = :id AND academia_id = :academia_id AND rol = "PROFESOR"'
    )->execute([
        'estado' => academia_enum($estado, academia_miembro_estados(), 'ACTIVO'),
        'id' => $academiaMiembroId,
        'academia_id' => $academiaId,
    ]);
}

function academia_list_alumnos(PDO $pdo, int $academiaId, string $search = ''): array
{
    $sql = 'SELECT * FROM academia_alumnos WHERE academia_id = :academia_id';
    $params = ['academia_id' => $academiaId];

    if ($search !== '') {
        $sql .= ' AND (nombre LIKE :search OR apellidos LIKE :search OR email LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY nombre ASC LIMIT 200';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_get_alumno(PDO $pdo, int $academiaId, int $alumnoId): ?array
{
    $statement = $pdo->prepare('SELECT * FROM academia_alumnos WHERE id = :id AND academia_id = :academia_id LIMIT 1');
    $statement->execute(['id' => $alumnoId, 'academia_id' => $academiaId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function academia_upsert_alumno(PDO $pdo, int $academiaId, ?int $alumnoId, array $data, int $actorUserId): int
{
    $esMenor = false;
    if (!empty($data['fecha_nacimiento'])) {
        $birth = DateTime::createFromFormat('Y-m-d', (string) $data['fecha_nacimiento']);
        if ($birth instanceof DateTime) {
            $age = $birth->diff(new DateTime())->y;
            $esMenor = $age < 18;
        }
    }

    $estado = academia_enum($data['estado'] ?? 'ACTIVO', academia_alumno_estados(), 'ACTIVO');

    if ($alumnoId !== null) {
        $pdo->prepare(
            'UPDATE academia_alumnos SET
                nombre = :nombre, apellidos = :apellidos, fecha_nacimiento = :fecha_nacimiento,
                email = :email, telefono = :telefono, es_menor_edad = :es_menor_edad, estado = :estado,
                notas_internas = :notas_internas, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND academia_id = :academia_id'
        )->execute([
            'nombre' => $data['nombre'],
            'apellidos' => $data['apellidos'],
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?: null,
            'email' => $data['email'] ?: null,
            'telefono' => $data['telefono'] ?: null,
            // (int) obligatorio: PDO manda `false` como cadena vacia y MySQL en
            // modo estricto rechaza '' para una columna BOOLEAN.
            'es_menor_edad' => (int) $esMenor,
            'estado' => $estado,
            'notas_internas' => $data['notas_internas'] ?: null,
            'id' => $alumnoId,
            'academia_id' => $academiaId,
        ]);

        academia_sync_alumno_usuario($pdo, $academiaId, $alumnoId, (string) ($data['email'] ?? ''), $actorUserId);

        return $alumnoId;
    }

    $pdo->prepare(
        'INSERT INTO academia_alumnos (
            academia_id, nombre, apellidos, fecha_nacimiento, email, telefono, es_menor_edad, estado, notas_internas, created_by
        ) VALUES (
            :academia_id, :nombre, :apellidos, :fecha_nacimiento, :email, :telefono, :es_menor_edad, :estado, :notas_internas, :creado_por
        )'
    )->execute([
        'academia_id' => $academiaId,
        'nombre' => $data['nombre'],
        'apellidos' => $data['apellidos'],
        'fecha_nacimiento' => $data['fecha_nacimiento'] ?: null,
        'email' => $data['email'] ?: null,
        'telefono' => $data['telefono'] ?: null,
        'es_menor_edad' => (int) $esMenor,
        'estado' => $estado,
        'notas_internas' => $data['notas_internas'] ?: null,
        'creado_por' => $actorUserId,
    ]);

    $nuevoId = (int) $pdo->lastInsertId();
    academia_sync_alumno_usuario($pdo, $academiaId, $nuevoId, (string) ($data['email'] ?? ''), $actorUserId);

    return $nuevoId;
}

/**
 * Enlaza la ficha de alumno con su cuenta de Con Sabor Flamenco, si existe.
 *
 * Sin esto el panel del alumno era inalcanzable: academia_require_alumno() busca
 * por `academia_alumnos.usuario_id` y esa columna no se rellenaba nunca, asi que
 * ningun alumno podia entrar jamas.
 *
 * El vinculo se deduce del email de la ficha. Si no hay cuenta con ese email, la
 * ficha se queda sin usuario (caso normal en menores que gestiona la academia).
 * Ademas se crea la fila de rol ALUMNO en `academia_miembros` para que el modelo
 * de roles siga siendo la unica fuente de verdad.
 */
function academia_sync_alumno_usuario(PDO $pdo, int $academiaId, int $alumnoId, string $email, int $actorUserId): void
{
    $email = normalize_email($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $lookup = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $lookup->execute(['email' => $email]);
    $usuarioId = $lookup->fetchColumn();

    if ($usuarioId === false) {
        return;
    }

    $usuarioId = (int) $usuarioId;

    // Una cuenta no puede quedar enlazada a dos fichas de la misma academia: la
    // segunda veria los datos de la primera al entrar en su panel.
    $ocupado = $pdo->prepare(
        'SELECT id FROM academia_alumnos
         WHERE academia_id = :academia_id AND usuario_id = :usuario_id AND id != :alumno_id LIMIT 1'
    );
    $ocupado->execute(['academia_id' => $academiaId, 'usuario_id' => $usuarioId, 'alumno_id' => $alumnoId]);
    if ($ocupado->fetchColumn() !== false) {
        return;
    }

    $pdo->prepare('UPDATE academia_alumnos SET usuario_id = :usuario_id WHERE id = :id AND academia_id = :academia_id')
        ->execute(['usuario_id' => $usuarioId, 'id' => $alumnoId, 'academia_id' => $academiaId]);

    $pdo->prepare(
        'INSERT IGNORE INTO academia_miembros (academia_id, usuario_id, rol, estado, fecha_alta, created_by)
         VALUES (:academia_id, :usuario_id, "ALUMNO", "ACTIVO", CURRENT_DATE(), :creado_por)'
    )->execute(['academia_id' => $academiaId, 'usuario_id' => $usuarioId, 'creado_por' => $actorUserId]);
}

function academia_list_cursos(PDO $pdo, int $academiaId, array $filters = []): array
{
    $sql = 'SELECT c.*, d.nombre AS disciplina_nombre, n.nombre AS nivel_nombre
            FROM academia_cursos c
            LEFT JOIN disciplinas d ON d.id = c.disciplina_id
            LEFT JOIN academia_niveles n ON n.id = c.nivel_id
            WHERE c.academia_id = :academia_id';
    $params = ['academia_id' => $academiaId];

    if (!empty($filters['estado'])) {
        $sql .= ' AND c.estado = :estado';
        $params['estado'] = $filters['estado'];
    }

    if (!empty($filters['disciplina_id'])) {
        $sql .= ' AND c.disciplina_id = :disciplina_id';
        $params['disciplina_id'] = (int) $filters['disciplina_id'];
    }

    $sql .= ' ORDER BY c.created_at DESC LIMIT 200';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $cursos = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (!$cursos) {
        return [];
    }

    $ids = array_column($cursos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $profesorStatement = $pdo->prepare(
        "SELECT cp.curso_id, u.nombre
         FROM academia_curso_profesores cp
         INNER JOIN academia_miembros am ON am.id = cp.academia_miembro_id
         INNER JOIN usuarios u ON u.id = am.usuario_id
         WHERE cp.curso_id IN ($placeholders)
         ORDER BY u.nombre ASC"
    );
    $profesorStatement->execute($ids);
    $profesoresPorCurso = [];
    foreach ($profesorStatement->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $profesoresPorCurso[(int) $fila['curso_id']][] = (string) $fila['nombre'];
    }

    foreach ($cursos as &$curso) {
        $curso['profesores'] = $profesoresPorCurso[(int) $curso['id']] ?? [];
    }
    unset($curso);

    return $cursos;
}

function academia_get_curso(PDO $pdo, int $academiaId, int $cursoId): ?array
{
    $statement = $pdo->prepare('SELECT * FROM academia_cursos WHERE id = :id AND academia_id = :academia_id LIMIT 1');
    $statement->execute(['id' => $cursoId, 'academia_id' => $academiaId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function academia_upsert_curso(PDO $pdo, int $academiaId, ?int $cursoId, array $data, int $actorUserId): int
{
    $params = [
        'academia_id' => $academiaId,
        'disciplina_id' => $data['disciplina_id'] ?: null,
        'nivel_id' => $data['nivel_id'] ?: null,
        'nombre' => $data['nombre'],
        'descripcion' => $data['descripcion'] ?: null,
        'modalidad' => academia_enum($data['modalidad'] ?? '', academia_curso_modalidades(), 'PRESENCIAL'),
        'fecha_inicio' => $data['fecha_inicio'] ?: null,
        'fecha_fin' => $data['fecha_fin'] ?: null,
        'precio' => $data['precio'] !== '' ? $data['precio'] : null,
        'tipo_cuota' => $data['tipo_cuota'] ?: null,
        'estado' => academia_enum($data['estado'] ?? '', academia_curso_estados(), 'BORRADOR'),
        'requisitos' => $data['requisitos'] ?: null,
        'material_necesario' => $data['material_necesario'] ?: null,
        'visible_publico' => !empty($data['visible_publico']) ? 1 : 0,
        'plazas_maximas' => $data['plazas_maximas'] !== '' ? $data['plazas_maximas'] : null,
        'periodo_matricula_inicio' => $data['periodo_matricula_inicio'] ?: null,
        'periodo_matricula_fin' => $data['periodo_matricula_fin'] ?: null,
    ];

    if ($cursoId !== null) {
        $params['id'] = $cursoId;
        $params['actor'] = $actorUserId;
        $pdo->prepare(
            'UPDATE academia_cursos SET
                disciplina_id = :disciplina_id, nivel_id = :nivel_id, nombre = :nombre, descripcion = :descripcion,
                modalidad = :modalidad, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, precio = :precio,
                tipo_cuota = :tipo_cuota, estado = :estado, requisitos = :requisitos,
                material_necesario = :material_necesario, visible_publico = :visible_publico,
                plazas_maximas = :plazas_maximas, periodo_matricula_inicio = :periodo_matricula_inicio,
                periodo_matricula_fin = :periodo_matricula_fin, updated_by = :actor, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND academia_id = :academia_id'
        )->execute($params);

        return $cursoId;
    }

    $params['creado_por'] = $actorUserId;
    $pdo->prepare(
        'INSERT INTO academia_cursos (
            academia_id, disciplina_id, nivel_id, nombre, descripcion, modalidad, fecha_inicio, fecha_fin, precio,
            tipo_cuota, estado, requisitos, material_necesario, visible_publico, plazas_maximas,
            periodo_matricula_inicio, periodo_matricula_fin, created_by
        ) VALUES (
            :academia_id, :disciplina_id, :nivel_id, :nombre, :descripcion, :modalidad, :fecha_inicio, :fecha_fin, :precio,
            :tipo_cuota, :estado, :requisitos, :material_necesario, :visible_publico, :plazas_maximas,
            :periodo_matricula_inicio, :periodo_matricula_fin, :creado_por
        )'
    )->execute($params);

    return (int) $pdo->lastInsertId();
}

function academia_curso_profesor_ids(PDO $pdo, int $cursoId): array
{
    $statement = $pdo->prepare('SELECT academia_miembro_id FROM academia_curso_profesores WHERE curso_id = :curso_id');
    $statement->execute(['curso_id' => $cursoId]);

    return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

function academia_set_curso_profesores(PDO $pdo, int $academiaId, int $cursoId, array $academiaMiembroIds): void
{
    // El DELETE va filtrado por academia: sin ese INNER JOIN, enviando el
    // curso_id de otra academia se le borraban sus asignaciones de profesorado.
    $pdo->prepare(
        'DELETE cp FROM academia_curso_profesores cp
         INNER JOIN academia_cursos c ON c.id = cp.curso_id
         WHERE cp.curso_id = :curso_id AND c.academia_id = :academia_id'
    )->execute(['curso_id' => $cursoId, 'academia_id' => $academiaId]);

    if (!$academiaMiembroIds) {
        return;
    }

    // El INSERT ... SELECT solo inserta si el profesor pertenece de verdad a esta
    // academia: impide asignar profesores de otra academia manipulando el formulario.
    $statement = $pdo->prepare(
        'INSERT IGNORE INTO academia_curso_profesores (curso_id, academia_miembro_id)
         SELECT :curso_id, am.id
         FROM academia_miembros am
         WHERE am.id = :academia_miembro_id AND am.academia_id = :academia_id AND am.rol = "PROFESOR"'
    );

    foreach ($academiaMiembroIds as $academiaMiembroId) {
        $statement->execute([
            'curso_id' => $cursoId,
            'academia_miembro_id' => (int) $academiaMiembroId,
            'academia_id' => $academiaId,
        ]);
    }
}

function academia_list_grupos(PDO $pdo, int $academiaId, ?int $cursoId = null): array
{
    $sql = 'SELECT g.*, c.nombre AS curso_nombre
            FROM academia_grupos g
            INNER JOIN academia_cursos c ON c.id = g.curso_id
            WHERE g.academia_id = :academia_id';
    $params = ['academia_id' => $academiaId];

    if ($cursoId !== null) {
        $sql .= ' AND g.curso_id = :curso_id';
        $params['curso_id'] = $cursoId;
    }

    $sql .= ' ORDER BY g.created_at DESC LIMIT 200';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $grupos = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (!$grupos) {
        return [];
    }

    $ids = array_column($grupos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $horarioStatement = $pdo->prepare(
        "SELECT * FROM academia_horarios_grupo WHERE grupo_id IN ($placeholders) ORDER BY dia_semana ASC, hora_inicio ASC"
    );
    $horarioStatement->execute($ids);
    $horarios = [];
    foreach ($horarioStatement->fetchAll(PDO::FETCH_ASSOC) as $horario) {
        $horarios[(int) $horario['grupo_id']][] = $horario;
    }

    foreach ($grupos as &$grupo) {
        $grupo['horarios'] = $horarios[(int) $grupo['id']] ?? [];
    }
    unset($grupo);

    return $grupos;
}

function academia_get_grupo(PDO $pdo, int $academiaId, int $grupoId): ?array
{
    $statement = $pdo->prepare('SELECT * FROM academia_grupos WHERE id = :id AND academia_id = :academia_id LIMIT 1');
    $statement->execute(['id' => $grupoId, 'academia_id' => $academiaId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Crea o actualiza un grupo. NO toca los horarios.
 *
 * Antes esta funcion borraba todos los horarios del grupo y reinsertaba como
 * mucho uno, asi que editar el nombre de un grupo con tres clases semanales se
 * llevaba por delante dos. El horario se gestiona aparte, con
 * academia_set_grupo_horarios().
 */
function academia_upsert_grupo(PDO $pdo, int $academiaId, ?int $grupoId, array $data): int
{
    if ($grupoId !== null) {
        $pdo->prepare(
            'UPDATE academia_grupos SET nombre = :nombre, aula_o_ubicacion = :aula, plazas_maximas = :plazas,
                estado = :estado, updated_at = UTC_TIMESTAMP()
             WHERE id = :id AND academia_id = :academia_id'
        )->execute([
            'nombre' => $data['nombre'],
            'aula' => $data['aula_o_ubicacion'] ?: null,
            'plazas' => $data['plazas_maximas'] !== '' ? $data['plazas_maximas'] : null,
            'estado' => academia_enum($data['estado'] ?? '', academia_grupo_estados(), 'ACTIVO'),
            'id' => $grupoId,
            'academia_id' => $academiaId,
        ]);

        return $grupoId;
    }

    $pdo->prepare(
        'INSERT INTO academia_grupos (curso_id, academia_id, nombre, aula_o_ubicacion, plazas_maximas, estado)
         VALUES (:curso_id, :academia_id, :nombre, :aula, :plazas, :estado)'
    )->execute([
        'curso_id' => $data['curso_id'],
        'academia_id' => $academiaId,
        'nombre' => $data['nombre'],
        'aula' => $data['aula_o_ubicacion'] ?: null,
        'plazas' => $data['plazas_maximas'] !== '' ? $data['plazas_maximas'] : null,
        'estado' => academia_enum($data['estado'] ?? '', academia_grupo_estados(), 'ACTIVO'),
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Reemplaza el horario semanal completo de un grupo.
 *
 * $horarios: lista de ['dia_semana' => int, 'hora_inicio' => 'HH:MM',
 * 'hora_fin' => 'HH:MM', 'aula' => string]. Las filas incompletas se ignoran.
 * Si la lista llega vacia, el grupo se queda sin horario (es una accion
 * explicita del formulario, no un efecto colateral de editar otro campo).
 */
function academia_set_grupo_horarios(PDO $pdo, int $academiaId, int $grupoId, array $horarios): int
{
    // El DELETE filtra por academia via subconsulta: aunque el grupo_id venga
    // manipulado, no se pueden borrar horarios de otra academia.
    $pdo->prepare(
        'DELETE h FROM academia_horarios_grupo h
         INNER JOIN academia_grupos g ON g.id = h.grupo_id
         WHERE h.grupo_id = :grupo_id AND g.academia_id = :academia_id'
    )->execute(['grupo_id' => $grupoId, 'academia_id' => $academiaId]);

    $insert = $pdo->prepare(
        'INSERT INTO academia_horarios_grupo (grupo_id, dia_semana, hora_inicio, hora_fin, aula)
         VALUES (:grupo_id, :dia_semana, :hora_inicio, :hora_fin, :aula)'
    );

    $guardados = 0;
    foreach ($horarios as $horario) {
        $inicio = academia_normalize_hora((string) ($horario['hora_inicio'] ?? ''));
        $fin = academia_normalize_hora((string) ($horario['hora_fin'] ?? ''));
        $dia = $horario['dia_semana'];

        if ($inicio === null || $fin === null || $dia === null || $dia === '' || $fin <= $inicio) {
            continue;
        }

        $insert->execute([
            'grupo_id' => $grupoId,
            'dia_semana' => (int) $dia,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'aula' => ($horario['aula'] ?? '') !== '' ? $horario['aula'] : null,
        ]);
        $guardados++;
    }

    return $guardados;
}

function academia_normalize_hora(string $valor): ?string
{
    $valor = trim($valor);

    return preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $valor) === 1 ? $valor : null;
}

function academia_list_horarios_grupo(PDO $pdo, int $grupoId): array
{
    $statement = $pdo->prepare(
        'SELECT * FROM academia_horarios_grupo WHERE grupo_id = :grupo_id ORDER BY dia_semana ASC, hora_inicio ASC'
    );
    $statement->execute(['grupo_id' => $grupoId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Plazas ocupadas y maximas de un grupo.
 *
 * Solo cuentan las matriculas que ocupan sitio de verdad: una cancelada o
 * rechazada no bloquea la plaza. `maximas` es null cuando el grupo no tiene
 * limite declarado.
 */
function academia_grupo_ocupacion(PDO $pdo, int $academiaId, int $grupoId): array
{
    $estados = academia_matricula_estados_ocupan_plaza();
    $placeholders = implode(',', array_fill(0, count($estados), '?'));

    $statement = $pdo->prepare(
        "SELECT g.plazas_maximas,
                (SELECT COUNT(*) FROM academia_matriculas mt
                  WHERE mt.grupo_id = g.id AND mt.estado IN ($placeholders)
                ) AS ocupadas
         FROM academia_grupos g
         WHERE g.id = ? AND g.academia_id = ? LIMIT 1"
    );
    $statement->execute([...$estados, $grupoId, $academiaId]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return ['ocupadas' => 0, 'maximas' => null, 'completo' => false];
    }

    $maximas = $row['plazas_maximas'] !== null ? (int) $row['plazas_maximas'] : null;
    $ocupadas = (int) $row['ocupadas'];

    return [
        'ocupadas' => $ocupadas,
        'maximas' => $maximas,
        'completo' => $maximas !== null && $ocupadas >= $maximas,
    ];
}

function academia_list_matriculas(PDO $pdo, int $academiaId, array $filters = []): array
{
    $sql = 'SELECT mt.*, al.nombre AS alumno_nombre, al.apellidos AS alumno_apellidos,
                   c.nombre AS curso_nombre, g.nombre AS grupo_nombre
            FROM academia_matriculas mt
            INNER JOIN academia_alumnos al ON al.id = mt.alumno_id
            INNER JOIN academia_cursos c ON c.id = mt.curso_id
            LEFT JOIN academia_grupos g ON g.id = mt.grupo_id
            WHERE mt.academia_id = :academia_id';
    $params = ['academia_id' => $academiaId];

    if (!empty($filters['estado'])) {
        $sql .= ' AND mt.estado = :estado';
        $params['estado'] = $filters['estado'];
    }

    if (!empty($filters['curso_id'])) {
        $sql .= ' AND mt.curso_id = :curso_id';
        $params['curso_id'] = (int) $filters['curso_id'];
    }

    $sql .= ' ORDER BY mt.created_at DESC LIMIT 200';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_create_matricula(PDO $pdo, int $academiaId, array $data, int $actorUserId): int
{
    $pdo->prepare(
        'INSERT INTO academia_matriculas (
            academia_id, alumno_id, curso_id, grupo_id, estado, fecha_alta, descuento_pct, observaciones_internas, created_by
        ) VALUES (
            :academia_id, :alumno_id, :curso_id, :grupo_id, :estado, CURRENT_DATE(), :descuento_pct, :observaciones, :creado_por
        )'
    )->execute([
        'academia_id' => $academiaId,
        'alumno_id' => $data['alumno_id'],
        'curso_id' => $data['curso_id'],
        'grupo_id' => $data['grupo_id'] ?: null,
        'estado' => academia_enum($data['estado'] ?? '', academia_matricula_estados(), 'SOLICITADA'),
        'descuento_pct' => $data['descuento_pct'] !== '' ? $data['descuento_pct'] : null,
        'observaciones' => $data['observaciones_internas'] ?: null,
        'creado_por' => $actorUserId,
    ]);

    return (int) $pdo->lastInsertId();
}

function academia_update_matricula_estado(PDO $pdo, int $academiaId, int $matriculaId, string $estado): void
{
    $estado = academia_enum($estado, academia_matricula_estados(), 'SOLICITADA');
    $fechaBaja = in_array($estado, ['FINALIZADA', 'CANCELADA', 'RECHAZADA'], true) ? 'CURRENT_DATE()' : 'NULL';

    $pdo->prepare(
        "UPDATE academia_matriculas SET estado = :estado, fecha_baja = {$fechaBaja}, updated_at = UTC_TIMESTAMP()
         WHERE id = :id AND academia_id = :academia_id"
    )->execute(['estado' => $estado, 'id' => $matriculaId, 'academia_id' => $academiaId]);
}

function academia_create_solicitud_info(PDO $pdo, int $academiaId, array $data): void
{
    $pdo->prepare(
        'INSERT INTO academia_solicitudes_info (academia_id, nombre, email, telefono, mensaje, disciplina_interes, ip_hash)
         VALUES (:academia_id, :nombre, :email, :telefono, :mensaje, :disciplina, :ip_hash)'
    )->execute([
        'academia_id' => $academiaId,
        'nombre' => $data['nombre'],
        'email' => $data['email'],
        'telefono' => $data['telefono'] ?: null,
        'mensaje' => $data['mensaje'] ?: null,
        'disciplina' => $data['disciplina_interes'] ?: null,
        'ip_hash' => $data['ip_hash'] ?? null,
    ]);
}

function academia_create_solicitud_matricula(PDO $pdo, int $academiaId, array $data): void
{
    $pdo->prepare(
        'INSERT INTO academia_solicitudes_matricula (
            academia_id, curso_id, nombre_alumno, fecha_nacimiento, nombre_tutor, email, telefono, mensaje
        ) VALUES (
            :academia_id, :curso_id, :nombre_alumno, :fecha_nacimiento, :nombre_tutor, :email, :telefono, :mensaje
        )'
    )->execute([
        'academia_id' => $academiaId,
        'curso_id' => $data['curso_id'] ?: null,
        'nombre_alumno' => $data['nombre_alumno'],
        'fecha_nacimiento' => $data['fecha_nacimiento'] ?: null,
        'nombre_tutor' => $data['nombre_tutor'] ?: null,
        'email' => $data['email'],
        'telefono' => $data['telefono'] ?: null,
        'mensaje' => $data['mensaje'] ?: null,
    ]);
}

function academia_list_alumnos_for_profesor(PDO $pdo, int $academiaMiembroId): array
{
    $statement = $pdo->prepare(
        'SELECT DISTINCT al.*
         FROM academia_alumnos al
         INNER JOIN academia_matriculas mt ON mt.alumno_id = al.id
         INNER JOIN academia_curso_profesores cp ON cp.curso_id = mt.curso_id
         WHERE cp.academia_miembro_id = :academia_miembro_id
         ORDER BY al.nombre ASC'
    );
    $statement->execute(['academia_miembro_id' => $academiaMiembroId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_list_cursos_for_profesor(PDO $pdo, int $academiaMiembroId): array
{
    $statement = $pdo->prepare(
        'SELECT c.*, d.nombre AS disciplina_nombre, n.nombre AS nivel_nombre
         FROM academia_cursos c
         INNER JOIN academia_curso_profesores cp ON cp.curso_id = c.id
         LEFT JOIN disciplinas d ON d.id = c.disciplina_id
         LEFT JOIN academia_niveles n ON n.id = c.nivel_id
         WHERE cp.academia_miembro_id = :academia_miembro_id
         ORDER BY c.created_at DESC'
    );
    $statement->execute(['academia_miembro_id' => $academiaMiembroId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_list_grupos_for_profesor(PDO $pdo, int $academiaMiembroId): array
{
    $statement = $pdo->prepare(
        'SELECT g.*, c.nombre AS curso_nombre
         FROM academia_grupos g
         INNER JOIN academia_cursos c ON c.id = g.curso_id
         INNER JOIN academia_curso_profesores cp ON cp.curso_id = c.id
         WHERE cp.academia_miembro_id = :academia_miembro_id
         ORDER BY g.created_at DESC'
    );
    $statement->execute(['academia_miembro_id' => $academiaMiembroId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_public_cursos(PDO $pdo, int $academiaId): array
{
    $statement = $pdo->prepare(
        'SELECT c.*, d.nombre AS disciplina_nombre, n.nombre AS nivel_nombre
         FROM academia_cursos c
         LEFT JOIN disciplinas d ON d.id = c.disciplina_id
         LEFT JOIN academia_niveles n ON n.id = c.nivel_id
         WHERE c.academia_id = :academia_id AND c.visible_publico = 1
            AND c.estado IN ("PUBLICADO", "MATRICULA_ABIERTA", "EN_CURSO")
         ORDER BY c.fecha_inicio ASC'
    );
    $statement->execute(['academia_id' => $academiaId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_public_profesores(PDO $pdo, int $academiaId): array
{
    $statement = $pdo->prepare(
        'SELECT u.nombre, ap.especialidad, ap.biografia_docente
         FROM academia_miembros am
         INNER JOIN usuarios u ON u.id = am.usuario_id
         LEFT JOIN academia_profesores ap ON ap.academia_miembro_id = am.id
         WHERE am.academia_id = :academia_id AND am.rol = "PROFESOR" AND am.estado = "ACTIVO"
         ORDER BY u.nombre ASC'
    );
    $statement->execute(['academia_id' => $academiaId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_list_matriculas_for_alumno(PDO $pdo, int $alumnoId): array
{
    $statement = $pdo->prepare(
        'SELECT mt.*, c.nombre AS curso_nombre, c.modalidad, g.nombre AS grupo_nombre
         FROM academia_matriculas mt
         INNER JOIN academia_cursos c ON c.id = mt.curso_id
         LEFT JOIN academia_grupos g ON g.id = mt.grupo_id
         WHERE mt.alumno_id = :alumno_id
         ORDER BY mt.created_at DESC'
    );
    $statement->execute(['alumno_id' => $alumnoId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_list_horarios_for_alumno(PDO $pdo, int $alumnoId): array
{
    $statement = $pdo->prepare(
        'SELECT h.*, c.nombre AS curso_nombre, g.nombre AS grupo_nombre
         FROM academia_horarios_grupo h
         INNER JOIN academia_grupos g ON g.id = h.grupo_id
         INNER JOIN academia_cursos c ON c.id = g.curso_id
         INNER JOIN academia_matriculas mt ON mt.grupo_id = g.id
         WHERE mt.alumno_id = :alumno_id AND mt.estado = "ACTIVA"
         ORDER BY h.dia_semana ASC, h.hora_inicio ASC'
    );
    $statement->execute(['alumno_id' => $alumnoId]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_update_alumno_contacto(PDO $pdo, int $alumnoId, array $data): void
{
    $pdo->prepare(
        'UPDATE academia_alumnos SET email = :email, telefono = :telefono, updated_at = UTC_TIMESTAMP() WHERE id = :id'
    )->execute([
        'email' => $data['email'] ?: null,
        'telefono' => $data['telefono'] ?: null,
        'id' => $alumnoId,
    ]);
}

function academia_dashboard_stats(PDO $pdo, int $academiaId): array
{
    $count = static function (PDO $pdo, string $sql, array $params) {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    };

    $params = ['academia_id' => $academiaId];

    return [
        'alumnos_activos' => $count($pdo, 'SELECT COUNT(*) FROM academia_alumnos WHERE academia_id = :academia_id AND estado = "ACTIVO"', $params),
        'profesores_activos' => $count($pdo, 'SELECT COUNT(*) FROM academia_miembros WHERE academia_id = :academia_id AND rol = "PROFESOR" AND estado = "ACTIVO"', $params),
        'cursos_activos' => $count($pdo, 'SELECT COUNT(*) FROM academia_cursos WHERE academia_id = :academia_id AND estado IN ("PUBLICADO","MATRICULA_ABIERTA","EN_CURSO")', $params),
        'matriculas_pendientes' => $count($pdo, 'SELECT COUNT(*) FROM academia_matriculas WHERE academia_id = :academia_id AND estado IN ("SOLICITADA","PENDIENTE_DOCUMENTACION","PENDIENTE_PAGO")', $params),
        'solicitudes_info_nuevas' => $count($pdo, 'SELECT COUNT(*) FROM academia_solicitudes_info WHERE academia_id = :academia_id AND estado = "NUEVA"', $params),
        'solicitudes_matricula_nuevas' => $count($pdo, 'SELECT COUNT(*) FROM academia_solicitudes_matricula WHERE academia_id = :academia_id AND estado = "NUEVA"', $params),
    ];
}

function academia_admin_estados(): array
{
    return [
        'PENDIENTE' => 'Pendiente',
        'ACTIVA' => 'Activa',
        'SUSPENDIDA' => 'Suspendida',
        'BAJA' => 'Baja',
    ];
}

/**
 * Numero de academias por estado, para las tarjetas de resumen del panel.
 * Devuelve siempre las cuatro claves, aunque alguna este a cero.
 */
function academia_admin_estado_counts(PDO $pdo): array
{
    $counts = array_fill_keys(array_keys(academia_admin_estados()), 0);
    $counts['TOTAL'] = 0;

    foreach ($pdo->query('SELECT estado, COUNT(*) AS total FROM academias GROUP BY estado') as $row) {
        $estado = (string) $row['estado'];
        $total = (int) $row['total'];
        if (array_key_exists($estado, $counts)) {
            $counts[$estado] = $total;
        }
        $counts['TOTAL'] += $total;
    }

    return $counts;
}

function academia_admin_list(PDO $pdo, array $filters = []): array
{
    // Los contadores van como subconsulta y no como JOIN + GROUP BY: con dos
    // LEFT JOIN a la vez los totales se multiplican entre si.
    $sql = 'SELECT a.miembro_id, a.estado, a.plan, a.created_at, m.nombre_publico, m.slug, m.ciudad, m.provincia_texto,
                   u.nombre AS responsable_nombre, u.email AS responsable_email,
                   (SELECT COUNT(*) FROM academia_alumnos al WHERE al.academia_id = a.miembro_id AND al.estado = "ACTIVO") AS alumnos_activos,
                   (SELECT COUNT(*) FROM academia_cursos c WHERE c.academia_id = a.miembro_id) AS cursos_totales,
                   (SELECT COUNT(*) FROM academia_miembros p WHERE p.academia_id = a.miembro_id AND p.rol = "PROFESOR" AND p.estado = "ACTIVO") AS profesores_activos,
                   (SELECT COUNT(*) FROM academia_solicitudes_info si WHERE si.academia_id = a.miembro_id AND si.estado = "NUEVA") AS solicitudes_nuevas
            FROM academias a
            INNER JOIN miembros m ON m.id = a.miembro_id
            LEFT JOIN academia_miembros am ON am.academia_id = a.miembro_id AND am.rol = "RESPONSABLE"
            LEFT JOIN usuarios u ON u.id = am.usuario_id';
    $params = [];

    $estado = academia_enum_filter($filters['estado'] ?? '', academia_admin_estados());
    if ($estado !== '') {
        $sql .= ' WHERE a.estado = :estado';
        $params['estado'] = $estado;
    }

    $sql .= ' ORDER BY FIELD(a.estado, "PENDIENTE") DESC, a.created_at DESC LIMIT 200';

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function academia_admin_set_estado(PDO $pdo, int $academiaId, string $estado, int $actorUserId): void
{
    $pdo->prepare('UPDATE academias SET estado = :estado, updated_by = :actor, updated_at = UTC_TIMESTAMP() WHERE miembro_id = :id')
        ->execute(['estado' => $estado, 'actor' => $actorUserId, 'id' => $academiaId]);
}
