# Sistema Web de Asistencia

Sistema de gestión de asistencia de empleados con panel de administrador y empleado.

## Características

- **Panel de Administrador**: 
  - Gestión de empleados
  - Visualización de asistencias
  - Generación de reportes
  - Administración de vacaciones
  - Historial de justificaciones

- **Panel de Empleado**:
  - Marcar asistencia
  - Calendario de asistencias
  - Solicitud de justificaciones
  - Ver información personal
  - Solicitud de vacaciones

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Composer
- Servidor web (Apache/Nginx)

## Instalación

1. Clona este repositorio:
```bash
git clone [URL_DE_TU_REPOSITORIO]
```

2. Instala las dependencias con Composer:
```bash
composer install
```

3. Configura la base de datos:
   - Crea una base de datos MySQL
   - Copia el archivo `includes/db.example.php` a `includes/db.php`
   - Edita `includes/db.php` con tus credenciales de base de datos

4. Importa el esquema de base de datos (si tienes un archivo SQL de exportación)

5. Configura tu servidor web para que apunte al directorio del proyecto

## Configuración

El archivo `includes/db.php` contiene la configuración de conexión a la base de datos. Este archivo no está incluido en el repositorio por seguridad. Usa el archivo `db.example.php` como plantilla.

## Uso

- Accede a `login.php` para iniciar sesión
- Los administradores accederán al panel de administrador
- Los empleados accederán al panel de empleado

## Seguridad

⚠️ **IMPORTANTE**: Nunca subas el archivo `includes/db.php` con tus credenciales reales al repositorio.

## Licencia

[Especifica tu licencia aquí]
