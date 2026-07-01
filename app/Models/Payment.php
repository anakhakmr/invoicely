<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['invoice_id', 'stripe_payment_intent_id', 'amount', 'status'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;
}
