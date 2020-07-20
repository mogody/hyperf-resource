<?php

declare(strict_types=1);
/**
 * This file is part of mogody/hyperf-resource.
 *
 * @link     https://github.com/mogody/hyperf-resource
 * @document https://github.com/mogody/hyperf-resource/blob/master/README.md
 * @contact  wenghang1228@gmail.com
 * @license  https://github.com/mogody/hyperf-resource/blob/master/LICENSE
 */
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Request;
use Hyperf\Utils\ApplicationContext;

/*
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

\Swoole\Runtime::enableCoroutine(true);

require BASE_PATH . '/vendor/autoload.php';
Hyperf\Di\ClassLoader::init();
$container = new Container((new DefinitionSourceFactory(true))());

if (! $container instanceof \Psr\Container\ContainerInterface) {
    throw new RuntimeException('The dependency injection container is invalid.');
}

ApplicationContext::setContainer($container);

$container->define(RequestInterface::class, function () {
    return new Request();
});
