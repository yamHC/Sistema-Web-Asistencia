<?php
include 'includes/db.php';

// Obtener todas las áreas disponibles
$queryAreas = "SELECT DISTINCT area FROM usuarios";
$stmtAreas = $conn->prepare($queryAreas);
$stmtAreas->execute();
$areasDisponibles = $stmtAreas->fetchAll(PDO::FETCH_COLUMN);

// Obtener el área y la fecha seleccionadas desde la URL
$area = isset($_GET['area']) ? $_GET['area'] : '';
$fechaSeleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : '';

// Inicializar la lista de empleados
$empleados = [];

// Solo ejecutar la consulta si ambos filtros están presentes
if (!empty($area) && !empty($fechaSeleccionada)) {
    // Construir la consulta SQL
    $query = "SELECT a.id, u.nombre, u.email, u.area, a.fecha, a.hora_entrada, a.hora_salida 
              FROM asistencias a
              JOIN usuarios u ON a.usuario_id = u.id
              WHERE u.area = :area AND a.fecha = :fecha";

    $stmt = $conn->prepare($query);

    // Vincular los parámetros
    $stmt->bindParam(':area', $area, PDO::PARAM_STR);
    $stmt->bindParam(':fecha', $fechaSeleccionada, PDO::PARAM_STR);

    // Ejecutar la consulta
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            padding: 20px;
        }
        .main-content h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            color:rgb(0, 0, 0);
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
            color:rgb(0, 0, 0);
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

        .amarillo {
            background-color: yellow;
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
            background: #08B8F8;
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

        .justificado {
            color: yellow;
            font-weight: bold;
        }

        .mt-3{
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
        <h2 class="mb-4 text-center">Lista de Asistencia de <?php echo htmlspecialchars($area ?? ''); ?> del día de <?php echo htmlspecialchars($fechaSeleccionada ?? ''); ?></h2>

        <div class="leyenda">
            <div class="leyenda-item">
                <span class="circulo" style="background: #27ae60;"></span> Asistencia a tiempo
            </div>
            <div class="leyenda-item">
                <span class="circulo" style="background: #f39c12;"></span> Llegada tarde
            </div>
            <div class="leyenda-item">
                <span class="circulo" style="background: #e74c3c;"></span> Falta
            </div>
            <div class="leyenda-item">
                <span class="circulo" style="background:rgb(255, 230, 6);"></span> Justificación
            </div>
        </div>

        <form method="GET" class="mb-3">
            <select name="area" class="form-select" required>
                <option value="">Selecciona un área</option>
                <?php foreach ($areasDisponibles as $opcion): ?>
                    <option value="<?php echo htmlspecialchars($opcion); ?>" 
                        <?php echo ($opcion === $area) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($opcion); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="fecha" class="form-control mt-2" value="<?php echo htmlspecialchars($fechaSeleccionada ?? ''); ?>" required>
            <button type="submit" class="btn btn-primary mt-3">Filtrar</button>
        </form>

        <?php if (!empty($area) && !empty($fechaSeleccionada)): ?>
            <?php if (count($empleados) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <!-- <th>ID</th> -->
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Área</th>
                            <th>Fecha</th>
                            <th>Hora Entrada</th>
                            <th>Hora Salida</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $empleado): 
                            $horaEntrada = !empty($empleado['hora_entrada']) ? $empleado['hora_entrada'] : 'N/A';
                            $horaSalida = !empty($empleado['hora_salida']) ? $empleado['hora_salida'] : 'N/A';
                            $estado = '';
                            $clase_estado = '';

                            // Determinar el estado
                            if ($horaEntrada === '00:00:00' && $horaSalida === '00:00:00') {
                                $estado = "Justificación";
                                $clase_estado = 'justificado'; // Clase CSS para el texto amarillo
                            } elseif ($horaEntrada === 'N/A') {
                                $estado = "Faltó";
                                $clase_estado = 'falto'; // Clase CSS para el texto rojo
                            } elseif (strtotime($horaEntrada) > strtotime("09:00:00")) {
                                $estado = "Tardanza";
                                $clase_estado = 'tardanza'; // Clase CSS para el texto naranja
                            } else {
                                $estado = "Asistió a tiempo";
                                $clase_estado = 'asistencia-a-tiempo'; // Clase CSS para el texto verde
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($empleado['nombre'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($empleado['email'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($empleado['area'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($empleado['fecha'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($horaEntrada); ?></td>
                                <td><?php echo htmlspecialchars($horaSalida); ?></td>
                                <td class="<?php echo $clase_estado; ?>"><?php echo $estado; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No se han encontrado registros para el área y fecha seleccionadas.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>Por favor, selecciona un área y una fecha para ver los registros.</p>
        <?php endif; ?>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
