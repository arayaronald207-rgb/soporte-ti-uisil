
<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/conexion.php";

function escaparUsuario($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// Comprobar que el usuario sea administrador
$usuario_id = (int)$_SESSION["admin_id"];

$consulta_rol = $conn->prepare(
    "SELECT usuario, rol FROM usuarios WHERE id = ? LIMIT 1"
);
$consulta_rol->bind_param("i", $usuario_id);
$consulta_rol->execute();
$usuario_actual = $consulta_rol->get_result()->fetch_assoc();
$consulta_rol->close();

if (!$usuario_actual) {
    $_SESSION = [];
    session_destroy();
    header("Location: ../login.php");
    exit;
}

if ($usuario_actual["rol"] !== "admin") {
    http_response_code(403);
    exit("Acceso denegado. No tienes permisos para gestionar usuarios.");
}

$_SESSION["admin_usuario"] = $usuario_actual["usuario"];
$_SESSION["admin_rol"] = $usuario_actual["rol"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$usuario_ingresado = "";
$accion_actual = "";
$editar_id = 0;
$editar_usuario = "";
$editar_rol = "tecnico";

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "crear";
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {

        $error = "Solicitud no válida. Actualiza la página.";

    } elseif ($accion === "crear") {

        $usuario_ingresado = trim($_POST["usuario"] ?? "");
        $contrasena = $_POST["contrasena"] ?? "";
        $confirmar = $_POST["confirmar"] ?? "";

        if (
            $usuario_ingresado === "" ||
            $contrasena === "" ||
            $confirmar === ""
        ) {
            $error = "Completa todos los campos.";

        } elseif (
            strlen($usuario_ingresado) < 3 ||
            strlen($usuario_ingresado) > 50
        ) {
            $error = "El usuario debe tener entre 3 y 50 caracteres.";

        } elseif (
            !preg_match('/^[a-zA-Z0-9._-]+$/', $usuario_ingresado)
        ) {
            $error = "El usuario solo puede contener letras, números, puntos, guiones y guiones bajos.";

        } elseif (strlen($contrasena) < 8) {
            $error = "La contraseña debe tener al menos 8 caracteres.";

        } elseif ($contrasena !== $confirmar) {
            $error = "Las contraseñas no coinciden.";

        } else {

            $consulta = $conn->prepare(
                "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1"
            );
            $consulta->bind_param("s", $usuario_ingresado);
            $consulta->execute();
            $existe = $consulta->get_result()->fetch_assoc();
            $consulta->close();

            if ($existe) {
                $error = "Ese nombre de usuario ya está registrado.";
            } else {

                $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                $insertar = $conn->prepare(
                    "INSERT INTO usuarios (usuario, contrasena, rol)
                     VALUES (?, ?, 'tecnico')"
                );
                $insertar->bind_param("ss", $usuario_ingresado, $hash);

                if ($insertar->execute()) {
                    $insertar->close();
                    header("Location: usuarios.php?creado=1");
                    exit;
                }

                $error = "No se pudo registrar el usuario.";
                $insertar->close();
            }
        }

    } elseif ($accion === "editar") {

        $accion_actual = "editar";
        $editar_id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $editar_usuario = trim($_POST["usuario"] ?? "");
        $editar_rol = $_POST["rol"] ?? "";
        $contrasena = $_POST["contrasena"] ?? "";
        $confirmar = $_POST["confirmar"] ?? "";

        if (!$editar_id || $editar_id <= 0) {
            $error = "Selecciona un usuario válido.";

        } elseif (
            strlen($editar_usuario) < 3 ||
            strlen($editar_usuario) > 50 ||
            !preg_match('/^[a-zA-Z0-9._-]+$/', $editar_usuario)
        ) {
            $error = "El nombre de usuario debe tener entre 3 y 50 caracteres y solo utilizar letras, números, puntos o guiones.";

        } elseif (!in_array($editar_rol, ["admin", "tecnico"], true)) {
            $error = "Selecciona un rol válido.";

        } elseif ($editar_id === $usuario_id &&
                  $editar_rol !== "admin") {
            $error = "No puedes quitarte tu propio rol de administrador.";

        } elseif ($contrasena !== "" && strlen($contrasena) < 8) {
            $error = "La nueva contraseña debe tener al menos 8 caracteres.";

        } elseif ($contrasena !== $confirmar) {
            $error = "Las contraseñas no coinciden.";

        } else {

            $consulta = $conn->prepare(
                "SELECT id FROM usuarios WHERE id = ? LIMIT 1"
            );
            $consulta->bind_param("i", $editar_id);
            $consulta->execute();
            $existe_id = $consulta->get_result()->fetch_assoc();
            $consulta->close();

            if (!$existe_id) {
                $error = "El usuario que deseas editar ya no existe.";
            } else {

                $consulta = $conn->prepare(
                    "SELECT id FROM usuarios
                     WHERE usuario = ? AND id <> ? LIMIT 1"
                );
                $consulta->bind_param(
                    "si",
                    $editar_usuario,
                    $editar_id
                );
                $consulta->execute();
                $duplicado = $consulta->get_result()->fetch_assoc();
                $consulta->close();

                if ($duplicado) {
                    $error = "Ese nombre de usuario ya está en uso.";
                } else {

                    if ($contrasena !== "") {

                        $hash = password_hash(
                            $contrasena,
                            PASSWORD_DEFAULT
                        );

                        $actualizar = $conn->prepare(
                            "UPDATE usuarios
                             SET usuario = ?, rol = ?, contrasena = ?
                             WHERE id = ?"
                        );
                        $actualizar->bind_param(
                            "sssi",
                            $editar_usuario,
                            $editar_rol,
                            $hash,
                            $editar_id
                        );

                    } else {

                        $actualizar = $conn->prepare(
                            "UPDATE usuarios
                             SET usuario = ?, rol = ?
                             WHERE id = ?"
                        );
                        $actualizar->bind_param(
                            "ssi",
                            $editar_usuario,
                            $editar_rol,
                            $editar_id
                        );
                    }

                    if ($actualizar->execute()) {
                        $actualizar->close();

                        if ($editar_id === $usuario_id) {
                            $_SESSION["admin_usuario"] = $editar_usuario;
                        }

                        header("Location: usuarios.php?editado=1");
                        exit;
                    }

                    $error = "No se pudo actualizar el usuario.";
                    $actualizar->close();
                }
            }
        }

    } elseif ($accion === "eliminar") {

        $eliminar_id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$eliminar_id || $eliminar_id <= 0) {
            $error = "Selecciona un usuario válido.";

        } elseif ($eliminar_id === $usuario_id) {
            $error = "No puedes eliminar tu propia cuenta.";

        } else {

            $consulta = $conn->prepare(
                "SELECT id FROM usuarios WHERE id = ? LIMIT 1"
            );
            $consulta->bind_param("i", $eliminar_id);
            $consulta->execute();
            $existe = $consulta->get_result()->fetch_assoc();
            $consulta->close();

            if (!$existe) {
                $error = "El usuario que deseas eliminar ya no existe.";
            } else {

                $eliminar = $conn->prepare(
                    "DELETE FROM usuarios WHERE id = ?"
                );
                $eliminar->bind_param("i", $eliminar_id);

                if ($eliminar->execute()) {
                    $eliminar->close();
                    header("Location: usuarios.php?eliminado=1");
                    exit;
                }

                $error = "No se pudo eliminar el usuario.";
                $eliminar->close();
            }
        }

    } else {
        $error = "Operación no válida.";
    }
}

