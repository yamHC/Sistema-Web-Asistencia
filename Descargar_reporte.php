<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (isset($_POST['generar_reporte'])) {
    include 'includes/db.php';

    $area = $_POST['area'];
    $mes = $_POST['mes'];

    if ($area && $mes) {
        $sql = "SELECT u.nombre, u.apellido, u.email, u.dni, u.cargo, u.area, 
               a.fecha AS ultima_asistencia, 
               a.hora_entrada, 
               a.hora_salida, 
               TIMEDIFF(a.hora_salida, a.hora_entrada) AS horas_trabajadas,
               CASE 
                   WHEN a.hora_entrada = '00:00:00' AND a.hora_salida = '00:00:00' THEN 'Justificado'
                   WHEN a.hora_entrada IS NULL THEN 'Faltó'
                   WHEN a.hora_entrada > '08:00:00' THEN 'Tardanza'
                   ELSE 'Asistió'
               END AS estado,
               v.fecha_inicio AS vacaciones_inicio,
               v.fecha_fin AS vacaciones_fin
        FROM usuarios u
        LEFT JOIN asistencias a ON u.id = a.usuario_id AND DATE_FORMAT(a.fecha, '%Y-%m') = ?
        LEFT JOIN vacaciones v ON u.id = v.usuario_id
        WHERE u.area = ?
        ORDER BY u.nombre, a.fecha";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$mes, $area]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($resultados) > 0) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Título dinámico
            $titulo = "Asistencia del Área: $area";
            $sheet->setCellValue('A1', $titulo);
            $sheet->mergeCells('A1:G1'); // Combinar celdas para el título
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

            // Leyenda de colores
            $sheet->setCellValue('B2', 'Asistió');
            $sheet->setCellValue('C2', 'Tardanza');
            $sheet->setCellValue('D2', 'Faltó');
            $sheet->setCellValue('E2', 'Justificación');

            // Aplicar colores a la leyenda
            $sheet->getStyle('B2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF008000'); // Verde
            $sheet->getStyle('C2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFA500'); // Naranja
            $sheet->getStyle('D2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000'); // Rojo
            $sheet->getStyle('E2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF6F208'); // Amarillo

            $sheet->getStyle('B2:E2')->getFont()->getColor()->setARGB('FF000000'); // Texto negro

            // Variables para controlar la posición de las tablas
            $row = 4; // Fila inicial para la primera tabla

            // Agrupar datos por trabajador
            $trabajadores = [];
            foreach ($resultados as $fila) {
                $nombre_completo = $fila['nombre'] . ' ' . $fila['apellido'];
                if (!isset($trabajadores[$nombre_completo])) {
                    $trabajadores[$nombre_completo] = [];
                }
                $trabajadores[$nombre_completo][] = $fila;
            }

            // Generar una tabla por cada trabajador
            foreach ($trabajadores as $nombre_completo => $asistencias) {
                // Inicializar contadores
                $contador_asistio = 0;
                $contador_tardanza = 0;
                $contador_falto = 0;
                $contador_justificado = 0;
                $total_horas_extras = 0;
                $vacaciones_inicio = 'N/A';
                $vacaciones_fin = 'N/A';

                // Título del trabajador
                $sheet->setCellValue("A{$row}", "Trabajador: $nombre_completo");
                $sheet->mergeCells("A{$row}:G{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal('center');
                $row++;

                // Encabezados de la tabla
                $headers = ['Fecha', 'Hora Entrada', 'Hora Salida', 'Horas Trabajadas', 'Horas Extras', 'Estado'];
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue("{$col}{$row}", $header);
                    $col++;
                }

                // Aplicar estilo a los encabezados
                $headerStyle = $sheet->getStyle("A{$row}:G{$row}");
                $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF003366'); // Azul oscuro
                $headerStyle->getFont()->getColor()->setARGB('FFFFFFFF'); // Texto blanco
                $headerStyle->getFont()->setBold(true);
                $row++;

               // Agregar datos del trabajador
                foreach ($asistencias as $asistencia) {
                    $horas_trabajadas = $asistencia['horas_trabajadas'] ?: '00:00:00';
                    $horas_extras = '00:00:00';

                    // Calcular horas extras (si trabajó más de 8 horas)
                    if ($horas_trabajadas > '08:00:00') {
                        $horas_extras = date('H:i:s', strtotime($horas_trabajadas) - strtotime('08:00:00'));
                        $total_horas_extras += strtotime($horas_extras) - strtotime('00:00:00');
                    }

                    // Guardar fechas de vacaciones
                    if ($asistencia['vacaciones_inicio'] && $asistencia['vacaciones_fin']) {
                        $vacaciones_inicio = $asistencia['vacaciones_inicio'];
                        $vacaciones_fin = $asistencia['vacaciones_fin'];
                    }

                    $sheet->setCellValue("A{$row}", $asistencia['ultima_asistencia'] ?: 'N/A');
                    $sheet->setCellValue("B{$row}", $asistencia['hora_entrada'] ?: 'N/A');
                    $sheet->setCellValue("C{$row}", $asistencia['hora_salida'] ?: 'N/A');
                    $sheet->setCellValue("D{$row}", $horas_trabajadas);
                    $sheet->setCellValue("E{$row}", $horas_extras);
                    $sheet->setCellValue("F{$row}", $asistencia['estado'] ?: 'N/A');

                    // Aplicar colores según el estado
                    $estadoColor = match ($asistencia['estado']) {
                        'Asistió' => 'FF008000', // Verde
                        'Tardanza' => 'FFFFA500', // Naranja
                        'Faltó' => 'FFFF0000', // Rojo
                        'Justificado' => 'FFF6F208', // Amarillo
                        default => 'FFFFFFFF', // Blanco
                    };
                    $sheet->getStyle("F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($estadoColor);

                    // Contar el estado
                    if ($asistencia['estado'] === 'Asistió') {
                        $contador_asistio++;
                    } elseif ($asistencia['estado'] === 'Tardanza') {
                        $contador_tardanza++;
                    } elseif ($asistencia['estado'] === 'Faltó') {
                        $contador_falto++;
                    } elseif ($asistencia['estado'] === 'Justificado') {
                        $contador_justificado++;
                    }

                    $row++;
                }

                // Convertir total de horas extras a formato H:i:s
                $total_horas_extras_formatted = gmdate('H:i:s', $total_horas_extras);

                // Mostrar sumatorias al final de la tabla
                $sheet->setCellValue("D{$row}", "Totales:");
                $sheet->mergeCells("D{$row}:E{$row}");
                $sheet->getStyle("D{$row}:E{$row}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("D{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF003366'); // Azul oscuro
                $sheet->getStyle("D{$row}:E{$row}")->getFont()->getColor()->setARGB('FFFFFFFF'); // Texto blanco
                $row++;

                // Encabezados de la tabla de totales
                $sheet->setCellValue("D{$row}", "Categoría");
                $sheet->setCellValue("E{$row}", "Valor");
                $sheet->getStyle("D{$row}:E{$row}")->getFont()->setBold(true);
                $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("D{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC'); // Gris claro
                $row++;

                // Totales de asistencia
                $sheet->setCellValue("D{$row}", "Asistió");
                $sheet->setCellValue("E{$row}", $contador_asistio);
                $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal('center');
                $row++;

                $sheet->setCellValue("D{$row}", "Tardanza");
                $sheet->setCellValue("E{$row}", $contador_tardanza);
                $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal('center');
                $row++;

                $sheet->setCellValue("D{$row}", "Faltó");
                $sheet->setCellValue("E{$row}", $contador_falto);
                $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal('center');
                $row++;

                $sheet->setCellValue("D{$row}", "Justificado");
                $sheet->setCellValue("E{$row}", $contador_justificado);
                $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal('center');
                $row++;

                // Total de horas extras
                $sheet->setCellValue("D{$row}", "Total Horas Extras");
                $sheet->setCellValue("E{$row}", $total_horas_extras_formatted);
                $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal('center');
                $row++;

                // Fechas de vacaciones
                $sheet->setCellValue("D{$row}", "Vacaciones Inicio");
                $sheet->setCellValue("E{$row}", $vacaciones_inicio);
                $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal('center');
                $row++;

                $sheet->setCellValue("D{$row}", "Vacaciones Fin");
                $sheet->setCellValue("E{$row}", $vacaciones_fin);
                $sheet->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal('center');
                $row++;

                // Aplicar bordes a la tabla de totales
                $sheet->getStyle("D" . ($row - 7) . ":E" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $row += 5; // Espacio entre tablas
            }

            // Ajustar ancho de columnas
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Limpiar cualquier salida previa
            if (ob_get_length()) {
                ob_end_clean();
            }

            // Generar archivo
            $writer = new Xlsx($spreadsheet);
            $nombre_archivo = "Reporte_Asistencia_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $area) . ".xlsx";

            // Configurar cabeceras correctamente
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"$nombre_archivo\"");
            header('Cache-Control: max-age=0');

            // Guardar el archivo y enviarlo al usuario
            $writer->save('php://output');
            exit();
        } else {
            echo "<script>alert('No se encontraron registros para el área y mes seleccionados.');</script>";
        }
    } else {
        echo "<script>alert('Por favor, selecciona un área y un mes.');</script>";
    }
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
            background: #34495e;
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

        .btn {
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
    <h2>Generar Reporte de Asistencia</h2>
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
    <form action="Descargar_reporte.php" method="POST" class="mb-4">
        <div class="mb-3">
            <label for="area" class="form-label">Área</label>
            <select name="area" id="area" class="form-control" required>
                <option value="">Selecciona un área</option>
                <option value="Diseñador Web">Diseñador Web</option>
                <option value="Marketing">Marketing</option>
                <option value="Desarrollador Web">Desarrollador Web</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="mes" class="form-label">Mes</label>
            <input type="month" name="mes" id="mes" class="form-control" required>
        </div>
        <button type="submit" name="generar_reporte" class="btn btn-primary">Generar Reporte</button>
    </form>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
