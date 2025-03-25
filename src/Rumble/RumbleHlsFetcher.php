<?php

namespace Mati\Rumble;

use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class RumbleHlsFetcher
{
  public function __construct(
    private CacheInterface $cache,
    private HttpClientInterface $httpClient,
    #[Autowire(env: 'MATI_CHANNEL_URL')]
    private string $channelUrl,
    private LoggerInterface $logger,
  ) {}

  public function fetch(): ?string
  {
    return $this->cache->get('rumble_hls_url', function (CacheItemInterface $cacheItem) {
      $cacheItem->expiresAfter(600);

      try {
        $livestreamUrl = $this->fetchLivestreamUrl();
        $hlsUrl = $this->fetchHlsUrl($livestreamUrl);
      } catch (RumbleException $e) {
        if ($e->isWarning) {
          $this->logger->warning('RumbleHlsFetcher: {message}', ['message' => $e->getMessage(), ...$e->context]);
        } else {
          $this->logger->error('RumbleHlsFetcher: {message}', ['message' => $e->getMessage(), ...$e->context]);
        }

        return null;
      }

      return $hlsUrl;
    });
  }

  private function fetchLivestreamUrl(): string
  {
    $response = $this->httpClient->request('GET', $this->channelUrl);

    $html = $response->getContent();
    $crawler = new Crawler($html, $this->channelUrl);

    $liveThumbnail = $crawler->filter('.thumbnail__thumb--live,.thumbnail__thumb--upcoming')->first();
    if (0 === $liveThumbnail->count()) {
      throw new RumbleException('no live thumbnail present', isWarning: true);
    }

    $livestreamLink = $liveThumbnail->filter('a')->first();
    if (0 === $livestreamLink->count()) {
      throw new RumbleException('live thumbnail has no link');
    }

    $livestreamUrl = $livestreamLink->link()->getUri();
    $this->logger->debug('LivestreamUrlFetcher: got livestream URL', ['livestreamUrl' => $livestreamUrl]);

    return $livestreamUrl;
  }

  private function fetchHlsUrl(string $livestreamUrl): string
  {
    $response = $this->httpClient->request('GET', $livestreamUrl);

    $html = $response->getContent();
    $matched = preg_match('/\.serviceGet\(\'media\.embed\',{video:"(.*?)"/', $html, $matches);
    if (1 !== $matched) {
      throw new RumbleException('RumbleHlsFetcher: HLS URL components not found', ['matched' => $matched], isWarning: true);
    }

    $hlsId = $matches[1];
    $hlsUrl = "https://rumble.com/live-hls-dvr/{$hlsId}/playlist.m3u8?";
    $this->logger->debug('RumbleHlsFetcher: got HLS URL', ['hlsUrl' => $hlsUrl]);

    return $hlsUrl;
  }
}