// Cargar los datos del usuario que se desea editar
if ($_SERVER["REQUEST_METHOD"] !== "POST" &&
    isset($_GET["editar"])) {

    $editar_id = filter_var(
        $_GET["editar"],
        FILTER_VALIDATE_INT
    );

    if ($editar_id && $editar_id > 0) {

        $consulta = $conn->prepare(
            "SELECT id, usuario, rol
             FROM usuarios WHERE id = ? LIMIT 1"
        );
        $consulta->bind_param("i", $editar_id);
        $consulta->execute();
        $datos_edicion = $consulta->get_result()->fetch_assoc();
        $consulta->close();

        if ($datos_edicion) {
            $accion_actual = "editar";
            $editar_usuario = $datos_edicion["usuario"];
            $editar_rol = $datos_edicion["rol"];
        } else {
            $error = "No se encontró el usuario seleccionado.";
        }
    }
}

// Consultar usuarios registrados
$usuarios = $conn->query(
    "SELECT id, usuario, rol
     FROM usuarios
     ORDER BY id ASC"
);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Gestión de usuarios | Soporte TI</title>

    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        .usuarios-acciones {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .usuarios-btn-editar,
        .usuarios-btn-eliminar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: 0.2s;
        }

        .usuarios-btn-editar {
            background: #e8f0fb;
            color: #173b68;
        }

        .usuarios-btn-editar:hover {
            background: #d7e6f9;
        }

        .usuarios-btn-eliminar {
            background: #fff0f0;
            color: #b42332;
        }

        .usuarios-btn-eliminar:hover {
            background: #ffe0e0;
        }

        .usuarios-btn-eliminar:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .usuarios-btn-cancelar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            margin-top: 10px;
            border-radius: 9px;
            text-decoration: none;
            background: #edf1f5;
            color: #344054;
            font-weight: 600;
        }

        .usuarios-btn-cancelar:hover {
            background: #dfe6ed;
        }

        .usuarios-form select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d6dce5;
            border-radius: 9px;
            background: #fff;
            color: #17324d;
            font: inherit;
        }

        .usuarios-form select:focus {
            outline: none;
            border-color: #32669b;
        }

        .usuarios-ayuda {
            font-size: 12px;
            color: #667085;
            margin: 2px 0 12px;
        }

        .usuarios-propio {
            font-size: 11px;
            color: #667085;
            white-space: nowrap;
        }

        .usuarios-tabla th:last-child,
        .usuarios-tabla td:last-child {
            min-width: 170px;
        }

        .usuarios-form-eliminar {
            margin: 0;
        }
    </style>
