<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use function Symfony\Component\String\s;
use App\Validator;
use App\Generator3;



$container = new Container();
//flash-message part
session_start();
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
//flash message part
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$router = $app->getRouteCollector()->getRouteParser();
$data = new \App\UserRepository();
$companies = \App\Generator::generate(100);
$users = \App\Generator2::generate(100);

//$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);


$app->get('/', function ($request, $response) {
    $response->getBody()->write("Hello, world!!!");
    return $response;
});

$app->get("/about", function ($request, $response) {
    return $response->write("GET /about");
});

$app->get('/users3', function ($request, $response) {
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 10);
    return $response->write("{$page} : {$per}");
});

$app->post('/users2', function ($request, $response) {
    return $response->withStatus(302);
});

$app->post("/about", function ($request, $response) {
    return $response->write("POST /about");
});

$app->get("/companies", function ($request, $response) use ($companies) {
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $interval = ($page - 1) * $per;
    $slicedColl = array_slice($companies, $interval, $per);
    $response->getBody()->write(json_encode($slicedColl));

    return $response;
});

$app->get("/courses/{coursesId}/lesson/{id}", function ($request, $response, array $args) {
    $coursesId = $args['coursesId'];
    $lessonId = $args['id'];
    return $response->write("Course: {$coursesId}, ")
        ->write("Lesson: {$lessonId}!!!");
});

$app->get("/companies/{id}", function ($request, $response, array $args) use ($companies) {
    $idCompany = $args['id'];
    $company = collect($companies)->firstWhere('id', $idCompany);
    return ($company) ? $response->write(json_encode($company)) : $response->withStatus(404)->write('Page not found');
});

$app->get('/users2/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show2.phtml', $params);
});

$app->get("/company", function ($request, $response) use ($companies) {
    $company = collect($companies)->sort();
    //$params = ['id' => $idCompany, 'name' => $companies['name'], 'phone' => $companies['phone']];
    $params = [
        'companies' => $company
    ];
    return $this->get('renderer')->render($response, 'companies/company.phtml', $params);
});

$app->get("/users4", function ($request, $response) use ($users) {
    $search = $request->getQueryParam('term');
    $users = collect($users)->sort();
    $filtered = $users->firstWhere('id', $search);
    $users = ($filtered) ? [$filtered] : $users;
    $params = [
        'users' => $users,
        'term' => $search
    ];
    return $this->get('renderer')->render($response, 'users/index4.phtml', $params);
});

$app->get("/users4/{id}", function ($request, $response, array $args) use ($users) {
    $user = collect($users)->firstWhere('id', $args['id']);
    $params = [
        'user' => $user
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});
$app->get("/users5", function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $searched = collect($users)->filter(function ($user) use ($term) {
        return (!isset($term)) ? true : s($user['firstName'])->ignoreCase()->startsWith($term);
    });
    $params = [
        'searched' => $searched,
        'term' => $term
    ];
    return $this->get('renderer')->render($response, 'users/index5.phtml', $params);
});
//-----------------------modificate forms, named routing, psr-7----------------------//
$app->get("/users", function ($request, $response) use ($data) {
    $user = $data->read();
    $params = [
        'user' => ($user) ?? [],
        'flash' =>[]
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName("users");

$app->get("/users/new", function ($request, $response) {
    $params = [
        'user' => [
            'name' => '',
            'email' => '',
            'city' => ''
        ],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});
$app->get("/users/{id}", function ($request, $response, array $args) use ($data) {
    $user = $data->read();
    $id = $args['id'];
    if (empty($user['id']) || $user['id'] !== (int)$id) {
        return $response->withStatus(404);
    }
    $flash = $this->get('flash')->getMessages();
    $params = [
        'user' => $user,
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, "users/index.phtml", $params);
})->setName("user");

$app->post("/users", function ($request, $response) use ($data, $router) {
    $validator = new Validator();
    $userId = new Generator3(0, 99);
    $user = $request->getParsedBodyParam('user');
    $user['id'] = $userId->getId();
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        $data->save($user);
        $this->get('flash')->addMessage('success', 'User Added');
        return $response->withRedirect($router->urlFor('user', ['id' => $user['id']]));
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response->withStatus(422), '/users/new.phtml', $params);
});
//----------------------flash messages ----------------

$app->get('/flash', function ($request, $response) {
    $flash = $this->get('flash')->getMessages();
    $params = ['flash' => $flash];
    return $this->get('renderer')->render($response, 'users/flash.phtml', $params);
});

$app->post('/courses', function ($request, $response) {
    $this->get('flash')->addMessage('success', 'Course Added');
    return $response->withRedirect('/flash');
});

$app->run();


