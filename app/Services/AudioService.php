<?php

namespace App\Services;

use OpenAI;

class AudioService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    public function audioToText(string $filePath): string
    {
        $response = $this->client->audio()->transcribe([
            'model' => 'whisper-1',
            'file' => fopen($filePath, 'r'),
            'response_format' => 'text'
        ]);

        return $response->text;
    }
}
