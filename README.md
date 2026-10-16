# Soporte TI - Sistema de Gestión de Incidencias

## Descripción del proyecto

Soporte TI es una aplicación web desarrollada para registrar, consultar y administrar incidencias tecnológicas que se presentan en laboratorios y equipos informáticos.

El objetivo del sistema es facilitar el reporte de problemas y permitir que el personal encargado pueda dar seguimiento a cada incidencia hasta su resolución.

## Tecnologías utilizadas

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP
- Visual Studio Code

## Funcionalidades

El sistema permite:

- Registrar incidencias indicando quién reporta, laboratorio, equipo, descripción y prioridad.
- Consultar las incidencias registradas.
- Buscar y filtrar incidencias por laboratorio, estado y prioridad.
- Editar la informacion de los tickets.
- Cambiar el estado de una incidencia entre Pendiente, En proceso y Resuelta.
- Eliminar incidencias mediante una confirmacion.
- Consultar estadisticas generales de los tickets.
- Iniciar y cerrar sesion.
- Administrar cuentas de tecnicos desde una cuenta administradora.

## Roles del sistema

**Administrador:** puede gestionar incidencias y administrar cuentas de usuarios.

**Técnico:** puede consultar, editar, actualizar estados y eliminar incidencias, pero no administrar las cuentas de usuarios.

Los visitantes pueden registrar y consultar incidencias sin iniciar sesión.

## Requisitos para instalar

- XAMPP con Apache y MySQL.
- Navegador web.
- PHP compatible con las funciones utilizadas por el proyecto (PHP 8.0 o superior).

## Instalación

1. Descargar el proyecto desde GitHub.
2. Copiar la carpeta `gestion_incidencias` dentro de `C:\xampp\htdocs`.
3. Iniciar Apache y MySQL desde XAMPP.
4. Abrir `http://localhost/phpmyadmin`.
5. Crear una base de datos llamada `gestion_incidencias`.
6. Importar el archivo `gestion_incidencias.sql` incluido en el proyecto.
7. Revisar la configuración de conexión en `config/conexion.php`.
8. Abrir el sistema desde `http://localhost/gestion_incidencias/`.

## Acceso al sistema

Desde la página principal se pueden registrar y consultar incidencias.

Para acceder al panel administrativo se debe iniciar sesión con una cuenta registrada en la base de datos.

Las contraseñas de los usuarios se almacenan mediante hashes y no en texto plano.

## Estructura del proyecto

- `admin/`: panel administrativo, edición, eliminación, cambio de estados y usuarios.
- `config/`: conexión a MySQL.
- `css/`: estilos de las páginas.
- `imagenes/`: recursos gráficos.
- `index.php`: página principal.
- `incidencias.php`: consulta y filtros de incidencias.
- `registrar.php`: registro de nuevos tickets.
- `login.php`: inicio de sesión.
- `gestion_incidencias.sql`: estructura y datos exportados de la base de datos.

## Autor

Ronald Mauricio Araya Oviedo

Ingeniería en Sistemas - UISIL

2026