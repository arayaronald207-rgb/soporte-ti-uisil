
<?php
session_start();

// Verificar que exista una sesión iniciada
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/conexion.php";

// Comprobar que la cuenta siga existiendo
$usuario_id = (int) $_SESSION["admin_id"];

$stmt_usuario = $conn->prepare(
    "SELECT id, rol FROM usuarios WHERE id = ? LIMIT 1"
);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();

$usuario_actual = $stmt_usuario->get_result()->fetch_assoc();
$stmt_usuario->close();

if (
    !$usuario_actual ||
    !in_array($usuario_actual["rol"], ["admin", "tecnico"], true)
) {
    $_SESSION = [];
    session_destroy();

    header("Location: ../login.php");
    exit;
}

// Crear token CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Validar ID del ticket
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Consultar la incidencia
$sql = "SELECT * FROM incidencias WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$incidencia = $resultado->fetch_assoc();

$stmt->close();

if (!$incidencia) {
    header("Location: index.php");
    exit;
}

$error = "";

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $laboratorio = trim($_POST["laboratorio"] ?? "");
    $equipo = trim($_POST["equipo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $prioridad = $_POST["prioridad"] ?? "";

    $prioridades_validas = ["Baja", "Media", "Alta"];

    $token = $_POST["csrf_token"] ?? "";

    $token_valido = is_string($token) &&
        hash_equals($_SESSION["csrf_token"], $token);

    // Mantener los valores escritos si ocurre un error
    $incidencia["reportado_por"] = $nombre;
    $incidencia["laboratorio"] = $laboratorio;
    $incidencia["equipo"] = $equipo;
    $incidencia["descripcion"] = $descripcion;
    $incidencia["prioridad"] = $prioridad;

    // Validaciones
    if (!$token_valido) {

        $error = "Solicitud no válida. Actualiza la página e inténtalo de nuevo.";

    } elseif (
        $nombre === "" ||
        $laboratorio === "" ||
        $equipo === "" ||
        $descripcion === "" ||
        !in_array($prioridad, $prioridades_validas, true)
    ) {

        $error = "Completa correctamente todos los campos.";

    } elseif (
        mb_strlen($nombre) > 100 ||
        mb_strlen($laboratorio) > 100 ||
        mb_strlen($equipo) > 100 ||
        mb_strlen($descripcion) > 3000
    ) {

        $error = "Uno de los campos supera la cantidad máxima de caracteres.";

    } else {

        // Actualizar la incidencia
        $sql = "UPDATE incidencias
                SET reportado_por = ?,
                    laboratorio = ?,
                    equipo = ?,
                    descripcion = ?,
                    prioridad = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssssi",
            $nombre,
            $laboratorio,
            $equipo,
            $descripcion,
            $prioridad,
            $id
        );

        if ($stmt->execute()) {

            $stmt->close();

            header("Location: index.php?actualizado=1");
            exit;

        } else {

            $error = "No se pudo actualizar la incidencia.";
        }

        $stmt->close();
    }
}

// Escapar contenido HTML
function escapar($valor) {
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Editar ticket | Soporte TI</title>

    <link rel="stylesheet" href="../css/estilos.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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

            <a href="index.php" class="activo">
                <i class="fa-solid fa-pen"></i>
                Editar incidencia
            </a>

            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>

        </nav>

        <div class="sidebar-footer">
            <i class="fa-solid fa-headset"></i>
            Centro de Soporte Tecnológico
        </div>

    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="dashboard-main">

        <header class="dashboard-topbar">

            <div>

                <span class="topbar-etiqueta">
                    ADMINISTRACIÓN DE INCIDENCIAS
                </span>

                <h1>
                    Editar ticket #<?= (int) $id ?>
                </h1>

                <p>
                    Modifica la información de la incidencia.
                </p>

            </div>

            <a href="index.php" class="btn-limpiar">
                <i class="fa-solid fa-arrow-left"></i>
                Volver
            </a>

        </header>

        <section class="tarjeta editar-tarjeta">

            <h2>
                <i class="fa-solid fa-pen-to-square"></i>
                Información del ticket
            </h2>

            <p class="subtitulo">
                Actualiza los datos y guarda los cambios.
            </p>

            <!-- MOSTRAR ERRORES -->
            <?php if ($error !== ""): ?>

                <div class="mensaje error">
                    <?= escapar($error) ?>
                </div>

            <?php endif; ?>

            <!-- FORMULARIO -->
            <form method="POST" class="editar-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($_SESSION["csrf_token"]) ?>"
                >

                <div class="editar-grid">

                    <!-- NOMBRE -->
                    <div class="campo-form">

                        <label for="nombre">
                            Nombre de quien reporta
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            maxlength="100"
                            value="<?= escapar($incidencia["reportado_por"]) ?>"
                            required
                        >

                    </div>

                    <!-- LABORATORIO -->
                    <div class="campo-form">

                        <label for="laboratorio">
                            Laboratorio
                        </label>

                        <input
                            type="text"
                            id="laboratorio"
                            name="laboratorio"
                            maxlength="100"
                            value="<?= escapar($incidencia["laboratorio"]) ?>"
                            required
                        >

                    </div>

                    <!-- EQUIPO -->
                    <div class="campo-form">

                        <label for="equipo">
                            Equipo afectado
                        </label>

                        <input
                            type="text"
                            id="equipo"
                            name="equipo"
                            maxlength="100"
                            value="<?= escapar($incidencia["equipo"]) ?>"
                            required
                        >

                    </div>

                    <!-- PRIORIDAD -->
                    <div class="campo-form">

                        <label for="prioridad">
                            Prioridad
                        </label>

                        <select
                            id="prioridad"
                            name="prioridad"
                            required
                        >

                            <?php foreach (["Baja", "Media", "Alta"] as $p): ?>

                                <option
                                    value="<?= escapar($p) ?>"
                                    <?= $incidencia["prioridad"] === $p
                                        ? "selected" : "" ?>
                                >
                                    <?= escapar($p) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

                <!-- DESCRIPCIÓN -->
                <div class="campo-form">

                    <label for="descripcion">
                        Descripción del problema
                    </label>

                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="5"
                        maxlength="3000"
                        required
                    ><?= escapar($incidencia["descripcion"]) ?></textarea>

                </div>

                <!-- BOTONES -->
                <div class="editar-acciones">

                    <a href="index.php" class="btn-limpiar">
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn-nuevo"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar cambios
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>
