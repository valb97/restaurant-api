<?php


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\controllers\Items;


require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();


header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers:X-Request-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS,DELETE,UPDATE,PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$app->get('/', 'App\controllers\Items:index');
//items
$app->post('/items', 'App\controllers\Items:crearItem');
$app->get('/items/{id}', 'App\Controllers\Items:obtenerUnico');
$app->put('/items/{id}', 'App\Controllers\Items:actualizarItem');
$app->delete('/items/{id}', 'App\Controllers\Items:eliminarItem');
$app->get('/items', 'App\Controllers\Items:obtenerItems');
//pedidos
$app->post('/pedidos', 'App\Controllers\Pedidos:crearPedido');
$app->get('/pedidos', 'App\Controllers\Pedidos:obtenerPedidosTodos');
$app->delete('/pedidos/{id}', 'App\Controllers\Pedidos:eliminarPedido');
$app->get('/pedidos/{id}', 'App\Controllers\Pedidos:obtenerPedido');


$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});
// fin
$app->run();
