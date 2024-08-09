<?php

namespace App\Services;

use App\Jobs\ProcessMedia;
use App\Models\Summary;
use Illuminate\Support\Str;

class MediaService
{
    public function processMedia(string $summaryId, array $data): void
    {
        ProcessMedia::dispatch($summaryId, $data);
    }

    public function getSummary(string $id): ?Summary
    {
        return Summary::findOrFail($id);
    }
}
