<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('can create a new user', function () {
    $user = User::factory()->make();

    visit('/app')
        ->click('Users')
        ->click('New user')
        ->fill('form.name', $user->name)
        ->fill('form.email', $user->email)
        ->fill('form.password', 'Password123!')
        ->select('form.jenis_kelamin', 'L')
        ->select('form.role', 'teacher')
        ->click('[type="submit"][wire\\:target="create"]')
        ->assertSee('Created');

    assertDatabaseHas('users', [
        'name' => $user->name,
        'email' => $user->email,
    ]);
})->skip('Playwright needs updating. Run: npm install playwright@latest && npx playwright install');

it('can edit an existing user', function () {
    $newRecord = User::factory()->make();

    visit('/app')
        ->click('Users')
        ->click('Edit')
        ->fill('form.name', $newRecord->name)
        ->click('[type="submit"][wire\\:target="save"]')
        ->assertSee('Saved');

    assertDatabaseHas('users', [
        'name' => $newRecord->name,
    ]);
})->skip('Playwright needs updating. Run: npm install playwright@latest && npx playwright install');

it('can delete an existing user', function () {
    $user = User::factory()->create();

    visit('/app')
        ->click('Users')
        ->click('Edit')
        ->click('Delete')
        ->click('[type="submit"][wire\\:target="callMountedAction"]')
        ->assertSee('Deleted');

    assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
})->skip('Playwright needs updating. Run: npm install playwright@latest && npx playwright install');
