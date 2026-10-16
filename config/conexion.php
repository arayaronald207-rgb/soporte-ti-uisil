
<?php

$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "gestion_incidencias";

$conn = new mysqli(
    $servidor,
    $usuario,
    $contrasena,
    $base_datos
);

if ($conn->connect_error) {
    error_log("Error de conexión: " . $conn->connect_error);
    exit("No se pudo conectar con la base de datos.");
}

$conn->set_charset("utf8mb4");
