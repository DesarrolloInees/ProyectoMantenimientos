<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

class ReporteFacturacionModelo
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Aquí no necesitamos funciones SQL para el Excel, 
    // ya que la lectura y filtrado se hará del lado del cliente (JS).
}