<?php

declare(strict_types=1);

header('Content-Type: text/plain');


require_once('../vendor/autoload.php');


echo 'hi' . PHP_EOL;

echo 'warning' . PHP_EOL;
echo $undefined_variable;

echo 'error' . PHP_EOL;


throw new \RuntimeException('test message', 100);
