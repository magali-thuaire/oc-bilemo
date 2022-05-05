<?php /** @noinspection PhpCSValidationInspection */

/** @noinspection PhpCSValidationInspection */

use App\Kernel;

/** @noinspection PhpCSValidationInspection */
/** @noinspection PhpCSValidationInspection */
require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
