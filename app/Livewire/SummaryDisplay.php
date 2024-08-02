<?php

namespace App\Livewire;

use App\Models\Summary;
use Livewire\Component;

class SummaryDisplay extends Component
{
    public $summaryId;
    public $summary;

    public function mount($summaryId)
    {
        $this->summaryId = $summaryId;
        $this->loadSummary();
    }

    public function loadSummary()
    {
        $this->summary = Summary::findOrFail($this->summaryId);
    }

    public function render()
    {
        return view('livewire.summary-display');
    }
}
