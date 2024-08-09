<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\MediaService;
use App\Models\Summary;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;

class MediaSummarizer extends Component
{
    use WithFileUploads;

    #[Validate('url|required_without:file')]
    public $youtubeUrl = '';

    #[Validate('nullable|file|mimes:mp3,wav,ogg,mp4,avi,mov|max:50000|required_without:youtubeUrl')]
    public $file;

    public $summaryId;
    public $isLoading = false;
    public $error = '';

    public function summarize(MediaService $mediaService)
    {
        Log::info('Summarize method called');

        $this->validate();
        Log::info('Validation passed');

        $this->isLoading = true;
        $this->error = '';

        try {
            // Create a new Summary record with a unique ID
            $this->summaryId = (string) Str::uuid();
            $summary = Summary::create([
                'id' => $this->summaryId,
                'is_completed' => false,
                'summary' => null,
                'original_content' => null,
            ]);

            if ($this->youtubeUrl) {
                Log::info('Processing YouTube URL: ' . $this->youtubeUrl);
                $mediaService->processMedia($this->summaryId, ['youtube_url' => $this->youtubeUrl]);
            } elseif ($this->file) {
                Log::info('Processing uploaded file');
                $path = $this->file->store('uploads');
                $mediaService->processMedia($this->summaryId, ['file_path' => $path]);
            } else {
                throw new Exception('No YouTube URL or file provided');
            }

            Log::info('Summary ID generated: ' . $this->summaryId);
            $this->dispatch('summarizationStarted', $this->summaryId);
        } catch (Exception $e) {
            Log::error('Error in summarize method: ' . $e->getMessage());
            $this->error = 'An error occurred while processing your media: ' . $e->getMessage();
            if ($this->summaryId) {
                Summary::where('id', $this->summaryId)->update(['error' => $this->error]);
            }
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.media-summarizer');
    }
}
