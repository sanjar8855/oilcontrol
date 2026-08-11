<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Superadmin paneli: to'lov qo'shish → obuna avtomatik uzayadi.
 * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 1.
 */
class SubscriptionPaymentController extends Controller
{
    public function store(Request $request, Workshop $workshop): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => 'required|string|in:start,pro,maxsus',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string|in:cash,p2p,click,payme',
            'period_days' => 'required|integer|min:1|max:3650',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $paidAt = $validated['paid_at'] ?? now();

        $workshop->subscriptionPayments()->create([
            'plan' => $validated['plan'],
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'status' => 'confirmed',
            'period_days' => $validated['period_days'],
            'paid_at' => $paidAt,
            'confirmed_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $currentExpiry = $workshop->subscription_expires_at?->isFuture()
            ? $workshop->subscription_expires_at
            : now();

        $workshop->update([
            'subscription_plan' => $validated['plan'],
            'subscription_expires_at' => $currentExpiry->copy()->addDays($validated['period_days']),
        ]);

        return redirect()->route('workshops.show', $workshop)
            ->with('success', 'To\'lov qo\'shildi, obuna uzaytirildi!');
    }
}
