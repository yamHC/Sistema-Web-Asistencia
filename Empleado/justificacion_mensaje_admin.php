<?php
session_start();
include '../includes/db.php'; // Conexión a la base de datos

// Verificar si el usuario está logueado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit();
}

// Obtener el nombre del empleado logueado
$usuario_id = $_SESSION["usuario_id"];
$sql_usuario = "SELECT nombre FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
$nombre_empleado = $usuario['nombre'];

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fecha = $_POST["fecha"];
    $motivo = $_POST["motivo"];
    $archivo = $_FILES["archivo"]["name"];
    $ruta_archivo = "uploads/" . basename($archivo);

    // Crear la carpeta "uploads" si no existe
    if (!is_dir("uploads")) {
        mkdir("uploads", 0777, true);
    }

    // Mover el archivo subido a la carpeta "uploads"
    if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $ruta_archivo)) {
        // Insertar la justificación en la tabla "justificaciones"
        $sql_insert = "INSERT INTO justificaciones (usuario_id, fecha, motivo, archivo_adjunto) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->execute([$_SESSION["usuario_id"], $fecha, $motivo, $ruta_archivo]);

        $_SESSION['justificacion_enviada'] = true;
        header("Location: justificacion_mensaje_admin.php");
        exit();
    } else {
        $_SESSION['justificacion_error'] = "Error al subir el archivo.";
        header("Location: justificacion_mensaje_admin.php");
        exit();
    }
}

// Mostrar alerta si existe
$justificacion_enviada = isset($_SESSION['justificacion_enviada']);
$justificacion_error = isset($_SESSION['justificacion_error']) ? $_SESSION['justificacion_error'] : null;
unset($_SESSION['justificacion_enviada'], $_SESSION['justificacion_error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justificar Asistencia</title>
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
        .container {
            flex: 1;
            padding: 40px;
        }
        .form-label {
            font-weight: 500;
        }
        .btn {
            font-weight: 500;
            transition: background-color 0.4s ease, color 0.4s ease;
        }
        .btn:hover {
            opacity: 0.9;
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

<div class="container mt-5">
    <h2 class="text-center">Justificar tu Asistencia</h2>
    <?php if ($justificacion_enviada): ?>
        <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            <i class="fas fa-check-circle"></i> ¡Justificación enviada correctamente!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php elseif ($justificacion_error): ?>
        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($justificacion_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    <form action="justificacion_mensaje_admin.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre_empleado); ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="fecha" name="fecha" required>
        </div>
        <div class="mb-3">
            <label for="motivo" class="form-label">Motivo</label>
            <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label for="archivo" class="form-label">Archivo Adjunto</label>
            <input type="file" class="form-control" id="archivo" name="archivo" accept="image/*,application/pdf" required>
        </div>
        <button type="submit" class="btn btn-primary">Enviar Justificación</button>
    </form>
</div>
</body>
</html>