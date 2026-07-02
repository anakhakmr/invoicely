<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;

/**
 * Staff (User, via Filament) have full access to every invoice. Clients
 * (via the portal and the API) may only view and check out their own.
 */
class InvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|Client $user): bool
    {
        return $user instanceof User;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|Client $user, Invoice $invoice): bool
    {
        if ($user instanceof Client) {
            return $invoice->client_id === $user->id;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|Client $user): bool
    {
        return $user instanceof User;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|Client $user, Invoice $invoice): bool
    {
        return $user instanceof User;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|Client $user, Invoice $invoice): bool
    {
        return $user instanceof User;
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User|Client $user): bool
    {
        return $user instanceof User;
    }

    /**
     * Determine whether the user can start a Stripe checkout for the invoice.
     */
    public function checkout(User|Client $user, Invoice $invoice): bool
    {
        return $user instanceof Client && $invoice->client_id === $user->id;
    }
}
