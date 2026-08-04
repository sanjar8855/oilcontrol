<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\ServiceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * ServiceLog uchun to'lovlarni ko'rsatish
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $workshop = $user->currentWorkshop();

        $query = Payment::where('workshop_id', $workshop->id);

        // Branch filtering
        if (!$user->canAccessAllBranches()) {
            $query->where('branch_id', $user->branch_id);
        }

        $payments = $query->with(['serviceLog.vehicle.client', 'user'])
            ->latest('payment_date')
            ->paginate(20);

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
        ]);
    }

    /**
     * Yangi to'lov qo'shish formasini ko'rsatish
     */
    public function create(Request $request): Response
    {
        $serviceLogId = $request->query('service_log_id');
        $serviceLog = null;

        if ($serviceLogId) {
            $serviceLog = ServiceLog::with(['vehicle.client', 'products'])
                ->findOrFail($serviceLogId);

            // Tekshirish
            if ($serviceLog->vehicle->client->workshop_id !== $request->user()->currentWorkshop()->id) {
                abort(403);
            }
        }

        return Inertia::render('Payments/Create', [
            'serviceLog' => $serviceLog,
        ]);
    }

    /**
     * Yangi to'lovni saqlash
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_log_id' => 'required|exists:service_logs,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|in:USD,UZS',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,transfer,other',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $serviceLog = ServiceLog::with('vehicle.client')->findOrFail($validated['service_log_id']);

        // Tekshirish
        if ($serviceLog->vehicle->client->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access
        if (!$user->canAccessAllBranches() && $serviceLog->branch_id !== $user->branch_id) {
            abort(403);
        }

        // Qarz miqdorini tekshirish
        if ($validated['amount'] > $serviceLog->remaining_amount) {
            return back()->withErrors([
                'amount' => "To'lov summasi qolgan qarzdan ko'p bo'lishi mumkin emas. Qolgan qarz: {$serviceLog->remaining_amount}"
            ])->withInput();
        }

        // To'lovni qo'shish
        $serviceLog->addPayment(
            amount: $validated['amount'],
            method: $validated['payment_method'],
            notes: $validated['notes'] ?? null
        );

        return redirect()->route('service-logs.show', $serviceLog->id)
            ->with('success', 'To\'lov muvaffaqiyatli qo\'shildi!');
    }

    /**
     * To'lovni ko'rsatish
     */
    public function show(Request $request, Payment $payment): Response
    {
        $user = $request->user();

        // Tekshirish
        if ($payment->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access
        if (!$user->canAccessAllBranches() && $payment->branch_id !== $user->branch_id) {
            abort(403);
        }

        $payment->load(['serviceLog.vehicle.client', 'user']);

        return Inertia::render('Payments/Show', [
            'payment' => $payment,
        ]);
    }

    /**
     * To'lovni o'chirish
     */
    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        $user = $request->user();

        // Tekshirish
        if ($payment->workshop_id !== $user->currentWorkshop()->id) {
            abort(403);
        }

        // Check branch access
        if (!$user->canAccessAllBranches() && $payment->branch_id !== $user->branch_id) {
            abort(403);
        }

        $serviceLogId = $payment->service_log_id;
        $serviceLog = $payment->serviceLog;

        // To'lovni o'chirish va ServiceLog ni yangilash
        $serviceLog->paid_amount -= $payment->amount;
        $serviceLog->remaining_amount += $payment->amount;

        if ($serviceLog->remaining_amount >= $serviceLog->total_amount) {
            $serviceLog->payment_status = 'unpaid';
        } elseif ($serviceLog->remaining_amount > 0) {
            $serviceLog->payment_status = 'partial';
        } else {
            $serviceLog->payment_status = 'paid';
        }

        $serviceLog->save();
        $payment->delete();

        return redirect()->route('service-logs.show', $serviceLogId)
            ->with('success', 'To\'lov o\'chirildi!');
    }
}
