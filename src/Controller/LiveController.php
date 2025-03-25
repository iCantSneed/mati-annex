<?php

namespace Mati\Controller;

use Mati\Rumble\RumbleHlsFetcher;
use Psr\Cache\CacheItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;

final class LiveController extends AbstractController
{
  #[Route('/live', name: 'app_live')]
  public function live(CacheInterface $cache, RumbleHlsFetcher $rumbleHlsFetcher): Response
  {
    $hlsUrl = $cache->get('rumble_hls_url', function (CacheItemInterface $cacheItem) use ($rumbleHlsFetcher) {
      $cacheItem->expiresAfter(600);
      $hlsUrl = $rumbleHlsFetcher->fetch();
      if (null === $hlsUrl) {
        return $this->generateUrl('app_nonlive_chunklist');
      }

      return $hlsUrl;
    });

    return $this->redirect($hlsUrl);
  }

  #[Route('/nonlive/chunklist.m3u8', name: 'app_nonlive_chunklist')]
  public function nonliveChunklist(): Response
  {
    return $this->render('nonlive/chunklist.m3u8.twig', [], new Response(null, 200, [
      'Content-Type' => 'application/vnd.apple.mpegurl.audio',
    ]));
  }
}
