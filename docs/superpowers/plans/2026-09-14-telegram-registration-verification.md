# Telegram Registration Verification + Users Bot Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a dedicated second Telegram bot (`@oilcontrol_customers_bot`) that verifies new workshop owners at registration via a bot-delivered code, stays bound to that owner for future subscription warnings, and sends a sample notification when the onboarding tutorial finishes.

**Architecture:** A new `UserTelegramBotService` (independent of the existing `TelegramBotService`, which stays untouched and keeps serving workshops' own customers) owns all Telegram API calls and verification-code logic for the new bot. A `telegram_verifications` table tracks the one-time link token + hashed code per user; `users.telegram_verified_at` (new column) gates access via a new `EnsureTelegramIsVerified` middleware, mirroring the existing `EnsureOnboardingComplete` pattern. `users.telegram_chat_id` (already exists) is reused, populated automatically instead of typed in by hand.

**Tech Stack:** Laravel 11, Inertia + Vue 3, PHPUnit (class-based, `RefreshDatabase`), SQLite in-memory for tests, `Illuminate\Support\Facades\Http` (faked in tests, never hits the real Telegram API).

**Spec:** `docs/superpowers/specs/2026-09-14-telegram-registration-verification-design.md`

## Global Constraints

- Second bot only — `@oilcontrol_customers_bot`, bot ID `8957400095`, config keys `services.telegram.users_bot_token` / `users_bot_username` / `users_bot_webhook_secret`. Already set in `.env`. Never touch `services.telegram.bot_token` (the existing client-reminder bot) or its service/controller/routes.
- `users.telegram_chat_id` is reused as-is — no migration for it. Only `users.telegram_verified_at` is new.
- Verification code: 6 digits, stored only as a hash (`Hash::make`), valid 5 minutes, max 5 wrong attempts before a new code is required, resend throttled to 60 seconds.
- Webhook requests must be rejected (403) unless the `X-Telegram-Bot-Api-Secret-Token` header matches `services.telegram.users_bot_webhook_secret`.
- Existing users must never be blocked: the `telegram_verified_at` migration backfills all pre-existing rows to `now()`. `Database\Factories\UserFactory` must do the same by default (`telegram_verified_at => now()`) so the rest of the test suite (onboarding/dashboard gates) keeps working without modification — this is the single most important non-obvious constraint in this plan.
- All user-facing strings are Uzbek, matching the rest of the app (see `Login.vue`, `OnboardingController`, `TelegramBotService`).
- Tests are PHPUnit classes extending `Tests\TestCase`, using `RefreshDatabase` and (where a workshop is needed) `Tests\Concerns\CreatesTestWorkshop::createDirectorWithWorkshop()`. Any test that exercises `UserTelegramBotService` must `Http::fake(...)` — never let a test call the real Telegram API.
- Money formatting in Telegram messages: `number_format($amount, 0, '.', ' ')` (matches the "so'm" style already used elsewhere).

---

### Task 1: Config + migrations

**Files:**
- Modify: `config/services.php`
- Create: `database/migrations/2026_09_14_100000_add_telegram_verified_at_to_users_table.php`
- Create: `database/migrations/2026_09_14_100001_create_telegram_verifications_table.php`

**Interfaces:**
- Produces: `users.telegram_verified_at` (nullable timestamp), `telegram_verifications` table (`id, user_id, link_token, code_hash, code_expires_at, attempts, last_sent_at, timestamps`), config keys `services.telegram.users_bot_token`, `services.telegram.users_bot_username`, `services.telegram.users_bot_webhook_secret`.

- [ ] **Step 1: Add the new config keys**

Edit `config/services.php`, inside the existing `'telegram' => [...]` array (do not create a second `telegram` key):

```php
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'mini_app_url' => env('TELEGRAM_MINI_APP_URL'),
        'users_bot_token' => env('TELEGRAM_USERS_BOT_TOKEN'),
        'users_bot_username' => env('TELEGRAM_USERS_BOT_USERNAME'),
        'users_bot_webhook_secret' => env('TELEGRAM_USERS_BOT_WEBHOOK_SECRET'),
    ],
```

- [ ] **Step 2: Write the `telegram_verified_at` migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('telegram_verified_at')->nullable()->after('telegram_chat_id');
        });

        // Mavjud userlar bloklanmasin — faqat yangi ro'yxatdan o'tuvchilar
        // Telegram orqali tasdiqlashi shart.
        DB::table('users')->whereNull('telegram_verified_at')->update(['telegram_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_verified_at');
        });
    }
};
```

- [ ] **Step 3: Write the `telegram_verifications` migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('link_token')->unique();
            $table->string('code_hash')->nullable();
            $table->timestamp('code_expires_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_verifications');
    }
};
```

- [ ] **Step 4: Run the migrations**

Run: `php artisan migrate`
Expected: both migrations run without error; `php artisan tinker --execute="dd(Schema::hasColumn('users','telegram_verified_at'), Schema::hasTable('telegram_verifications'));"` prints `true, true`.

- [ ] **Step 5: Commit**

```bash
git add config/services.php database/migrations/2026_09_14_100000_add_telegram_verified_at_to_users_table.php database/migrations/2026_09_14_100001_create_telegram_verifications_table.php
git commit -m "feat: add telegram_verified_at column and telegram_verifications table"
```

---

### Task 2: TelegramVerification model + User model + UserFactory default

**Files:**
- Create: `app/Models/TelegramVerification.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Test: `tests/Feature/TelegramVerificationModelTest.php`

**Interfaces:**
- Consumes: `telegram_verifications` table, `users.telegram_verified_at` (Task 1).
- Produces: `App\Models\TelegramVerification` (fillable `user_id, link_token, code_hash, code_expires_at, attempts, last_sent_at`; casts `code_expires_at`/`last_sent_at` to datetime, `attempts` to integer; `belongsTo(User::class)`), `User::telegramVerification(): HasOne`, `User::casts()` includes `telegram_verified_at => datetime`, `UserFactory::unverifiedTelegram()` state.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/TelegramVerificationModelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\TelegramVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramVerificationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_factory_users_are_telegram_verified_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->telegram_verified_at);
    }

    public function test_the_unverified_telegram_factory_state_clears_the_timestamp(): void
    {
        $user = User::factory()->unverifiedTelegram()->create();

        $this->assertNull($user->telegram_verified_at);
    }

    public function test_a_user_has_one_telegram_verification_record(): void
    {
        $user = User::factory()->create();
        TelegramVerification::create([
            'user_id' => $user->id,
            'link_token' => 'abc123',
        ]);

        $this->assertInstanceOf(TelegramVerification::class, $user->telegramVerification);
        $this->assertSame('abc123', $user->telegramVerification->link_token);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TelegramVerificationModelTest`
