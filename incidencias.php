
<?php
session_start();

require_once __DIR__ . "/config/conexion.php";

// Comprobar si existe una sesión iniciada
$sesion_iniciada = isset($_SESSION["admin_id"]);

// Recibir filtros
$laboratorio = trim($_GET["laboratorio"] ?? "");
$estado = $_GET["estado"] ?? "";
$prioridad = $_GET["prioridad"] ?? "";

$estados_validos = ["Pendiente", "En proceso", "Resuelta"];
$prioridades_validas = ["Baja", "Media", "Alta"];

if (!in_array($estado, $estados_validos, true)) {
    $estado = "";
}

if (!in_array($prioridad, $prioridades_validas, true)) {
    $prioridad = "";
}

// Estadísticas generales
$sql_estadisticas = "
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(estado = 'Pendiente'), 0) AS pendientes,
        COALESCE(SUM(estado = 'En proceso'), 0) AS proceso,
        COALESCE(SUM(estado = 'Resuelta'), 0) AS resueltas
    FROM incidencias
";

$resultado_estadisticas = $conn->query($sql_estadisticas);
$estadisticas = $resultado_estadisticas->fetch_assoc();

// Consulta con filtros
$sql = "SELECT * FROM incidencias WHERE 1=1";

$tipos = "";
$parametros = [];

if ($laboratorio !== "") {
    $sql .= " AND laboratorio LIKE ?";
    $tipos .= "s";
    $parametros[] = "%" . $laboratorio . "%";
}

if ($estado !== "") {
    $sql .= " AND estado = ?";
    $tipos .= "s";
    $parametros[] = $estado;
}

if ($prioridad !== "") {
    $sql .= " AND prioridad = ?";
    $tipos .= "s";
    $parametros[] = $prioridad;
}

$sql .= " ORDER BY fecha_registro DESC, id DESC";

$stmt = $conn->prepare($sql);

