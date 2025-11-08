<?php

use Domain\State\Exceptions\InvalidStateNameException;
use Domain\State\ValueObjects\StateName;

it('creates state name from valid string', function () {
    $name = StateName::fromString('California');

    expect($name->value())->toBe('California')
        ->and($name->toString())->toBe('California');
});

it('accepts state names of various lengths up to 60 chars', function () {
    $shortName = StateName::fromString('Texas');
    $mediumName = StateName::fromString('New York');
    $longName = StateName::fromString('District of Columbia and Surrounding Metropolitan Areas');

    expect($shortName->value())->toBe('Texas')
        ->and($mediumName->value())->toBe('New York')
        ->and($longName->value())->toBe('District of Columbia and Surrounding Metropolitan Areas');
});

it('throws exception for empty state name', function () {
    StateName::fromString('');
})->throws(InvalidStateNameException::class, 'State name cannot be empty');

it('throws exception for state name too long', function () {
    $longName = str_repeat('A', 61);
    StateName::fromString($longName);
})->throws(InvalidStateNameException::class, 'exceeds maximum length of 60 characters');

it('compares equality correctly', function () {
    $name1 = StateName::fromString('California');
    $name2 = StateName::fromString('California');
    $name3 = StateName::fromString('Texas');

    expect($name1->equals($name2))->toBeTrue()
        ->and($name1->equals($name3))->toBeFalse();
});

it('accepts names with spaces and special characters', function () {
    $name1 = StateName::fromString('New York');
    $name2 = StateName::fromString('North Carolina');
    $name3 = StateName::fromString("Hawai'i");

    expect($name1->value())->toBe('New York')
        ->and($name2->value())->toBe('North Carolina')
        ->and($name3->value())->toBe("Hawai'i");
});
