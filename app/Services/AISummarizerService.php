<?php

namespace App\Services;

use OpenAI;

class AISummarizerService
{
    protected OpenAI\Client $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    public function summarize(string $text, string $videoTitle = null, string $timeRange = null): string
    {
        $prompt = $this->buildPrompt($text, $videoTitle, $timeRange);

        $response = $this->client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that summarizes text.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    protected function buildPrompt(string $text, ?string $videoTitle, ?string $timeRange): string
    {
        $prompt = "Please summarize the following text segment concisely. ";
        $prompt .= "Focus on the key points, main ideas, and any notable events or items mentioned. ";
        $prompt .= "Avoid repeating the video title or time range in your summary. ";
        $prompt .= "Don't include any concluding statements about the overall video or its value. ";
        $prompt .= "Just summarize the content for this specific time segment. ";

        if ($videoTitle) {
            $prompt .= "The text is from a video titled: \"$videoTitle\". ";
        }

        if ($timeRange) {
            $prompt .= "This summary covers the time range: $timeRange. ";
        }

        $prompt .= "Here's the text to summarize:\n\n$text";

        return $prompt;
    }
}
