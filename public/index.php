<?php

use App\Kernel;

// On PHP >= 8.4, instantiating symfony/error-handler 7.0's ErrorHandler
// evaluates property defaults referencing E_STRICT, raising a deprecation
// at a bootstrap instant where no error handler is installed yet. With
// display_errors on (local dev), that text is prepended to every response
// and corrupts binary ones (jpg). Trigger that one-time evaluation here,
// muted. (No-op on the first pass, before the autoloader is loaded.)
error_reporting(\E_ALL & ~\E_DEPRECATED);
if (class_exists(\Symfony\Component\ErrorHandler\ErrorHandler::class)) {
    new \Symfony\Component\ErrorHandler\ErrorHandler();
}
error_reporting(\E_ALL);

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
