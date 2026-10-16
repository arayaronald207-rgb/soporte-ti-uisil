
<?php
session_start();

// Proteger el acceso administrativo
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../config/conexion.php";

// Escapar contenido HTML
function escapar($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

// =====================================
// ESTADÍSTICAS GENERALES
// =====================================

$sql_estadisticas = "
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(estado = 'Pendiente'), 0) AS pendientes,
        COALESCE(SUM(estado = 'En proceso'), 0) AS en_proceso,
        COALESCE(SUM(estado = 'Resuelta'), 0) AS resueltas,
        COALESCE(SUM(prioridad = 'Baja'), 0) AS prioridad_baja,
        COALESCE(SUM(prioridad = 'Media'), 0) AS prioridad_media,
        COALESCE(SUM(prioridad = 'Alta'), 0) AS prioridad_alta
    FROM incidencias
";

$estadisticas = $conn->query($sql_estadisticas)->fetch_assoc();

$total = (int)$estadisticas["total"];
$pendientes = (int)$estadisticas["pendientes"];
$en_proceso = (int)$estadisticas["en_proceso"];
$resueltas = (int)$estadisticas["resueltas"];

$baja = (int)$estadisticas["prioridad_baja"];
$media = (int)$estadisticas["prioridad_media"];
$alta = (int)$estadisticas["prioridad_alta"];

// =====================================
// BUSCADOR Y FILTROS
// =====================================

$buscar = trim($_GET["buscar"] ?? "");
$estado = $_GET["estado"] ?? "";

if ($estado === "actualizado") {
    $estado = "";
}

$prioridad = $_GET["prioridad"] ?? "";

$estados_validos = ["Pendiente", "En proceso", "Resuelta"];
$prioridades_validas = ["Baja", "Media", "Alta"];

if (!in_array($estado, $estados_validos, true)) {
    $estado = "";
}

if (!in_array($prioridad, $prioridades_validas, true)) {
    $prioridad = "";
}

$sql = "SELECT *
        FROM incidencias
        WHERE 1 = 1";

$parametros = [];
$tipos = "";

if ($buscar !== "") {

    $sql .= " AND (
        CAST(id AS CHAR) LIKE ?
        OR reportado_por LIKE ?
        OR laboratorio LIKE ?
        OR equipo LIKE ?
    )";

    $termino = "%" . $buscar . "%";

    for ($i = 0; $i < 4; $i++) {
        $parametros[] = $termino;
        $tipos .= "s";
    }
}

if ($estado !== "") {
    $sql .= " AND estado = ?";
    $parametros[] = $estado;
    $tipos .= "s";
}

if ($prioridad !== "") {
    $sql .= " AND prioridad = ?";
    $parametros[] = $prioridad;
    $tipos .= "s";
}

$sql .= " ORDER BY fecha_registro DESC, id DESC";

$stmt = $conn->prepare($sql);

if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();

$resultado = $stmt->get_result();
$cantidad_resultados = $resultado->num_rows;

// =====================================
// MENSAJES DE ACCIONES
// =====================================

$mensaje_accion = "";

if (isset($_GET["actualizado"])) {
    $mensaje_accion = "La incidencia fue actualizada correctamente.";
} elseif (($_GET["estado"] ?? "") === "actualizado") {
    $mensaje_accion = "El estado de la incidencia fue actualizado.";
} elseif (isset($_GET["eliminado"])) {
    $mensaje_accion = "La incidencia fue eliminada correctamente.";
}

// =====================================
// ROL DEL USUARIO
// =====================================

// Se consulta el rol actual en MySQL para que
// los permisos no dependan solamente de la sesión.
$usuario_id = (int)$_SESSION["admin_id"];

$stmt_rol = $conn->prepare(
    "SELECT usuario, rol FROM usuarios WHERE id = ? LIMIT 1"
);

$stmt_rol->bind_param("i", $usuario_id);
$stmt_rol->execute();

$usuario_actual = $stmt_rol->get_result()->fetch_assoc();
$stmt_rol->close();

