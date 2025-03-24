<?php

use Mati\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
  // @phpstan-ignore argument.type
  return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
