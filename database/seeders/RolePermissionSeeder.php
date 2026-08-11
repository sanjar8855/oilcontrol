<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Faqat platforma egasi (superadmin) boshqaradigan modullar — kompaniyalarni
     * yaratish/o'chirish director'ga berilmaydi, faqat superadmin uchun.
     */
    private const SUPERADMIN_PERMISSIONS = [
        'workshops.manage',
        'global-products.manage',
        'subscription-payments.manage',
    ];

    /**
     * Faqat superadmin/director boshqaradigan ("back-office") modullar.
     */
    private const ADMIN_PERMISSIONS = [
        'users.manage',
        'car-makes.manage',
        'branches.manage',
    ];

    /**
     * Savdo jarayoni bilan bog'liq modullar — xodim ham kira oladi.
     */
    private const SALES_PERMISSIONS = [
        'clients.manage',
        'vehicles.manage',
        'service-logs.manage',
        'payments.manage',
    ];

    /**
     * Menejerlik modullari — xodimga berilmaydi.
     */
    private const BACK_OFFICE_PERMISSIONS = [
        'categories.manage',
        'products.manage',
        'suppliers.manage',
        'expenses.manage',
        'salaries.manage',
        'reports.view',
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superadmin = Role::findOrCreate('superadmin');
        $director = Role::findOrCreate('director');
        $manager = Role::findOrCreate('manager');
        $employee = Role::findOrCreate('employee');

        $allPermissions = collect([
            ...self::SUPERADMIN_PERMISSIONS,
            ...self::ADMIN_PERMISSIONS,
            ...self::SALES_PERMISSIONS,
            ...self::BACK_OFFICE_PERMISSIONS,
        ])->map(fn (string $name) => Permission::findOrCreate($name));

        $superadmin->syncPermissions($allPermissions);
        $director->syncPermissions([
            ...self::ADMIN_PERMISSIONS,
            ...self::SALES_PERMISSIONS,
            ...self::BACK_OFFICE_PERMISSIONS,
        ]);

        $manager->syncPermissions([...self::SALES_PERMISSIONS, ...self::BACK_OFFICE_PERMISSIONS]);

        $employee->syncPermissions(self::SALES_PERMISSIONS);
    }
}
