<?php
include 'includes/db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = $_POST["dni"];
    $password = $_POST["password"];

    // Consultar el usuario por DNI
    $sql = "SELECT * FROM usuarios WHERE dni = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$dni]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario existe y la contraseña es correcta
    if ($usuario && password_verify($password, $usuario["password"])) {
        $_SESSION["usuario_id"] = $usuario["id"];
        $_SESSION["cargo"] = $usuario["cargo"]; // Guardar el cargo en la sesión

        // Redirigir según el cargo
        if ($usuario["cargo"] == "admin") {
            header("Location: historial_empleado_admin.php");
        } elseif ($usuario["cargo"] == "empleado") {
            header("Location: Empleado/index_empleado.php");
        }
        exit();
    } else {
        $error = "DNI o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .left-section {
            flex: 1;
            background-color: #08b8f8;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
        }

        .left-section img {
            width: 650px;
            margin-bottom: 20px;
        }

        .right-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: rgb(255, 255, 255);
        }

        .login-container {
            width: 100%;
            max-width: 520px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow:0px 4px 10px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .login-container .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #6c757d;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }

        .login-container h2 {
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .login-container input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .login-container button {
            width: 100%;
            padding: 10px;
            background-color:#0a63d3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .login-container button:hover {
            background-color:#0a63d3;
        }

        .login-container a {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #007bff;
        }

        .login-container a:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="left-section">
        <img src="img/logoBlanco.png" alt="Logo">
    </div>

    <div class="right-section">
        <div class="login-container">    
        <i class="fas fa-user" style="font-size: 50px; color: #08b8f8; margin-bottom: 10px;"></i> <!-- Ícono de persona en azul -->
        <h2>Iniciar Sesión</h2>
            <form action="login.php" method="POST">
                <input type="text" name="dni" placeholder="DNI" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit">Entrar</button>
            </form>
            <a href="registro.php">Registrarse</a>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>