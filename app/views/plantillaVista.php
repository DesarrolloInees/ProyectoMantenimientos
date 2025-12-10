<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo) ? $titulo . ' - INEES' : 'INEES Mantenimientos' ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- SheetJS para exportar Excel con múltiples hojas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- jQuery (necesario para Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Select2 CSS y JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Estilos personalizados para Select2 */
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

        /* Sidebar siempre oculto por defecto */
        #sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }

        #sidebar.active {
            transform: translateX(0);
        }

        /* Overlay oscuro */
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

        <!-- OVERLAY OSCURO -->
        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30" onclick="toggleSidebar()"></div>

        <!-- SIDEBAR (SIEMPRE OCULTO) -->
        <aside id="sidebar" class="fixed w-64 bg-gray-900 text-white flex-shrink-0 flex flex-col z-[100001] h-full shadow-2xl"> <!-- Header del Sidebar -->
            <div class="p-6 text-center font-bold text-2xl tracking-wider border-b border-gray-700 flex justify-between items-center">
                <span>INEES APP</span>
                <button class="text-white hover:text-red-400 transition" onclick="toggleSidebar()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 overflow-y-auto py-4">
                <a href="index.php?pagina=inicio"
                    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition">
                    <i class="fas fa-home mr-3"></i> Inicio
                </a>

                <p class="px-6 py-2 text-xs text-gray-500 uppercase font-bold mt-4">Gestión de Servicios</p>

                <a href="index.php?pagina=ordenCrear"
                    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-green-500 transition">
                    <i class="fas fa-plus-circle mr-3"></i> Nueva Orden
                </a>
                <a href="index.php?pagina=ordenVer"
                    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-blue-500 transition">
                    <i class="fas fa-list-alt mr-3"></i> Historial
                </a>

                <p class="px-6 py-2 text-xs text-gray-500 uppercase font-bold mt-4">Reportes</p>

                <a href="index.php?pagina=reportes"
                    class="block py-3 px-6 hover:bg-gray-800 border-l-4 border-transparent hover:border-yellow-500 transition">
                    <i class="fas fa-chart-bar mr-3"></i> Reportes
                </a>
            </nav>

            <!-- Footer del Sidebar -->
            <div class="p-4 border-t border-gray-700 text-center text-xs text-gray-500">
                © 2025 INEES
            </div>
        </aside>

        <!-- CONTENIDO PRINCIPAL (AHORA OCUPA TODO EL ANCHO) -->
        <div class="flex-1 flex flex-col overflow-hidden w-full">

            <!-- HEADER -->
            <header class="bg-white shadow-sm flex justify-between items-center p-4 z-20">
                <!-- Botón hamburguesa (SIEMPRE VISIBLE) -->
                <button class="text-gray-600 hover:text-blue-600 focus:outline-none transition" onclick="toggleSidebar()">
                    <i class="fas fa-bars fa-lg"></i>
                </button>

                <!-- Título dinámico -->
                <h2 class="text-xl font-semibold text-gray-700 flex-1 text-center">
                    <?= isset($titulo) ? $titulo : 'Panel de Control' ?>
                </h2>

                <!-- Usuario -->
                <div class="flex items-center space-x-3">
                    <span class="text-sm font-medium hidden sm:inline">Técnico</span>
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </header>

            <!-- CONTENIDO DINÁMICO -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-6">
                <?php
                // Esta variable $vistaContenido viene del Controlador
                if (isset($vistaContenido) && file_exists($vistaContenido)) {
                    include $vistaContenido;
                } else {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <p class="font-bold">Error</p>
                            <p>No se pudo cargar la vista solicitada.</p>
                            </div>';
                }
                ?>
            </main>
        </div>
    </div>

    <!-- SCRIPT PARA EL MENÚ -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Cerrar sidebar al hacer clic en un link
        document.querySelectorAll('#sidebar a').forEach(link => {
            link.addEventListener('click', () => {
                toggleSidebar();
            });
        });

        // Cerrar con tecla ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('overlay');
                if (sidebar.classList.contains('active')) {
                    toggleSidebar();
                }
            }
        });
    </script>

</body>

</html>