<?php
// 1. Iniciar el almacenamiento en el búfer de salida
ob_start();

// 2. Configurar parámetros de la cookie
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

// 3. Incluir base de datos
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";

// Redirige si ya está logueado
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = limpiar($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? ''; 
    $pass_md5 = md5($pass);

    if (!empty($user) && !empty($pass)) {
        // Agregamos 'area' a la consulta
        $stmt = $conn->prepare("SELECT id, nombre, tipo, area, iniciales, password, estado FROM usuarios WHERE usuario = ? LIMIT 1");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($u = $res->fetch_assoc()) {
            // 1. Validamos estado
            if (trim($u['estado']) !== 'Activo') {
                $error = "Acceso denegado: El usuario no se encuentra activo.";
            } 
            // 2. Validamos contraseña
            elseif ($pass_md5 !== $u['password']) {
                $error = "La contraseña ingresada es incorrecta.";
            } 
            else {
                session_regenerate_id(true);

                // Éxito: Guardamos los datos en la sesión, incluyendo el ÁREA
                $_SESSION['user_id']   = $u['id'];
                $_SESSION['user_tipo'] = $u['tipo']; 
                $_SESSION['user_area'] = strtoupper($u['area'] ?? 'MARGINADOR'); // Guardamos el área
                $_SESSION['iniciales'] = $u['iniciales'];
                $_SESSION['nombre']    = $u['nombre'];
                
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "El usuario ingresado no existe en el sistema.";
        }
    } else {
        $error = "Por favor, complete todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - REVFA Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #1e3c72 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            padding: 30px;
            text-align: center;
            border-bottom: 4px solid #ffc107;
        }
        .logo-icon {
            font-size: 3rem;
            color: #fff;
            margin-bottom: 10px;
            display: inline-block;
        }
        .login-header h2 { color: #fff; font-size: 1.5rem; font-weight: 600; margin: 0; }
        .login-header p { color: rgba(255,255,255,0.8); font-size: 0.85rem; margin-top: 8px; }
        .login-body { padding: 35px 30px; }
        .form-label { font-weight: 600; color: #333; margin-bottom: 8px; display: block; font-size: 0.85rem; text-transform: uppercase; }
        .input-group-custom { position: relative; margin-bottom: 20px; }
        .input-group-custom i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; z-index: 10; }
        .form-control-custom {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .form-control-custom:focus { outline: none; border-color: #2a5298; box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1); }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(42, 82, 152, 0.3); }
        .alert-custom {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <div class="logo-icon"><i class="bi bi-file-earmark-medical-fill"></i></div>
        <h2>REVFA Digital</h2>
        <p>Sistema de Gestión de Marginaciones</p>
    </div>
    
    <div class="login-body">
        <?php if ($error): ?>
            <div class="alert-custom">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label class="form-label">Usuario</label>
                <div class="input-group-custom">
                    <i class="bi bi-person-fill"></i>
                    <input type="text" name="usuario" class="form-control-custom" placeholder="Ingrese su usuario" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <div class="input-group-custom">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" name="password" id="password" class="form-control-custom" placeholder="Ingrese su contraseña" required>
                    <i class="bi bi-eye-slash-fill" id="togglePassword" style="left: auto; right: 12px; cursor: pointer;"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> Ingresar al Sistema
            </button>
        </form>
    </div>
    
    <div class="login-footer text-center p-3 bg-light border-top">
        <small class="text-muted">© <?php echo date('Y'); ?> - Registro del Estado Familiar</small>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });
</script>

</body>
</html>
<?php ob_end_flush(); ?>