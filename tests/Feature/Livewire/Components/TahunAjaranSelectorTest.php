<?php

use App\Livewire\Components\TahunAjaranSelector;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(TahunAjaranSelector::class)
        ->assertStatus(200);
});
