<?php
session_start();
include '../includes/db.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id']; // ID del trabajador logueado

// Consultar las asistencias del trabajador
$sql_asistencias = "SELECT fecha, hora_entrada, hora_salida FROM asistencias WHERE usuario_id = ?";
$stmt_asistencias = $conn->prepare($sql_asistencias);
$stmt_asistencias->execute([$usuario_id]);
$asistencias = $stmt_asistencias->fetchAll(PDO::FETCH_ASSOC);

// Consultar las vacaciones del trabajador
$sql_vacaciones = "SELECT fecha_inicio, fecha_fin FROM vacaciones WHERE usuario_id = ?";
$stmt_vacaciones = $conn->prepare($sql_vacaciones);
$stmt_vacaciones->execute([$usuario_id]);
$vacaciones = $stmt_vacaciones->fetchAll(PDO::FETCH_ASSOC);

// Preparar eventos para el calendario
$eventos = [];

// Agregar asistencias al calendario
foreach ($asistencias as $asistencia) {
    $horaEntrada = $asistencia['hora_entrada'];
    $horaSalida = $asistencia['hora_salida'] ?? 'No registrada';
    $fecha = $asistencia['fecha'];

    if ($horaEntrada == 'Vacaciones') {
        $color = 'blue'; // Azul para vacaciones
        $title = 'Vacaciones';
    } elseif (!$horaEntrada || $horaEntrada == '00:00:00') {
        $color = '#e74c3c'; // Rojo para faltas
        $title = 'Falta';
    } else {
        $color = ($horaEntrada < '09:00:00') ? '#27ae60' : '#f39c12'; // Verde o naranja según la hora de entrada
        $title = "Entrada: $horaEntrada | Salida: $horaSalida";
    }

    $eventos[] = [
        'title' => $title,
        'start' => $fecha,
        'color' => $color
    ];
}

// Agregar vacaciones al calendario
foreach ($vacaciones as $vacacion) {
    $eventos[] = [
        'title' => 'Vacaciones',
        'start' => $vacacion['fecha_inicio'],
        'end' => date('Y-m-d', strtotime($vacacion['fecha_fin'] . ' +1 day')),
        'color' => 'blue'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Asistencia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>
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
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        #calendar {
            width: 100%;
            max-width: 900px;
            height: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .leyenda {
            display: flex;
            justify-content: center;
            gap: 55px;
            margin-bottom: 20px;
            padding: 10px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .leyenda-item {
            display: flex;
            align-items: center;
            font-size: 16px;
            color: #000;
        }

        .circulo {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            border: 2px solid #bdc3c7;
        }

        .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .fc-button {
            background: #08B8F8;
            color: white;
            border: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .fc-button:hover {
            background: #0A63D3;
        }

        .fc-daygrid-day-number {
            font-weight: bold;
            color: #333;
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

    <div class="main-content">
        <h2 class="text-center mb-4">Calendario de Asistencia</h2>

        <!-- Leyenda -->
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
                <span class="circulo" style="background: #f1c40f;"></span> Justificado
            </div>
        </div>

        <!-- Calendario -->
        <div id="calendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode($eventos); ?>
            });
            calendar.render();
        });
    </script>

</body>
</html>