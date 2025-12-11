<?php
date_default_timezone_set('America/Bogota');
$error_login = $data['error_login'] ?? false;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - I-Nexis</title>

    <!-- Tailwind CSS -->

    <script src="js/tailwind.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Favicon -->
    <link rel="shortcut icon" href="imagenes/logoIneesSinFondo.png" type="image/x-icon">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow: hidden;
            background: #0a0e27;
        }

        /* ✨ FONDO ANIMADO ULTRA MODERNO ✨ */
        .animated-bg {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: linear-gradient(135deg,
                    #0cdf40ff 0%,
                    #10673aff 25%,
                    #14b58aff 50%,
                    #4facfe 75%,
                    #667eea 100%);
            background-size: 400% 400%;
            animation: gradientFlow 20s ease infinite;
            z-index: 1;
        }

        @keyframes gradientFlow {

            0%,
            100% {
                background-position: 0% 50%;
            }

            25% {
                background-position: 50% 100%;
            }

            50% {
                background-position: 100% 50%;
            }

            75% {
                background-position: 50% 0%;
            }
        }

        /* Partículas flotantes */
        .particles {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 2;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 15s infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100px) translateX(100px);
                opacity: 0;
            }
        }

        /* Generamos partículas con diferentes delays */
        .particle:nth-child(1) {
            left: 10%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            left: 20%;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            left: 30%;
            animation-delay: 4s;
        }

        .particle:nth-child(4) {
            left: 40%;
            animation-delay: 1s;
        }

        .particle:nth-child(5) {
            left: 50%;
            animation-delay: 3s;
        }

        .particle:nth-child(6) {
            left: 60%;
            animation-delay: 5s;
        }

        .particle:nth-child(7) {
            left: 70%;
            animation-delay: 2.5s;
        }

        .particle:nth-child(8) {
            left: 80%;
            animation-delay: 4.5s;
        }

        .particle:nth-child(9) {
            left: 90%;
            animation-delay: 1.5s;
        }

        .particle:nth-child(10) {
            left: 95%;
            animation-delay: 3.5s;
        }

        /* ✨ CARD GLASSMORPHISM PREMIUM ✨ */
        .card-login {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            animation: cardEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(50px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* ✨ INPUTS CON EFECTOS PREMIUM ✨ */
        .input-wrapper {
            position: relative;
        }

        .input-login {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 1rem 1rem 1rem 3.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-login::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .input-login:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #60a5fa;
            box-shadow:
                0 0 0 4px rgba(96, 165, 250, 0.2),
                0 10px 25px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .input-login:focus~.input-icon {
            color: #60a5fa;
            transform: translateY(-50%) scale(1.1);
        }

        .password-toggle {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            color: #60a5fa;
            transform: translateY(-50%) scale(1.15);
        }

        /* ✨ BOTÓN CON EFECTO WOW ✨ */
        .btn-login {
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow:
                0 10px 30px rgba(102, 126, 234, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.3),
                    transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow:
                0 15px 40px rgba(102, 126, 234, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        /* ✨ LOGO CON EFECTO DE BRILLO ✨ */
        .logo-container {
            position: relative;
            display: inline-block;
        }

        .logo-container::after {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.3) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        /* ✨ ANIMACIÓN DE ERROR ✨ */
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-8px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(8px);
            }
        }

        .animate-shake {
            animation: shake 0.6s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        /* ✨ MENSAJE DE ERROR MODERNO ✨ */
        .error-message {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            backdrop-filter: blur(10px);
            animation: errorSlideIn 0.5s ease-out;
        }

        @keyframes errorSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 640px) {
            .card-login {
                margin: 1rem;
                padding: 2rem 1.5rem !important;
            }
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen">

    <!-- ✨ FONDO ANIMADO ✨ -->
    <div class="animated-bg"></div>

    <!-- ✨ PARTÍCULAS FLOTANTES ✨ -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- ✨ CARD DE LOGIN ✨ -->
    <div class="card-login rounded-3xl p-10 w-full max-w-md mx-4 text-center">

        <!-- Logo con efecto -->
        <div class="logo-container mb-8">
            <img src="https://lu-co.com/wp-content/uploads/2025/06/SmallLogo-copia.png"
                alt="Logo I-Nexis"
                class="w-28 mx-auto drop-shadow-2xl transform hover:scale-110 transition-transform duration-300">
        </div>

        <!-- Título -->
        <h1 class="text-4xl font-bold text-white mb-2 tracking-tight">
            Bienvenido
        </h1>
        <p class="text-blue-200 mb-8 text-lg font-light">
            Inicia sesión en <span class="font-semibold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">I-Nexis</span>
        </p>

        <!-- Formulario -->
        <form class="space-y-5" method="post" action="login" id="loginForm">
            <input type="hidden" name="MM_Login" value="form1">

            <!-- Input Usuario -->
            <div class="input-wrapper">
                <input
                    id="usuario"
                    type="text"
                    name="usuario"
                    placeholder="Usuario"
                    required
                    class="input-login w-full rounded-xl focus:outline-none text-base font-medium"
                    autocomplete="username">
                <i class="input-icon fa-solid fa-user"></i>
            </div>

            <!-- Input Contraseña -->
            <div class="input-wrapper">
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Contraseña"
                    required
                    class="input-login w-full rounded-xl focus:outline-none text-base font-medium"
                    autocomplete="current-password">
                <i class="input-icon fa-solid fa-lock"></i>
                <i id="togglePassword" class="password-toggle fa-solid fa-eye"></i>
            </div>

            <!-- Botón de login -->
            <!-- AHORA: type="button" (Esto es seguro, no recarga nunca) -->
            <button
                type="button"
                id="btnLogin"
                class="btn-login w-full text-white font-bold py-4 rounded-xl text-base tracking-wide relative">

                <!-- El contenido sigue igual -->
                <span id="btn-text">Iniciar Sesión</span>
                <span id="btn-loading" class="hidden">
                    <span class="spinner"></span>
                    <span class="ml-2">Verificando...</span>
                </span>
            </button>
        </form>

        <!-- ¿Olvidaste tu contraseña? -->
        <div class="mt-6">
            <a href="<?php echo htmlspecialchars($datos_plantilla['baseURL']); ?>solicitarCodigo"
                class="text-sm text-blue-300 hover:text-white hover:underline transition-all duration-300 font-medium inline-flex items-center">
                <i class="fa-solid fa-key mr-2"></i>
                ¿Olvidaste tu contraseña?
            </a>
        </div>

        <!-- Contenedor para mensajes de error -->
        <div id="js-message-container" class="mt-5"></div>

        <!-- Mensaje de error del servidor -->
        <?php if ($error_login): ?>
            <div id="error-message" class="error-message mt-5 text-red-300 px-4 py-3 rounded-xl text-sm font-semibold flex items-center justify-center">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                Usuario o contraseña incorrectos
            </div>
        <?php endif; ?>

        <!-- Footer del card -->
        <div class="mt-8 pt-6 border-t border-white border-opacity-10">
            <p class="text-xs text-white text-opacity-60">
                <i class="fa-solid fa-shield-halved mr-1"></i>
                Sistema seguro I-Nexis &copy; <?php echo date('Y'); ?>
            </p>
        </div>
    </div>


    <script src="<?php echo BASE_URL; ?>js/login/login.js"></script>
</body>

</html>