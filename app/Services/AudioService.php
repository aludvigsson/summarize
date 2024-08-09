<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Storage;

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
            'file' => fopen(Storage::path($filePath), 'r'),
            'response_format' => 'text'
        ]);

        return $response->text;
    }

    public function splitTextByTimeSegments(string $text, int $segmentDuration = 180): array
    {
        $segments = [];
        $words = explode(' ', $text);
        $currentSegment = '';
        $currentTime = 0;
        $wordsPerMinute = 150; // Adjust this based on average speaking speed

        foreach ($words as $word) {
            $currentSegment .= $word . ' ';
            $wordCount = str_word_count($currentSegment);

            if ($wordCount >= ($wordsPerMinute * $segmentDuration / 60)) {
                $segments[] = [
                    'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                    'text' => trim($currentSegment),
                ];
                $currentSegment = '';
                $currentTime += $segmentDuration;
            }
        }

        // Add the last segment if there's any text left
        if (!empty($currentSegment)) {
            $segments[] = [
                'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                'text' => trim($currentSegment),
            ];
        }

        return $segments;
    }
}
