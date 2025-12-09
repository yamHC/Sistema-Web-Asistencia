<?php
include 'includes/db.php'; // Asegúrate de que esta ruta sea correcta
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $email = trim($_POST["email"]);
    $dni = trim($_POST["dni"]);
    $cargo = $_POST["cargo"];
    $area = ($cargo == "empleado") ? $_POST["area"] : "No Aplica";
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);

    // Verificar si el email o el DNI ya existen en la base de datos
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? OR dni = ?");
    $stmt->execute([$email, $dni]);

    if ($stmt->rowCount() > 0) {
        $error = "El correo o DNI ya están registrados.";
    } else {
        $sql = "INSERT INTO usuarios (nombre, apellido, email, dni, cargo, area, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$nombre, $apellido, $email, $dni, $cargo, $area, $password])) {
            echo "<script>
                window.location.href = 'registro.php?mensaje=registrado';
            </script>";
            exit();
        } else {
            $error = "Error al registrar usuario.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> <!-- Font Awesome -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
         body {
            display: flex;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            overflow: hidden; /* Evita que el contenido se desplace */
            background-color: #f8f9fa; /* Fondo general */
        }

        .left-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color:rgb(255, 255, 255); /* Fondo sólido */
            position: relative; /* Asegura que el diseño no se mueva */
            z-index: 1; /* Mantiene el formulario en su lugar */
            overflow: hidden; /* Evita que el contenido se desborde */
        }

        .registro-container {
            width: 100%;
            max-width: 520px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow:0px 4px 10px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative; /* Asegura que el diseño no se mueva */
            z-index: 2; /* Mantiene el formulario por encima del fondo */
        }

        .right-section {
            flex: 1;
            background-color: #007bff;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .right-section img {
            width: 600px;
            margin-bottom: 20px;
        }

        .registro-container input,
        .registro-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .registro-container button {
            width: 100%;
            padding: 10px;
            background-color: #08b8f8;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .registro-container button:hover {
            background-color:  #08b8f8;
        }

        .registro-container a {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #007bff;
        }

        .registro-container i {
            font-size: 50px; /* Tamaño del ícono */
            color: #4caf50; /* Color del ícono */
            margin-bottom: 10px; /* Espaciado debajo del ícono */
        }

        .registro-container a:hover {
            text-decoration: underline;
        }

        /* Personalización de SweetAlert2 */
        .custom-swal-popup {
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
        }

        .custom-swal-title {
            font-size: 24px;
            font-weight: bold;
            color: #4caf50; /* Color verde para el título */
        }

        .custom-swal-button {
            font-size: 16px;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 5px;
            background-color: #4caf50; /* Color verde para el botón */
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }

        .custom-swal-button:hover {
            background-color: #388e3c; /* Color más oscuro al pasar el mouse */
        }
    </style>
</head>
<body>
<div class="left-section">
        <div class="registro-container">
            <i class="fas fa-sign-in-alt" style="font-size: 50px; color: #0a63d3; margin-bottom: 10px;"></i> <!-- Ícono azul -->
            <h2>Registro de Usuario</h2>
            <form id="registroForm" action="registro.php" method="POST">
                <input type="text" name="nombre" id="nombre" placeholder="Nombre" required>
                <input type="text" name="apellido" id="apellido" placeholder="Apellido" required>
                <input type="email" name="email" id="email" placeholder="Correo Electrónico" required>
                <input type="text" name="dni" id="dni" placeholder="DNI" required>
                <select name="cargo" id="cargo" onchange="toggleAreaField()">
                    <option value="empleado">Empleado</option>
                    <option value="admin">Administrador</option>
                </select>
                <div id="areaContainer">
                    <select name="area" id="area">
                        <option value="">Selecciona un área</option>
                        <option value="Diseñador Web">Diseñador Web</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Desarrollador Web">Desarrollador Web</option>
                    </select>
                </div>
                <input type="password" name="password" id="password" placeholder="Contraseña" required>
                <button type="submit">Registrar</button>
                <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
            </form>
        </div>
    </div>

    <div class="right-section">
        <img src="img/logoBlanco.png" alt="Logo">
    </div>
    
    <script>
        // Función para mostrar/ocultar el campo de área según el cargo seleccionado
        function toggleAreaField() {
            const cargo = document.getElementById('cargo').value;
            const areaContainer = document.getElementById('areaContainer');
            if (cargo === 'admin') {
                areaContainer.style.display = 'none'; // Ocultar el campo de área
            } else {
                areaContainer.style.display = 'block'; // Mostrar el campo de área
            }
        }

        // Ejecutar la función al cargar la página para establecer el estado inicial
        document.addEventListener('DOMContentLoaded', toggleAreaField);

        // SweetAlert2 para mostrar el mensaje de registro exitoso
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('mensaje') === 'registrado') {
            document.querySelector('.left-section').style.display = 'none';
            document.querySelector('.right-section').style.display = 'none';

            Swal.fire({
                title: '<h2 style="color: #4caf50; font-size: 24px;">¡Registro exitoso!</h2>',
                html: '<p style="color: #333; font-size: 16px;">El usuario se ha registrado correctamente.</p>',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#4caf50',
                background: '#ffffff',
                customClass: {
                    popup: 'custom-swal-popup',
                    title: 'custom-swal-title',
                    confirmButton: 'custom-swal-button'
                },
                backdrop: 'rgba(0, 0, 0, 0.5)',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.href = 'login.php';
            });
        }
    </script>
</body>
</html>