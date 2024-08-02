<?php

namespace App\Jobs;

use App\Services\YouTubeService;
use App\Services\AudioService;
use App\Services\AISummarizerService;
use App\Models\Summary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    protected $data;

    public function __construct(string $id, array $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    public function handle(YouTubeService $youtubeService, AudioService $audioService, AISummarizerService $aiSummarizer)
    {
        $text = $this->extractText($youtubeService, $audioService);
        $summary = $aiSummarizer->summarize($text);

        Summary::create([
            'id' => $this->id,
            'summary' => $summary,
            'original_content' => $this->data,
        ]);
    }

    protected function extractText($youtubeService, $audioService): string
    {
        if (isset($this->data['youtube_url'])) {
            return $youtubeService->getTranscript($this->data['youtube_url']);
        } elseif (isset($this->data['file_path'])) {
            return $audioService->audioToText($this->data['file_path']);
        }

        throw new \Exception('Unsupported media type');
    }
}
