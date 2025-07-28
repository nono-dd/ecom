<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SpatiePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions

        $permissions = [
            // Utilisateurs et rôles
            'view users',
            'create users',
            'edit users',
            'delete users',
            'restore users',          //  Pour le soft delete
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',

            // Catalogue
            'view all products',      // all (pour les administrateurs)
            'view own products',      // own (pour les utilisateurs)
            'create products',
            'edit all products',
            'edit own products',
            'delete all products',
            'delete own products',
            'restore products',       // Pour le soft delete
            'publish products',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'manage inventory',

            // Commandes
            'view all orders',
            'view own orders',
            'edit order status',
            'cancel orders',
            'refund orders',
            'ship orders',
            'ship order items',

            // Marketplace
            'view all sellings',
            'view own sellings',
            'create sellings',
            'edit all sellings',
            'edit own sellings',
            'delete all sellings',
            'delete own sellings',
            'moderate sellings',

            // Contenu
            'view reviews',
            'create own reviews',
            'delete own reviews',
            'edit own reviews',
            'moderate own reviews',
            'view own addresses',
            'edit own addresses',
            'manage addresses',

            // Administration
            'view reports',
            'view analytics',
            'manage general settings',
            'manage payment settings',
            'manage shipping settings',
            'view countries',
            'create countries',
            'edit countries',
            'delete countries',
        ];

        foreach($permissions as $permission)
        {
            Permission::findOrCreate($permission);
        }

        // Créer les rôles
        $roles = [
            'manager' => [
                'view users',
                'edit users',
                'view all products',
                'create products',
                'edit all products',
                'delete all products',
                'view all orders',
                'edit order status',
                'view reviews',
                'moderate reviews',
                'manage general settings',
            ],
            'customer' => [
                'view all products',
                'view own products',
                'view categories',
                'view all orders',
                'view own orders',
                'create reviews',
                'edit own reviews',
                'moderate reviews',
                'view own addresses',
                'edit own addresses',
            ],
        ];

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions(Permission::all());

        foreach($roles as $role => $rolePermissions)
        {
            $role = Role::findOrCreate($role);
            $role->syncPermissions($rolePermissions);
        }

    }
}
