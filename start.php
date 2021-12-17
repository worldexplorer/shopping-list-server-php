<?php
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Autoloader;
use PHPSocketIO\SocketIO;

define('GLOBAL_START', true);

require_once __DIR__ . '/start_io.php';
require_once __DIR__ . '/start_web.php';

Worker::runAll();