if (!$usuario_actual) {
    $_SESSION = [];
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$_SESSION["admin_usuario"] = $usuario_actual["usuario"];
$_SESSION["admin_rol"] = $usuario_actual["rol"];

$es_admin = $usuario_actual["rol"] === "admin";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Panel administrativo | Soporte TI</title>

    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"
            defer></script>
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

            <a href="index.php" class="activo">
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

            <!-- SOLO ADMINISTRADOR PRINCIPAL -->
            <?php if ($es_admin): ?>

                <a href="usuarios.php">
                    <i class="fa-solid fa-users-gear"></i>
                    Gestión de usuarios
                </a>

            <?php endif; ?>

            <a href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>

        </nav>

        <div class="sidebar-footer">
            <i class="fa-solid fa-user-shield"></i>

            <?= $es_admin ? "Administrador" : "Técnico de soporte" ?>
        </div>

    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="dashboard-main">

        <header class="dashboard-topbar">

            <div>
                <span class="topbar-etiqueta">
                    ÁREA ADMINISTRATIVA
                </span>

                <h1>Panel de control</h1>

                <p>
                    Bienvenido,
                    <?= escapar($_SESSION["admin_usuario"]) ?>
                </p>
            </div>

            <a href="../registrar.php" class="btn-nuevo">
                <i class="fa-solid fa-plus"></i>
                Nuevo ticket
            </a>

        </header>

        <?php if ($mensaje_accion !== ""): ?>

            <div class="admin-mensaje-exito">
                <i class="fa-solid fa-circle-check"></i>
                <?= escapar($mensaje_accion) ?>
            </div>

        <?php endif; ?>

        <!-- TARJETAS ESTADÍSTICAS -->
        <section class="admin-estadisticas">

            <div class="admin-stat-card">

                <div class="admin-stat-info">
                    <span>Total de tickets</span>
                    <strong><?= $total ?></strong>
                    <small>Incidencias registradas</small>
                </div>

                <div class="admin-stat-icono total">
                    <i class="fa-solid fa-ticket"></i>
                </div>

            </div>

            <div class="admin-stat-card">

                <div class="admin-stat-info">
                    <span>Pendientes</span>
                    <strong><?= $pendientes ?></strong>
                    <small>Esperando atención</small>
                </div>

                <div class="admin-stat-icono pendientes">
                    <i class="fa-solid fa-clock"></i>
                </div>

            </div>

            <div class="admin-stat-card">

                <div class="admin-stat-info">
                    <span>En proceso</span>
                    <strong><?= $en_proceso ?></strong>
                    <small>Actualmente en atención</small>
                </div>

                <div class="admin-stat-icono proceso">
                    <i class="fa-solid fa-spinner"></i>
                </div>

            </div>

            <div class="admin-stat-card">

                <div class="admin-stat-info">
                    <span>Resueltas</span>
                    <strong><?= $resueltas ?></strong>
                    <small>Incidencias solucionadas</small>
                </div>

                <div class="admin-stat-icono resueltas">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

            </div>

        </section>

        <!-- GRÁFICOS -->
        <section class="admin-graficos">

            <div class="admin-grafico-card">

                <div class="admin-grafico-titulo">
                    <h2>Incidencias por estado</h2>
                    <p>Distribución de tickets registrados</p>
                </div>

                <div class="admin-grafico-contenedor">
                    <canvas id="graficoEstados"></canvas>
                </div>

            </div>

            <div class="admin-grafico-card">

                <div class="admin-grafico-titulo">
                    <h2>Incidencias por prioridad</h2>
                    <p>Nivel de urgencia de las solicitudes</p>
                </div>

                <div class="admin-grafico-contenedor">
                    <canvas id="graficoPrioridades"></canvas>
                </div>

            </div>

        </section>

        <!-- TABLA DE INCIDENCIAS -->
        <section class="panel-tabla">

            <div class="panel-titulo">

                <h2>
                    <i class="fa-solid fa-list-check"></i>
                    Administrar incidencias
                </h2>

                <p>
                    Busca, filtra, edita, actualiza o elimina tickets.
                </p>

            </div>

            <!-- BUSCADOR -->
            <form method="GET" action="index.php" class="admin-filtros">

                <div class="admin-buscar">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        name="buscar"
                        placeholder="Buscar por ticket, nombre, laboratorio o equipo..."
                        value="<?= escapar($buscar) ?>"
                    >

                </div>

                <select name="estado">

                    <option value="">Todos los estados</option>

                    <?php foreach ($estados_validos as $opcion): ?>

                        <option
                            value="<?= escapar($opcion) ?>"
                            <?= $estado === $opcion ? "selected" : "" ?>
                        >
                            <?= escapar($opcion) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <select name="prioridad">

                    <option value="">Todas las prioridades</option>

                    <?php foreach ($prioridades_validas as $opcion): ?>

                        <option
                            value="<?= escapar($opcion) ?>"
                            <?= $prioridad === $opcion ? "selected" : "" ?>
                        >
                            <?= escapar($opcion) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button type="submit" class="admin-btn-buscar">
                    <i class="fa-solid fa-filter"></i>
                    Filtrar
                </button>

                <a href="index.php" class="admin-btn-limpiar">
                    Limpiar
                </a>

            </form>

            <div class="admin-resultados">
                <i class="fa-solid fa-list"></i>
                Mostrando <?= $cantidad_resultados ?> incidencia(s)
            </div>

            <div class="tabla-scroll">

                <table class="tabla-incidencias">

                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Reportado por</th>
                            <th>Laboratorio</th>
                            <th>Equipo</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($cantidad_resultados === 0): ?>

                        <tr>
                            <td colspan="7" class="admin-sin-resultados">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                <strong>No se encontraron incidencias</strong>

                                <p>Prueba con otros criterios de búsqueda.</p>

                            </td>
                        </tr>

                    <?php else: ?>

                        <?php while ($fila = $resultado->fetch_assoc()): ?>

                            <?php
                            $clase_prioridad = match ($fila["prioridad"]) {
                                "Alta" => "prioridad-alta",
                                "Media" => "prioridad-media",
                                default => "prioridad-baja"
                            };

                            $clase_estado = match ($fila["estado"]) {
                                "Resuelta" => "estado-resuelta",
                                "En proceso" => "estado-proceso",
                                default => "estado-pendiente"
                            };
                            ?>

                            <tr>

                                <td>
                                    <strong>
                                        #<?= str_pad(
                                            $fila["id"],
                                            4,
                                            "0",
                                            STR_PAD_LEFT
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= escapar($fila["reportado_por"]) ?>
                                </td>

                                <td>
                                    <?= escapar($fila["laboratorio"]) ?>
                                </td>

                                <td>
                                    <?= escapar($fila["equipo"]) ?>
                                </td>

                                <td>
                                    <span class="etiqueta <?= $clase_prioridad ?>">
                                        <?= escapar($fila["prioridad"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="etiqueta <?= $clase_estado ?>">
                                        <?= escapar($fila["estado"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="acciones-tabla">

                                        <a
                                            href="editar.php?id=<?= (int)$fila["id"] ?>"
                                            class="accion-btn accion-editar"
                                            title="Editar incidencia"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <a
                                            href="estado.php?id=<?= (int)$fila["id"] ?>"
                                            class="accion-btn accion-estado"
                                            title="Cambiar estado"
                                        >
                                            <i class="fa-solid fa-arrows-rotate"></i>
                                        </a>

                                        <a
                                            href="eliminar.php?id=<?= (int)$fila["id"] ?>"
                                            class="accion-btn accion-eliminar"
                                            title="Eliminar incidencia"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    if (typeof Chart === "undefined") {
        console.warn("No se pudo cargar Chart.js");
        return;
    }

    // DATOS REALES DE MYSQL
    const estados = [
        <?= $pendientes ?>,
        <?= $en_proceso ?>,
        <?= $resueltas ?>
    ];

    const prioridades = [
        <?= $baja ?>,
        <?= $media ?>,
        <?= $alta ?>
    ];

    // GRÁFICO POR ESTADO
    new Chart(
        document.getElementById("graficoEstados"),
        {
            type: "doughnut",

            data: {
                labels: ["Pendientes", "En proceso", "Resueltas"],

                datasets: [{
                    data: estados,
                    backgroundColor: [
                        "#f59e0b",
                        "#6366f1",
                        "#16a34a"
                    ],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "72%",

                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        }
    );

    // GRÁFICO POR PRIORIDAD
    new Chart(
        document.getElementById("graficoPrioridades"),
        {
            type: "bar",

            data: {
                labels: ["Baja", "Media", "Alta"],

                datasets: [{
                    label: "Tickets",
                    data: prioridades,
                    backgroundColor: [
                        "#16a34a",
                        "#f59e0b",
                        "#dc2626"
                    ],
                    borderRadius: 8,
                    maxBarThickness: 65
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        display: false
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: "#edf2f7"
                        }
                    },

                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        }
    );
});
</script>

</body>
</html>

<?php
$stmt->close();
?>
