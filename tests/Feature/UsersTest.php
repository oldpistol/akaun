<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('runs user migrations and creates a user', function () {
    // Ensure the users table exists from migrations.
    expect(Schema::hasTable('users'))->toBeTrue();

    // Ensure expected columns exist.
    foreach (['id', 'name', 'email', 'password', 'remember_token', 'created_at', 'updated_at'] as $column) {
        expect(Schema::hasColumn('users', $column))->toBeTrue();
    }

    // Create a user via factory and assert persisted.
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'testuser@example.com',
    ]);

    expect($user->exists)->toBeTrue();

    // Assert database has the record.
    $this->assertDatabaseHas('users', [
        'email' => 'testuser@example.com',
        'name' => 'Test User',
    ]);

    // Assert hashed password attribute works (not plain 'password').
    expect($user->password)->not()->toBe('password');
});

it('enforces unique email constraint', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $this->expectException(\Illuminate\Database\QueryException::class);

    // Attempt to violate unique constraint.
    User::factory()->create(['email' => 'duplicate@example.com']);
});