if ($tipos !== "") {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();
$resultado = $stmt->get_result();

// Escapar contenido introducido por usuarios
function escapar($texto) {
    return htmlspecialchars(
        (string)$texto,
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

    <title>Incidencias | Soporte TI</title>

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/incidencias.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Estilos de la ventana de descripción -->
    <style>
        .btn-ver-descripcion {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            border: 1px solid #c9d9ff;
            border-radius: 8px;
            background: #eff5ff;
            color: #2458bf;
            font: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s, border-color 0.2s;
        }

        .btn-ver-descripcion:hover {
            background: #dfeaff;
            border-color: #8eb1fa;
        }

        .modal-descripcion {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(12, 28, 50, 0.65);
        }

        .modal-descripcion.abierto {
            display: flex;
        }

        .modal-descripcion-contenido {
            width: 100%;
            max-width: 580px;
            max-height: 85vh;
            overflow-y: auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }

        .modal-descripcion-encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 22px 25px;
            border-bottom: 1px solid #e5ebf3;
        }

        .modal-descripcion-encabezado h2 {
            margin: 0;
            color: #17324d;
            font-size: 20px;
        }

        .modal-descripcion-cerrar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 8px;
            background: #f0f3f8;
            color: #52657c;
            cursor: pointer;
            font-size: 18px;
        }

        .modal-descripcion-cerrar:hover {
            background: #e2e8f0;
        }

        .modal-descripcion-cuerpo {
            padding: 25px;
        }

        .modal-descripcion-etiqueta {
            display: block;
            margin-bottom: 10px;
            color: #687c95;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .modal-descripcion-texto {
            margin: 0;
            color: #24384e;
            font-size: 15px;
            line-height: 1.8;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .modal-descripcion-pie {
            display: flex;
            justify-content: flex-end;
            padding: 16px 25px;
            border-top: 1px solid #e5ebf3;
        }

        .modal-descripcion-boton {
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .modal-descripcion-boton:hover {
            background: #1d4ed8;
        }

        body.modal-abierto {
            overflow: hidden;
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
            MENÚ PRINCIPAL
        </div>

        <nav class="sidebar-nav">

            <a href="index.php">
                <i class="fa-solid fa-house"></i>
                Inicio
            </a>

            <a href="incidencias.php" class="activo">
                <i class="fa-solid fa-ticket"></i>
                Incidencias
            </a>

            <a href="registrar.php">
                <i class="fa-solid fa-circle-plus"></i>
                Nuevo ticket
            </a>

            <div class="sidebar-seccion">
                ADMINISTRACIÓN
            </div>

            <?php if ($sesion_iniciada): ?>

                <a href="admin/index.php">
                    <i class="fa-solid fa-gauge"></i>
                    Panel administrativo
                </a>

                <a href="admin/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Cerrar sesión
                </a>

            <?php else: ?>

                <a href="login.php">
                    <i class="fa-solid fa-lock"></i>
                    Acceso administrativo
                </a>

            <?php endif; ?>

        </nav>

        <div class="sidebar-footer">
            <i class="fa-solid fa-headset"></i>
            Centro de Soporte Tecnológico
        </div>

    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="dashboard-main">

        <!-- ENCABEZADO -->
        <header class="dashboard-topbar">

            <div>
                <span class="topbar-etiqueta">
                    SISTEMA DE GESTIÓN
                </span>

                <h1>Panel de incidencias</h1>

                <p>
                    Consulta y seguimiento de tickets tecnológicos
                </p>
            </div>

            <a href="registrar.php" class="btn-nuevo">
                <i class="fa-solid fa-plus"></i>
                Nuevo ticket
            </a>

        </header>

        <!-- ESTADÍSTICAS -->
        <section class="estadisticas-grid">

            <!-- TOTAL -->
            <div class="stat-card">

                <div class="stat-icon azul">
                    <i class="fa-solid fa-ticket"></i>
                </div>

                <span>Total de tickets</span>

                <h2>
                    <?= (int)$estadisticas["total"] ?>
                </h2>

                <small>Incidencias registradas</small>

            </div>

            <!-- PENDIENTES -->
            <div class="stat-card">

                <div class="stat-icon naranja">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <span>Pendientes</span>

                <h2>
                    <?= (int)$estadisticas["pendientes"] ?>
                </h2>

                <small>Esperando atención</small>

            </div>

            <!-- EN PROCESO -->
            <div class="stat-card">

                <div class="stat-icon morado">
                    <i class="fa-solid fa-spinner"></i>
                </div>

                <span>En proceso</span>

                <h2>
                    <?= (int)$estadisticas["proceso"] ?>
                </h2>

                <small>En atención técnica</small>

            </div>

            <!-- RESUELTAS -->
            <div class="stat-card">

                <div class="stat-icon verde">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <span>Resueltas</span>

                <h2>
                    <?= (int)$estadisticas["resueltas"] ?>
                </h2>

                <small>Problemas solucionados</small>

            </div>

        </section>

        <!-- LISTADO DE INCIDENCIAS -->
        <section class="panel-tabla">

            <div class="panel-titulo">

                <div>
                    <h2>
                        <i class="fa-solid fa-list-check"></i>
                        Listado de incidencias
                    </h2>

                    <p>Consulta los tickets registrados</p>
                </div>

            </div>

            <!-- FILTROS -->
            <form method="GET" class="filtros-form">

                <div class="filtro-grupo">

                    <label for="laboratorio">
                        Laboratorio
                    </label>

                    <input
                        type="text"
                        id="laboratorio"
                        name="laboratorio"
                        placeholder="Buscar laboratorio..."
                        value="<?= escapar($laboratorio) ?>"
                    >

                </div>

                <div class="filtro-grupo">

                    <label for="estado">
                        Estado
                    </label>

                    <select id="estado" name="estado">

                        <option value="">
                            Todos los estados
                        </option>

                        <?php foreach ($estados_validos as $e): ?>

                            <option
                                value="<?= escapar($e) ?>"
                                <?= $estado === $e ? "selected" : "" ?>
                            >
                                <?= escapar($e) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="filtro-grupo">

                    <label for="prioridad">
                        Prioridad
                    </label>

                    <select id="prioridad" name="prioridad">

                        <option value="">
                            Todas las prioridades
                        </option>

                        <?php foreach ($prioridades_validas as $p): ?>

                            <option
                                value="<?= escapar($p) ?>"
                                <?= $prioridad === $p ? "selected" : "" ?>
                            >
                                <?= escapar($p) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <button type="submit" class="btn-filtrar">
                    <i class="fa-solid fa-filter"></i>
                    Filtrar
                </button>

                <a href="incidencias.php" class="btn-limpiar">
                    Limpiar
                </a>

            </form>

            <!-- TABLA -->
            <div class="tabla-scroll">

                <table class="tabla-incidencias">

                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Reportado por</th>
                            <th>Laboratorio</th>
                            <th>Equipo</th>
                            <th>Descripción</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($resultado->num_rows > 0): ?>

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

                                <!-- DESCRIPCIÓN CON BOTÓN -->
                                <td class="descripcion-celda">

                                    <button
                                        type="button"
                                        class="btn-ver-descripcion"
                                        data-ticket="<?= (int)$fila["id"] ?>"
                                        data-descripcion="<?= escapar($fila["descripcion"]) ?>"
                                        onclick="abrirDescripcion(this)"
                                    >
                                        <i class="fa-regular fa-eye"></i>
                                        Ver descripción
                                    </button>

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
                                    <?= date(
                                        "d/m/Y H:i",
                                        strtotime($fila["fecha_registro"])
                                    ) ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="sin-resultados">

                                <i class="fa-solid fa-folder-open"></i>

                                <p>
                                    No se encontraron incidencias.
                                </p>

                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <!-- PIE DE PÁGINA -->
        <footer class="dashboard-footer">
            &copy; 2026 Soporte TI · Sistema de Gestión de Incidencias
        </footer>

    </main>

</div>

<!-- VENTANA EMERGENTE DE DESCRIPCIÓN -->
<div
    id="modalDescripcion"
    class="modal-descripcion"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modalDescripcionTitulo"
    aria-hidden="true"
>
    <div class="modal-descripcion-contenido">

        <div class="modal-descripcion-encabezado">

            <h2 id="modalDescripcionTitulo">
                Descripción del ticket
            </h2>

            <button
                type="button"
                class="modal-descripcion-cerrar"
                onclick="cerrarDescripcion()"
                aria-label="Cerrar ventana"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div class="modal-descripcion-cuerpo">

            <span class="modal-descripcion-etiqueta">
                DESCRIPCIÓN COMPLETA
            </span>

            <p
                id="modalDescripcionTexto"
                class="modal-descripcion-texto"
            ></p>

        </div>

        <div class="modal-descripcion-pie">

            <button
                type="button"
                class="modal-descripcion-boton"
                onclick="cerrarDescripcion()"
            >
                Cerrar
            </button>

        </div>

    </div>
</div>

<script>
    const modalDescripcion = document.getElementById("modalDescripcion");
    const modalDescripcionTitulo = document.getElementById("modalDescripcionTitulo");
    const modalDescripcionTexto = document.getElementById("modalDescripcionTexto");
    const botonCerrarModal = document.querySelector(".modal-descripcion-cerrar");

    let botonAnterior = null;

    function abrirDescripcion(boton) {
        botonAnterior = boton;

        const numeroTicket = boton.dataset.ticket;
        const descripcion = boton.dataset.descripcion;

        modalDescripcionTitulo.textContent = "Descripción del ticket #" +
            String(numeroTicket).padStart(4, "0");

        // textContent evita interpretar la descripción como HTML
        modalDescripcionTexto.textContent = descripcion;

        modalDescripcion.classList.add("abierto");
        modalDescripcion.setAttribute("aria-hidden", "false");

        document.body.classList.add("modal-abierto");
        botonCerrarModal.focus();
    }

    function cerrarDescripcion() {
        modalDescripcion.classList.remove("abierto");
        modalDescripcion.setAttribute("aria-hidden", "true");

        document.body.classList.remove("modal-abierto");

        if (botonAnterior) {
            botonAnterior.focus();
        }
    }

    // Cerrar al hacer clic fuera del contenido
    modalDescripcion.addEventListener("click", function(evento) {
        if (evento.target === modalDescripcion) {
            cerrarDescripcion();
        }
    });

    // Cerrar con la tecla Escape
    document.addEventListener("keydown", function(evento) {
        if (
            evento.key === "Escape" &&
            modalDescripcion.classList.contains("abierto")
        ) {
            cerrarDescripcion();
        }
    });
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
