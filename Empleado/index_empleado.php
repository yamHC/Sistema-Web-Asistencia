<?php
session_start();
include '../includes/db.php';

// Obtener el nombre del empleado logueado
$usuario_id = $_SESSION["usuario_id"];
$sql_usuario = "SELECT nombre FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
$nombre_empleado = $usuario['nombre'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            display: flex;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            height: 100vh;
            background-color: #f8f9fa;
        }

        .sidebar {
            width: 300px;
            background: #08B8F8;
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
            background: #0A63D3;
            color: #f8f9fa;
        }

        .sidebar a.active {
            background: #0A63D3;
            color: #f8f9fa;
            font-weight: bold;
        }

        .sidebar .logout {
    margin-top: auto;
    background: #e74c3c !important;
    color: #fff !important;
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

        .card {
            border-radius: 10px;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-weight: 500;
            font-size: 1.25rem;
        }

        .card-text {
            font-size: 1rem;
            color: #6c757d;
        }

        .btn {
            font-weight: 500;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .alert {
            font-size: 1.1rem;
            font-weight: 400;
        }
    </style>
</head>
<body>

<div class="sidebar">
        <img src="../img/logoBlanco.png" alt="Usuario">
        <a href="index_empleado.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index_empleado.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Bienvenido Trabajador</span>
        </a>

        <!-- Menú desplegable para Historial de Asistencia -->
        <div class="dropdown">
            <a href="#" class="dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['panel_empleado.php', 'marcar_asistencia.php', 'justificacion_mensaje_admin.php']) ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#historialMenu" aria-expanded="false">
                <i class="fas fa-history"></i>
                <span>Rebisa tu Asistencia</span>
            </a>
            <div id="historialMenu" class="collapse">
                <a href="panel_empleado.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'panel_empleado.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span>Ver Historial</span>
                </a>
                <a href="marcar_asistencia.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'marcar_asistencia.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span>Marcar Asistencia</span>
                </a>
                <a href="justificacion_mensaje_admin.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'justificacion_mensaje_admin.php' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    <span>Justificar Asistencia</span>
                </a>
            </div>
        </div>

        <a href="Ver_informacion_personal.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'Ver_informacion_personal.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i>
            <span>Ver Información Personal</span>
        </a>
        <a href="calendario.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'calendario.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Calendario de Asistencia</span>
        </a>
        <a href="../login.php" class="logout <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>

</div>

    <div class="main-content container mt-5">
    <h2 class="mb-4 text-center">¡Bienvenido, <?php echo htmlspecialchars($nombre_empleado); ?>!</h2>
    <p class="text-center">
        Este es tu panel de empleado. Aquí puedes gestionar tu asistencia, consultar tu historial y más.
    </p>

    <div class="row mt-4">
        <!-- Tarjeta 1 -->
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-history fa-3x mb-3 text-primary"></i>
                    <h5 class="card-title">Historial de Asistencia</h5>
                    <p class="card-text">Consulta tu historial de asistencia.</p>
                    <a href="panel_empleado.php" class="btn btn-primary">Ver Historial</a>
                </div>
            </div>
        </div>

        <!-- Tarjeta 2 -->
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-clock fa-3x mb-3 text-success"></i>
                    <h5 class="card-title">Marcar Asistencia</h5>
                    <p class="card-text">Registra tu asistencia diaria.</p>
                    <a href="marcar_asistencia.php" class="btn btn-success">Marcar Asistencia</a>
                </div>
            </div>
        </div>

        <!-- Tarjeta 3 -->
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-user-circle fa-3x mb-3 text-info"></i>
                    <h5 class="card-title">Información Personal</h5>
                    <p class="card-text">Consulta y actualiza tu información personal.</p>
                    <a href="Ver_informacion_personal.php" class="btn btn-info">Ver Información</a>
                </div>
            </div>
        </div>

        <!-- Tarjeta 4 -->
        <div class="col-md-4 mt-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-calendar-alt fa-3x mb-3 text-warning"></i>
                    <h5 class="card-title">Calendario de Asistencia</h5>
                    <p class="card-text">Consulta tu calendario de asistencia.</p>
                    <a href="calendario.php" class="btn btn-warning">Ver Calendario</a>
                </div>
            </div>
        </div>

        <!-- Tarjeta 5 (Justificación de Asistencia) -->
        <div class="col-md-4 mt-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-3x mb-3 text-danger"></i>
                    <h5 class="card-title">Justificación de Asistencia</h5>
                    <p class="card-text">Envía una justificación para tu asistencia.</p>
                    <a href="justificacion_mensaje_admin.php" class="btn btn-danger">Justificar Asistencia</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensaje adicional -->
    <div class="alert alert-info mt-5 text-center" role="alert">
        <i class="fas fa-info-circle"></i> Recuerda marcar tu asistencia diariamente y mantener tu información actualizada.
    </div>
</div>
</body>
</html>