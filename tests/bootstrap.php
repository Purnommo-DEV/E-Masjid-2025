<?php

require dirname(__DIR__).'/vendor/autoload.php';

$configuration = [
    'APP_ENV' => (string) getenv('APP_ENV'),
    'DB_CONNECTION' => (string) getenv('DB_CONNECTION'),
    'DB_HOST' => (string) getenv('DB_HOST'),
    'DB_PORT' => (string) getenv('DB_PORT'),
    'DB_DATABASE' => (string) getenv('DB_DATABASE'),
];

$violations = [];
if ($configuration['APP_ENV'] !== 'testing') {
    $violations[] = 'APP_ENV must equal testing';
}
if ($configuration['DB_CONNECTION'] !== 'mysql') {
    $violations[] = 'DB_CONNECTION must equal mysql';
}
if ($configuration['DB_DATABASE'] !== 'mrj_test_db') {
    $violations[] = 'DB_DATABASE must equal mrj_test_db';
}
if ($configuration['DB_DATABASE'] === 'mrj_prod_db') {
    $violations[] = 'DB_DATABASE is the forbidden production database';
}

if ($violations !== []) {
    throw new RuntimeException('Unsafe PHPUnit database configuration: '.implode('; ', $violations).'. Refusing to start tests.');
}

fwrite(STDOUT, sprintf(
    'TEST DATABASE PREFLIGHT: APP_ENV=%s DB_CONNECTION=%s DB_HOST=%s DB_PORT=%s DB_DATABASE=%s%s',
    $configuration['APP_ENV'],
    $configuration['DB_CONNECTION'],
    $configuration['DB_HOST'],
    $configuration['DB_PORT'],
    $configuration['DB_DATABASE'],
    PHP_EOL,
));
