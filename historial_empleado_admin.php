<?php
include 'includes/db.php';


// Obtener todas las áreas disponibles
$queryAreas = "SELECT DISTINCT area FROM usuarios";
$stmtAreas = $conn->prepare($queryAreas);
$stmtAreas->execute();
$areasDisponibles = $stmtAreas->fetchAll(PDO::FETCH_COLUMN);

// Obtener los valores seleccionados desde el formulario
$areaSeleccionada = isset($_GET['area']) ? $_GET['area'] : '';
$empleadoIdSeleccionado = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$mesSeleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('m'); // Mes actual por defecto

// Inicializar la lista de empleados
$empleados = [];
$datosAsistencia = ['asistio' => 0, 'tardanza' => 0, 'falta' => 0];

// Si se selecciona un área
if (!empty($areaSeleccionada)) {
    // Si también se selecciona un empleado específico
    if (!empty($empleadoIdSeleccionado)) {
        $query = "SELECT u.id, u.nombre, u.apellido, u.email, u.area, 
                         MAX(a.fecha) AS ultima_asistencia
                  FROM usuarios u
                  LEFT JOIN asistencias a ON u.id = a.usuario_id
                  WHERE u.area = :area AND u.id = :id
                  GROUP BY u.id, u.nombre, u.apellido, u.email, u.area";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':area', $areaSeleccionada, PDO::PARAM_STR);
        $stmt->bindParam(':id', $empleadoIdSeleccionado, PDO::PARAM_INT);
    } else {
        // Si no se selecciona un empleado, mostrar todos los empleados del área
        $query = "SELECT u.id, u.nombre, u.apellido, u.email, u.area, 
                         MAX(a.fecha) AS ultima_asistencia
                  FROM usuarios u
                  LEFT JOIN asistencias a ON u.id = a.usuario_id
                  WHERE u.area = :area
                  GROUP BY u.id, u.nombre, u.apellido, u.email, u.area";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':area', $areaSeleccionada, PDO::PARAM_STR);
    }

    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener los datos de asistencia del empleado seleccionado por mes
if (!empty($empleadoIdSeleccionado)) {
    $queryAsistencia = "SELECT 
                    SUM(CASE WHEN hora_entrada IS NOT NULL AND hora_entrada <= '08:00:00' THEN 1 ELSE 0 END) AS asistio,
                    SUM(CASE WHEN hora_entrada > '08:00:00' AND hora_entrada <= '17:00:00' THEN 1 ELSE 0 END) AS tardanza,
                    SUM(CASE WHEN hora_entrada IS NULL THEN 1 ELSE 0 END) AS falta,
                    SUM(CASE WHEN hora_entrada = '00:00:00' AND hora_salida = '00:00:00' THEN 1 ELSE 0 END) AS justificacion
                FROM asistencias
                WHERE usuario_id = :usuario_id AND MONTH(fecha) = :mes";
    $stmtAsistencia = $conn->prepare($queryAsistencia);
    $stmtAsistencia->bindParam(':usuario_id', $empleadoIdSeleccionado, PDO::PARAM_INT);
    $stmtAsistencia->bindParam(':mes', $mesSeleccionado, PDO::PARAM_INT);
    $stmtAsistencia->execute();
    $datosAsistencia = $stmtAsistencia->fetch(PDO::FETCH_ASSOC);
}

// Manejar la eliminación del empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_empleado']) && isset($_POST['empleado_id'])) {
    $empleadoId = (int)$_POST['empleado_id'];

    // Eliminar el empleado de la base de datos
    $queryEliminar = "DELETE FROM usuarios WHERE id = :id";
    $stmtEliminar = $conn->prepare($queryEliminar);
    $stmtEliminar->bindParam(':id', $empleadoId, PDO::PARAM_INT);

    if ($stmtEliminar->execute()) {
        echo json_encode(['success' => true, 'message' => 'Empleado eliminado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el empleado.']);
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
            <a href="historial_empleado_admin.php" class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'historial_empleado_admin.php' ? 'active' : ''; ?>" style="margin-bottom: 5px;">
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
    <h2 class="mb-4 text-center">
        <?php if (!empty($areaSeleccionada)): ?>
            Historial de Trabajador en el Área: <?php echo htmlspecialchars($areaSeleccionada); ?>
        <?php else: ?>
            Historial de Trabajador
        <?php endif; ?>
    </h2>

    <!-- Filtro por área, nombre y mes -->
    <form method="GET" class="mb-3">
        <div class="row">
            
            <div class="col-md-4">
                <select name="area" class="form-select" required>
                    <option value="">Selecciona un área</option>
                    <?php foreach ($areasDisponibles as $opcion): ?>
                        <option value="<?php echo htmlspecialchars($opcion); ?>" 
                            <?php echo ($opcion === $areaSeleccionada) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($opcion); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="nombre" class="form-select">
                    <option value="">Selecciona un empleado (opcional)</option>
                    <?php if (!empty($areaSeleccionada)): ?>
                        <?php
                        $queryNombres = "SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM usuarios WHERE area = :area";
                        $stmtNombres = $conn->prepare($queryNombres);
                        $stmtNombres->bindParam(':area', $areaSeleccionada, PDO::PARAM_STR);
                        $stmtNombres->execute();
                        $nombresEmpleados = $stmtNombres->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php foreach ($nombresEmpleados as $empleado): ?>
                            <option value="<?php echo htmlspecialchars($empleado['id']); ?>" 
                                <?php echo ($empleado['id'] == $empleadoIdSeleccionado) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($empleado['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="mes" class="form-select">
                    <option value="1" <?php echo ($mesSeleccionado == 1) ? 'selected' : ''; ?>>Enero</option>
                    <option value="2" <?php echo ($mesSeleccionado == 2) ? 'selected' : ''; ?>>Febrero</option>
                    <option value="3" <?php echo ($mesSeleccionado == 3) ? 'selected' : ''; ?>>Marzo</option>
                    <option value="4" <?php echo ($mesSeleccionado == 4) ? 'selected' : ''; ?>>Abril</option>
                    <option value="5" <?php echo ($mesSeleccionado == 5) ? 'selected' : ''; ?>>Mayo</option>
                    <option value="6" <?php echo ($mesSeleccionado == 6) ? 'selected' : ''; ?>>Junio</option>
                    <option value="7" <?php echo ($mesSeleccionado == 7) ? 'selected' : ''; ?>>Julio</option>
                    <option value="8" <?php echo ($mesSeleccionado == 8) ? 'selected' : ''; ?>>Agosto</option>
                    <option value="9" <?php echo ($mesSeleccionado == 9) ? 'selected' : ''; ?>>Septiembre</option>
                    <option value="10" <?php echo ($mesSeleccionado == 10) ? 'selected' : ''; ?>>Octubre</option>
                    <option value="11" <?php echo ($mesSeleccionado == 11) ? 'selected' : ''; ?>>Noviembre</option>
                    <option value="12" <?php echo ($mesSeleccionado == 12) ? 'selected' : ''; ?>>Diciembre</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Filtrar</button>
    </form>

    <!-- Tabla de empleados -->
    <?php if (!empty($areaSeleccionada)): ?>
        <?php if (count($empleados) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Área</th>
                        <th>Última Asistencia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empleados as $empleado): ?>
                        <tr id="empleado-<?php echo $empleado['id']; ?>">
                            <td><?php echo htmlspecialchars($empleado['id']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['email']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['area']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['ultima_asistencia'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick="eliminarEmpleado(<?php echo $empleado['id']; ?>)">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <!-- Gráfico circular -->
        <?php if (!empty($empleadoIdSeleccionado)): ?>
            <div class="chart-container">
                <canvas id="graficoAsistencia"></canvas>
            </div>
            <script>
                const asistio = <?php echo isset($datosAsistencia['asistio']) ? (int)$datosAsistencia['asistio'] : 0; ?>;
                const tardanza = <?php echo isset($datosAsistencia['tardanza']) ? (int)$datosAsistencia['tardanza'] : 0; ?>;
                const falta = <?php echo isset($datosAsistencia['falta']) ? (int)$datosAsistencia['falta'] : 0; ?>;
                const justificacion = <?php echo isset($datosAsistencia['justificacion']) ? (int)$datosAsistencia['justificacion'] : 0; ?>;

                const datosAsistencia = {
                    labels: ['Asistió', 'Tardanza', 'Falta', 'Justificación'],
                    datasets: [{
                        data: [asistio, tardanza, falta, justificacion],
                        backgroundColor: ['#27ae60', '#f39c12', '#e74c3c', '#f1c40f'], // Amarillo para Justificación
                        borderColor: ['#27ae60', '#f39c12', '#e74c3c', '#f1c40f'],
                        borderWidth: 1
                    }]
                };

                const opcionesAsistencia = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#2c3e50',
                                font: {
                                    size: 14
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    const label = datosAsistencia.labels[tooltipItem.dataIndex];
                                    const value = datosAsistencia.datasets[0].data[tooltipItem.dataIndex];
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    }
                };
                const ctx = document.getElementById('graficoAsistencia').getContext('2d');
                const graficoAsistencia = new Chart(ctx, {
                    type: 'doughnut',
                    data: datosAsistencia,
                    options: opcionesAsistencia
                });
            </script>
        <?php endif; ?>
        <?php else: ?>
            <p|12345676>No se encontraron empleados en el área seleccionada.</p|12345676''
             nhm'
        <?php endif; ?>
    <?php else: ?>
        <p>Por favor, selecciona un área para ver los empleados.</p>
    <?php endif; ?>
</div>

<script>
    function eliminarEmpleado(empleadoId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción eliminará al empleado de forma permanente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar la solicitud de eliminación al servidor con AJAX
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `eliminar_empleado=1&empleado_id=${empleadoId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        Swal.fire({
                            title: 'Eliminado',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        });

                        // Eliminar la fila de la tabla
                        const fila = document.getElementById(`empleado-${empleadoId}`);
                        if (fila) {
                            fila.remove();
                        }

                        // Verificar si la tabla está vacía
                        const tabla = document.querySelector('tbody');
                        if (!tabla.hasChildNodes()) {
                            const mensaje = document.createElement('p');
                            mensaje.textContent = 'No se encontraron empleados en el área seleccionada.';
                            tabla.parentElement.replaceWith(mensaje);
                        }
                    } else {
                        // Mostrar mensaje de error
                        Swal.fire({
                            title: 'Error',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al intentar eliminar al empleado.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                });
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>