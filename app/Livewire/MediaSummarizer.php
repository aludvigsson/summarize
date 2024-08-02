<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\MediaService;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;

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
            if ($this->youtubeUrl) {
                Log::info('Processing YouTube URL: ' . $this->youtubeUrl);
                $result = $mediaService->processMedia(['youtube_url' => $this->youtubeUrl]);
            } elseif ($this->file) {
                Log::info('Processing uploaded file');
                $path = $this->file->store('uploads');
                $result = $mediaService->processMedia(['file_path' => $path]);
            } else {
                throw new Exception('No YouTube URL or file provided');
            }

            $this->summaryId = $result['id'];
            Log::info('Summary ID generated: ' . $this->summaryId);
            $this->dispatch('summarizationStarted', $this->summaryId);
        } catch (Exception $e) {
            Log::error('Error in summarize method: ' . $e->getMessage());
            $this->error = 'An error occurred while processing your media: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }

        if (!$this->error) {
            return redirect()->to('/summary/' . $this->summaryId);
        }
    }

    public function render()
    {
        return view('livewire.media-summarizer');
    }
}
