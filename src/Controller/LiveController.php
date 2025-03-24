<?php

namespace Mati\Controller;

use Mati\Rumble\RumbleHlsFetcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LiveController extends AbstractController
{
  #[Route('/live', name: 'app_live')]
  public function live(RumbleHlsFetcher $rumbleHlsFetcher): Response
  {
    $hlsUrl = $rumbleHlsFetcher->fetch();
    if (null === $hlsUrl) {
      // TODO
      return new Response();
    }

    return $this->redirect($hlsUrl);
  }
}
