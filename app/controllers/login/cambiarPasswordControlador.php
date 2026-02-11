<?php
// app/controllers/login/cambiarPasswordControlador.php

if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

require_once __DIR__ . '/../../config/conexion.php';

class cambiarPasswordControlador
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function index()
    {
        // SEGURIDAD: Solo accesible si viene de un login con forzar_cambio_pwd = 1
        // Si alguien entra directo aquí sin pasar por el login, lo mandamos de vuelta
        if (!isset($_SESSION['temp_user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        $data = [
            'error' => $_GET['error'] ?? null,
        ];

        require_once "app/views/login/cambiarPasswordVista.php";
    }
}