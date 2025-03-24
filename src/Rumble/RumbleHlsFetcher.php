<?php

namespace Mati\Rumble;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class RumbleHlsFetcher
{
  public function __construct(
    private HttpClientInterface $httpClient,
    #[Autowire(env: 'MATI_CHANNEL_URL')]
    private string $channelUrl,
    private LoggerInterface $logger,
  ) {}

  public function fetch(): ?string
  {
    $livestreamUrl = $this->fetchLivestreamUrl();
    if (null === $livestreamUrl) {
      return null;
    }

    $hlsUrl = $this->fetchHlsUrl($livestreamUrl);
    if (null === $hlsUrl) {
      return null;
    }

    return $hlsUrl;
  }

  private function fetchLivestreamUrl(): ?string
  {
    $response = $this->httpClient->request('GET', $this->channelUrl);

    $statusCode = $response->getStatusCode();
    if (200 !== $statusCode) {
      $this->logger->error('RumbleHlsFetcher: failed to get livestream landing page', [
        'statusCode' => $statusCode,
      ]);

      return null;
    }

    $html = $response->getContent(false);
    $crawler = new Crawler($html, $this->channelUrl);

    $liveThumbnail = $crawler->filter('.thumbnail__thumb--live,.thumbnail__thumb--upcoming')->first();
    if (0 === $liveThumbnail->count()) {
      $this->logger->warning('RumbleHlsFetcher: no live thumbnail present');

      return null;
    }

    $livestreamLink = $liveThumbnail->filter('a')->first();
    if (0 === $livestreamLink->count()) {
      $this->logger->error('RumbleHlsFetcher: live thumbnail has no link');

      return null;
    }

    $livestreamUrl = $livestreamLink->link()->getUri();
    $this->logger->debug('LivestreamUrlFetcher: got livestream URL', ['livestreamUrl' => $livestreamUrl]);

    return $livestreamUrl;
  }

  private function fetchHlsUrl(string $livestreamUrl): ?string
  {
    $response = $this->httpClient->request('GET', $livestreamUrl);

    $statusCode = $response->getStatusCode();
    if (200 !== $statusCode) {
      $this->logger->error('RumbleHlsFetcher: failed to get livestream page', [
        'statusCode' => $statusCode,
      ]);

      return null;
    }

    $html = $response->getContent(false);
    $matched = preg_match('/\.serviceGet\(\'media\.embed\',{video:"(.*?)"/', $html, $matches);
    if (1 !== $matched) {
      $this->logger->warning('RumbleHlsFetcher: HLS URL components not found', ['matched' => $matched]);

      return null;
    }

    $hlsId = $matches[1];
    $hlsUrl = "https://rumble.com/live-hls-dvr/{$hlsId}/playlist.m3u8?";
    $this->logger->debug('RumbleHlsFetcher: got HLS URL', ['hlsUrl' => $hlsUrl]);

    return $hlsUrl;
  }
}
