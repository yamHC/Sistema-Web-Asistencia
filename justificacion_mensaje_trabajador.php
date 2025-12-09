<?php
session_start();
include 'includes/db.php'; // Conexión a la base de datos

// Verificar si el usuario está logueado como administrador
if (!isset($_SESSION["usuario_id"]) || $_SESSION["cargo"] != "admin") {
    header("Location: login.php");
    exit();
}

// Filtrar las justificaciones por fecha seleccionada
$justificaciones_filtradas = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["fecha"])) {
    $fecha_seleccionada = $_POST["fecha"];
    $sql = "SELECT j.id, j.fecha, j.motivo, j.archivo_adjunto, u.nombre, u.area
            FROM justificaciones j
            INNER JOIN usuarios u ON j.usuario_id = u.id
            WHERE j.fecha = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fecha_seleccionada]);
    $justificaciones_filtradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Eliminar justificación si se envía la solicitud
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["eliminar_id"])) {
    $id = intval($_POST["eliminar_id"]);
    $sql = "DELETE FROM justificaciones WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Redirigir al usuario de vuelta a la misma página con un mensaje de éxito
    $_SESSION['mensaje'] = "La justificación ha sido eliminada correctamente.";
    header("Location: justificacion_mensaje_trabajador.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justificaciones de Trabajadores</title>
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
            background: #08B8F8;
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
<div class="container mt-5">
    <h2 class="text-center mb-4">
        <?php if (!empty($fecha_seleccionada)): ?>
            Justificaciones del día <?php echo htmlspecialchars($fecha_seleccionada); ?>
        <?php else: ?>
            Justificaciones de Trabajadores
        <?php endif; ?>
    </h2>
    <p class="text-center mb-4">Seleccione una fecha para ver las justificaciones correspondientes.</p>
    <!-- Formulario para seleccionar la fecha -->
    <form method="POST" action="justificacion_mensaje_trabajador.php" class="text-center mb-5">
        <div class="mb-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input type="date" class="form-control w-50 mx-auto" id="fecha" name="fecha" required>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>
    <!-- Mostrar justificaciones filtradas en una tabla -->
    <?php if (!empty($justificaciones_filtradas)): ?>
        <table class="table-bordered ">
            <thead class="table-primary text-center">
                <tr>
                    <th>Nombre</th>
                    <th>Motivo</th>
                    <th>Área</th>
                    <th>Ver Imagen</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($justificaciones_filtradas as $justificacion): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($justificacion["nombre"]); ?></td>
                        <td><?php echo htmlspecialchars($justificacion["motivo"]); ?></td>
                        <td><?php echo htmlspecialchars($justificacion["area"]); ?></td>
                        <td class="text-center">
                            <?php if (!empty($justificacion["archivo_adjunto"])): ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalArchivo<?php echo $justificacion["id"]; ?>">Ver Imagen</button>
                            <?php else: ?>
                                <span class="text-muted">No hay archivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm" onclick="confirmarEliminacion(<?php echo $justificacion['id']; ?>)">Eliminar</button>
                        </td>
                    </tr>
                    <!-- Modal para mostrar la imagen o PDF -->
                    <?php if (!empty($justificacion["archivo_adjunto"])): ?>
                    <div class="modal fade" id="modalArchivo<?php echo $justificacion["id"]; ?>" tabindex="-1" aria-labelledby="modalArchivoLabel<?php echo $justificacion["id"]; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalArchivoLabel<?php echo $justificacion["nombre"]; ?>">Archivo de <?php echo htmlspecialchars($justificacion["nombre"]); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <?php
                                    // Ajustar ruta para acceder correctamente al archivo
                                    $ruta_archivo = htmlspecialchars($justificacion["archivo_adjunto"]);
                                    if (strpos($ruta_archivo, 'uploads/') === 0) {
                                        $ruta_archivo = 'Empleado/' . $ruta_archivo;
                                    }
                                    $extension = strtolower(pathinfo($ruta_archivo, PATHINFO_EXTENSION));
                                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                                        echo '<img src="' . $ruta_archivo . '" alt="Archivo de Justificación" class="img-fluid" style="max-height: 80vh;">';
                                    } elseif ($extension === 'pdf') {
                                        echo '<embed src="' . $ruta_archivo . '" type="application/pdf" width="100%" height="600px" />';
                                    } else {
                                        echo '<p class="text-muted">El archivo no es compatible. Solo se permiten archivos PDF, PNG, JPG y JPEG.</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (isset($fecha_seleccionada)): ?>
        <p class="mt-5 text-center">No hay justificaciones para la fecha seleccionada.</p>
    <?php endif; ?>

    <script>
    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear un formulario dinámico para enviar la solicitud de eliminación
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'justificacion_mensaje_trabajador.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'eliminar_id';
                input.value = id;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    </script>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>