Expected: FAIL — `TelegramVerification` class not found / `telegramVerification` relation not found / `unverifiedTelegram` state not found.

- [ ] **Step 3: Create the `TelegramVerification` model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramVerification extends Model
{
    protected $fillable = [
        'user_id',
        'link_token',
        'code_hash',
        'code_expires_at',
        'attempts',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'code_expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 4: Add the relation + cast to `User`**

In `app/Models/User.php`, add the import and relation near `workshop()`:

```php
use Illuminate\Database\Eloquent\Relations\HasOne; // already imported — reuse it
```

(`HasOne` is already imported for `workshop()`; no new import needed.) Add the relation method right after `workshop()`:

```php
    public function telegramVerification(): HasOne
    {
        return $this->hasOne(TelegramVerification::class);
    }
```

Add `use App\Models\TelegramVerification;`? Not needed — same namespace `App\Models`, no import required for the return-type-only reference since PHP resolves same-namespace classes automatically.

In `casts()`, add:

```php
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'telegram_verified_at' => 'datetime',
            'password' => 'hashed',
            'salary' => 'decimal:2',
            'hire_date' => 'date',
        ];
    }
```

- [ ] **Step 5: Update `UserFactory`**

In `database/factories/UserFactory.php`, add `'telegram_verified_at' => now(),` to `definition()`:

```php
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+998#########'),
            'email_verified_at' => now(),
            'telegram_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
```

Add the new state right after `unverified()`:

```php
    /**
     * Indicate that the user has not completed Telegram verification.
     */
    public function unverifiedTelegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_verified_at' => null,
        ]);
    }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=TelegramVerificationModelTest`
Expected: PASS (3 tests)

- [ ] **Step 7: Run the full existing suite to confirm nothing else broke**

Run: `php artisan test`
Expected: PASS — this is the step that proves the `telegram_verified_at` default didn't silently break onboarding/dashboard gate tests.

- [ ] **Step 8: Commit**

```bash
git add app/Models/TelegramVerification.php app/Models/User.php database/factories/UserFactory.php tests/Feature/TelegramVerificationModelTest.php
git commit -m "feat: add TelegramVerification model and default users to telegram-verified in tests"
```

---

### Task 3: UserTelegramBotService

**Files:**
- Create: `app/Services/UserTelegramBotService.php`
- Test: `tests/Feature/UserTelegramBotServiceTest.php`

**Interfaces:**
- Consumes: `App\Models\User` (`telegram_chat_id`, `telegram_verified_at` — Task 2), `App\Models\TelegramVerification` (Task 2), `config('services.telegram.users_bot_token')` (Task 1).
- Produces (used by Tasks 5, 6, 7, 9, 10):
  - `sendMessage(string $chatId, string $message): bool`
  - `setWebhook(string $url, string $secretToken): array`
  - `deleteWebhook(): array`
  - `getMe(): array`
  - `getWebhookInfo(): array`
  - `startVerification(User $user): TelegramVerification`
  - `handleStartCommand(int $chatId, string $token): void`
  - `verifyCode(User $user, string $code): array` — `['ok' => bool, 'message' => string]`
  - `resendCode(User $user): array` — `['ok' => bool, 'message' => string]`
  - `sendOnboardingResult(User $user, array $summary): bool` — `$summary` has keys `saleTotal` (float), `profit` (float), `reminders` (array)

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/UserTelegramBotServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\TelegramVerification;
use App\Models\User;
use App\Services\UserTelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserTelegramBotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_verification_creates_a_link_token_for_the_user(): void
    {
        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);

        $verification = $service->startVerification($user);

        $this->assertNotEmpty($verification->link_token);
        $this->assertSame($user->id, $verification->user_id);
    }

    public function test_start_verification_reuses_the_existing_record_on_repeat_calls(): void
    {
        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);

        $first = $service->startVerification($user);
        $second = $service->startVerification($user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->link_token, $second->link_token);
    }

    public function test_handle_start_command_binds_the_chat_id_and_sends_a_code(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);

        $service->handleStartCommand(555666, $verification->link_token);

        $this->assertSame('555666', $user->fresh()->telegram_chat_id);
        $this->assertNotNull($verification->fresh()->code_hash);
        $this->assertNotNull($verification->fresh()->code_expires_at);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }

    public function test_handle_start_command_ignores_an_unknown_token(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $service = app(UserTelegramBotService::class);
        $service->handleStartCommand(999, 'unknown-token');

        $this->assertDatabaseCount('telegram_verifications', 0);
    }

    public function test_verify_code_marks_the_user_as_verified_on_a_correct_code(): void
    {
        $capturedText = null;
        Http::fake(function ($request) use (&$capturedText) {
            if (str_contains($request->url(), 'sendMessage')) {
                $capturedText = $request['text'];
            }
            return Http::response(['ok' => true], 200);
        });

        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);
        $service->handleStartCommand(1234, $verification->link_token);

        preg_match('/<code>(\d{6})<\/code>/', $capturedText, $matches);
        $result = $service->verifyCode($user->fresh(), $matches[1]);

        $this->assertTrue($result['ok']);
        $this->assertNotNull($user->fresh()->telegram_verified_at);
        $this->assertDatabaseCount('telegram_verifications', 0);
    }

    public function test_verify_code_rejects_a_wrong_code_and_counts_the_attempt(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);
        $service->handleStartCommand(1234, $verification->link_token);

        $result = $service->verifyCode($user->fresh(), '000000');

        $this->assertFalse($result['ok']);
        $this->assertSame(1, $verification->fresh()->attempts);
        $this->assertNull($user->fresh()->telegram_verified_at);
    }

    public function test_verify_code_locks_out_after_five_wrong_attempts(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);
        $service->handleStartCommand(1234, $verification->link_token);

        for ($i = 0; $i < 5; $i++) {
            $service->verifyCode($user->fresh(), '000000');
        }

        $result = $service->verifyCode($user->fresh(), '000000');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Yangi kod', $result['message']);
    }

    public function test_resend_code_is_throttled_within_sixty_seconds(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        $service = app(UserTelegramBotService::class);
        $verification = $service->startVerification($user);
        $service->handleStartCommand(1234, $verification->link_token);

        $result = $service->resendCode($user->fresh());

        $this->assertFalse($result['ok']);
    }

    public function test_send_onboarding_result_does_nothing_without_a_verified_chat(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->unverifiedTelegram()->create();
        $service = app(UserTelegramBotService::class);

        $sent = $service->sendOnboardingResult($user, ['saleTotal' => 40000, 'profit' => 10000, 'reminders' => []]);

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_send_onboarding_result_sends_a_message_with_a_verified_chat(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create(['telegram_chat_id' => '777']);
        $service = app(UserTelegramBotService::class);

        $sent = $service->sendOnboardingResult($user, ['saleTotal' => 40000, 'profit' => 10000, 'reminders' => ['2026-10-01']]);

        $this->assertTrue($sent);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=UserTelegramBotServiceTest`
Expected: FAIL — class `App\Services\UserTelegramBotService` not found.

- [ ] **Step 3: Write the service**

```php
<?php

namespace App\Services;

use App\Models\TelegramVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SaaS mijozi (ustaxona egasi) uchun alohida bot — @oilcontrol_customers_bot.
 * Ustaxonalarning o'z mijozlariga (Client modeli) xabar yuboradigan
 * TelegramBotService'dan butunlay mustaqil.
 */
class UserTelegramBotService
{
    protected string $botToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->botToken = (string) config('services.telegram.users_bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(string $chatId, string $message): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("Users bot xabar yuborishda xato: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Users bot xatolik: " . $e->getMessage());
            return false;
        }
    }

    public function setWebhook(string $url, string $secretToken): array
    {
        $response = Http::post("{$this->apiUrl}/setWebhook", [
            'url' => $url,
            'secret_token' => $secretToken,
        ]);

        return $response->json();
    }

    public function deleteWebhook(): array
    {
        return Http::post("{$this->apiUrl}/deleteWebhook")->json();
    }

    public function getMe(): array
    {
        return Http::get("{$this->apiUrl}/getMe")->json();
    }

    public function getWebhookInfo(): array
    {
        return Http::get("{$this->apiUrl}/getWebhookInfo")->json();
    }

    /**
     * /telegram/verify sahifasi ochilganda chaqiriladi. Bir foydalanuvchi
     * uchun bitta yozuv — qayta chaqirilsa, mavjud (hali ishlatilmagan)
     * link_token qaytariladi, chunki sahifani qayta yuklash allaqachon
     * yuborilgan kodni bekor qilmasligi kerak.
     */
    public function startVerification(User $user): TelegramVerification
    {
        return TelegramVerification::firstOrCreate(
            ['user_id' => $user->id],
            ['link_token' => Str::random(32)]
        );
    }

    /**
     * Botda "/start <token>" kelganda: tokenni topib, shu userning
     * chat_id'sini yozadi va 6 xonali kodni yuboradi. Token topilmasa,
     * jimgina rad javobi yuboriladi (userga bog'liq bo'lmagan xato bo'lgani
     * uchun DB'da hech narsa o'zgarmaydi).
     */
    public function handleStartCommand(int $chatId, string $token): void
    {
        $verification = TelegramVerification::where('link_token', $token)->first();

        if (!$verification) {
            $this->sendMessage((string) $chatId, "❌ Havola muddati o'tgan yoki noto'g'ri. Saytga qaytib, \"Kodni qayta yuborish\"ni bosing.");
            return;
        }

        $user = $verification->user;
        $user->update(['telegram_chat_id' => (string) $chatId]);

        $this->issueCode($verification, $chatId, $user);
    }

    protected function issueCode(TelegramVerification $verification, int $chatId, User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $verification->update([
            'code_hash' => Hash::make($code),
            'code_expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'last_sent_at' => now(),
        ]);

        $message = "🔐 <b>Tasdiqlash kodi</b>\n\n";
        $message .= "Hurmatli {$user->name}, OilControl hisobingizni tasdiqlash uchun quyidagi kodni saytga kiriting:\n\n";
        $message .= "<code>{$code}</code>\n\n";
        $message .= "Kod 5 daqiqa amal qiladi.";

        $this->sendMessage((string) $chatId, $message);
    }

    /**
     * "Kodni qayta yuborish" tugmasi. 60 soniyalik throttle bilan.
     *
     * @return array{ok: bool, message: string}
     */
    public function resendCode(User $user): array
    {
        $verification = $user->telegramVerification;

        if (!$verification || !$user->telegram_chat_id) {
            return ['ok' => false, 'message' => "Avval Telegram botni ochib \"Start\" bosing."];
        }

        if ($verification->last_sent_at && $verification->last_sent_at->diffInSeconds(now()) < 60) {
            return ['ok' => false, 'message' => "Iltimos, 60 soniyadan keyin qayta urinib ko'ring."];
        }

        $this->issueCode($verification, (int) $user->telegram_chat_id, $user);

        return ['ok' => true, 'message' => "Kod qayta yuborildi."];
    }

    /**
     * Saytdagi forma orqali kod tasdiqlanadi.
     *
     * @return array{ok: bool, message: string}
     */
    public function verifyCode(User $user, string $code): array
    {
        $verification = $user->telegramVerification;

        if (!$verification || !$verification->code_expires_at) {
            return ['ok' => false, 'message' => "Avval Telegram botdan kod so'rang."];
        }

        if ($verification->attempts >= 5) {
            return ['ok' => false, 'message' => "Urinishlar soni tugadi. Yangi kod so'rang."];
        }

        if ($verification->code_expires_at->isPast()) {
            return ['ok' => false, 'message' => "Kod muddati tugagan. Yangi kod so'rang."];
        }

        if (!Hash::check($code, $verification->code_hash)) {
            $verification->increment('attempts');

            if ($verification->fresh()->attempts >= 5) {
                return ['ok' => false, 'message' => "Kod noto'g'ri. Urinishlar tugadi — Yangi kod so'rang."];
            }

            return ['ok' => false, 'message' => "Kod noto'g'ri."];
        }

        $user->update(['telegram_verified_at' => now()]);
        $verification->delete();

        return ['ok' => true, 'message' => "Tasdiqlandi."];
    }

    /**
     * Onboarding (4-qadam) yakunlanganda namunaviy natija xabari.
     *
     * @param  array{saleTotal: float, profit: float, reminders: array}  $summary
     */
    public function sendOnboardingResult(User $user, array $summary): bool
    {
        if (!$user->telegram_chat_id || !$user->telegram_verified_at) {
            return false;
        }

        $remindersCount = count($summary['reminders'] ?? []);

        $message = "🎉 <b>O'quv bosqichi yakunlandi!</b>\n\n";
        $message .= "Bu — sinov uchun yaratilgan <b>namunaviy</b> savdo natijasi. ";
        $message .= "Kelajakda haqiqiy savdolaringiz bo'yicha xuddi shunday xabar shu yerga kelib turadi.\n\n";
        $message .= "💰 Savdo summasi: <b>" . number_format($summary['saleTotal'] ?? 0, 0, '.', ' ') . " so'm</b>\n";
        $message .= "📈 Foyda: <b>" . number_format($summary['profit'] ?? 0, 0, '.', ' ') . " so'm</b>\n";
        $message .= "🔔 Rejalashtirilgan eslatmalar: <b>{$remindersCount} ta</b>";

        return $this->sendMessage((string) $user->telegram_chat_id, $message);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=UserTelegramBotServiceTest`
Expected: PASS (10 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/UserTelegramBotService.php tests/Feature/UserTelegramBotServiceTest.php
git commit -m "feat: add UserTelegramBotService for registration verification and reports"
```

---

### Task 4: EnsureTelegramIsVerified middleware

**Files:**
- Create: `app/Http/Middleware/EnsureTelegramIsVerified.php`
- Modify: `bootstrap/app.php`
- Modify: `app/Http/Middleware/CheckSubscription.php`
- Test: `tests/Feature/TelegramVerificationGateTest.php`

**Interfaces:**
- Consumes: `User::telegram_verified_at` (Task 2).
- Produces: `App\Http\Middleware\EnsureTelegramIsVerified`, registered in the `web` middleware group between `EnsureActiveWorkshop` and `CheckSubscription`. The route `/telegram/verify` itself doesn't exist until Task 5, but this task's tests never call it — they only assert on the redirect **path** (`'/telegram/verify'` as a string) and on the already-existing `logout` route, so all three tests are fully self-contained and must pass by the end of this task.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/TelegramVerificationGateTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class TelegramVerificationGateTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_an_unverified_user_is_redirected_to_the_telegram_verify_page(): void
    {
        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/telegram/verify');
    }

    public function test_logout_stays_reachable_while_unverified(): void
    {
        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
    }

    public function test_a_verified_user_is_never_redirected_to_the_telegram_verify_page(): void
    {
        [$user] = $this->createDirectorWithWorkshop();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
}
```

(The "verify page itself stays reachable" case is covered in Task 5's controller test, once the route exists.)

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TelegramVerificationGateTest`
Expected: FAIL — `test_an_unverified_user_is_redirected...` fails because there's no redirect yet (dashboard loads normally); `test_a_verified_user_is_never_redirected` already passes (no-op today), that's fine.

- [ ] **Step 3: Write the middleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Yangi ro'yxatdan o'tgan (Telegram orqali hali tasdiqlamagan) userlarni
 * /telegram/verify sahifasiga qaytaradi. EnsureOnboardingComplete
 * patterniga o'xshash, lekin onboarding boshlanishidan OLDIN ishga tushishi
 * kerak — shuning uchun bootstrap/app.php'da EnsureOnboardingComplete'dan
 * oldin ro'yxatga olinadi.
 */
class EnsureTelegramIsVerified
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->telegram_verified_at) {
            return $next($request);
        }

        if ($request->routeIs('telegram.verify*') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect('/telegram/verify');
    }
}
```

- [ ] **Step 4: Register it in `bootstrap/app.php`**

Edit the `$middleware->web(append: [...])` block:

```php
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsureActiveWorkshop::class,
            \App\Http\Middleware\EnsureTelegramIsVerified::class,
            \App\Http\Middleware\CheckSubscription::class,
            \App\Http\Middleware\EnsureOnboardingComplete::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
            'telegram/users-webhook',
        ]);
