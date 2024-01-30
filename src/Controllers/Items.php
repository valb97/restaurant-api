<?php

namespace App\Controllers;

require 'src/Models/Db.php';
require './vendor/autoload.php';
require 'src/Models/funciones.php';

use PDOException;
use PDO;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Db;

class  Items
{


    public function index(Request $request, Response $response, $args)
    {
        $response->getBody()->write("hola");
        return $response;
    }

    public function obtenerUnico(Request $request, Response $response, $args)
    {
        $itemId = $args['id'] ?? null;

        if ($itemId) {
            try {
                $db = new Db();
                $conexion = $db->getConexion();

                $sql = "SELECT * FROM items_menu WHERE id = :id";
                $stmt = $conexion->prepare($sql);
                $stmt->bindParam(':id', $itemId);
                $stmt->execute();

                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($item) {
                    $imagenBase64 = base64_encode($item["imagen"]);
                    $item["imagen"] = $imagenBase64;

                    $jsonResponse = json_encode(['Solicitud exitosa', $item]);
                    if ($jsonResponse === false) {
                        throw new \Exception('Error al codificar la respuesta a JSON');
                    }

                    $response = $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                    $response->getBody()->write($jsonResponse);
                    return $response;
                } else {
                    $jsonResponse = json_encode(['error' => 'Ítem no encontrado']);
                    if ($jsonResponse === false) {
                        throw new \Exception('Error al codificar la respuesta a JSON');
                    }

                    $response = $response->withHeader('Content-Type', 'application/json')->withStatus(404);
                    $response->getBody()->write($jsonResponse);
                    return $response;
                }
            } catch (\PDOException $e) {
                $jsonResponse = json_encode(['Error en la conexión con la base de datos']);
                if ($jsonResponse === false) {
                    $jsonResponse = '{"error": "Error al codificar la respuesta a JSON"}';
                }

                $response = $response->withHeader('Content-Type', 'application/json')->withStatus(500);
                $response->getBody()->write($jsonResponse);
                return $response;
            } catch (\Exception $e) {
                $response = $response->withHeader('Content-Type', 'application/json')->withStatus(500);
                $response->getBody()->write('{"error": "' . $e->getMessage() . '"}');
                return $response;
            }
        } else {
            $jsonResponse = json_encode(['error' => 'ID de ítem no proporcionado']);
            if ($jsonResponse === false) {
                $jsonResponse = '{"error": "Error al codificar la respuesta a JSON"}';
            }

            $response = $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            $response->getBody()->write($jsonResponse);
            return $response;
        }
    }


    public function crearItem(Request $request, Response $response)
    {
        $json = $request->getBody()->getContents();
        $data = json_decode($json, true);

        $nombre = $data['nombre'] ?? null;
        $precio = $data['precio'] ?? null;
        $tipo = $data['tipo'] ?? null;
        $tipo_imagen = $data['tipo_imagen'] ?? null;
        $imagenBase64 = $data['imagen'] ?? null;

        // Realizar validaciones de datos
        $nombre = validarNombre($nombre);
        $precio = validarPrecio($precio);
        $tipo = validarTipo($tipo);
        $tipo_imagen = validarTipoImagen($tipo_imagen);
        //hay que agregar el validar Imagen

        if ($nombre !== null && $precio !== null && $tipo !== null && $imagenBase64 !== null && $tipo_imagen !== null) {
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

            $imagenBinaria = base64_decode($imagenBase64);

            $sql = "INSERT INTO items_menu (nombre, precio, tipo, imagen, tipo_imagen) VALUES (:nombre, :precio, :tipo, :imagen, :tipo_imagen)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':imagen', $imagenBinaria, PDO::PARAM_LOB);
            $stmt->bindParam(':tipo_imagen', $tipo_imagen);

            if ($stmt->execute()) {
                $id = $conexion->lastInsertId();

                $respuesta = ['item creado exitosamente'];
                $response = $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                $response->getBody()->write(json_encode($respuesta));
                return $response;
            } else {
                $respuesta = ['Error al crear el item'];
                $response = $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                $response->getBody()->write(json_encode($respuesta));
                return $response;
            }
        } else {
            $campos = [];
            if ($nombre === null) {
                $campos[] = "nombre";
            }
            if ($precio === null) {
                $campos[] = "precio";
            }
            if ($tipo === null) {
                $campos[] = "tipo";
            }
            if ($imagenBase64 === null) {
                $campos[] = "imagen";
            }
            if ($tipo_imagen === null) {
                $campos[] = "tipo de imagen";
            }
            $respuesta = ["El error se encuentra en: ", ...$campos];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }
    }

