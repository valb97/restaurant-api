<?php

namespace App\Controllers;

require 'src/Models/funciones.php';
require 'src/Models/Db.php';

use App\Models\Db;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class  Pedidos
{

    public function crearPedido(Request $request, Response $response)
    {
        $json = $request->getBody()->getContents();
        $data = json_decode($json, true);
        $mesa = $data['nromesa'] ?? null;
        $itemId = $data['idItemMenu'] ?? null;
        $comentario = $data['comentarios'] ?? null;
        $fechaActual = date("Y-m-d H:i:s");

        $mesa = validarMesa($mesa);
        if ($mesa) {
            try {
                $db = new Db();
                $conexion = $db->getConexion();
            } catch (\PDOException $e) {
                $respuesta = ['Conexion con el servidor fallida'];
                $response = $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
                $response->getBody()->write(json_encode($respuesta));
                return $response;
            }
            $itemId = validarIdExistente($conexion, $itemId);
            if ($itemId) {
                $sql = "INSERT INTO pedidos (nromesa, idItemMenu, comentarios,fechaAlta) VALUES (?, ?, ?,?)";
                $stmt = $conexion->prepare($sql);
                if ($stmt->execute([$mesa, $itemId, $comentario, $fechaActual])) {
                    $respuesta = ['pedido creado exitosamente'];
                    $response = $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(200);
                    $response->getBody()->write(json_encode($respuesta));
                    return $response;
                } else {
                    $respuesta = ['el pedido no  fue creado'];
                    $response = $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(400);
                    $response->getBody()->write(json_encode($respuesta));
                    return $response;
                }
            } else {
                $respuesta = ["el Error se encuentra en que no exite el item para agregar al pedido"];
                $response = $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
                $response->getBody()->write(json_encode($respuesta));
                return $response;
            }
        } else {
            $respuesta = ["el Error se encuentra en el numero de mesa"];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }
    }

    public function obtenerPedidosTodos(Request $request, Response $response)
    {
        $sql = "SELECT p.*, i.nombre AS nombre_item, i.precio, i.tipo AS tipo_item, i.imagen AS imagen_item, i.tipo_imagen AS tipo_imagen_item
            FROM pedidos p
            JOIN items_menu i ON p.idItemMenu = i.id
            ORDER BY p.fechaAlta DESC";

        try {
            $db = new Db();
            $conexion = $db->getConexion();
        } catch (\PDOException $e) {
            $respuesta = ['Conexion fallida con el servidor'];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }

        $stmt = $conexion->prepare($sql);
        $stmt->execute();
        $pedidos = $stmt->fetchAll();

        if ($pedidos == null) {
            $respuesta = ['La tabla se encuentra vacía'];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }
        $pedidosAsObjects = ["solicitud exitosa"];
        foreach ($pedidos as $pedido) {
            $imagenBinaria = base64_encode($pedido["imagen_item"]);
            $pedidosAsObjects[] = [
                "id" => $pedido["id"],
                "idItemMenu" => $pedido["idItemMenu"],
                "nromesa" => $pedido["nromesa"],
                "comentarios" => $pedido["comentarios"],
                "fechaAlta" => $pedido["fechaAlta"],
                "nombre_item" => $pedido["nombre_item"],
                "precio" => $pedido["precio"],
                "tipo_item" => $pedido["tipo_item"],
                "imagen_item" => $imagenBinaria,
                "tipo_imagen_item" => $pedido["tipo_imagen_item"],
            ];
        }

        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
        $response->getBody()->write(json_encode($pedidosAsObjects));
        return $response;
    }

    public function eliminarPedido(Request $request, Response $response, $args)
    {
        $pedidoId = $args['id'];


        try {
            $db = new Db();
            $conexion = $db->getConexion();
        } catch (\PDOException $e) {
            $respuesta = ['Conexion fallida con el servidor'];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }
        $pedidoId = validarIdPedidoExistente($conexion, $pedidoId);

        if ($pedidoId) {
            $sql = "DELETE FROM pedidos WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            if ($stmt->execute([$pedidoId])) {
                $respuesta = ['Pedido eliminado'];
                $response = $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
                $response->getBody()->write(json_encode($respuesta));
                return $response;
            }
        } else {
            $respuesta = ['el pedido no se encuentra en la bese de datos'];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(409);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }
    }
    function obtenerPedido(Request $request, Response $response, $args)
    {
        $pedidoId = $args['id'] ?? null;
        if ($pedidoId === null) {
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        try {
            $db = new Db();
            $conexion = $db->getConexion();
        } catch (\PDOException $e) {
            $respuesta = ['Conexion fallida con el servidor'];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }
        $sql = "SELECT pedidos.*, items_menu.nombre AS nombre_item, items_menu.precio, items_menu.tipo, items_menu.imagen AS foto_item
        FROM pedidos
        JOIN items_menu ON pedidos.idItemMenu = items_menu.id
        WHERE pedidos.id = :pedidoId";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':pedidoId', $pedidoId);
        $stmt->execute();

        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pedido) {
            $respuesta = ['solicitud exitosa', $pedido];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        } else {
            $respuesta = ['solicitud fallida el id no existe',];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }
    }
}
