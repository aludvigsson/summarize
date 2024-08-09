<?php

namespace App\Livewire;

use App\Models\Summary;
use Livewire\Component;

class SummaryDisplay extends Component
{
    public $summaryId;
    public $summary;
    public $error;
    public $isCompleted = false;

    public function mount($summaryId)
    {
        $this->summaryId = $summaryId;
        $this->loadSummary();
    }

    public function loadSummary()
    {
        $summary = Summary::findOrFail($this->summaryId);
        $this->isCompleted = $summary->is_completed;
        $this->error = $summary->error;

        if ($this->isCompleted && !$this->error) {
            $this->summary = json_decode($summary->summary, true);
            $this->formatTimeRanges();
        }
    }

    protected function formatTimeRanges()
    {
        if (!is_array($this->summary)) return;

        foreach ($this->summary as &$item) {
            if (isset($item['time'])) {
                $times = explode('-', $item['time']);
                if (count($times) == 2) {
                    $start = $this->formatTime($times[0]);
                    $end = $this->formatTime($times[1]);
                    $item['time'] = "$start - $end";
                }
            }
        }
    }

    protected function formatTime($time)
    {
        $parts = explode(':', $time);
        if (count($parts) == 3) {
            return sprintf("%02d:%02d:%02d", $parts[0], $parts[1], $parts[2]);
        }
        return $time;
    }

    public function render()
    {
        return view('livewire.summary-display');
    }
}
