<?php

use Domain\Customer\Exceptions\InvalidEmailException;
use Domain\Customer\ValueObjects\Email;

it('creates email from valid email string', function () {
    $email = Email::fromString('test@example.com');

    expect($email->value())->toBe('test@example.com')
        ->and((string) $email)->toBe('test@example.com');
});

it('throws exception for invalid email format', function () {
    Email::fromString('invalid-email');
})->throws(InvalidEmailException::class, 'Invalid email address: invalid-email');

it('throws exception for empty email', function () {
    Email::fromString('');
})->throws(InvalidEmailException::class);

it('throws exception for email without domain', function () {
    Email::fromString('test@');
})->throws(InvalidEmailException::class);

it('throws exception for email without at symbol', function () {
    Email::fromString('testexample.com');
})->throws(InvalidEmailException::class);

it('accepts valid email with subdomain', function () {
    $email = Email::fromString('user@mail.example.com');

    expect($email->value())->toBe('user@mail.example.com');
});

it('accepts valid email with plus sign', function () {
    $email = Email::fromString('user+tag@example.com');

    expect($email->value())->toBe('user+tag@example.com');
});

it('compares equality correctly', function () {
    $email1 = Email::fromString('test@example.com');
    $email2 = Email::fromString('test@example.com');
    $email3 = Email::fromString('other@example.com');

    expect($email1->equals($email2))->toBeTrue()
        ->and($email1->equals($email3))->toBeFalse();
});
