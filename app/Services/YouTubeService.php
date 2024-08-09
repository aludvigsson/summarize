<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    protected $client;
    protected $languageCode;

    public function __construct($languageCode = 'en')
    {
        $this->client = new Client([
            'headers' => [
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'accept-language' => 'en-En,en;q=0.9',
                'cache-control' => 'no-cache',
                'pragma' => 'no-cache',
                'sec-ch-ua' => '"Google Chrome";v="117", "Not;A=Brand";v="8", "Chromium";v="117"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"Windows"',
                'sec-fetch-dest' => 'document',
                'sec-fetch-mode' => 'navigate',
                'sec-fetch-site' => 'none',
                'sec-fetch-user' => '?1',
                'upgrade-insecure-requests' => '1',
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36'
            ],
            'timeout' => 20,
            'allow_redirects' => [
                'max' => 10,
                'strict' => false,
                'referer' => false,
                'protocols' => ['http', 'https'],
                'track_redirects' => false,
            ],
            'verify' => false,
        ]);

        $this->languageCode = $languageCode;
    }

    public function getTranscript(string $videoUrl): array
    {
        Log::info('Fetching transcript', ['url' => $videoUrl]);
        $captionUrl = $this->getCaptionsBaseUrl($videoUrl);
        if (empty($captionUrl)) {
            throw new Exception('Captions not available for this video.');
        }

        $subtitles = $this->getSubtitles($captionUrl);
        return $subtitles;
    }

    protected function getCaptionsBaseUrl(string $videoUrl): ?string
    {
        $response = $this->client->get($videoUrl);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to get captions: " . $response->getStatusCode());
        }

        $html = $response->getBody()->getContents();
        preg_match('/"captionTracks":([^\]]*])/', $html, $matches);

        if (empty($matches[1]) || strpos($matches[1], '"baseUrl":') === false) {
            throw new Exception("Caption URL not found");
        }

        $results = json_decode($matches[1], true);
        $result = array_filter($results, function ($result) {
            return $result['languageCode'] == $this->languageCode;
        });

        if (empty($result)) {
            throw new Exception("Caption URL not found for languageCode: {$this->languageCode}");
        }

        return $result[0]['baseUrl'] ?? null;
    }

    protected function getSubtitles(string $captionUrl): array
    {
        $response = $this->client->get($captionUrl);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to get subtitles: " . $response->getStatusCode());
        }

        $subtitlesContent = $response->getBody()->getContents();
        return $this->parseXmlTextNodes($subtitlesContent);
    }

    private function parseXmlTextNodes(string $xml): array
    {
        $result = [];
        $xmlObject = simplexml_load_string($xml);
        if ($xmlObject) {
            $textNodes = $xmlObject->xpath('//text');
            foreach ($textNodes as $textNode) {
                $result[] = [
                    'time' => (string)$textNode['start'],
                    'text' => (string)$textNode,
                ];
                Log::info('Parsed text node', [
                    'time' => (string)$textNode['start'],
                    'text' => (string)$textNode,
                ]);
            }
        }
        return $result;
    }

    public function getVideoTitle(string $videoUrl): string
    {
        Log::info('Fetching video title', ['url' => $videoUrl]);
        $response = $this->client->get($videoUrl);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to get video page: " . $response->getStatusCode());
        }

        $html = $response->getBody()->getContents();
        preg_match('/<title>(.*?)<\/title>/', $html, $matches);

        if (empty($matches[1])) {
            throw new Exception("Video title not found");
        }

        $title = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        $title = str_replace(' - YouTube', '', $title);

        return trim($title);
    }
}

