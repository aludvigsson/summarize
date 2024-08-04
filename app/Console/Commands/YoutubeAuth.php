<?php

namespace App\Console\Commands;

use Google_Client;
use Google_Service_YouTube;
use Illuminate\Console\Command;

class YouTubeAuth extends Command
{
    protected $signature = 'youtube:auth';
    protected $description = 'Authenticate with YouTube API';

    public function handle()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/google-credentials.json'));
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->addScope(Google_Service_YouTube::YOUTUBE_FORCE_SSL);
        $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);
        $client->setRedirectUri('https://summarize.eu-1.sharedwithexpose.com/auth/google/callback');

        $authUrl = $client->createAuthUrl();
        $this->info("Please visit this URL to authorize the application: " . $authUrl);

        $authCode = $this->ask('Enter the authorization code:');

        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        if (isset($accessToken['refresh_token'])) {
            $this->info("Refresh token: " . $accessToken['refresh_token']);
            $this->info("Please add this refresh token to your .env file as YOUTUBE_REFRESH_TOKEN");
        } else {
            $this->error("No refresh token was received. Make sure you've set the access type to 'offline' and added the 'prompt' parameter.");
        }
    }
}
