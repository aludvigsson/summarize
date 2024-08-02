<?php

namespace App\Livewire;


use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\MediaService;

class MediaSummarizer extends Component
{
    use WithFileUploads;

    public $youtubeUrl = '';
    public $file;
    public $summaryId;
    public $isLoading = false;

    protected $rules = [
        'youtubeUrl' => 'required_without:file|url',
        'file' => 'required_without:youtubeUrl|file|mimes:mp3,wav,ogg,mp4,avi,mov|max:50000',
    ];

    public function summarize(MediaService $mediaService): void
    {
        $this->validate();
        $this->isLoading = true;

        if ($this->youtubeUrl) {
            $result = $mediaService->processMedia(['youtube_url' => $this->youtubeUrl]);
        } else {
            $path = $this->file->store('uploads');
            $result = $mediaService->processMedia(['file_path' => $path]);
        }

        $this->summaryId = $result['id'];
        $this->isLoading = false;

        $this->dispatch('summarizationStarted', $this->summaryId);
    }

    public function render(): Factory|View|Application
    {
        return view('livewire.media-summarizer');
    }
}
