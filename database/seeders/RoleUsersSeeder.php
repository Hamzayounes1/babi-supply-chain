<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'administrator',
            'supply_manager',
            'buyer',
            'warehouse_manager',
            'logistics',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $users = [
            ['name'=>'Super Admin','email'=>'admin@example.com','password'=>'AdminPass123','role'=>'administrator'],
            ['name'=>'Supply Manager','email'=>'supply@example.com','password'=>'SupplyPass123','role'=>'supply_manager'],
            ['name'=>'Buyer','email'=>'buyer@example.com','password'=>'BuyerPass123','role'=>'buyer'],
            ['name'=>'Warehouse Manager','email'=>'warehouse@example.com','password'=>'WarehousePass123','role'=>'warehouse_manager'],
            ['name'=>'Logistics','email'=>'logistics@example.com','password'=>'LogisticsPass123','role'=>'logistics'],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make($u['password']),
                ]
            );

            if (!$user->hasRole($u['role'])) {
                $user->assignRole($u['role']);
            }
        }
    }
}
