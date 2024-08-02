<div class="min-h-screen bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="text-center">
            <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl sm:tracking-tight lg:text-6xl">
                Media Summarizer
            </h1>
            <p class="mt-5 text-xl text-gray-500">
                Transform your media into concise summaries in seconds.
            </p>
        </div>

        <div class="mt-12 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form wire:submit.prevent="summarize">
                    <div class="space-y-6">
                        <div>
                            <label for="youtubeUrl" class="block text-sm font-medium text-gray-700">
                                YouTube URL
                            </label>
                            <div class="mt-1">
                                <input type="url" name="youtubeUrl" id="youtubeUrl" wire:model="youtubeUrl"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                       placeholder="https://www.youtube.com/watch?v=...">
                            </div>
                        </div>

                        <div class="flex items-center">
                            <span class="flex-grow border-t border-gray-300"></span>
                            <span class="px-2 text-gray-500">OR</span>
                            <span class="flex-grow border-t border-gray-300"></span>
                        </div>

                        <div>
                            <label for="file" class="block text-sm font-medium text-gray-700">
                                Upload Media File
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload a file</span>
                                            <input id="file-upload" name="file-upload" type="file" class="sr-only" wire:model="file">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        MP3, WAV, OGG, MP4, AVI, MOV up to 50MB
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Summarize
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($isLoading)
            <div class="mt-6 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-indigo-600">Processing your media...</p>
            </div>
        @endif

        @if ($summaryId)
            <div class="mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"> Your media is being summarized. Summary ID: {{ $summaryId }}</span>
            </div>
        @endif
    </div>
</div>
