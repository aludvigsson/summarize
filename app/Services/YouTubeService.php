<?php

namespace App\Services;

use Google_Client;
use Google_Service_YouTube;
use Google_Service_Exception;

class YouTubeService
{
    protected $client;
    protected $youtube;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setDeveloperKey(config('services.youtube.api_key'));
        $this->youtube = new Google_Service_YouTube($this->client);
    }

    public function getTranscript(string $videoUrl): string
    {
        $videoId = $this->extractVideoId($videoUrl);

        if (empty($videoId)) {
            throw new \Exception('Invalid YouTube URL');
        }

        try {
            // Fetch captions
            $captions = $this->youtube->captions->listCaptions('snippet', $videoId);

            // Check if captions are available
            if (empty($captions->getItems())) {
                throw new \Exception('No captions available for this video');
            }

            // Get the first available caption track
            $captionId = $captions->getItems()[0]->getId();

            // Download the caption track
            $captionTrack = $this->youtube->captions->download($captionId);

            // Parse the caption track and convert to plain text
            $plainText = strip_tags($captionTrack);

            return $plainText;
        } catch (Google_Service_Exception $e) {
            // Handle API errors (e.g., invalid API key, quota exceeded)
            throw new \Exception('YouTube API error: ' . $e->getMessage());
        }
    }

    protected function extractVideoId(string $url): string
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        return $params['v'] ?? '';
    }
}
