<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION["usuario_id"];
$stmt = $conn->prepare("SELECT nombre, apellido,  email, dni FROM usuarios WHERE id = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nuevo_nombre = $_POST["nombre"];
    $nuevo_apellido = $_POST["apellido"];
    $nuevo_email = $_POST["email"] ?? "";
    $nuevo_dni = $_POST["dni"];

    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, dni = ? WHERE id = ?");
    $stmt->execute([$nuevo_nombre, $nuevo_apellido, $nuevo_email, $nuevo_dni, $id_usuario]);

    header("Location: Ver_informacion_administrador.php");
    exit();
}
?>






<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
       body {
            display: flex;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            height: 100vh;
            background-color: #f8f9fa;
        }

        .sidebar {
            /* width: 300px; */
            background: rgb(4, 114, 231);
            color: white;
            padding: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
        }

        .sidebar img {
            width: 250px;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            margin-top: 15px;
            padding: 15px;
            border-radius: 5px;
            width: 100%;
            transition: background 0.4s ease, color 0.4s ease;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .sidebar a i {
            margin-right: 15px;
            font-size: 1.5rem;
        }

        .sidebar a:hover {
            background: #08B8F8;
            color: #f8f9fa;
        }

        .sidebar a.active {
            background: #08B8F8; /* Mismo color que el hover */
            color: #f8f9fa;
            font-weight: bold;
        }

        .sidebar .logout {
            background: #e74c3c !important;
            color: #fff !important;
            font-weight: bold;
            margin-top: auto;
            border-radius: 5px;
            padding: 15px;
            transition: background 0.3s, color 0.3s;
            display: flex;
            align-items: center;
        }
        .sidebar .logout:hover,
        .sidebar .logout.active {
            background: #c0392b !important;
            color: #fff !important;
        }


        .main-content {
            flex: 1;
            padding: 40px;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-container h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
            color:rgb(0, 0, 0);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: #007bff;
            outline: none;
        }

        .modify-button {
            display: block;
            width: 100%;
            padding: 10px;
            background: #08B8F8;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.2rem;
            cursor: pointer;
            margin-top: 20px;
            font-weight: 550; /* Opcional: resaltar el texto */

        }

        .modify-button:hover {
            background:  #08B8F8;
        }

        .edit-container {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100%;
            background: white;
            box-shadow: -3px 0 10px rgba(0, 0, 0, 0.3);
            padding: 20px;
            transition: right 0.3s ease;
        }

        .edit-container.show {
            right: 0;
        }

        .close-button {
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
        }

        .save-button {
            display: block;
            width: 100%;
            padding: 10px;
            background:#007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.2rem;
            cursor: pointer;
            margin-top: 20px;
        }

        .save-button:hover {
            background: #007bff;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <img src="img/logoBlanco.png" alt="Administrador">
    <a href="index_admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index_admin.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span>Bienvenido Administrador</span>
    </a>

    <!-- Menú desplegable para Información de Trabajador -->
    <div class="dropdown">
        <a href="#" class="dropdown-toggle <?php echo basename($_SERVER['PHP_SELF']) == 'historial_empleado_admin.php' ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#informacionTrabajadorMenu" aria-expanded="false">
            <i class="fas fa-users"></i>
            <span>Información de Trabajador</span>
        </a>
        <div id="informacionTrabajadorMenu" class="collapse" style="padding-left: 15px; margin-top: 10px;">
            <a href="historial_empleado_admin.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'historial_empleado_admin.php' ? 'active' : ''; ?>" style="margin-bottom: 5px; ">
                <i class="fas fa-user"></i>
                <span>Historial de Trabajador</span>
            </a>
        </div>
    </div>

    <!-- Menú desplegable para Asistencia de Trabajador -->
    <div class="dropdown">
        <a href="#" class="dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['panel_administrador.php', 'justificacion_mensaje_trabajador.php', 'agregar_vacaciones_trabajador.php', 'ver_vacaciones_trabajador.php']) ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#asistenciaTrabajadorMenu" aria-expanded="false">
            <i class="fas fa-clipboard-list"></i>
            <span>Asistencia de Trabajadores</span>
        </a>
        <div id="asistenciaTrabajadorMenu" class="collapse" style="padding-left: 15px; margin-top: 10px;">
            <a href="panel_administrador.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'panel_administrador.php' ? 'active' : ''; ?>" style="margin-bottom: 5px; ">
                <i class="fas fa-clipboard-list"></i>
                <span>Historial de Asistencia</span>
            </a>
            <a href="justificacion_mensaje_trabajador.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'justificacion_mensaje_trabajador.php' ? 'active' : ''; ?>" style="margin-bottom: 5px; ">
                <i class="fas fa-user-clock"></i>
                <span>Justificaciones</span>
            </a>
            <a href="agregar_vacaciones_trabajador.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'agregar_vacaciones_trabajador.php' ? 'active' : ''; ?>" style="margin-bottom: 5px; ">
                <i class="fas fa-plus-circle"></i>
                <span>Agregar Vacaciones</span>
            </a>
            <a href="ver_vacaciones_trabajador.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'ver_vacaciones_trabajador.php' ? 'active' : ''; ?>" style="">
                <i class="fas fa-eye"></i>
                <span>Ver Vacaciones</span>
            </a>
        </div>
    </div>

    <!-- Enlace para Descargar Reporte -->
    <a href="Descargar_reporte.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'Descargar_reporte.php' ? 'active' : ''; ?>">
        <i class="fas fa-file-download"></i>
        <span>Descargar Reporte Asistencia </span>
    </a>

    <!-- Otros enlaces -->
    <a href="historial_administrador.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'historial_administrador.php' ? 'active' : ''; ?>">
        <i class="fas fa-history"></i>
        <span>Historial de Administrador</span>
    </a>
    <a href="Ver_informacion_administrador.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'Ver_informacion_administrador.php' ? 'active' : ''; ?>">
        <i class="fas fa-user-circle"></i>
        <span>Información Personal</span>
    </a>
    <a href="login.php" class="logout <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">
        <i class="fas fa-sign-out-alt"></i>
        <span>Cerrar Sesión</span>
</a>
</div>

    <div class="main-content">
        <div class="form-container">
            <h1>Información Personal</h1>
            <form>
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" value="<?= htmlspecialchars($usuario['nombre']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" value="<?= htmlspecialchars($usuario['apellido']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>DNI</label>
                    <input type="text" value="<?= htmlspecialchars($usuario['dni']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" value="<?= htmlspecialchars($usuario['email']) ?>" readonly>
                </div>
            </form>
            <button class="modify-button" onclick="openEditForm()">Modificar Perfil</button>
        </div>
    </div>

    <!-- Ventana de edición -->
    <div class="edit-container" id="editForm">
        <button class="close-button" onclick="closeEditForm()">X</button>
        <h2>Editar Perfil</h2>
        <form method="POST">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>">
            </div>
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" value="<?= htmlspecialchars($usuario['apellido']) ?>">
            </div>
            <div class="form-group">
                <label>DNI</label>
                <input type="text" name="dni" value="<?= htmlspecialchars($usuario['dni']) ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="text" name="email" value="<?= htmlspecialchars($usuario['email']) ?>">
            </div>
            <button type="submit" class="save-button">Guardar Cambios</button>
        </form>
    </div>

    <script>
        function openEditForm() {
            document.getElementById("editForm").classList.add("show");
        }
        function closeEditForm() {
            document.getElementById("editForm").classList.remove("show");
        }
    </script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
