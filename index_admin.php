<?php
session_start();
include 'includes/db.php';

// Obtener el nombre del administrador logueado
$usuario_id = $_SESSION["usuario_id"];
$sql_usuario = "SELECT nombre FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
$nombre_administrador = $usuario['nombre'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
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

        /* ...existing code... */
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
            padding: 20px;
        }
        .main-content h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: rgb(0, 0, 0);
        }
        .main-content p {
            font-size: 1rem;
            color: #6c757d;
        }
        .card {
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
        }
        .alert {
            font-size: 1.1rem;
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

<div class="main-content container mt-5">
    <h2 class="mb-4 text-center">¡Bienvenido, <?php echo htmlspecialchars($nombre_administrador); ?>!</h2>
    <p class="text-center">
        Este es tu panel de administración. Aquí puedes gestionar la asistencia, consultar reportes y administrar la información del sistema.
    </p>

    <div class="row mt-4">
    <!-- Tarjeta 1 -->
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                <h5 class="card-title">Historial de Trabajadores</h5>
                <p class="card-text">Consulta y gestiona el historial de asistencia de los empleados.</p>
                <a href="historial_empleado_admin.php" class="btn btn-primary">Ver Historial</a>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2 -->
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-file-alt fa-3x mb-3 text-success"></i>
                <h5 class="card-title">Reportes</h5>
                <p class="card-text">Descarga reportes detallados de asistencia y rendimiento.</p>
                <a href="Descargar_reporte.php" class="btn btn-success">Descargar Reportes</a>
            </div>
        </div>
    </div>

    <!-- Tarjeta 3 -->
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-user-circle fa-3x mb-3 text-info"></i>
                <h5 class="card-title">Información Personal</h5>
                <p class="card-text">Consulta y actualiza tu información personal de tu perfil.</p>
                <a href="Ver_informacion_administrador.php" class="btn btn-info">Ver Información</a>
            </div>
        </div>
    </div>

    <!-- Tarjeta 4 -->
    <div class="col-md-4 mt-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-user-tie fa-3x mb-3 text-warning"></i>
                <h5 class="card-title">Historial de Administrador</h5>
                <p class="card-text">Consulta el historial de tus actividades como administrador.</p>
                <a href="historial_administrador.php" class="btn btn-warning">Ver Historial</a>
            </div>
        </div>
    </div>

    <!-- Tarjeta 5 -->
    <div class="col-md-4 mt-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-clipboard-list fa-3x mb-3 text-danger"></i>
                <h5 class="card-title">Asistencia de Trabajadores</h5>
                <p class="card-text">Consulta y gestiona la asistencia de todos los empleados.</p>
                <a href="panel_administrador.php" class="btn btn-danger">Ver Asistencia</a>
            </div>
        </div>
    </div>

    <!-- Tarjeta 6 (Justificaciones) -->
    <div class="col-md-4 mt-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <i class="fas fa-file-signature fa-3x mb-3 text-secondary"></i>
                <h5 class="card-title">Justificaciones</h5>
                <p class="card-text">Revisa y gestiona las justificaciones enviadas por los empleados.</p>
                <a href="justificacion_mensaje_trabajador.php" class="btn btn-secondary">Ver Justificaciones</a>
            </div>
        </div>
    </div>
</div>
    <!-- Mensaje adicional -->
    <div class="alert alert-info mt-5 text-center" role="alert">
        <i class="fas fa-info-circle"></i> Recuerda mantener actualizada la información de los empleados y generar reportes periódicamente.
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>