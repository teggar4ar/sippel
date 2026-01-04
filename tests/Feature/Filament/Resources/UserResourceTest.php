<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(function () {
    /** @var User $adminUser */
    $adminUser = Auth::user();

    // Keep only the admin user for RBAC tests
    User::whereNot('id', $adminUser->id)->delete();
});

it('can render the index page', function () {
    livewire(ListUsers::class)
        ->assertOk();
});

it('can render the create page', function () {
    livewire(CreateUser::class)
        ->assertOk();
});

it('can render the edit page', function () {
    $user = User::factory()->create();

    livewire(EditUser::class, [
        'record' => $user->id,
    ])
        ->assertOk()
        ->assertSchemaStateSet([
            'name' => $user->name,
            'email' => $user->email,
        ]);
});

it('has column', function (string $column) {
    livewire(ListUsers::class)
        ->assertTableColumnExists($column);
})->with(['name', 'email', 'created_at', 'updated_at']);

it('can render visible column', function (string $column) {
    livewire(ListUsers::class)
        ->loadTable()
        ->assertCanRenderTableColumn($column);
})->with(['name', 'created_at']); // email and updated_at are hidden by default

it('can sort column', function (string $column) {
    $records = User::factory(5)->create();

    livewire(ListUsers::class)
        ->loadTable()
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($column), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($column), inOrder: true);
})->with(['name']);

it('can search column', function (string $column) {
    $records = User::factory(5)->create();

    $value = $records->first()->{$column};

    livewire(ListUsers::class)
        ->loadTable()
        ->searchTable($value)
        ->assertCanSeeTableRecords($records->where($column, $value))
        ->assertCanNotSeeTableRecords($records->where($column, '!=', $value));
})->with(['name']);

it('can create a user', function () {
    $user = User::factory()->make();

    livewire(CreateUser::class)
        ->set('data.name', $user->name)
        ->set('data.email', $user->email)
        ->set('data.password', 'Password123!')
        ->set('data.jenis_kelamin', 'L')
        ->set('data.role', 'teacher')
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(User::class, [
        'name' => $user->name,
        'email' => $user->email,
    ]);
});

it('can update a user', function () {
    $user = User::factory()->create(['jenis_kelamin' => 'L']);
    $user->assignRole('teacher');
    $newUserData = User::factory()->make();

    livewire(EditUser::class, [
        'record' => $user->id,
    ])
        ->set('data.name', $newUserData->name)
        ->set('data.email', $newUserData->email)
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(User::class, [
        'id' => $user->id,
        'name' => $newUserData->name,
        'email' => $newUserData->email,
    ]);
});

it('can delete a user', function () {
    $user = User::factory()->create();

    livewire(EditUser::class, [
        'record' => $user->id,
    ])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseMissing($user);
});

it('can bulk delete users', function () {
    $users = User::factory()->count(5)->create();

    livewire(ListUsers::class)
        ->loadTable()
        ->assertCanSeeTableRecords($users)
        ->selectTableRecords($users)
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertNotified()
        ->assertCanNotSeeTableRecords($users);

    $users->each(fn (User $user) => assertDatabaseMissing($user));
});

it('validates unique email on create', function () {
    $existingUser = User::factory()->create();

    livewire(CreateUser::class)
        ->set('data.name', 'Test Name')
        ->set('data.email', $existingUser->email)
        ->set('data.password', 'Password123!')
        ->set('data.jenis_kelamin', 'L')
        ->set('data.role', 'teacher')
        ->call('create')
        ->assertHasFormErrors(['email']);
});

it('validates required fields on create', function () {
    livewire(CreateUser::class)
        ->set('data.name', '')
        ->set('data.email', '')
        ->set('data.password', '')
        ->set('data.jenis_kelamin', '')
        ->set('data.role', '')
        ->call('create')
        ->assertHasFormErrors(['name', 'email', 'password', 'jenis_kelamin', 'role']);
});

it('validates email format on create', function () {
    livewire(CreateUser::class)
        ->set('data.name', 'Test Name')
        ->set('data.email', 'invalid-email')
        ->set('data.password', 'Password123!')
        ->set('data.jenis_kelamin', 'L')
        ->set('data.role', 'teacher')
        ->call('create')
        ->assertHasFormErrors(['email']);
});

it('validates max length on create', function () {
    livewire(CreateUser::class)
        ->set('data.name', Str::random(256))
        ->set('data.email', Str::random(256).'@test.com')
        ->set('data.password', 'Password123!')
        ->set('data.jenis_kelamin', 'L')
        ->set('data.role', 'teacher')
        ->call('create')
        ->assertHasFormErrors(['name', 'email']);
});
