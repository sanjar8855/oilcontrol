<?php

namespace App\Http\Controllers;

use App\Services\UserTelegramBotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TelegramVerificationController extends Controller
{
    public function show(Request $request, UserTelegramBotService $telegramBot): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->telegram_verified_at) {
            return redirect()->route('dashboard');
        }

        $verification = $telegramBot->startVerification($user);
        $username = config('services.telegram.users_bot_username');

        return Inertia::render('Telegram/Verify', [
            'deepLink' => "https://t.me/{$username}?start={$verification->link_token}",
            'status' => session('status'),
        ]);
    }

    public function store(Request $request, UserTelegramBotService $telegramBot): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $result = $telegramBot->verifyCode($request->user(), $validated['code']);

        if (!$result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        return redirect()->route('dashboard')->with('success', 'Telegram muvaffaqiyatli tasdiqlandi!');
    }

    public function resend(Request $request, UserTelegramBotService $telegramBot): RedirectResponse
    {
        $result = $telegramBot->resendCode($request->user());

        return back()->with('status', $result['message']);
    }
}
