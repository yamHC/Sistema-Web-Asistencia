<?php
session_start();
include '../includes/db.php'; // Ruta corregida

// Ajustar la zona horaria a Perú (UTC-5)
date_default_timezone_set('America/Lima');

// Obtener los datos del usuario, incluyendo el área
$usuario_id = $_SESSION["usuario_id"];
$sql_usuario = "SELECT nombre, apellido, email, dni, cargo, area FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);



// Obtener el historial de asistencias del usuario
$sql_asistencias = "SELECT fecha, hora_entrada, hora_salida FROM asistencias WHERE usuario_id = ? ORDER BY fecha DESC";
$stmt_asistencias = $conn->prepare($sql_asistencias);
$stmt_asistencias->execute([$usuario_id]);
$asistencias = $stmt_asistencias->fetchAll(PDO::FETCH_ASSOC);

// Configuración de la paginación
$registros_por_pagina = 8; // Número de registros por página
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Consulta para obtener el total de registros
$sql_total = "SELECT COUNT(*) AS total FROM asistencias WHERE usuario_id = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->execute([$usuario_id]);
$total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

// Calcular el número total de páginas
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta para obtener los registros de la página actual
$sql_asistencias = "SELECT fecha, hora_entrada, hora_salida FROM asistencias WHERE usuario_id = ? ORDER BY fecha DESC LIMIT ? OFFSET ?";
$stmt_asistencias = $conn->prepare($sql_asistencias);
$stmt_asistencias->bindParam(1, $usuario_id, PDO::PARAM_INT);
$stmt_asistencias->bindParam(2, $registros_por_pagina, PDO::PARAM_INT);
$stmt_asistencias->bindParam(3, $offset, PDO::PARAM_INT);
$stmt_asistencias->execute();
$asistencias = $stmt_asistencias->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcar Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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

    .main-content h2 {
        font-size: 2rem;
        margin-bottom: 20px;
        color:rgb(0, 0, 0);
    }

    .main-content p {
        font-size: 1rem;
        color:rgb(0, 0, 0);
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
        /* font-weight: bold; */
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
        background:#0A63D3;
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

    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 30px; /* Más separación con la tabla */
    }

    .pagination .btn {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 1rem;
        font-weight: bold;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .pagination .btn-light {
        background-color: #f8f9fa;
        color: #2c3e50;
        border: 1px solid #ddd;
    }

    .pagination .btn-light:hover {
        background-color: #e2e6ea;
        color:#0A63D3;
    }

    .pagination .btn-primary {
        background-color: #007bff;
        color: white;
        border: 1px solid #0056b3;
    }

    .pagination .btn-primary:hover {
        background-color: #0056b3;
        color: white;
    }

    .pagination .btn-secondary {
        background-color: #6c757d;
        color: white;
        border: 1px solid #545b62;
    }

    .pagination .btn-secondary:hover {
        background-color: #545b62;
        color: white;
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
    <div class="main-content">
        <h2 class="text-center mb-4">Historial de asistencia de <?php echo $usuario["nombre"] . " " . $usuario["apellido"]; ?></h2>

        <!-- Leyenda -->
        <div class="leyenda d-flex justify-content-center align-items-center mb-4">
            <div class="d-flex align-items-center me-4">
                <span class="circulo verde"></span> A tiempo
            </div>
            <div class="d-flex align-items-center me-4">
                <span class="circulo naranja"></span> Tardanza
            </div>
            <div class="d-flex align-items-center">
                <span class="circulo rojo"></span> Falta
            </div>
            <div class="d-flex align-items-center">
                <span class="circulo amarillo"></span> Justificado
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>DNI</th>
                    <th>Cargo</th>
                    <th>Área</th>
                    <th>Fecha</th>
                    <th>Hora Entrada</th>
                    <th>Hora Salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <?php foreach ($asistencias as $asistencia) : ?>
        <?php
        $hora_entrada = $asistencia["hora_entrada"] ?? '';
        $hora_salida = $asistencia["hora_salida"] ?? '';
        $estado = '';
        $clase_estado = '';

        // Formatear la fecha como día/mes/año
        $fecha_formateada = date('d/m/Y', strtotime($asistencia["fecha"]));

          // Determinar el estado
          if ($hora_entrada === '00:00:00' && $hora_salida === '00:00:00') {
            $estado = 'Justificado';
            $clase_estado = 'justificado'; // Clase CSS para el texto amarillo
        } elseif (!empty($hora_entrada) && $hora_entrada !== "00:00:00") {
            if (strtotime($hora_entrada) <= strtotime("08:00:00")) {
                $estado = 'Asistió';
                $clase_estado = 'asistencia-a-tiempo'; // Clase CSS para el texto verde
            } else {
                $estado = 'Tarde';
                $clase_estado = 'tardanza'; // Clase CSS para el texto naranja
            }
        } else {
            $estado = 'Falta';
            $clase_estado = 'falto'; // Clase CSS para el texto rojo
        }
        ?>
        <tr>
            <td><?php echo $usuario["nombre"] . " " . $usuario["apellido"]; ?></td>
            <td><?php echo $usuario["email"]; ?></td>
            <td><?php echo $usuario["dni"]; ?></td>
            <td><?php echo ucfirst($usuario["cargo"]); ?></td>
            <td><?php echo $usuario["area"]; ?></td>
            <td><?php echo $fecha_formateada; ?></td>
            <td><?php echo !empty($hora_entrada) ? $hora_entrada : 'N/A'; ?></td>
            <td><?php echo !empty($hora_salida) ? $hora_salida : 'N/A'; ?></td>
            <td class="<?php echo $clase_estado; ?>"><?php echo $estado; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
        </table>

        <div class="pagination">
            <?php if ($pagina_actual > 1): ?>
                <a href="?pagina=<?php echo $pagina_actual - 1; ?>" class="btn btn-secondary">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?php echo $i; ?>" class="btn <?php echo $i == $pagina_actual ? 'btn-primary' : 'btn-light'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_actual + 1; ?>" class="btn btn-secondary">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>