<?php

use App\Support\RegistrationAgeLimits;

it('allows any campista age when age limits are zero', function () {
    $limits = new RegistrationAgeLimits(minimumAge: 0, maximumAge: 0);

    expect($limits->allows('15/02/2018'))->toBeTrue()
        ->and($limits->allows('15/02/1980'))->toBeTrue();
});

it('blocks campistas below the configured minimum age', function () {
    $limits = new RegistrationAgeLimits(minimumAge: 16, maximumAge: 0);

    expect($limits->allows(now()->subYears(15)->format('d/m/Y')))->toBeFalse()
        ->and($limits->allows(now()->subYears(16)->format('d/m/Y')))->toBeTrue();
});

it('blocks campistas above the configured maximum age', function () {
    $limits = new RegistrationAgeLimits(minimumAge: 0, maximumAge: 30);

    expect($limits->allows(now()->subYears(31)->format('d/m/Y')))->toBeFalse()
        ->and($limits->allows(now()->subYears(30)->format('d/m/Y')))->toBeTrue();
});
