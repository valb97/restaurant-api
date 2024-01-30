<?php
//funciones para validar el tipo de dato 
function validarNombre($nombre)
{
    return is_string($nombre) ? $nombre : null;
}

function validarPrecio($precio)
{
    return is_numeric($precio) ? $precio : null;
}

function validarTipo($tipo)
{
    return ($tipo === "comida" || $tipo === "bebida") ? $tipo : null;
}

function validarImagen($imagen)
{
    // Verificar si es una cadena en base64 válida
    if (is_string($imagen)) {
        // Decodificar la imagen en base64
        var_dump($decodedImage = $imagen);
        // Obtener información sobre la imagen
        $imgInfo = getimagesizefromstring($decodedImage);

        if ($imgInfo !== false) {
            // Verificar el tipo de imagen (puedes agregar más tipos si es necesario)
            $allowedTypes = [
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
                IMAGETYPE_GIF,
            ];

            if (in_array($imgInfo[2], $allowedTypes)) {
                // Verificar el tamaño de la imagen (opcional: puedes ajustar el tamaño permitido)
                $maxFileSize = 5 * 1024 * 1024; // 5 MB (en bytes)

                if (strlen($decodedImage) <= $maxFileSize) {
                    // La imagen es válida
                    return $decodedImage; // Devuelve la imagen decodificada
                }
            }
        }
    }

    // Si la imagen no es válida, puedes retornar un mensaje de error o null
    return null;
}
// la siguiente funcion recibe como parametro el tipo de imagen
function validarTipoImagen($tipo_imagen)
{
    //por ahi agregar el .[tipo de archivo] ? 
    $tiposPermitidos = ["image/jpg", "image/jpeg", "image/png"];
    //convierte todas las char a minuscula y compara con los tipos permitidos, sino devuelve null
    return in_array(strtolower($tipo_imagen), $tiposPermitidos) ? $tipo_imagen : null;
}
// funcion para validar los campos que se reciben en actualizar items 
function validarCampos($data)
{
    $nombre = $data['nombre'] ?? null;
    $precio = $data['precio'] ?? null;
    $tipo = $data['tipo'] ?? null;
    $imagen = $data['imagen'] ?? null;
    $tipo_imagen = $data['tipo_imagen'] ?? null;
    if ($nombre !== null || $precio !== null || $tipo !== null || $imagen !== null || $tipo_imagen !== null) {
        $camposInvalidos = [];
        if ($nombre !== null) {
            $nombreValido = validarNombre($nombre);
            if ($nombreValido === null) {
                $camposInvalidos[] = "nombre";
            }
        }

        if ($precio !== null) {
            $precioValido = validarPrecio($precio);
            if ($precioValido === null) {
                $camposInvalidos[] = "precio";
            }
        }

        if ($tipo !== null) {
            $tipoValido = validarTipo($tipo);
            if ($tipoValido === null) {
                $camposInvalidos[] = "tipo";
            }
        }

        if ($imagen !== null) {
            $imagenValida = validarImagen($imagen);
            if ($imagenValida === null) {
                $camposInvalidos[] = "imagen";
            }
        }

        if (!empty($tipo_imagen)) {
            $tipo_imagenValido = validarTipoImagen($tipo_imagen);
            if ($tipo_imagenValido === null) {
                $camposInvalidos[] = "tipo_imagen";
            }
        }
        if (!empty($camposInvalidos)) {
            return "Error: El campo '" . implode("' y '", $camposInvalidos) . "' es inválido";
        }

        return true;
    } else {
        return "Error: Ningún campo fue enviado.";
    }
}

function validarIdExistente($conexion, $id)
{
    $stmt = $conexion->prepare("SELECT 1 FROM items_menu WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false ? $id : null;
}
function validarMesa($mesa)
{
    return is_numeric($mesa) ? $mesa : null;
}
function validarIdPedidoExistente($conexion, $idPedido)
{
    $stmt = $conexion->prepare("SELECT 1 FROM pedidos WHERE id = :idPedido LIMIT 1");
    $stmt->bindParam(':idPedido', $idPedido);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false ? $idPedido : null;
}
