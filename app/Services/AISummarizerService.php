<?php

namespace App\Services;

use OpenAI;

class AISummarizerService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    public function summarize(string $text): string
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that summarizes text.'],
                ['role' => 'user', 'content' => "Please summarize the following text:\n\n$text"],
            ],
        ]);

        return $response->choices[0]->message->content;
    }
}
