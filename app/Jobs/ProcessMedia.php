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
use Illuminate\Support\Facades\Log;

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

    public function handle(AISummarizerService $aiSummarizer)
    {
        Log::info('ProcessMedia job started', ['id' => $this->id]);

        try {
            $text = $this->extractText();
            $summary = $aiSummarizer->summarize($text);

            Summary::create([
                'id' => $this->id,
                'summary' => $summary,
                'original_content' => $this->data,
            ]);

            Log::info('ProcessMedia job completed successfully', ['id' => $this->id]);
        } catch (\Exception $e) {
            Log::error('Error in ProcessMedia job', [
                'id' => $this->id,
                'error' => $e->getMessage()
            ]);

            // Optionally, you can rethrow the exception if you want the job to be retried
            // throw $e;
        }
    }

    protected function extractText(): string
    {
        if (isset($this->data['youtube_url'])) {
            return app(YouTubeService::class)->getTranscript($this->data['youtube_url']);
        } elseif (isset($this->data['file_path'])) {
            return app(AudioService::class)->audioToText($this->data['file_path']);
        }

        throw new \Exception('Unsupported media type');
    }
}