```

- [ ] **Step 5: Allow the verify routes through `CheckSubscription` defensively**

Edit `app/Http/Middleware/CheckSubscription.php`, add to `ALWAYS_ALLOWED`:

```php
    private const ALWAYS_ALLOWED = [
        'logout',
        'profile.*',
        'workshops.switch*',
        'workshops.*',
        'subscription-payments.*',
        'global-products.*',
        'onboarding.*',
        'telegram.verify*',
    ];
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=TelegramVerificationGateTest`
Expected: PASS (all 3 tests). None of them require the `/telegram/verify` route to exist yet: the redirect assertion checks the `Location` header path as a plain string (Laravel doesn't validate that the target route exists to issue a redirect), and the logout test only relies on the already-existing `logout` route being exempted by the middleware.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Middleware/EnsureTelegramIsVerified.php app/Http/Middleware/CheckSubscription.php bootstrap/app.php tests/Feature/TelegramVerificationGateTest.php
git commit -m "feat: gate authenticated routes behind Telegram verification"
```

---

### Task 5: TelegramVerificationController + routes + Verify.vue

**Files:**
- Create: `app/Http/Controllers/TelegramVerificationController.php`
- Create: `resources/js/Pages/Telegram/Verify.vue`
- Modify: `routes/web.php`
- Test: `tests/Feature/TelegramVerificationControllerTest.php`

