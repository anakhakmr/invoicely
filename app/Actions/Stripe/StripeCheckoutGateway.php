<?php

namespace App\Actions\Stripe;

use Stripe\Checkout\Session;
use Stripe\StripeClient;

/**
 * Thin wrapper around Stripe's checkout session API, so calling code
 * depends on two plain methods instead of the SDK's nested service
 * factories - this is what gets mocked in tests instead of the SDK itself.
 */
class StripeCheckoutGateway
{
    public function __construct(private StripeClient $stripe) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function createSession(array $params): Session
    {
        return $this->stripe->checkout->sessions->create($params);
    }

    public function retrieveSession(string $id): Session
    {
        return $this->stripe->checkout->sessions->retrieve($id);
    }
}
