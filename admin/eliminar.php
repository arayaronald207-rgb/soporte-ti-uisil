
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

// Obtener y validar el ID del ticket
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Consultar el ticket
$stmt = $conn->prepare(
    "SELECT id, reportado_por, laboratorio, equipo
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

// Eliminar solamente al confirmar mediante POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    // Validar token CSRF
    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {

        $error = "Solicitud no válida. Actualiza la página.";

    } else {

        // Eliminar la incidencia seleccionada
        $stmt = $conn->prepare(
            "DELETE FROM incidencias WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {

            $stmt->close();

            header("Location: index.php?eliminado=1");
            exit;
        }

        $error = "No se pudo eliminar la incidencia.";

        $stmt->close();
    }
}

// Escapar texto para mostrarlo en HTML
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

    <title>Eliminar ticket | Soporte TI</title>

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
                <i class="fa-solid fa-trash"></i>
                Eliminar incidencia
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

    <!-- CONTENIDO -->
    <main class="dashboard-main">

        <header class="dashboard-topbar">

            <div>

                <span class="topbar-etiqueta">
                    ADMINISTRACIÓN DE INCIDENCIAS
                </span>

                <h1>Eliminar incidencia</h1>

                <p>
                    Confirma la eliminación del ticket seleccionado.
                </p>

            </div>

            <a href="index.php" class="btn-limpiar">
                Volver
            </a>

        </header>

        <section class="tarjeta eliminar-tarjeta">

            <div class="eliminar-icono">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <h2>¿Eliminar este ticket?</h2>

            <p class="eliminar-descripcion">
                Estás a punto de eliminar permanentemente
                la siguiente incidencia.
            </p>

            <div class="eliminar-detalles">

                <div>

                    <span>Número de ticket</span>

                    <strong>
                        #<?= str_pad(
                            (string) $incidencia["id"],
                            4,
                            "0",
                            STR_PAD_LEFT
                        ) ?>
                    </strong>

                </div>

                <div>

                    <span>Reportado por</span>

                    <strong>
                        <?= escapar($incidencia["reportado_por"]) ?>
                    </strong>

                </div>

                <div>

                    <span>Laboratorio</span>

                    <strong>
                        <?= escapar($incidencia["laboratorio"]) ?>
                    </strong>

                </div>

                <div>

                    <span>Equipo</span>

                    <strong>
                        <?= escapar($incidencia["equipo"]) ?>
                    </strong>

                </div>

            </div>

            <!-- MOSTRAR ERRORES -->
            <?php if ($error !== ""): ?>

                <div class="mensaje error">
                    <?= escapar($error) ?>
                </div>

            <?php endif; ?>

            <div class="eliminar-advertencia">

                <i class="fa-solid fa-circle-exclamation"></i>

                Esta acción no se puede deshacer.

            </div>

            <!-- FORMULARIO DE CONFIRMACIÓN -->
            <form method="POST" class="eliminar-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($_SESSION["csrf_token"]) ?>"
                >

                <a href="index.php" class="btn-limpiar">
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn-eliminar-confirmar"
                >
                    <i class="fa-solid fa-trash"></i>
                    Eliminar definitivamente
                </button>

            </form>

        </section>

    </main>

</div>

</body>
</html>

<?php
$conn->close();
?>


