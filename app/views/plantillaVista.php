<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo) ? $titulo . ' - INEES' : 'INEES Mantenimientos' ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Select2 Custom Styles */
        .select2-container .select2-selection--single {
            height: 100% !important;
            padding: 0.25rem !important;
            border-color: #d1d5db !important;
            border-radius: 0.25rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 0 !important;
            bottom: 0 !important;
            height: 100% !important;
        }

        .select2-search__field {
            outline: none !important;
        }

        /* ESTILOS DEL NAVBAR */
        .group:hover .group-hover\:block {
            display: block;
        }

        .dropdown-menu {
            transform-origin: top;
            transition: transform 0.2s ease-out, opacity 0.2s ease-out;
        }

        /* Z-Index Fix */
        .z-super-top {
            z-index: 99999 !important;
        }

        /* Estilo para quitar la flecha por defecto del details en algunos navegadores */
        details>summary {
            list-style: none;
        }

        details>summary::-webkit-details-marker {
            display: none;
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        azul: {
                            500: '#3b82f6',
                            900: '#1e3a8a'
                        },
                        gris: '#f3f4f6'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-100 font-sans text-gray-800">

    <div class="min-h-screen flex flex-col">

        <nav class="bg-gray-900 text-white shadow-lg fixed w-full z-[9000] top-0">
            <div class="w-full px-4">
                <div class="flex justify-between h-16">

                    <div class="flex items-center">
                        <a href="<?= BASE_URL ?>inicio" class="flex-shrink-0 flex items-center mr-6 hover:text-blue-400 transition">
                            <i class="fas fa-tools mr-2 text-blue-500"></i>
                            <span class="font-bold text-xl tracking-wider">INEES APP</span>
                        </a>

                        <div class="hidden xl:flex space-x-1 items-center h-full">
                            <?php
                            if (file_exists(__DIR__ . '/../partials/navbar_menu.php')) {
                                include __DIR__ . '/../partials/navbar_menu.php';
                            } else {
                                echo "<span class='text-xs text-red-400'>Falta navbar_menu.php</span>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">

                        <div class="hidden md:block text-right leading-tight">
                            <div class="text-sm font-bold text-gray-200"><?= $_SESSION['usuario_name'] ?? 'Usuario' ?></div>
                            <div class="text-xs text-gray-400"><?= $_SESSION['usuario_cargo'] ?? 'Colaborador' ?></div>
                        </div>

                        <div class="relative group h-full flex items-center">
                            <button class="flex items-center focus:outline-none">
                                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white shadow border-2 border-gray-700 hover:border-blue-400 transition">
                                    <i class="fas fa-user text-sm"></i>
                                </div>
                            </button>

                            <div class="absolute right-0 top-12 w-48 bg-white rounded-md shadow-xl py-1 hidden group-hover:block border border-gray-200 z-[9100]">
                                <div class="px-4 py-2 border-b border-gray-100 md:hidden">
                                    <p class="text-sm font-bold text-gray-800"><?= $_SESSION['usuario_name'] ?? 'Usuario' ?></p>
                                    <p class="text-xs text-gray-500"><?= $_SESSION['usuario_cargo'] ?? '' ?></p>
                                </div>
                                <a href="<?= BASE_URL ?>usuarioVer" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2 text-gray-500"></i> Configuración
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="<?= BASE_URL ?>logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                                </a>
                            </div>
                        </div>

                        <div class="xl:hidden flex items-center ml-2">
                            <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-gray-300 hover:text-white focus:outline-none p-2 border border-gray-700 rounded active:bg-gray-800">
                                <i class="fas fa-bars fa-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="mobile-menu" class="hidden xl:hidden bg-gray-900 border-t border-gray-700 pb-4 px-2 overflow-y-auto max-h-[85vh] shadow-inner">
                <?php
                // Aquí incluimos el archivo NUEVO que creamos en el paso 1
                if (file_exists(__DIR__ . '/../partials/navbar_menu_mobile.php')) {
                    include __DIR__ . '/../partials/navbar_menu_mobile.php';
                } else {
                    echo "<div class='p-4 text-red-400'>Crea navbar_menu_mobile.php primero.</div>";
                }
                ?>
            </div>
        </nav>

        <main class="flex-1 pt-20 pb-8 px-4 md:px-8 w-full max-w-[1600px] mx-auto z-0 relative">

            <div class="mb-6 border-b border-gray-300 pb-2 flex flex-col md:flex-row justify-between md:items-end gap-2">
                <h2 class="text-2xl font-bold text-gray-800">
                    <?= isset($titulo) ? $titulo : 'Panel de Control' ?>
                </h2>
                <div class="text-xs text-gray-500">
                    INEES v5.0
                </div>
            </div>

            <?php
            if (isset($vistaContenido) && file_exists($vistaContenido)) {
                include $vistaContenido;
            } elseif (isset($vistaContenido)) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow" role="alert">
                        <p class="font-bold"><i class="fas fa-exclamation-triangle"></i> Error 404</p>
                        <p>No se encuentra la vista solicitada: <code class="bg-red-200 px-1 rounded">' . $vistaContenido . '</code></p>
                        </div>';
            }
            ?>
        </main>

        <footer class="bg-white border-t border-gray-200 mt-auto py-4 text-center text-xs text-gray-500">
            &copy; 2025 INEES Mantenimientos - Todos los derechos reservados.
        </footer>
    </div>

</body>

</html>