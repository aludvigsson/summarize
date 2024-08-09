<div>
    @if($user && $user->subscribed('default'))
        <p>Subscribed to {{ $user->subscription('default')->stripe_price }}.</p>
        <button wire:click="unsubscribe">Cancel Subscription</button>
    @else
        <p>Not subscribed.</p>
        <button wire:click="subscribe">Subscribe Now</button>
    @endif

    @if($errorMessage)
        <div class="alert alert-danger">
            {{ $errorMessage }}
        </div>
    @endif

    @if($successMessage)
        <div class="alert alert-success">
            {{ $successMessage }}
        </div>
    @endif
</div>
