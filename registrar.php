
<?php
session_start();

require_once __DIR__ . "/config/conexion.php";

$admin_activo = isset($_SESSION["admin_id"]);

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$mensaje = "";
$ticket_creado = null;

$nombre = "";
$laboratorio = "";
$equipo = "";
$descripcion = "";
$prioridad = "";

if (isset($_SESSION["ticket_creado"])) {
    $ticket_creado = (int) $_SESSION["ticket_creado"];
    unset($_SESSION["ticket_creado"]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $laboratorio = trim($_POST["laboratorio"] ?? "");
    $equipo = trim($_POST["equipo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $prioridad = $_POST["prioridad"] ?? "";

    $prioridades = ["Baja", "Media", "Alta"];
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $mensaje = "La sesión del formulario no es válida. Actualiza la página.";

    } elseif (
        $nombre === "" ||
        $laboratorio === "" ||
        $equipo === "" ||
        $descripcion === "" ||
        !in_array($prioridad, $prioridades, true)
    ) {
        $mensaje = "Completa todos los campos correctamente.";

    } elseif (
        mb_strlen($nombre) > 100 ||
        mb_strlen($laboratorio) > 100 ||
        mb_strlen($equipo) > 100 ||
        mb_strlen($descripcion) > 3000
    ) {
        $mensaje = "Uno de los campos supera la cantidad máxima de caracteres.";

    } else {

        $sql = "INSERT INTO incidencias
                (reportado_por, laboratorio, equipo, descripcion, prioridad)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssss",
            $nombre,
            $laboratorio,
            $equipo,
            $descripcion,
            $prioridad
        );

        if ($stmt->execute()) {

            $_SESSION["ticket_creado"] = $conn->insert_id;
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            $stmt->close();

            header("Location: registrar.php");
            exit;

        } else {
            $mensaje = "No se pudo registrar el ticket.";
        }

        $stmt->close();
    }
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear Ticket | Soporte TI</title>

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/registro.css">
    <link rel="stylesheet" href="css/registro-panel.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="pagina-registro pagina-registro-panel">

<div class="registro-layout">

    <!-- MENÚ LATERAL -->
    <aside class="registro-sidebar">

        <a href="index.php" class="registro-sidebar-logo">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Soporte TI</span>
        </a>

        <div class="registro-sidebar-grupo">

            <p class="registro-sidebar-titulo">MENÚ PRINCIPAL</p>

            <a href="index.php" class="registro-sidebar-enlace">
                <i class="fa-solid fa-house"></i>
                Inicio
            </a>

            <a href="incidencias.php" class="registro-sidebar-enlace">
                <i class="fa-solid fa-ticket"></i>
                Incidencias
            </a>

            <a href="registrar.php"
               class="registro-sidebar-enlace activo"
               aria-current="page">
                <i class="fa-solid fa-circle-plus"></i>
                Nuevo ticket
            </a>

        </div>

        <div class="registro-sidebar-grupo">

            <p class="registro-sidebar-titulo">ADMINISTRACIÓN</p>

            <?php if ($admin_activo): ?>

                <a href="admin/index.php" class="registro-sidebar-enlace">
                    <i class="fa-solid fa-gauge-high"></i>
                    Panel administrativo
                </a>

                <a href="admin/logout.php" class="registro-sidebar-enlace">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Cerrar sesión
                </a>

            <?php else: ?>

                <a href="login.php" class="registro-sidebar-enlace">
                    <i class="fa-solid fa-lock"></i>
                    Acceso administrativo
                </a>

            <?php endif; ?>

        </div>

        <div class="registro-sidebar-pie">
            <i class="fa-solid fa-headset"></i>
            Centro de Soporte Tecnológico
        </div>

    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="registro-main">

        <header class="registro-topbar">

            <div>
                <span class="registro-topbar-etiqueta">
                    CENTRO DE SOPORTE
                </span>

                <h1>Registrar nueva incidencia</h1>

                <p>
                    Describe el problema tecnológico y genera
                    un ticket para darle seguimiento.
                </p>
            </div>

            <a href="incidencias.php" class="registro-volver-listado">
                <i class="fa-solid fa-list-check"></i>
                Ver incidencias
            </a>

        </header>

        <main class="registro-contenedor">

            <?php if ($ticket_creado !== null): ?>

                <!-- CONFIRMACIÓN -->
                <section class="registro-exito">

                    <div class="registro-exito-icono">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <h2>¡Ticket registrado correctamente!</h2>

                    <p>
                        Tu incidencia fue guardada en el sistema.
                        Conserva el número de ticket para identificarla.
                    </p>

                    <div class="registro-numero">

                        <span>NÚMERO DE TICKET</span>

                        <strong>
                            #<?= str_pad(
                                $ticket_creado,
                                4,
                                "0",
                                STR_PAD_LEFT
                            ) ?>
                        </strong>

                    </div>

                    <div class="registro-exito-acciones">

                        <a href="incidencias.php"
                           class="registro-btn-principal">
                            <i class="fa-solid fa-list-check"></i>
                            Ver incidencias
                        </a>

                        <a href="registrar.php"
                           class="registro-btn-secundario">
                            <i class="fa-solid fa-plus"></i>
                            Registrar otro ticket
                        </a>

                    </div>

                </section>

            <?php else: ?>

                <!-- FORMULARIO -->
                <section class="registro-tarjeta">

                    <div class="registro-tarjeta-header">

                        <div class="registro-tarjeta-icono">
                            <i class="fa-solid fa-ticket"></i>
                        </div>

                        <div>
                            <h2>Información de la incidencia</h2>
                            <p>Los campos marcados con * son obligatorios.</p>
                        </div>

                    </div>

                    <?php if ($mensaje !== ""): ?>

                        <div class="registro-mensaje-error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <?= escapar($mensaje) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST" class="registro-formulario">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= escapar($_SESSION["csrf_token"]) ?>"
                        >

                        <div class="registro-grid">

                            <div class="registro-campo">

                                <label for="nombre">
                                    <i class="fa-regular fa-user"></i>
                                    Nombre de quien reporta *
                                </label>

                                <input
                                    type="text"
                                    id="nombre"
                                    name="nombre"
                                    maxlength="100"
                                    value="<?= escapar($nombre) ?>"
                                    placeholder="Ejemplo: Ronald Araya"
                                    autocomplete="name"
                                    required
                                >

                            </div>

                            <div class="registro-campo">

                                <label for="laboratorio">
                                    <i class="fa-solid fa-building"></i>
                                    Laboratorio o ubicación *
                                </label>

                                <input
                                    type="text"
                                    id="laboratorio"
                                    name="laboratorio"
                                    maxlength="100"
                                    value="<?= escapar($laboratorio) ?>"
                                    placeholder="Ejemplo: Laboratorio 2"
                                    required
                                >

                            </div>

                            <div class="registro-campo">

                                <label for="equipo">
                                    <i class="fa-solid fa-desktop"></i>
                                    Equipo afectado *
                                </label>

                                <input
                                    type="text"
                                    id="equipo"
                                    name="equipo"
                                    maxlength="100"
                                    value="<?= escapar($equipo) ?>"
                                    placeholder="Ejemplo: Computadora 05"
                                    required
                                >

                            </div>

                            <div class="registro-campo">

                                <label for="prioridad">
                                    <i class="fa-solid fa-flag"></i>
                                    Prioridad *
                                </label>

                                <select
                                    id="prioridad"
                                    name="prioridad"
                                    required
                                >

                                    <option value="">
                                        Selecciona la prioridad
                                    </option>

                                    <?php foreach (["Baja", "Media", "Alta"] as $p): ?>

                                        <option
                                            value="<?= escapar($p) ?>"
                                            <?= $prioridad === $p ? "selected" : "" ?>
                                        >
                                            <?= escapar($p) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>

                        <div class="registro-campo registro-descripcion">

                            <label for="descripcion">
                                <i class="fa-regular fa-file-lines"></i>
                                Descripción del problema *
                            </label>

                            <textarea
                                id="descripcion"
                                name="descripcion"
                                rows="6"
                                maxlength="3000"
                                placeholder="Describe qué está sucediendo, cuándo comenzó el problema y cualquier detalle que pueda ayudar..."
                                required
                            ><?= escapar($descripcion) ?></textarea>

                            <small>
                                Describe el problema con la mayor claridad posible.
                            </small>

                        </div>

                        <!-- GUÍA DE PRIORIDADES -->
                        <div class="registro-prioridades">

                            <h3>
                                <i class="fa-solid fa-circle-info"></i>
                                ¿Qué prioridad debo seleccionar?
                            </h3>

                            <div class="prioridades-grid">

                                <div class="prioridad-ayuda">

                                    <span class="prioridad-punto baja"></span>

                                    <div>
                                        <strong>Baja</strong>
                                        <p>
                                            El equipo funciona, pero presenta
                                            una molestia menor.
                                        </p>
                                    </div>

                                </div>

                                <div class="prioridad-ayuda">

                                    <span class="prioridad-punto media"></span>

                                    <div>
                                        <strong>Media</strong>
                                        <p>
                                            El problema dificulta el
                                            trabajo habitual.
                                        </p>
                                    </div>

                                </div>

                                <div class="prioridad-ayuda">

                                    <span class="prioridad-punto alta"></span>

                                    <div>
                                        <strong>Alta</strong>
                                        <p>
                                            El equipo o servicio
                                            no puede utilizarse.
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="registro-acciones">

                            <a href="incidencias.php"
                               class="registro-btn-secundario">
                                <i class="fa-solid fa-arrow-left"></i>
                                Volver a incidencias
                            </a>

                            <button
                                type="submit"
                                class="registro-btn-principal"
                            >
                                <i class="fa-solid fa-paper-plane"></i>
                                Registrar incidencia
                            </button>

                        </div>

                    </form>

                </section>

            <?php endif; ?>

        </main>

        <footer class="registro-footer">
            &copy; 2026 Soporte TI · Sistema de Gestión de Incidencias
        </footer>

    </div>

</div>

</body>
</html>
