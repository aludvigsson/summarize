<?php

namespace App\Livewire;

use Livewire\Component;

class SubscriptionManagement extends Component
{
    public $user;
    public $errorMessage = '';
    public $successMessage = '';

    public function mount()
    {
        $this->user = auth()->user();
    }

    public function subscribe()
    {
        try {
            $this->user->newSubscription('default', 'price_basic_monthly')
                ->trialDays(5)
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => route('subscription.success'),
                    'cancel_url' => route('subscription.cancel'),
                ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to start subscription: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.subscription-management', ['user' => $this->user]);
    }
}
