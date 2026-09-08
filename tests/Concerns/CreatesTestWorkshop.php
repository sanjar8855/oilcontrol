<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Models\Workshop;
use Database\Seeders\RolePermissionSeeder;

trait CreatesTestWorkshop
{
    /**
     * @return array{0: User, 1: Workshop}
     */
    protected function createDirectorWithWorkshop(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('director');

        $workshop = Workshop::create([
            'user_id' => $user->id,
            'name' => 'Test Workshop',
            'phone' => '+998901234567',
            'trial_ends_at' => now()->addDays(30),
        ]);

        return [$user, $workshop];
    }

    protected function createSuperAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }
}
