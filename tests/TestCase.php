<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure roles exist
        $roles = ['admin', 'auditor', 'reviewer', 'readonly'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    protected function createUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }
}
