<?php

namespace App\Services;

use App\Jobs\ProcessMedia;
use App\Models\Summary;
use Illuminate\Support\Str;

class MediaService
{
    public function processMedia(array $data): array
    {
        $id = Str::uuid()->toString();

        ProcessMedia::dispatch($id, $data);

        return [
            'id' => $id,
            'message' => 'Media submitted for summarization',
        ];
    }

    public function getSummary(string $id): ?Summary
    {
        return Summary::findOrFail($id);
    }
}
