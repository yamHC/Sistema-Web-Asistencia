<?php
include 'includes/db.php';

// Obtener todos los administradores para el filtro de nombres
$queryNombres = "SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM usuarios WHERE cargo = 'admin'";
$stmtNombres = $conn->prepare($queryNombres);
$stmtNombres->execute();
$nombresAdministradores = $stmtNombres->fetchAll(PDO::FETCH_ASSOC);

// Obtener los valores seleccionados desde el formulario
$cargoSeleccionado = isset($_GET['cargo']) ? $_GET['cargo'] : 'Administrador';
$nombreBuscado = isset($_GET['nombre']) ? $_GET['nombre'] : '';

// Construir la consulta para obtener los administradores
$query = "SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo, email, 'Administrador' AS cargo 
          FROM usuarios 
          WHERE cargo = 'admin'";

// Si se busca por nombre, agregar la condición a la consulta
if (!empty($nombreBuscado)) {
    $query .= " AND id = :id";
}

$stmt = $conn->prepare($query);

// Si se busca por nombre, vincular el parámetro
if (!empty($nombreBuscado)) {
    $stmt->bindParam(':id', $nombreBuscado, PDO::PARAM_INT);
}

$stmt->execute();
$administradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Manejar la eliminación del administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_administrador']) && isset($_POST['administrador_id'])) {
    $administradorId = (int)$_POST['administrador_id'];

    // Eliminar el administrador de la base de datos
    $queryEliminar = "DELETE FROM usuarios WHERE id = :id";
    $stmtEliminar = $conn->prepare($queryEliminar);
    $stmtEliminar->bindParam(':id', $administradorId, PDO::PARAM_INT);

    if ($stmtEliminar->execute()) {
        echo json_encode(['success' => true, 'message' => 'Administrador eliminado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el administrador.']);
    }
    exit; // Detener la ejecución para evitar cargar el resto del código
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding: 20px;
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
        .btn-danger {
            background-color: #e74c3c;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .chart-container {
            width: 300px;
            height: 300px;
            margin: 20px auto;
        }

        .btn-primary {
            background-color: #08B8F8;
            
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
        <h2 class="mb-4 text-center">Historial de Administradores</h2>

        <!-- Formulario de filtro -->
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-6">
                    <select name="cargo" class="form-select" disabled>
                        <option value="Administrador" selected>Administrador</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <select name="nombre" class="form-select">
                        <option value="">Selecciona un administrador</option>
                        <?php foreach ($nombresAdministradores as $admin): ?>
                            <option value="<?php echo htmlspecialchars($admin['id']); ?>" 
                                <?php echo ($admin['id'] == $nombreBuscado) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($admin['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Filtrar</button>
        </form>

        <!-- Tabla de administradores -->
        <?php if (count($administradores) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Cargo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($administradores as $administrador): ?>
                        <tr id="administrador-<?php echo $administrador['id']; ?>">
                            <td><?php echo htmlspecialchars($administrador['id']); ?></td>
                            <td><?php echo htmlspecialchars($administrador['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($administrador['email']); ?></td>
                            <td><?php echo htmlspecialchars($administrador['cargo']); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick="eliminarAdministrador(<?php echo $administrador['id']; ?>)">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron administradores.</p>
        <?php endif; ?>
    </div>
    <script>
    function eliminarAdministrador(administradorId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción eliminará al administrador de forma permanente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `eliminar_administrador=1&administrador_id=${administradorId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Eliminado', data.message, 'success');
                        const fila = document.getElementById(`administrador-${administradorId}`);
                        if (fila) fila.remove();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Ocurrió un error al intentar eliminar el administrador.', 'error');
                });
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>