**Interfaces:**
- Consumes: `UserTelegramBotService::startVerification/verifyCode/resendCode` (Task 3), route `telegram.users-webhook` (Task 6 — the test in this file posts directly to `/telegram/users-webhook`, so write this test's webhook calls against the literal path; Task 6 must produce that exact path).
- Produces: named routes `telegram.verify` (GET `/telegram/verify`), `telegram.verify.code` (POST `/telegram/verify`), `telegram.verify.resend` (POST `/telegram/verify/resend`); Inertia component `Telegram/Verify` with props `deepLink: string`, `status: string|null`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/TelegramVerificationControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\TelegramVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class TelegramVerificationControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_show_renders_the_verify_page_with_a_deep_link(): void
    {
        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $response = $this->actingAs($user)->get(route('telegram.verify'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Telegram/Verify')
            ->where('deepLink', fn ($link) => str_contains($link, 'https://t.me/') && str_contains($link, '?start=')));
    }

    public function test_submitting_the_correct_code_verifies_the_user(): void
    {
        $capturedText = null;
        Http::fake(function ($request) use (&$capturedText) {
            if (str_contains($request->url(), 'sendMessage')) {
                $capturedText = $request['text'];
            }
            return Http::response(['ok' => true], 200);
        });

        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get(route('telegram.verify'));
        $verification = TelegramVerification::where('user_id', $user->id)->firstOrFail();

        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 4242], 'text' => "/start {$verification->link_token}"],
        ], ['X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.users_bot_webhook_secret')])
            ->assertOk();

        preg_match('/<code>(\d{6})<\/code>/', $capturedText, $matches);

        $response = $this->actingAs($user)->post(route('telegram.verify.code'), ['code' => $matches[1]]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->telegram_verified_at);
    }

    public function test_submitting_a_wrong_code_shows_an_error(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get(route('telegram.verify'));
        $verification = TelegramVerification::where('user_id', $user->id)->firstOrFail();

        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 4242], 'text' => "/start {$verification->link_token}"],
        ], ['X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.users_bot_webhook_secret')]);

        $response = $this->actingAs($user)->post(route('telegram.verify.code'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->telegram_verified_at);
    }

    public function test_resend_is_throttled_right_after_the_first_code(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get(route('telegram.verify'));
        $verification = TelegramVerification::where('user_id', $user->id)->firstOrFail();

        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 4242], 'text' => "/start {$verification->link_token}"],
        ], ['X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.users_bot_webhook_secret')]);

        $response = $this->actingAs($user)->post(route('telegram.verify.resend'));

        $response->assertSessionHas('status');
        $this->assertStringContainsString('60', session('status'));
    }

    public function test_the_verify_page_itself_stays_reachable_while_unverified(): void
    {
        [$user] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_verified_at' => null]);

        $this->actingAs($user)->get(route('telegram.verify'))->assertOk();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=TelegramVerificationControllerTest`
Expected: FAIL — route `telegram.verify` not defined.

- [ ] **Step 3: Write the controller**

```php
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
```

- [ ] **Step 4: Register the routes**

In `routes/web.php`, add the import near the other controller imports:

```php
use App\Http\Controllers\TelegramVerificationController;
```

Add this block right after the existing `Route::middleware('auth')->prefix('telegram')->group(...)` block (the one with `set-webhook` etc.), still outside the big `Route::middleware('auth')->group(...)` that holds onboarding/resources — a small dedicated group is clearer:

```php
Route::middleware('auth')->group(function () {
    Route::get('/telegram/verify', [TelegramVerificationController::class, 'show'])->name('telegram.verify');
    Route::post('/telegram/verify', [TelegramVerificationController::class, 'store'])->name('telegram.verify.code');
    Route::post('/telegram/verify/resend', [TelegramVerificationController::class, 'resend'])->name('telegram.verify.resend');
});
```

- [ ] **Step 5: Write the Vue page**

Create `resources/js/Pages/Telegram/Verify.vue`:

```vue
<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    deepLink: String,
    status: String,
});

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('telegram.verify.code'), {
        onFinish: () => form.reset('code'),
    });
};

