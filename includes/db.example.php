<?php
/**
 * Archivo de ejemplo de configuración de base de datos
 * 
 * INSTRUCCIONES:
 * 1. Copia este archivo y renómbralo a "db.php"
 * 2. Reemplaza los valores de ejemplo con tus credenciales reales
 * 3. NUNCA subas el archivo db.php a Git (está en .gitignore)
 */

try {
    $conn = new PDO("mysql:host=localhost;dbname=nombre_de_tu_base_de_datos", "tu_usuario", "tu_contraseña");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
