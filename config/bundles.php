<?php

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
  FrameworkBundle::class => ['all' => true],
  MakerBundle::class => ['dev' => true],
  MonologBundle::class => ['all' => true],
  TwigBundle::class => ['all' => true],
];
