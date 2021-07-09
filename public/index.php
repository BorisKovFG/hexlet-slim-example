<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Hello, world!');
    //$response2 = $response->withStatus(302);
    return $response;
});

$app->get("/about", function ($request, $response) {
    return $response->write("GET /about");
});
$app->get('/users', function ($request, $response) {
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 10);
    return $response;
});
$app->post('/users', function ($request, $response) {
    return $response->withStatus(302);
});
$app->post("/about", function ($request, $response) {
    return $response->write("POST /about");
});

$app->run();