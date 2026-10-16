
<?php
session_start();

// Verificar que exista una sesión iniciada
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/conexion.php";

// Verificar que el usuario siga existiendo
// y tenga permisos para administrar incidencias
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

// Crear token de seguridad para el formulario
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Validar el ID de la incidencia
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Consultar la incidencia
$stmt = $conn->prepare(
    "SELECT id, equipo, laboratorio, estado
     FROM incidencias
     WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$incidencia = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$incidencia) {
    header("Location: index.php");
    exit;
}

$error = "";

$estados_validos = [
    "Pendiente",
    "En proceso",
    "Resuelta"
];

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $estado = $_POST["estado"] ?? "";
    $token = $_POST["csrf_token"] ?? "";

    // Validar token de seguridad
    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {

        $error = "Solicitud no válida. Actualiza la página.";

    } elseif (!in_array($estado, $estados_validos, true)) {

        $error = "Selecciona un estado válido.";

    } else {

        // Actualizar estado
        $stmt = $conn->prepare(
            "UPDATE incidencias
             SET estado = ?
             WHERE id = ?"
        );

        $stmt->bind_param("si", $estado, $id);

        if ($stmt->execute()) {

            $stmt->close();

            header("Location: index.php?estado=actualizado");
            exit;
        }

        $error = "No se pudo actualizar el estado.";

        $stmt->close();
    }
}

// Evitar que se interprete HTML en los datos
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

    <title>Cambiar estado | Soporte TI</title>

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
                <i class="fa-solid fa-arrows-rotate"></i>
                Cambiar estado
            </a>

            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>

        </nav>

        <div class="sidebar-footer">
            Centro de Soporte Tecnológico
        </div>

    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="dashboard-main">

        <!-- ENCABEZADO -->
        <header class="dashboard-topbar">

            <div>

                <span class="topbar-etiqueta">
                    SEGUIMIENTO DE INCIDENCIAS
                </span>

                <h1>Cambiar estado del ticket</h1>

                <p>
                    Actualiza el progreso de una incidencia.
                </p>

            </div>

            <a href="index.php" class="btn-limpiar">
                Volver
            </a>

        </header>

        <!-- FORMULARIO -->
        <section class="tarjeta editar-tarjeta">

            <h2>
                <i class="fa-solid fa-arrows-rotate"></i>
                Ticket #<?= (int) $incidencia["id"] ?>
            </h2>

            <p class="subtitulo">
                Equipo: <?= escapar($incidencia["equipo"]) ?>
            </p>

            <p class="subtitulo">
                Laboratorio:
                <?= escapar($incidencia["laboratorio"]) ?>
            </p>

            <!-- MOSTRAR ERRORES -->
            <?php if ($error !== ""): ?>

                <div class="mensaje error">
                    <?= escapar($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST" class="editar-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($_SESSION["csrf_token"]) ?>"
                >

                <div class="campo-form">

                    <label for="estado">
                        Selecciona el nuevo estado
                    </label>

                    <select
                        id="estado"
                        name="estado"
                        required
                    >

                        <?php foreach ($estados_validos as $e): ?>

                            <option
                                value="<?= escapar($e) ?>"
                                <?= $incidencia["estado"] === $e
                                    ? "selected" : "" ?>
                            >
                                <?= escapar($e) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

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
                        Guardar estado
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>

<?php
$conn->close();
?>
