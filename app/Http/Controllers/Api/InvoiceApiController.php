<?php

namespace App\Http\Controllers\Api;

use App\Actions\Stripe\CreateStripeCheckoutSession;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class InvoiceApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $client = $request->user();

        if (! $client instanceof Client) {
            abort(403);
        }

        $invoices = $client->invoices()
            ->latest('due_date')
            ->paginate(15);

        return InvoiceResource::collection($invoices);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        Gate::authorize('view', $invoice);

        return new InvoiceResource($invoice->load('items'));
    }

    public function checkout(Invoice $invoice, CreateStripeCheckoutSession $action): JsonResponse
    {
        Gate::authorize('checkout', $invoice);

        try {
            $session = $action->handle($invoice);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ]);
    }
}
