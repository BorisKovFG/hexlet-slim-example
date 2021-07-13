<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use function Symfony\Component\String\s;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);

$companies = \App\Generator::generate(100);
$users = \App\Generator2::generate(100);

//$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Hello, world!');
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
$app->get("/users", function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $searched = collect($users)->filter(function ($user) use ($term) {
        return (!isset($term)) ? true : s($user['firstName'])->ignoreCase()->startsWith($term);
    });
    $params = [
        'searched' => $searched,
        'term' => $term
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});
$app->run();


