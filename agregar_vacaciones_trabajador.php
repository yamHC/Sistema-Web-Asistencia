<?php
session_start();
include 'includes/db.php'; // Conexión a la base de datos

// Manejar la solicitud para agregar vacaciones
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["trabajador"])) {
    $trabajador_id = $_POST["trabajador"];
    $area = $_POST["area"];
    $fecha_inicio = $_POST["fecha_inicio"];
    $fecha_fin = $_POST["fecha_fin"];

    // Insertar las vacaciones en la tabla vacaciones
    $sql = "INSERT INTO vacaciones (usuario_id, fecha_inicio, fecha_fin, area) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([$trabajador_id, $fecha_inicio, $fecha_fin, $area])) {
        $_SESSION['mensaje'] = "Vacaciones agregadas correctamente.";
    } else {
        $_SESSION['mensaje'] = "Error al agregar las vacaciones.";
    }
    header("Location: agregar_vacaciones_trabajador.php");
    exit();
}

// Manejar solicitudes AJAX para obtener trabajadores según el área
if (isset($_GET['area'])) {
    $area = $_GET['area'];
    $sql = "SELECT id, nombre FROM usuarios WHERE area = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$area]);
    $trabajadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($trabajadores);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        .sidebar .dropdown-item {
            font-size: 0.85rem; /* Reducir el tamaño de la fuente de los hijos */
         }


        .main-content {
            flex: 1;
            padding: 20px;
        }
        .main-content h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
        }
        .main-content p {
            font-size: 1rem;
            color: #6c757d;
        }
        .leyenda {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
            padding: 10px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .leyenda div {
            display: flex;
            align-items: center;
            font-size: 1rem;
            color:rgb(14, 14, 14);
        }
        .circulo {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .verde {
            background-color: green;
        }
        .naranja {
            background-color: orange;
        }
        .rojo {
            background-color: red;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background:#08B8F8;
            color: white;
            padding: 15px;
            text-align: center;
        }
        td {
            padding: 10px;
            text-align: center;
            color: #2c3e50;
        }
        .asistencia-a-tiempo {
            color: green;
            font-weight: bold;
        }
        .tardanza {
            color: orange;
            font-weight: bold;
        }
        .falto {
            color: red;
            font-weight: bold;
        }

        /* .btn {
            background-color: #08B8F8;
        } */
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
<div class="container mt-5">
    <h1 class="text-center mb-4">Agregar Vacaciones al Trabajador</h1>

    <!-- Mostrar mensaje de éxito o error -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <script>
            Swal.fire({
                title: '<?php echo $_SESSION['mensaje']; ?>',
                icon: '<?php echo strpos($_SESSION['mensaje'], "Error") === false ? "success" : "error"; ?>',
                confirmButtonText: 'Aceptar'
            });
        </script>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <!-- Formulario para agregar vacaciones -->
    <form method="POST" action="agregar_vacaciones_trabajador.php">
        <div class="row">
            <!-- Primera columna -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="area" class="form-label">Área</label>
                    <select class="form-select" id="area" name="area" required>
                        <option value="">Seleccione un área</option>
                        <?php
                        $sql = "SELECT DISTINCT area FROM usuarios";
                        $stmt = $conn->query($sql);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="' . htmlspecialchars($row['area']) . '">' . htmlspecialchars($row['area']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="trabajador" class="form-label">Trabajador</label>
                    <select class="form-select" id="trabajador" name="trabajador" required>
                        <option value="">Seleccione un trabajador</option>
                    </select>
                </div>
            </div>

            <!-- Segunda columna -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                </div>
                <div class="mb-3">
                    <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                </div>
            </div>
        </div>

        <!-- Botón de acción -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Realizar Vacaciones</button>
        </div>
    </form>

    <!-- Enlace para ver las vacaciones -->
    <div class="text-center mt-4">
        <p>¿Quieres ver las vacaciones agregadas? <a href="ver_vacaciones_trabajador.php" class="text-primary">Presiona aquí</a></p>
    </div>
</div>

<script>
    // Llenar trabajadores dinámicamente según el área seleccionada
    document.getElementById('area').addEventListener('change', function () {
        const area = this.value;
        const trabajadorSelect = document.getElementById('trabajador');

        trabajadorSelect.innerHTML = '<option value="">Seleccione un trabajador</option>';

        if (area) {
            fetch('agregar_vacaciones_trabajador.php?area=' + encodeURIComponent(area))
                .then(response => response.json())
                .then(data => {
                    data.forEach(trabajador => {
                        const option = document.createElement('option');
                        option.value = trabajador.id;
                        option.textContent = trabajador.nombre;
                        trabajadorSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error al cargar los trabajadores:', error));
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
