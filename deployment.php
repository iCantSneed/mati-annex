<?php

declare(strict_types=1);

return [
  'prod' => [
    'remote' => $_ENV['FTP_REMOTE'],
    'include' => '
      /bin
      /config
      /html
      /src
      /var/cache/prod
      /vendor
      .env.local.php
      composer.json
    ',
    'before' => [
      'local: composer dump-env prod',
      'local: php bin/console cache:clear',
    ],
  ],
];
