<?php

namespace App\Models;

class Db
{
    private $host = 'localhost';
    private $nombre_de_usuario = 'root';
    private $contrasena = '';
    private $nombre_de_base_de_datos = 'resto';
    private $conexion;

    public function __construct()
    {
            $this->conexion = new \PDO("mysql:host={$this->host};dbname={$this->nombre_de_base_de_datos}", $this->nombre_de_usuario, $this->contrasena);

            $this->conexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
           
        } 
    

    public function getConexion()
    {
        return $this->conexion;
    }
}
