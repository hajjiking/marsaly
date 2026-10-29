<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'client'   => 'Client',
            'merchant' => 'Merchant',
            'driver'   => 'Driver',
            'admin'    => 'Administrator (super)',
            'support'  => 'Support agent',
            'finance'  => 'Finance agent',
        ];

        foreach ($roles as $name => $display) {
            Role::firstOrCreate(['name' => $name], ['display_name' => $display]);
        }

        $permissions = [
            'users.manage', 'merchants.approve', 'drivers.approve',
            'orders.view', 'orders.manage', 'orders.assign_driver',
            'settings.manage', 'promos.manage', 'content.manage',
            'finance.payouts', 'finance.settlements', 'finance.reports',
            'support.tickets', 'audit.view',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // Super admin: everything. Support & finance get scoped sets.
        $all = Permission::pluck('id');
        Role::where('name', 'admin')->first()->permissions()->sync($all);

        Role::where('name', 'support')->first()->permissions()->sync(
            Permission::whereIn('name', ['orders.view', 'users.manage', 'support.tickets'])->pluck('id')
        );

        Role::where('name', 'finance')->first()->permissions()->sync(
            Permission::whereIn('name', ['orders.view', 'finance.payouts', 'finance.settlements', 'finance.reports'])->pluck('id')
        );
    }
}
