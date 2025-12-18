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

        /* Sidebar Animations */
        #sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }

        #sidebar.active {
            transform: translateX(0);
        }

        /* Overlay */
        #overlay {
            display: none;
        }

        #overlay.active {
            display: block;
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

    <div class="flex h-screen overflow-hidden">

        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30" onclick="toggleSidebar()"></div>

        <aside id="sidebar" class="fixed w-64 bg-gray-900 text-white flex-shrink-0 flex flex-col z-[50] h-full shadow-2xl">

            <div class="p-6 text-center font-bold text-2xl tracking-wider border-b border-gray-700 flex justify-between items-center">
                <span>INEES APP</span>
                <button class="text-white hover:text-red-400 transition" onclick="toggleSidebar()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto py-4">

                <?php include __DIR__ . '/../partials/menu_lateral.php'; ?>

            </nav>

            <div class="p-4 border-t border-gray-700 text-center text-xs text-gray-500">
                © 2025 INEES <br>
                <span class="text-gray-600">v5.0 Stable</span>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden w-full">

            <header class="bg-white shadow-sm flex justify-between items-center p-4 z-20">
                <button class="text-gray-600 hover:text-blue-600 focus:outline-none transition" onclick="toggleSidebar()">
                    <i class="fas fa-bars fa-lg"></i>
                </button>

                <h2 class="text-xl font-semibold text-gray-700 flex-1 text-center">
                    <?= isset($titulo) ? $titulo : 'Panel de Control' ?>
                </h2>

                <div class="flex items-center space-x-3">
                    <div class="text-right hidden sm:block">
                        <div class="text-sm font-bold text-gray-800">
                            <?= $_SESSION['usuario_name'] ?? 'Usuario' ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?= $_SESSION['usuario_cargo'] ?? 'Colaborador' ?>
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-6">
                <?php
                if (isset($vistaContenido) && file_exists($vistaContenido)) {
                    // Importante: No usamos require_once porque la vista puede cargarse varias veces en flujo AJAX raro
                    include $vistaContenido;
                } elseif (isset($vistaContenido)) {
                    echo '  <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                <p class="font-bold">Error 404</p>
                                <p>No se encuentra el archivo de vista: <code>' . $vistaContenido . '</code></p>
                            </div>';
                }
                ?>
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        document.querySelectorAll('#sidebar a').forEach(link => {
            link.addEventListener('click', () => toggleSidebar());
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar.classList.contains('active')) toggleSidebar();
            }
        });
    </script>

</body>

</html>