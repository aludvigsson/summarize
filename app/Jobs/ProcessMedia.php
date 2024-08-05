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
            $segments = $this->extractText();
            $summaries = [];

            foreach ($segments as $segment) {
                Log::info('Processing segment', ['time' => $segment['time']]);
                $summaries[] = [
                    'time' => $segment['time'],
                    'summary' => $aiSummarizer->summarize($segment['text']),
                ];
            }

            Summary::create([
                'id' => $this->id,
                'summary' => json_encode($summaries),
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

    protected function extractText(): array
    {
        if (isset($this->data['youtube_url'])) {
            $transcript = app(YouTubeService::class)->getTranscript($this->data['youtube_url']);
            return $this->groupTextByTimeIntervals($transcript);
        } elseif (isset($this->data['file_path'])) {
            $text = app(AudioService::class)->audioToText($this->data['file_path']);
            return $this->splitTextByTimeSegments($text);
        }

        throw new \Exception('Unsupported media type');
    }

    protected function groupTextByTimeIntervals(array $transcript, int $interval = 180): array
    {
        $segments = [];
        $currentSegment = '';
        $startTime = 0;

        foreach ($transcript as $node) {
            $time = $this->convertTimeToSeconds($node['time']);
            if ($time - $startTime >= $interval) {
                if ($currentSegment) {
                    $segments[] = [
                        'time' => gmdate("H:i:s", $startTime) . '-' . gmdate("H:i:s", $startTime + $interval),
                        'text' => $currentSegment,
                    ];
                    Log::info('Created segment', [
                        'time' => gmdate("H:i:s", $startTime) . '-' . gmdate("H:i:s", $startTime + $interval),
                        'text' => $currentSegment,
                    ]);
                }
                $currentSegment = '';
                $startTime = $time;
            }
            $currentSegment .= ' ' . $node['text'];
        }

        if ($currentSegment) {
            $segments[] = [
                'time' => gmdate("H:i:s", $startTime) . '-' . gmdate("H:i:s", $startTime + $interval),
                'text' => $currentSegment,
            ];
            Log::info('Created segment', [
                'time' => gmdate("H:i:s", $startTime) . '-' . gmdate("H:i:s", $startTime + $interval),
                'text' => $currentSegment,
            ]);
        }

        return $segments;
    }

    protected function convertTimeToSeconds(string $time): int
    {
        $parts = explode('.', $time);
        $seconds = (int)$parts[0];
        return $seconds;
    }

    protected function splitTextByTimeSegments(string $text): array
    {
        // Example implementation: split text into fixed 3-minute segments.
        // This should be adapted based on your specific requirements.

        $segments = [];
        $lines = explode("\n", $text);
        $currentSegment = '';
        $currentTime = 0;
        $segmentDuration = 180; // 3 minutes in seconds

        foreach ($lines as $line) {
            $currentSegment += $line . ' ';
            if (strlen($currentSegment) > 200) { // Adjust the length as needed
                $segments[] = [
                    'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                    'text' => $currentSegment,
                ];
                Log::info('Created segment', [
                    'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                    'text' => $currentSegment,
                ]);
                $currentSegment = '';
                $currentTime += $segmentDuration;
            }
        }

        // Add the last segment
        if ($currentSegment !== '') {
            $segments[] = [
                'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                'text' => $currentSegment,
            ];
            Log::info('Created segment', [
                'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                'text' => $currentSegment,
            ]);
        }

        return $segments;
    }
}
?>