    public function actualizarItem(Request $request, Response $response, $args)
    {
        $json = $request->getBody()->getContents();
        $data = json_decode($json, true);
        $itemId = $args['id'] ?? null;
        //la funcion validarCampos returna null o true o un string 
        //en caso de devolverse se usa para mostrar el error en pantalla 
        //$validacion = true;
        if ($data['imagen'] != null) {
            $imagenBinaria = base64_decode($data['imagen']);
            $data['imagen'] = $imagenBinaria;
        }
        $validacion = validarCampos($data);
        //si itemd no esta vacio, si exsite data y si los datos son validos
        if ($itemId && $data && $validacion === true) {
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

            $sql = "UPDATE items_menu SET ";

            $updateFields = [];
            $params = [];
            //hace un for each para guardar los valores ingresador en 
            //data => agarra los valores existentes
            foreach ($data as $key => $value) {
                if ($value !== null) {
                    $updateFields[] = "$key = ?";
                    $params[] = $value;
                }
            }


            $sql .= implode(", ", $updateFields);
            $sql .= " WHERE id = ?";

            $params[] = $itemId;
            // revisa si existen items con la id 
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE idItemMenu = ?");
            $stmt->execute([$itemId]);
            $cantidadItems = $stmt->fetchColumn();
            if ($cantidadItems > 0) {
                $respuesta = ['operacion fallida, la id seleccionada esta conectada a un pedido'];
                $response = $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                $response->getBody()->write(json_encode($respuesta));
                return $response;
            }
            // ejecuta la query
            $stmt = $conexion->prepare($sql);
            if ($stmt->execute($params)) {
                $sql = "SELECT * FROM items_menu WHERE id = :id";
                $stmt = $conexion->prepare($sql);
                $stmt->bindParam(':id', $itemId);
                $stmt->execute();
                $objetoActualizado = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($objetoActualizado) {
                    $respuesta = ['Item editado'];
                    $response = $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                    $response->getBody()->write(json_encode($respuesta));
                    return $response;
                } else {
                    $respuesta = ['no se pudo actualizar el item ya que no se encuentra en la base de datos'];
                    $response = $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    $response->getBody()->write(json_encode($respuesta));
                    return $response;
                }
            } else {
                $respuesta = ['base de datos no disponible'];
                $response = $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                $response->getBody()->write(json_encode($respuesta));
                return $response;
            }
        } else {
            $respuesta = ['Error' => $validacion];
            $response = $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
            $response->getBody()->write(json_encode($respuesta));
            return $response;
        }
    }



    public function eliminarItem(Request $request, Response $response, $args)
    {
        $itemId = $args['id'];
        if (!empty($itemId)) {
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
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE idItemMenu = ?");
            $stmt->execute([$itemId]);
            $cantidadPedidos = $stmt->fetchColumn();


            if ($cantidadPedidos === 0) {
                $stmt = $conexion->prepare("DELETE FROM items_menu WHERE id = ?");
                if ($stmt->execute([$itemId])) {
                    $respuesta = ['Solicitud exitosa'];
                    $response = $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                    $response->getBody()->write(json_encode($respuesta));
                    return $response;
                } else {
                    $respuesta = ['no se pudo procesar la solicitud'];
                    $response = $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    $response->getBody()->write(json_encode($respuesta));
                    return $response;
                }
            } else {
                $respuesta = ['operacion fallida, la id seleccionada esta  conectada a un pedido'];
                $response = $response->withHeader('Content-Type', 'application/json')->withStatus(409);
                $response->getBody()->write(json_encode($respuesta));
                return $response;
            }
        }
        $respuesta = ['operacion fallida, la id esta vacia'];
        $response = $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        $response->getBody()->write(json_encode($respuesta));
        return $response;
    }

    public function obtenerItems(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();
        $filtroTipo = $params['tipo'] ?? null;
        $filtroNombre = $params['nombre'] ?? null;
        $orden = $params['orden'] ?? 'asc';

        $sql = "SELECT * FROM items_menu";
        $where = [];
        $paramsArray = [];

        if ($filtroTipo !== null) {
            $where[] = "tipo = ?";
            $paramsArray[] = $filtroTipo;
        }

        if ($filtroNombre !== null) {
            $where[] = "nombre LIKE ?";
            $paramsArray[] = "%$filtroNombre%";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY precio $orden";

        try {
            $db = new Db();
            $conexion = $db->getConexion();

            $stmt = $conexion->prepare($sql);
            if ($stmt->execute($paramsArray)) {
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $itemsAsObjects = [];
                foreach ($items as $item) {
                    $imagenBase64 = base64_encode($item["imagen"]);
                    $item["imagen"] = $imagenBase64;
                    $itemsAsObjects[] = [
                        "id" => $item["id"],
                        "nombre" => $item["nombre"],
                        "precio" => $item["precio"],
                        "tipo" => $item["tipo"],
                        "imagen" => $item["imagen"],
                    ];
                }

                $jsonResponse = json_encode(['Solicitud exitosa', ...$itemsAsObjects]);
                if ($jsonResponse === false) {
                    throw new \Exception('Error al codificar la respuesta a JSON');
                }

                $response = $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                $response->getBody()->write($jsonResponse);
                return $response;
            } else {
                $jsonResponse = json_encode(['error al obtener los items']);
                if ($jsonResponse === false) {
                    throw new \Exception('Error al codificar la respuesta a JSON');
                }

                $response = $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                $response->getBody()->write($jsonResponse);
                return $response;
            }
        } catch (\PDOException $e) {
            $jsonResponse = json_encode(['Error en la conexión con la base de datos']);
            if ($jsonResponse === false) {
                $jsonResponse = '{"error": "Error al codificar la respuesta a JSON"}';
            }

            $response = $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            $response->getBody()->write($jsonResponse);
            return $response;
        } catch (\Exception $e) {
            $response = $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            $response->getBody()->write('{"error": "' . $e->getMessage() . '"}');
            return $response;
        }
    }
}
