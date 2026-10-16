
<?php
session_start();

// Eliminar todas las variables de sesión
$_SESSION = [];

// Eliminar también la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Regresar al inicio de sesión
header("Location: ../login.php");
exit;
