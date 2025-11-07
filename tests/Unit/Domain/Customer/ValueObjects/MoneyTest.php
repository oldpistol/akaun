<?php

use Domain\Customer\Exceptions\InvalidMoneyException;
use Domain\Customer\ValueObjects\Money;

it('creates money from valid amount', function () {
    $money = Money::fromAmount('100.50', 'MYR');

    expect($money->amount())->toBe('100.50')
        ->and($money->currency())->toBe('MYR')
        ->and((string) $money)->toBe('MYR 100.50');
});

it('uses MYR as default currency', function () {
    $money = Money::fromAmount('50.00');

    expect($money->currency())->toBe('MYR');
});

it('accepts integer amount', function () {
    $money = Money::fromAmount(100);

    expect($money->amount())->toBe('100');
});

it('accepts float amount', function () {
    $money = Money::fromAmount(99.99);

    expect($money->amount())->toBe('99.99');
});

it('accepts zero amount', function () {
    $money = Money::fromAmount('0.00');

    expect($money->amount())->toBe('0.00');
});

it('throws exception for negative amount', function () {
    Money::fromAmount('-10.00');
})->throws(InvalidMoneyException::class, 'Money amount cannot be negative');

it('throws exception for non-numeric amount', function () {
    Money::fromAmount('abc');
})->throws(InvalidMoneyException::class, 'Invalid money amount: abc');

it('throws exception for invalid currency code', function () {
    Money::fromAmount('100', 'MYRMYR');
})->throws(InvalidMoneyException::class, 'Invalid currency code: MYRMYR');

it('throws exception for empty currency', function () {
    Money::fromAmount('100', '');
})->throws(InvalidMoneyException::class);

it('compares equality correctly', function () {
    $money1 = Money::fromAmount('100.00', 'MYR');
    $money2 = Money::fromAmount('100.00', 'MYR');
    $money3 = Money::fromAmount('50.00', 'MYR');
    $money4 = Money::fromAmount('100.00', 'USD');

    expect($money1->equals($money2))->toBeTrue()
        ->and($money1->equals($money3))->toBeFalse()
        ->and($money1->equals($money4))->toBeFalse();
});

it('compares greater than correctly', function () {
    $money1 = Money::fromAmount('100.00', 'MYR');
    $money2 = Money::fromAmount('50.00', 'MYR');

    expect($money1->greaterThan($money2))->toBeTrue()
        ->and($money2->greaterThan($money1))->toBeFalse();
});

it('compares less than correctly', function () {
    $money1 = Money::fromAmount('50.00', 'MYR');
    $money2 = Money::fromAmount('100.00', 'MYR');

    expect($money1->lessThan($money2))->toBeTrue()
        ->and($money2->lessThan($money1))->toBeFalse();
});

it('adds money correctly', function () {
    $money1 = Money::fromAmount('100.00', 'MYR');
    $money2 = Money::fromAmount('50.50', 'MYR');
    $result = $money1->add($money2);

    expect($result->amount())->toBe('150.50')
        ->and($result->currency())->toBe('MYR');
});

it('subtracts money correctly', function () {
    $money1 = Money::fromAmount('100.00', 'MYR');
    $money2 = Money::fromAmount('30.50', 'MYR');
    $result = $money1->subtract($money2);

    expect($result->amount())->toBe('69.50')
        ->and($result->currency())->toBe('MYR');
});

it('throws exception when adding different currencies', function () {
    $money1 = Money::fromAmount('100.00', 'MYR');
    $money2 = Money::fromAmount('50.00', 'USD');

    $money1->add($money2);
})->throws(InvalidMoneyException::class, 'Cannot perform operation on different currencies: MYR vs USD');

it('throws exception when subtracting different currencies', function () {
    $money1 = Money::fromAmount('100.00', 'MYR');
    $money2 = Money::fromAmount('50.00', 'USD');

    $money1->subtract($money2);
})->throws(InvalidMoneyException::class, 'Cannot perform operation on different currencies: MYR vs USD');

it('throws exception when comparing different currencies with greaterThan', function () {
    $money1 = Money::fromAmount('100.00', 'MYR');
    $money2 = Money::fromAmount('50.00', 'USD');

    $money1->greaterThan($money2);
})->throws(InvalidMoneyException::class);

it('throws exception when comparing different currencies with lessThan', function () {
    $money1 = Money::fromAmount('100.00', 'MYR');
    $money2 = Money::fromAmount('50.00', 'USD');

    $money1->lessThan($money2);
})->throws(InvalidMoneyException::class);
