<?php
session_start();
include 'includes/db.php'; // Conexión a la base de datos

// Manejar la solicitud AJAX para obtener los trabajadores de un área
if (isset($_GET['area'])) {
    $area = $_GET['area'];
    $sql = "SELECT id, nombre FROM usuarios WHERE area = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$area]);
    $trabajadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Devolver los trabajadores en formato JSON
    echo json_encode($trabajadores);
    exit();
}

// Variables para filtrar
$area = isset($_POST['area']) ? $_POST['area'] : '';
$trabajador_id = isset($_POST['trabajador']) ? $_POST['trabajador'] : '';

// Consultar vacaciones
$sql = "SELECT v.id, v.fecha_inicio, v.fecha_fin, v.area, u.nombre 
        FROM vacaciones v
        INNER JOIN usuarios u ON v.usuario_id = u.id";
$params = [];

if (!empty($area)) {
    $sql .= " WHERE v.area = ?";
    $params[] = $area;

    if (!empty($trabajador_id)) {
        $sql .= " AND v.usuario_id = ?";
        $params[] = $trabajador_id;
    }
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$vacaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Manejar la edición de vacaciones
if (isset($_POST['editar_vacaciones'])) {
    $vacacion_id = $_POST['vacacion_id'];
    $fecha_inicio = $_POST['editar_fecha_inicio'];
    $fecha_fin = $_POST['editar_fecha_fin'];

    $sql = "UPDATE vacaciones SET fecha_inicio = ?, fecha_fin = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fecha_inicio, $fecha_fin, $vacacion_id]);

    // Redirigir después de editar
    header("Location: ver_vacaciones_trabajador.php");
    exit();
}

// Manejar la eliminación de vacaciones
if (isset($_GET['eliminar_vacacion'])) {
    $vacacion_id = $_GET['eliminar_vacacion'];

    $sql = "DELETE FROM vacaciones WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$vacacion_id]);

    // Redirigir después de eliminar
    header("Location: ver_vacaciones_trabajador.php");
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

    <!-- Descargar reporte de trabajo -->
    <a href="ver_reporte_trabajo_trabajador.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ver_reporte_trabajo_trabajador.php' ? 'active' : ''; ?>">
        <i class="fas fa-briefcase"></i> <!-- Nuevo ícono -->
        <span>Descargar Reporte de Trabajo</span>
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
<h1 class="text-center mb-4">Ver Vacaciones del Trabajador</h1>

<!-- Formulario para filtrar vacaciones -->
<form method="POST" action="ver_vacaciones_trabajador.php" class="mb-4">
    <div class="row">
        <!-- Campo para seleccionar el área -->
        <div class="col-md-6">
            <div class="mb-3">
                <label for="area" class="form-label">Área</label>
                <select class="form-select" id="area" name="area" required>
                    <option value="">Seleccione un área</option>
                    <?php
                    $sql_areas = "SELECT DISTINCT area FROM usuarios";
                    $stmt_areas = $conn->query($sql_areas);
                    while ($row = $stmt_areas->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($row['area'] == $area) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($row['area']) . '" ' . $selected . '>' . htmlspecialchars($row['area']) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Campo para seleccionar el trabajador -->
        <div class="col-md-6">
            <div class="mb-3">
                <label for="trabajador" class="form-label">Trabajador</label>
                <select class="form-select" id="trabajador" name="trabajador" required>
                    <option value="">Seleccione un trabajador</option>
                    <?php
                    if (!empty($area)) {
                        $sql_trabajadores = "SELECT id, nombre FROM usuarios WHERE area = ?";
                        $stmt_trabajadores = $conn->prepare($sql_trabajadores);
                        $stmt_trabajadores->execute([$area]);
                        while ($row = $stmt_trabajadores->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($row['id'] == $trabajador_id) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($row['id']) . '" ' . $selected . '>' . htmlspecialchars($row['nombre']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Botón para filtrar -->
    <div class="text-center">
        <button type="submit" class="btn btn-primary">Filtrar Vacaciones</button>
    </div>
</form>

   <!-- Tabla de vacaciones -->
   <?php if (!empty($area) && !empty($trabajador_id)): ?>
        <div class="mt-5">
            <h2 class="text-center">Vacaciones Existentes</h2>
            <table class="table-bordered">
                <thead>
                    <tr>
                        <th>Trabajador</th>
                        <th>Área</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vacaciones)): ?>
                        <?php foreach ($vacaciones as $vacacion): ?>
                            <tr>
                                <td><?= htmlspecialchars($vacacion['nombre']) ?></td>
                                <td><?= htmlspecialchars($vacacion['area']) ?></td>
                                <td><?= htmlspecialchars($vacacion['fecha_inicio']) ?></td>
                                <td><?= htmlspecialchars($vacacion['fecha_fin']) ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm btn-editar" data-bs-toggle="modal" data-bs-target="#editarModal" 
                                        data-id="<?= $vacacion['id'] ?>" 
                                        data-fecha-inicio="<?= $vacacion['fecha_inicio'] ?>" 
                                        data-fecha-fin="<?= $vacacion['fecha_fin'] ?>">
                                        Editar
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?= $vacacion['id'] ?>">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No se encontraron vacaciones.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center mt-4">
            Por favor, seleccione un área y un trabajador para filtrar las vacaciones.
        </div>
    <?php endif; ?>
</div>

<!-- Modal para editar vacaciones -->
<div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="ver_vacaciones_trabajador.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarModalLabel">Editar Vacaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="vacacion_id" id="editarVacacionId">
                    <div class="mb-3">
                        <label for="editarFechaInicio" class="form-label">Fecha de Inicio</label>
                        <input type="date" class="form-control" id="editarFechaInicio" name="editar_fecha_inicio" required>
                    </div>
                    <div class="mb-3">
                        <label for="editarFechaFin" class="form-label">Fecha de Fin</label>
                        <input type="date" class="form-control" id="editarFechaFin" name="editar_fecha_fin" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_vacaciones" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    // Llenar el modal con los datos de la fila seleccionada
    document.querySelectorAll('.btn-editar').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const fechaInicio = this.getAttribute('data-fecha-inicio');
            const fechaFin = this.getAttribute('data-fecha-fin');

            document.getElementById('editarVacacionId').value = id;
            document.getElementById('editarFechaInicio').value = fechaInicio;
            document.getElementById('editarFechaFin').value = fechaFin;
        });
    });

    // Confirmación antes de eliminar
    document.querySelectorAll('.btn-eliminar').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `ver_vacaciones_trabajador.php?eliminar_vacacion=${id}`;
                }
            });
        });
    });

    // Actualizar dinámicamente los trabajadores según el área seleccionada
    document.getElementById('area').addEventListener('change', function () {
        const area = this.value;
        const trabajadorSelect = document.getElementById('trabajador');

        // Limpiar el campo de trabajadores
        trabajadorSelect.innerHTML = '<option value="">Seleccione un trabajador</option>';

        if (area) {
            // Realizar una solicitud AJAX para obtener los trabajadores del área seleccionada
            fetch('ver_vacaciones_trabajador.php?area=' + encodeURIComponent(area))
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
