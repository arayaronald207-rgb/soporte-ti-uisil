
<?php
session_start();

require_once __DIR__ . "/config/conexion.php";

// Comprobar si hay una sesión iniciada
$admin_activo = isset($_SESSION["admin_id"]);

// Consultar las 3 incidencias más recientes
$sql = "
    SELECT id, equipo, laboratorio, descripcion, estado
    FROM incidencias
    ORDER BY fecha_registro DESC, id DESC
    LIMIT 3
";

$resultado = $conn->query($sql);

// Guardar las incidencias encontradas
$incidencias_recientes = [];

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $incidencias_recientes[] = $fila;
    }
}

// Evitar que el contenido de la base de datos
// se interprete como código HTML
function escapar($texto) {
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Elegir la apariencia de cada estado
function claseEstado($estado) {
    return match ($estado) {
        "Pendiente" => "pendiente",
        "En proceso" => "proceso",
        "Resuelta" => "resuelta",
        default => "pendiente"
    };
}

// Elegir el icono según el equipo registrado
function obtenerIconoEquipo($equipo) {
    $equipo = mb_strtolower((string) $equipo, "UTF-8");

    if (
        str_contains($equipo, "impresora") ||
        str_contains($equipo, "printer")
    ) {
        return [
            "icono" => "fa-print",
            "color" => "morado"
        ];
    }

    if (
        str_contains($equipo, "router") ||
        str_contains($equipo, "red") ||
        str_contains($equipo, "wifi") ||
        str_contains($equipo, "internet")
    ) {
        return [
            "icono" => "fa-wifi",
            "color" => "verde"
        ];
    }

    return [
        "icono" => "fa-desktop",
        "color" => "azul"
    ];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Soporte TI | Gestión de Incidencias</title>

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/inicio.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        /* Evitar que los nombres largos alteren el diseño */
        .hero-mini-ticket > div:nth-child(2) {
            min-width: 0;
            flex: 1;
        }

        .hero-mini-ticket strong,
        .hero-mini-ticket small {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .hero-mini-ticket .mini-ticket-estado {
            flex-shrink: 0;
        }

        /* Mensaje cuando no existen incidencias */
        .hero-sin-incidencias {
            padding: 35px 15px;
            text-align: center;
            color: #71849b;
        }

        .hero-sin-incidencias i {
            display: block;
            margin-bottom: 12px;
            color: #9db0c7;
            font-size: 28px;
        }

        .hero-sin-incidencias p {
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
        }
    </style>

</head>

<body class="pagina-inicio">

<!-- NAVEGACIÓN -->
<header class="inicio-header">

    <a href="index.php" class="inicio-logo">
        <i class="fa-solid fa-shield-halved"></i>
        <span>Soporte TI</span>
    </a>

    <nav class="inicio-nav">

        <a href="index.php" class="nav-activo">
            Inicio
        </a>

        <a href="incidencias.php">
            Incidencias
        </a>

        <a href="registrar.php">
            Crear ticket
        </a>

        <?php if ($admin_activo): ?>

            <!-- OPCIONES PARA ADMINISTRADOR Y TÉCNICO -->

            <a href="admin/index.php" class="inicio-acceso">
                <i class="fa-solid fa-gauge"></i>
                Administración
            </a>

            <a href="admin/logout.php" class="inicio-acceso">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>

        <?php else: ?>

            <!-- OPCIÓN PARA VISITANTES -->

            <a href="login.php" class="inicio-acceso">
                <i class="fa-solid fa-lock"></i>
                Administrador
            </a>

        <?php endif; ?>

    </nav>

</header>

<main>

    <!-- SECCIÓN PRINCIPAL -->
    <section class="inicio-hero">

        <div class="hero-contenido">

            <span class="hero-etiqueta">
                <i class="fa-solid fa-circle-check"></i>
                Plataforma de soporte tecnológico
            </span>

            <h1>
                Gestiona tus incidencias
                <span>desde un solo lugar.</span>
            </h1>

            <p>
                Una plataforma diseñada para registrar,
                organizar y dar seguimiento a los problemas
                tecnológicos de manera rápida y sencilla.
            </p>

            <div class="hero-botones">

                <a href="registrar.php" class="hero-btn-principal">
                    <i class="fa-solid fa-plus"></i>
                    Crear nuevo ticket
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

                <a href="incidencias.php" class="hero-btn-secundario">
                    <i class="fa-solid fa-ticket"></i>
                    Consultar incidencias
                </a>

            </div>

            <div class="hero-beneficios">

                <span>
                    <i class="fa-solid fa-check"></i>
                    Registro sencillo
                </span>

                <span>
                    <i class="fa-solid fa-check"></i>
                    Seguimiento de tickets
                </span>

                <span>
                    <i class="fa-solid fa-check"></i>
                    Gestión centralizada
                </span>

            </div>

        </div>

        <!-- ILUSTRACIÓN CON CSS -->
        <div class="hero-visual">

            <div class="hero-circulo circulo-uno"></div>
            <div class="hero-circulo circulo-dos"></div>

            <div class="hero-panel">

                <div class="hero-panel-header">

                    <div class="hero-puntos">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <span>Centro de soporte</span>

                    <i class="fa-solid fa-ellipsis"></i>

                </div>

                <div class="hero-panel-body">

                    <div class="hero-panel-titulo">
                        <i class="fa-solid fa-chart-simple"></i>
                        <span>Gestión de incidencias</span>
                    </div>

                    <!-- INCIDENCIAS REALES DE MYSQL -->

                    <?php if (count($incidencias_recientes) > 0): ?>

                        <?php foreach ($incidencias_recientes as $incidencia): ?>

                            <?php
                            $apariencia = obtenerIconoEquipo(
                                $incidencia["equipo"]
                            );

                            $clase_estado = claseEstado(
                                $incidencia["estado"]
                            );
                            ?>

                            <div class="hero-mini-ticket">

                                <div class="mini-ticket-icono <?= escapar($apariencia["color"]) ?>">

                                    <i class="fa-solid <?= escapar($apariencia["icono"]) ?>"></i>

                                </div>

                                <div>

                                    <strong title="<?= escapar($incidencia["equipo"]) ?>">
                                        <?= escapar($incidencia["equipo"]) ?>
                                    </strong>

                                    <small title="<?= escapar($incidencia["laboratorio"]) ?>">
                                        <?= escapar($incidencia["laboratorio"]) ?>
                                    </small>

                                </div>

                                <span class="mini-ticket-estado <?= escapar($clase_estado) ?>">
                                    <?= escapar($incidencia["estado"]) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="hero-sin-incidencias">

                            <i class="fa-solid fa-folder-open"></i>

                            <p>
                                Todavía no hay incidencias registradas.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

            <div class="hero-flotante">

                <i class="fa-solid fa-circle-check"></i>

                <div>
                    <strong>Gestión eficiente</strong>
                    <small>Seguimiento en tiempo real</small>
                </div>

            </div>

        </div>

    </section>

    <!-- FUNCIONALIDADES -->
    <section class="inicio-funciones">

        <div class="inicio-seccion-titulo">

            <span>FUNCIONALIDADES</span>

            <h2>
                Todo lo que necesitas en un solo lugar
            </h2>

            <p>
                Simplifica el proceso de atención y
                seguimiento de incidencias tecnológicas.
            </p>

        </div>

        <div class="funciones-grid">

            <article class="funcion-card">

                <div class="funcion-icono azul">
                    <i class="fa-solid fa-ticket"></i>
                </div>

                <h3>Registro de incidencias</h3>

                <p>
                    Reporta problemas tecnológicos mediante
                    un formulario sencillo y organizado.
                </p>

                <a href="registrar.php">
                    Crear ticket
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </article>

            <article class="funcion-card">

                <div class="funcion-icono morado">
                    <i class="fa-solid fa-magnifying-glass-chart"></i>
                </div>

                <h3>Seguimiento de tickets</h3>

                <p>
                    Consulta las incidencias registradas,
                    su prioridad y el estado de atención.
                </p>

                <a href="incidencias.php">
                    Ver incidencias
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </article>

            <article class="funcion-card">

                <div class="funcion-icono verde">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>

                <h3>Administración segura</h3>

                <p>
                    Gestiona y actualiza las incidencias
                    mediante un panel de acceso protegido.
                </p>

                <a href="<?= $admin_activo ? 'admin/index.php' : 'login.php' ?>">
                    Ir al panel
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </article>

        </div>

    </section>

    <!-- LLAMADO A LA ACCIÓN -->
    <section class="inicio-cta">

        <div>

            <span>¿NECESITAS SOPORTE?</span>

            <h2>
                Estamos listos para recibir tu incidencia.
            </h2>

            <p>
                Registra tu solicitud y facilita el seguimiento
                de los problemas tecnológicos.
            </p>

        </div>

        <a href="registrar.php">
            <i class="fa-solid fa-plus"></i>
            Registrar incidencia
        </a>

    </section>

</main>

<footer class="inicio-footer">

    <div class="inicio-footer-logo">
        <i class="fa-solid fa-shield-halved"></i>
        Soporte TI
    </div>

    <p>
        &copy; 2026 Soporte TI · Sistema de Gestión de Incidencias
    </p>

</footer>

</body>
</html>

<?php
$conn->close();
?>
