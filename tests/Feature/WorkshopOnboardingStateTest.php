<?php
// tests/Feature/WorkshopOnboardingStateTest.php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesTestWorkshop;
use Tests\TestCase;

class WorkshopOnboardingStateTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTestWorkshop;

    public function test_workshops_table_has_onboarding_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('workshops', [
            'onboarding_step', 'onboarding_completed_at',
        ]));
    }

    public function test_a_workshop_created_without_an_explicit_step_is_not_onboarding(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();

        $this->assertNull($workshop->onboarding_step);
        $this->assertFalse($workshop->isOnboarding());
    }

    public function test_advance_onboarding_moves_to_the_next_step(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'products']);

        $workshop->advanceOnboarding('vehicle');

        $fresh = $workshop->fresh();
        $this->assertSame('vehicle', $fresh->onboarding_step);
        $this->assertTrue($fresh->isOnboarding());
        $this->assertNull($fresh->onboarding_completed_at);
    }

    public function test_advance_onboarding_to_null_marks_it_completed(): void
    {
        [, $workshop] = $this->createDirectorWithWorkshop();
        $workshop->update(['onboarding_step' => 'sale']);

        $workshop->advanceOnboarding(null);

        $fresh = $workshop->fresh();
        $this->assertNull($fresh->onboarding_step);
        $this->assertFalse($fresh->isOnboarding());
        $this->assertNotNull($fresh->onboarding_completed_at);
    }
}
