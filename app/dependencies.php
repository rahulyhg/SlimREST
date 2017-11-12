<?php

use App\Security\Jwt\Manager as JwtManager;
use App\Security\Storage\Pdo;
use Awurth\SlimValidation\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Cartalyst\Sentinel\Native\SentinelBootstrapper;
use Illuminate\Database\Capsule\Manager;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use OAuth2\Server;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\GrantType\UserCredentials;

$capsule = new Manager();
$capsule->addConnection($container['config']['parameters']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['sentinel'] = function ($container) {
    $sentinel = new Sentinel(new SentinelBootstrapper($container['config']['sentinel']));
    return $sentinel->getSentinel();
};

$container['oauth'] = function ($container) use ($capsule) {
    $storage = new Pdo($capsule->getConnection()->getPdo(), $container['config']['oauth']['pdo']);

    $server = new Server($storage);
    $server->addGrantType(new UserCredentials($storage));
    $server->addGrantType(new RefreshToken($storage));

    return $server;
};

$container['jwt'] = function ($container) {
    return new JwtManager($container['config']['parameters']['secret'], $container['config']['jwt']);
};

$container['validator'] = function () {
    return new Validator(false);
};

$container['monolog'] = function ($container) {
    $config = $container['config']['monolog'];

    $logger = new Logger($config['name']);
    $logger->pushProcessor(new UidProcessor());
    $logger->pushHandler(new StreamHandler($config['path'], $config['level']));

    return $logger;
};
