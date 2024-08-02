<?php

namespace App\Services;

use Google_Client;
use Google_Service_YouTube;

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

        // Fetch captions
        $captions = $this->youtube->captions->listCaptions('snippet', $videoId);

        // Assuming the first caption track is the one we want
        $captionId = $captions->getItems()[0]->getId();

        // Download the caption track
        $captionTrack = $this->youtube->captions->download($captionId);

        // Parse the caption track and convert to plain text
        // This is a simplified example; you might need a more robust parser
        $plainText = strip_tags($captionTrack);

        return $plainText;
    }

    protected function extractVideoId(string $url): string
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        return $params['v'] ?? '';
    }
}
