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

    protected $summaryId;
    protected $data;

    public function __construct(string $summaryId, array $data)
    {
        $this->summaryId = $summaryId;
        $this->data = $data;
    }

    public function handle(AISummarizerService $aiSummarizer, AudioService $audioService)
    {
        Log::info('ProcessMedia job started', ['id' => $this->summaryId]);

        try {
            $segments = $this->extractText($audioService);
            $summaries = [];
            $videoTitle = isset($this->data['youtube_url']) ? $this->getYouTubeTitle() : null;

            foreach ($segments as $segment) {
                Log::info('Processing segment', ['time' => $segment['time']]);
                $summaries[] = [
                    'time' => $segment['time'],
                    'summary' => $aiSummarizer->summarize($segment['text'], $videoTitle, $segment['time']),
                ];
            }

            Summary::where('id', $this->summaryId)->update([
                'summary' => json_encode($summaries),
                'original_content' => $this->data,
                'is_completed' => true,
            ]);

            Log::info('ProcessMedia job completed successfully', ['id' => $this->summaryId]);
        } catch (\Exception $e) {
            Log::error('Error in ProcessMedia job', [
                'id' => $this->summaryId,
                'error' => $e->getMessage()
            ]);

            Summary::where('id', $this->summaryId)->update([
                'error' => $e->getMessage(),
                'is_completed' => true,
            ]);
        }
    }

    protected function extractText(AudioService $audioService): array
    {
        if (isset($this->data['youtube_url'])) {
            $transcript = app(YouTubeService::class)->getTranscript($this->data['youtube_url']);
            return $this->groupTextByTimeIntervals($transcript);
        } elseif (isset($this->data['file_path'])) {
            $text = $audioService->audioToText($this->data['file_path']);
            return $this->splitTextByTimeSegments($text);
        }

        throw new \Exception('Unsupported media type');
    }

    protected function getYouTubeTitle(): string
    {
        return app(YouTubeService::class)->getVideoTitle($this->data['youtube_url']);
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
        $segments = [];
        $lines = explode("\n", $text);
        $currentSegment = '';
        $currentTime = 0;
        $segmentDuration = 180; // 3 minutes in seconds

        foreach ($lines as $line) {
            $currentSegment .= $line . ' '; // Changed from += to .=
            if (strlen($currentSegment) > 200) { // Adjust the length as needed
                $segments[] = [
                    'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                    'text' => trim($currentSegment),
                ];
                Log::info('Created segment', [
                    'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                    'text' => trim($currentSegment),
                ]);
                $currentSegment = '';
                $currentTime += $segmentDuration;
            }
        }

        // Add the last segment
        if ($currentSegment !== '') {
            $segments[] = [
                'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                'text' => trim($currentSegment),
            ];
            Log::info('Created segment', [
                'time' => gmdate("H:i:s", $currentTime) . '-' . gmdate("H:i:s", $currentTime + $segmentDuration),
                'text' => trim($currentSegment),
            ]);
        }

        return $segments;
    }
}
