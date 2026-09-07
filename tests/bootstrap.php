<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$testingEnvironmentPath = dirname(__DIR__) . '/env.testing';
if (is_file($testingEnvironmentPath)) {
    $testingEnvironment = parse_ini_file($testingEnvironmentPath, false, INI_SCANNER_RAW);
    if ($testingEnvironment === false) {
        throw new RuntimeException('Unable to parse the local env.testing file.');
    }

    foreach ($testingEnvironment as $name => $value) {
        if (!is_string($value)) {
            throw new RuntimeException(sprintf('Testing environment variable %s must be a string.', $name));
        }

        // CI injects its own isolated database values; local file values are
        // only defaults and must never override an existing environment.
        if (getenv($name) === false) {
            putenv($name . '=' . $value);
        }
    }
}