const resendForm = useForm({});

const resend = () => {
    resendForm.post(route('telegram.verify.resend'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Telegramda tasdiqlang" />

        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
            Telegramda tasdiqlang
        </h2>

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Hisobingizni faollashtirish uchun Telegram botimizni oching va
            "Start" tugmasini bosing — sizga 6 xonali tasdiqlash kodi keladi.
        </p>

        <a
            :href="deepLink"
            target="_blank"
            rel="noopener noreferrer"
            class="mb-4 inline-flex w-full items-center justify-center rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-400"
        >
            Botni ochish
        </a>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="mt-4">
            <InputLabel for="code" value="Tasdiqlash kodi" />

            <TextInput
                id="code"
                type="text"
                inputmode="numeric"
                maxlength="6"
                class="mt-1 block w-full tracking-widest"
                v-model="form.code"
                required
                autofocus
                placeholder="123456"
            />

            <InputError class="mt-2" :message="form.errors.code" />

            <div class="mt-4 flex items-center justify-between">
                <button
                    type="button"
                    :disabled="resendForm.processing"
                    @click="resend"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Kodni qayta yuborish
                </button>

                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Tasdiqlash
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-6 text-center">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
            >
                Chiqish
            </Link>
        </div>
    </GuestLayout>
</template>
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=TelegramVerificationControllerTest`
Expected: FAIL still on the two tests that post to `/telegram/users-webhook` (Task 6 not done yet) — that's expected at this point. `test_show_renders_the_verify_page_with_a_deep_link` and `test_the_verify_page_itself_stays_reachable_while_unverified` should PASS now.

Also re-run Task 4's gate test:

Run: `php artisan test --filter=TelegramVerificationGateTest`
Expected: all 3 PASS now that `/telegram/verify` (named `telegram.verify`) exists.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/TelegramVerificationController.php resources/js/Pages/Telegram/Verify.vue routes/web.php tests/Feature/TelegramVerificationControllerTest.php
git commit -m "feat: add the Telegram verification page and code-submission flow"
```

---

### Task 6: UserTelegramWebhookController + webhook route + set-webhook command

**Files:**
- Create: `app/Http/Controllers/UserTelegramWebhookController.php`
- Create: `app/Console/Commands/SetUsersTelegramWebhook.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/UserTelegramWebhookControllerTest.php`
- Test: `tests/Feature/SetUsersTelegramWebhookCommandTest.php`

**Interfaces:**
- Consumes: `UserTelegramBotService::handleStartCommand/setWebhook` (Task 3).
- Produces: route `telegram.users-webhook` (POST `/telegram/users-webhook`), artisan command `telegram:set-users-webhook`.

- [ ] **Step 1: Write the failing webhook controller test**

Create `tests/Feature/UserTelegramWebhookControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\TelegramVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserTelegramWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_requests_without_the_correct_secret_token(): void
    {
        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 1], 'text' => '/start whatever'],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret'])
            ->assertForbidden();
    }

    public function test_it_rejects_requests_with_no_secret_token_at_all(): void
    {
        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 1], 'text' => '/start whatever'],
        ])->assertForbidden();
    }

    public function test_it_binds_the_chat_id_for_a_valid_start_token(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        TelegramVerification::create(['user_id' => $user->id, 'link_token' => 'test-token-123']);

        $this->post('/telegram/users-webhook', [
            'message' => ['chat' => ['id' => 777], 'text' => '/start test-token-123'],
        ], ['X-Telegram-Bot-Api-Secret-Token' => config('services.telegram.users_bot_webhook_secret')])
            ->assertOk();

        $this->assertSame('777', $user->fresh()->telegram_chat_id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserTelegramWebhookControllerTest`
Expected: FAIL — route not found (404), so `assertForbidden()`/`assertOk()` fail.

- [ ] **Step 3: Write the webhook controller**

```php
<?php

namespace App\Http\Controllers;

use App\Services\UserTelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserTelegramWebhookController extends Controller
{
    public function __construct(protected UserTelegramBotService $telegramBot)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (!$secret || $secret !== config('services.telegram.users_bot_webhook_secret')) {
            Log::warning('Users bot webhook: secret_token mos kelmadi');
            abort(403);
        }

        $message = $request->input('message');

        if ($message && isset($message['text']) && str_starts_with($message['text'], '/start ')) {
            $chatId = (int) ($message['chat']['id'] ?? 0);
            $token = trim(substr($message['text'], 7));

            if ($chatId && $token !== '') {
                $this->telegramBot->handleStartCommand($chatId, $token);
            }
        }

        return response()->json(['ok' => true]);
    }
}
```

- [ ] **Step 4: Register the route + CSRF exemption**

In `routes/web.php`, add the import and route (near the existing `telegram/webhook` route):

```php
use App\Http\Controllers\UserTelegramWebhookController;
```

```php
// Telegram Users Bot Webhook (Auth siz - Telegram serveri chaqiradi)
Route::post('/telegram/users-webhook', [UserTelegramWebhookController::class, 'handle'])->name('telegram.users-webhook');
```

The CSRF exemption for this path was already added in Task 4 Step 4 (`'telegram/users-webhook'` in `bootstrap/app.php`'s `validateCsrfTokens(except: [...])`). Confirm it's there.

- [ ] **Step 5: Run webhook test to verify it passes**

Run: `php artisan test --filter=UserTelegramWebhookControllerTest`
Expected: PASS (3 tests)

- [ ] **Step 6: Re-run Task 5's controller test — it depended on this route**

Run: `php artisan test --filter=TelegramVerificationControllerTest`
Expected: all 5 tests PASS now.

- [ ] **Step 7: Write the failing command test**

Create `tests/Feature/SetUsersTelegramWebhookCommandTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SetUsersTelegramWebhookCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calls_telegrams_set_webhook_api_with_the_configured_secret(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200)]);

        $this->artisan('telegram:set-users-webhook')->assertExitCode(0);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'setWebhook')
                && $request['secret_token'] === config('services.telegram.users_bot_webhook_secret')
                && str_contains($request['url'], '/telegram/users-webhook');
        });
    }
}
```

- [ ] **Step 8: Run test to verify it fails**

Run: `php artisan test --filter=SetUsersTelegramWebhookCommandTest`
Expected: FAIL — command `telegram:set-users-webhook` not found.

- [ ] **Step 9: Write the command**

```php
<?php

namespace App\Console\Commands;

use App\Services\UserTelegramBotService;
use Illuminate\Console\Command;

class SetUsersTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-users-webhook';

    protected $description = "Users bot (@oilcontrol_customers_bot) uchun Telegram webhookni o'rnatish";

    public function handle(UserTelegramBotService $telegram): int
    {
        $url = route('telegram.users-webhook');
        $secret = (string) config('services.telegram.users_bot_webhook_secret');

        $result = $telegram->setWebhook($url, $secret);

        $this->info(json_encode($result));

        return (isset($result['ok']) && $result['ok']) ? self::SUCCESS : self::FAILURE;
    }
}
```

- [ ] **Step 10: Run test to verify it passes**

Run: `php artisan test --filter=SetUsersTelegramWebhookCommandTest`
Expected: PASS

- [ ] **Step 11: Commit**

```bash
git add app/Http/Controllers/UserTelegramWebhookController.php app/Console/Commands/SetUsersTelegramWebhook.php routes/web.php tests/Feature/UserTelegramWebhookControllerTest.php tests/Feature/SetUsersTelegramWebhookCommandTest.php
git commit -m "feat: add the users-bot webhook endpoint and set-webhook command"
```

---

### Task 7: Migrate the profile's manual Telegram field to a status display

**Files:**
- Modify: `app/Http/Requests/ProfileUpdateRequest.php`
- Modify: `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue`
- Test: `tests/Feature/ProfileTest.php`

**Interfaces:**
- Consumes: `route('telegram.verify')` (Task 5), `user.telegram_verified_at` (shared automatically via Inertia's `auth.user` prop — no controller change needed, see `HandleInertiaRequests::share()`).

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/ProfileTest.php` (inside the class, after `test_correct_password_must_be_provided_to_delete_account`):

```php
    public function test_telegram_chat_id_cannot_be_set_manually_via_profile_update(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'phone' => $user->phone,
                'telegram_chat_id' => '999999999',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->telegram_chat_id);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ProfileTest`
Expected: FAIL on the new test — today `telegram_chat_id` IS accepted and saved.

- [ ] **Step 3: Remove the manual field from `ProfileUpdateRequest`**

In `app/Http/Requests/ProfileUpdateRequest.php`, delete the whole `'telegram_chat_id' => [...]` block:

```php
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'locale' => ['nullable', 'string', 'in:uz,ru'],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'position' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['nullable', 'in:active,on_leave,terminated'],
            'address' => ['nullable', 'string'],
        ];
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ProfileTest`
Expected: PASS (all `ProfileTest` tests)

- [ ] **Step 5: Update the Vue form**

In `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue`:

Remove this line from the `useForm({...})` call:

```js
    telegram_chat_id: user.telegram_chat_id || '',
```

Replace the whole `<div v-if="$page.props.auth.user.role === 'director'">...</div>` block (the one with the editable Telegram Chat ID input) with:

```vue
            <div v-if="$page.props.auth.user.role === 'director'">
                <InputLabel value="Telegram" />

                <p v-if="user.telegram_verified_at" class="mt-1 text-sm text-green-600 dark:text-green-400">
                    ✅ Telegram ulangan — obuna ogohlantirishlari va hisobotlar shu yerga keladi.
                </p>
                <p v-else class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Ulanmagan.
                    <Link
                        :href="route('telegram.verify')"
                        class="font-medium text-indigo-600 underline hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-100"
                    >
                        Ulash
                    </Link>
                </p>
            </div>
```

Add `Link` to the existing `@inertiajs/vue3` import:

```js
import { Link, useForm, usePage } from '@inertiajs/vue3';
```

- [ ] **Step 6: Manually verify in the browser**

Run: `npm run dev` (if not already running), log in as a director whose `telegram_verified_at` is set (default for any factory/seeded user after Task 2), open `/profile`, confirm the Telegram section shows "✅ Telegram ulangan" and there is no longer an editable Chat ID field.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/ProfileUpdateRequest.php resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue tests/Feature/ProfileTest.php
git commit -m "feat: replace the manual Telegram chat ID field with a connection status"
```

---

### Task 8: Migrate SendSubscriptionExpiryWarnings to the users bot

**Files:**
- Modify: `app/Console/Commands/SendSubscriptionExpiryWarnings.php`
- Test: `tests/Feature/SendSubscriptionExpiryWarningsTest.php`

**Interfaces:**
- Consumes: `UserTelegramBotService::sendMessage` (Task 3).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/SendSubscriptionExpiryWarningsTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class SendSubscriptionExpiryWarningsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_it_sends_a_warning_through_the_users_bot_when_telegram_is_verified(): void
    {
        $capturedUrl = null;
        Http::fake(function ($request) use (&$capturedUrl) {
            $capturedUrl = $request->url();
            return Http::response(['ok' => true], 200);
        });

        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_chat_id' => '123456', 'telegram_verified_at' => now()]);
        $workshop->update(['trial_ends_at' => now()->addDays(3), 'subscription_expires_at' => null]);

        $this->artisan('subscriptions:notify-expiring')->assertExitCode(0);

        $this->assertNotNull($capturedUrl);
        $this->assertStringContainsString(config('services.telegram.users_bot_token'), $capturedUrl);
    }

    public function test_it_skips_a_workshop_whose_owner_has_not_completed_telegram_verification(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_chat_id' => '123456', 'telegram_verified_at' => null]);
        $workshop->update(['trial_ends_at' => now()->addDays(3), 'subscription_expires_at' => null]);

        $this->artisan('subscriptions:notify-expiring')->assertExitCode(0);

        Http::assertNothingSent();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=SendSubscriptionExpiryWarningsTest`
Expected: FAIL on the first test — today the command uses the OLD bot token (`services.telegram.bot_token`), so the captured URL won't contain `users_bot_token`.

- [ ] **Step 3: Update the command**

Replace the full contents of `app/Console/Commands/SendSubscriptionExpiryWarnings.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Workshop;
use App\Services\UserTelegramBotService;
use Illuminate\Console\Command;

/**
 * Obuna (yoki sinov muddati) 7/3/1 kun ichida tugaydigan kompaniyalar
 * direktoriga Telegram (users bot) orqali ogohlantirish yuboradi.
 * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 1.
 */
class SendSubscriptionExpiryWarnings extends Command
{
    protected $signature = 'subscriptions:notify-expiring';

    protected $description = 'Obuna muddati 7/3/1 kunda tugaydigan kompaniyalar direktoriga Telegram orqali ogohlantirish yuborish';

    public function handle(UserTelegramBotService $telegram)
    {
        $warningDays = config('plans.warning_days', [7, 3, 1]);

        $workshops = Workshop::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('subscription_expires_at')->orWhereNotNull('trial_ends_at');
            })
            ->with('user:id,name,telegram_chat_id,telegram_verified_at')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($workshops as $workshop) {
            $daysRemaining = $workshop->daysUntilExpiry();

            if ($daysRemaining === null || !in_array($daysRemaining, $warningDays, true)) {
                continue;
            }

            $user = $workshop->user;

            if (!$user || !$user->telegram_chat_id || !$user->telegram_verified_at) {
                $skipped++;
                $this->line("O'tkazib yuborildi (Telegram ulanmagan): {$workshop->name}");
                continue;
            }

            $label = $workshop->isOnTrial() ? 'Sinov muddati' : 'Obuna';
            $message = "⚠️ <b>{$workshop->name}</b>\n\n{$label} <b>{$daysRemaining} kundan</b> so'ng tugaydi.\n\nUzaytirish uchun tizim administratori bilan bog'laning.";

            if ($telegram->sendMessage($user->telegram_chat_id, $message)) {
                $sent++;
                $this->info("Yuborildi: {$workshop->name} ({$daysRemaining} kun qoldi)");
            } else {
                $this->error("Xato: {$workshop->name}");
            }
        }

        $this->newLine();
        $this->info("Yakunlandi! Yuborildi: {$sent}, O'tkazib yuborildi: {$skipped}");

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=SendSubscriptionExpiryWarningsTest`
Expected: PASS (both tests)

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/SendSubscriptionExpiryWarnings.php tests/Feature/SendSubscriptionExpiryWarningsTest.php
git commit -m "feat: send subscription expiry warnings through the users bot"
```

---

### Task 9: Onboarding finish() sends the sample result

**Files:**
- Modify: `app/Http/Controllers/OnboardingController.php`
- Test: `tests/Feature/OnboardingTelegramNotificationTest.php`

**Interfaces:**
- Consumes: `UserTelegramBotService::sendOnboardingResult` (Task 3).
- Produces: private `OnboardingController::resultSummary(Workshop $workshop): array` (keys `products`, `saleTotal`, `profit`, `reminders` — same shape `result()` already returns to Inertia today).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/OnboardingTelegramNotificationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\GlobalProduct;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class OnboardingTelegramNotificationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    private function completeOnboardingUpToResult($user, $workshop): void
    {
        $workshop->update(['onboarding_step' => 'products']);
        $globalProduct = GlobalProduct::create(['name' => 'Motor moyi (Cobalt)', 'unit' => 'litr', 'is_active' => true]);

        $this->actingAs($user)->post(route('onboarding.products.store'), [
            'items' => [
                ['global_product_id' => $globalProduct->id, 'stock_quantity' => 10, 'purchase_price' => 15000, 'selling_price' => 20000],
            ],
        ]);

        $this->actingAs($user)->post(route('onboarding.vehicle.store'), [
            'name' => 'Aziz Karimov',
            'phone' => '+998901234567',
            'plate_number' => '01A777AA',
            'make' => 'Cobalt',
        ]);

        $vehicle = Vehicle::where('plate_number', '01A777AA')->firstOrFail();
        $product = $workshop->fresh()->products()->firstOrFail();

        $this->actingAs($user)->post(route('service-logs.store'), [
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'next_service_km' => 5000,
            'service_type' => 'Birinchi servis',
            'products' => [
                ['id' => $product->id, 'quantity' => 2, 'unit_price' => 20000],
            ],
        ]);
    }

    public function test_finishing_onboarding_sends_a_sample_result_message_when_telegram_is_verified(): void
    {
        $capturedText = null;
        Http::fake(function ($request) use (&$capturedText) {
            if (str_contains($request->url(), 'sendMessage')) {
                $capturedText = $request['text'];
            }
            return Http::response(['ok' => true], 200);
        });

        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_chat_id' => '999888', 'telegram_verified_at' => now()]);
        $this->completeOnboardingUpToResult($user, $workshop);

        $this->actingAs($user)->post(route('onboarding.finish'))->assertRedirect(route('dashboard'));

        $this->assertNotNull($capturedText);
        $this->assertStringContainsString('40 000', $capturedText);
    }

    public function test_finishing_onboarding_sends_nothing_when_telegram_is_not_verified(): void
    {
        Http::fake();

        [$user, $workshop] = $this->createDirectorWithWorkshop();
        $user->update(['telegram_chat_id' => null, 'telegram_verified_at' => null]);
        $this->completeOnboardingUpToResult($user, $workshop);

        $this->actingAs($user)->post(route('onboarding.finish'))->assertRedirect(route('dashboard'));

        Http::assertNothingSent();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=OnboardingTelegramNotificationTest`
Expected: FAIL on the first test (`$capturedText` stays null — nothing is sent today); second test passes trivially already.

- [ ] **Step 3: Refactor `OnboardingController`**

In `app/Http/Controllers/OnboardingController.php`, add the import:

```php
use App\Models\Workshop;
use App\Services\UserTelegramBotService;
```

Replace the `result()` method and add `resultSummary()`:

```php
    public function result(Request $request): Response
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop && $workshop->onboarding_step === 'result', 403);

        return Inertia::render('Onboarding/Result', $this->resultSummary($workshop));
    }

    /**
     * @return array{products: \Illuminate\Support\Collection, saleTotal: float, profit: float, reminders: \Illuminate\Support\Collection}
     */
    private function resultSummary(Workshop $workshop): array
    {
        $products = $workshop->products()
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'stock_quantity']);

        $serviceLog = ServiceLog::whereHas('vehicle.client', fn ($q) => $q->where('workshop_id', $workshop->id))
            ->with(['products', 'reminders'])
            ->latest('id')
            ->first();

        $profit = 0;
        foreach ($serviceLog?->products ?? [] as $product) {
            $profit += ($product->pivot->unit_price - $product->purchase_price) * $product->pivot->quantity;
        }

        return [
            'products' => $products,
            'saleTotal' => (float) ($serviceLog->total_amount ?? 0),
            'profit' => $profit,
            'reminders' => $serviceLog?->reminders->pluck('scheduled_date')->map(fn ($date) => $date->toDateString())->sort()->values() ?? collect(),
        ];
    }
```

Replace `finish()`:

```php
    public function finish(Request $request, OnboardingCleanupService $cleanup, UserTelegramBotService $telegramBot): RedirectResponse
    {
        $workshop = $request->user()->currentWorkshop();

        abort_unless($workshop && $workshop->onboarding_step === 'result', 403);

        $user = $request->user();

        if ($user->telegram_verified_at) {
            try {
                $telegramBot->sendOnboardingResult($user, $this->resultSummary($workshop));
            } catch (\Exception $e) {
                report($e);
            }
        }

        $cleanup->purge($workshop);
        $workshop->advanceOnboarding(null);

        return redirect()->route('dashboard')
            ->with('success', "Tabriklaymiz! Tizimdan foydalanishni o'rganish yakunlandi.");
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=OnboardingTelegramNotificationTest`
Expected: PASS (both tests)

- [ ] **Step 5: Re-run the pre-existing onboarding result test to confirm the refactor didn't change its output**

Run: `php artisan test --filter=OnboardingResultStepTest`
Expected: PASS unchanged (the `Result` page still receives the exact same `products`/`saleTotal`/`profit`/`reminders` shape).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/OnboardingController.php tests/Feature/OnboardingTelegramNotificationTest.php
git commit -m "feat: send a sample Telegram report when the onboarding tutorial finishes"
```

---

### Task 10: Full regression + webhook setup note

**Files:** none (verification only)

- [ ] **Step 1: Run the entire test suite**

Run: `php artisan test`
Expected: PASS, zero failures. Pay special attention to any test outside this plan's files that newly fails — that almost always means some other test creates a `User` in a way that bypasses `UserFactory`'s default (`telegram_verified_at`), or asserts on the removed `telegram_chat_id` profile field. Investigate and fix rather than working around it.

- [ ] **Step 2: Build frontend assets**

Run: `npm run build`
Expected: no Vue/Vite errors (confirms `Telegram/Verify.vue` and the edited `UpdateProfileInformationForm.vue` compile).

- [ ] **Step 3: Document the production webhook step**

This is a manual, one-time production step (not part of the automated test suite) — note it for whoever deploys this: after deploying, run `php artisan telegram:set-users-webhook` on the production server once, then confirm with the existing bot-info-style check: `curl -s "https://api.telegram.org/bot<TELEGRAM_USERS_BOT_TOKEN>/getWebhookInfo"` and verify `"url"` points at `https://oilcontrol.uz/telegram/users-webhook` with no `last_error_message`.

- [ ] **Step 4: Final commit (only if Steps 1-2 required fixes)**

If any fix was needed to make the suite green, commit it separately with a clear message (e.g. `fix: correct telegram_verified_at handling in <test>`), following the same commit conventions as the rest of this plan.

---