</head>

<body class="pagina-dashboard">

<div class="dashboard-layout">

    <!-- MENÚ LATERAL -->
    <aside class="sidebar">

        <div class="sidebar-logo">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Soporte TI</span>
        </div>

        <div class="sidebar-seccion">
            ADMINISTRACIÓN
        </div>

        <nav class="sidebar-nav">

            <a href="index.php">
                <i class="fa-solid fa-gauge"></i>
                Panel administrativo
            </a>

            <a href="../index.php">
                <i class="fa-solid fa-house"></i>
                Página principal
            </a>

            <a href="../incidencias.php">
                <i class="fa-solid fa-ticket"></i>
                Ver incidencias
            </a>

            <a href="../registrar.php">
                <i class="fa-solid fa-plus"></i>
                Registrar ticket
            </a>

            <a href="usuarios.php" class="activo">
                <i class="fa-solid fa-users-gear"></i>
                Gestión de usuarios
            </a>

            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>

        </nav>

        <div class="sidebar-footer">
            <i class="fa-solid fa-user-shield"></i>
            Administrador
        </div>

    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="dashboard-main">

        <header class="dashboard-topbar">

            <div>
                <span class="topbar-etiqueta">
                    ÁREA ADMINISTRATIVA
                </span>

                <h1>Gestión de usuarios</h1>

                <p>
                    Registra, consulta, edita y elimina
                    las cuentas del sistema.
                </p>
            </div>

        </header>

        <!-- MENSAJES DE ÉXITO -->
        <?php if (isset($_GET["creado"])): ?>
            <div class="admin-mensaje-exito">
                <i class="fa-solid fa-circle-check"></i>
                El técnico fue registrado correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["editado"])): ?>
            <div class="admin-mensaje-exito">
                <i class="fa-solid fa-circle-check"></i>
                El usuario fue actualizado correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["eliminado"])): ?>
            <div class="admin-mensaje-exito">
                <i class="fa-solid fa-circle-check"></i>
                El usuario fue eliminado correctamente.
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="usuarios-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= escaparUsuario($error) ?>
            </div>
        <?php endif; ?>

        <section class="usuarios-grid">

            <!-- FORMULARIO DE REGISTRO O EDICIÓN -->
            <div class="usuarios-card">

                <div class="usuarios-card-titulo">

                    <h2>
                        <?php if ($accion_actual === "editar"): ?>
                            <i class="fa-solid fa-user-pen"></i>
                            Editar usuario
                        <?php else: ?>
                            <i class="fa-solid fa-user-plus"></i>
                            Registrar técnico
                        <?php endif; ?>
                    </h2>

                    <p>
                        <?php if ($accion_actual === "editar"): ?>
                            Modifica los datos de la cuenta seleccionada.
                        <?php else: ?>
                            Crea una cuenta con acceso a la
                            administración de incidencias.
                        <?php endif; ?>
                    </p>

                </div>

                <form method="POST" class="usuarios-form">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escaparUsuario($_SESSION["csrf_token"]) ?>"
                    >

                    <input
                        type="hidden"
                        name="accion"
                        value="<?= $accion_actual === "editar" ? "editar" : "crear" ?>"
                    >

                    <?php if ($accion_actual === "editar"): ?>
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$editar_id ?>"
                        >
                    <?php endif; ?>

                    <label for="usuario">
                        Nombre de usuario
                    </label>

                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        placeholder="Ejemplo: soporte01"
                        value="<?= escaparUsuario(
                            $accion_actual === "editar"
                                ? $editar_usuario
                                : $usuario_ingresado
                        ) ?>"
                        maxlength="50"
                        minlength="3"
                        autocomplete="off"
                        required
                    >

                    <?php if ($accion_actual === "editar"): ?>

                        <label for="rol">
                            Rol
                        </label>

                        <select id="rol" name="rol" required>

                            <option
                                value="tecnico"
                                <?= $editar_rol === "tecnico"
                                    ? "selected" : "" ?>
                            >
                                Técnico
                            </option>

                            <option
                                value="admin"
                                <?= $editar_rol === "admin"
                                    ? "selected" : "" ?>
                            >
                                Administrador
                            </option>

                        </select>

                        <p class="usuarios-ayuda">
                            Solo los administradores pueden
                            gestionar cuentas de usuarios.
                        </p>

                    <?php endif; ?>

                    <label for="contrasena">
                        <?= $accion_actual === "editar"
                            ? "Nueva contraseña (opcional)"
                            : "Contraseña" ?>
                    </label>

                    <input
                        type="password"
                        id="contrasena"
                        name="contrasena"
                        placeholder="<?= $accion_actual === "editar"
                            ? "Dejar vacío para conservarla"
                            : "Mínimo 8 caracteres" ?>"
                        minlength="8"
                        autocomplete="new-password"
                        <?= $accion_actual === "editar"
                            ? "" : "required" ?>
                    >

                    <label for="confirmar">
                        Confirmar contraseña
                    </label>

                    <input
                        type="password"
                        id="confirmar"
                        name="confirmar"
                        placeholder="Repite la contraseña"
                        minlength="8"
                        autocomplete="new-password"
                        <?= $accion_actual === "editar"
                            ? "" : "required" ?>
                    >

                    <button type="submit">
                        <?php if ($accion_actual === "editar"): ?>
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar cambios
                        <?php else: ?>
                            <i class="fa-solid fa-user-plus"></i>
                            Registrar técnico
                        <?php endif; ?>
                    </button>

                    <?php if ($accion_actual === "editar"): ?>
                        <a href="usuarios.php"
                           class="usuarios-btn-cancelar">
                            Cancelar edición
                        </a>
                    <?php endif; ?>

                </form>

            </div>

            <!-- TABLA DE USUARIOS -->
            <div class="usuarios-card">

                <div class="usuarios-card-titulo">

                    <h2>
                        <i class="fa-solid fa-users"></i>
                        Usuarios registrados
                    </h2>

                    <p>
                        Cuentas que pueden ingresar al sistema.
                    </p>

                </div>

                <div class="usuarios-tabla-scroll">

                    <table class="usuarios-tabla">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($fila = $usuarios->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        #<?= (int)$fila["id"] ?>
                                    </td>

                                    <td>
                                        <i class="fa-solid fa-circle-user"></i>
                                        <?= escaparUsuario($fila["usuario"]) ?>

                                        <?php if (
                                            (int)$fila["id"] === $usuario_id
                                        ): ?>
                                            <span class="usuarios-propio">
                                                (Tu cuenta)
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="usuarios-estado">
                                            <?= $fila["rol"] === "admin"
                                                ? "Administrador"
                                                : "Técnico" ?>
                                        </span>
                                    </td>

                                    <td>

                                        <div class="usuarios-acciones">

                                            <a
                                                href="usuarios.php?editar=<?= (int)$fila["id"] ?>"
                                                class="usuarios-btn-editar"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                                Editar
                                            </a>

                                            <form
                                                method="POST"
                                                class="usuarios-form-eliminar"
                                                onsubmit="return confirm('¿Seguro que deseas eliminar este usuario? Esta acción no se puede deshacer.');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= escaparUsuario($_SESSION["csrf_token"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="eliminar"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$fila["id"] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="usuarios-btn-eliminar"
                                                    <?= (int)$fila["id"] === $usuario_id
                                                        ? "disabled" : "" ?>
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                    Eliminar
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

                <p class="usuarios-nota">
                    Solo los administradores pueden gestionar
                    usuarios. Las contraseñas se almacenan
                    de forma segura y no se muestran en la lista.
                </p>

            </div>

        </section>

    </main>

</div>

</body>
</html>

<?php
$conn->close();
?>
