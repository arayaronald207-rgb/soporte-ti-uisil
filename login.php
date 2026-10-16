
<?php
session_start();

require_once __DIR__ . "/config/conexion.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: admin/index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    if ($usuario === "" || $contrasena === "") {
        $error = "Completa todos los campos.";
    } else {

        // Ahora también consultamos el rol del usuario
        $sql = "SELECT id, usuario, contrasena, rol
                FROM usuarios
                WHERE usuario = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $admin = $resultado->fetch_assoc();

        if (
            $admin &&
            password_verify($contrasena, $admin["contrasena"])
        ) {
            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_usuario"] = $admin["usuario"];

            // Guardamos el rol para controlar los permisos
            $_SESSION["admin_rol"] = $admin["rol"];

            $stmt->close();

            header("Location: admin/index.php");
            exit;

        } else {
            $error = "Usuario o contraseña incorrectos.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | Soporte TI</title>

    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/login.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="pagina-login">

<div class="login-contenedor">

    <div class="login-panel">

        <div class="login-logo">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h1>Acceso administrativo</h1>

        <p class="login-descripcion">
            Ingresa tus credenciales para acceder
            al panel administrativo.
        </p>

        <?php if ($error !== ""): ?>
            <div class="mensaje error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">

            <label for="usuario">Usuario</label>

            <div class="login-input">
                <i class="fa-solid fa-user"></i>
                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    placeholder="Ingresa tu usuario"
                    autocomplete="username"
                    required
                >
            </div>

            <label for="contrasena">Contraseña</label>

            <div class="login-input">
                <i class="fa-solid fa-lock"></i>
                <input
                    type="password"
                    id="contrasena"
                    name="contrasena"
                    placeholder="Ingresa tu contraseña"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-boton">
                Iniciar sesión
                <i class="fa-solid fa-arrow-right"></i>
            </button>

        </form>

        <a href="incidencias.php" class="login-volver">
            <i class="fa-solid fa-arrow-left"></i>
            Volver a incidencias
        </a>

    </div>

    <div class="login-lateral">

        <div class="login-lateral-contenido">
            <i class="fa-solid fa-headset"></i>

            <h2>Soporte TI</h2>

            <p>
               Sistema de gestión de incidencias
               para el registro y seguimiento
               de solicitudes de soporte técnico.
            </p>

            <div class="login-decoracion">
                <span>
                    <i class="fa-solid fa-check-circle"></i>
                    Gestión centralizada
                </span>

                <span>
                    <i class="fa-solid fa-shield-halved"></i>
                    Acceso seguro
                </span>

                <span>
                    <i class="fa-solid fa-chart-line"></i>
                    Seguimiento eficiente
                </span>
            </div>
        </div>

    </div>

</div>

</body>
</html>
