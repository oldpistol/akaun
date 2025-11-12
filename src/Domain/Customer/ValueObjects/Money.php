<?php

namespace Domain\Customer\ValueObjects;

use Domain\Customer\Exceptions\InvalidMoneyException;

final readonly class Money
{
    public function __construct(
        private string $amount,
        private string $currency = 'MYR'
    ) {
        $this->validate();
    }

    public static function fromAmount(string|float|int $amount, string $currency = 'MYR'): self
    {
        return new self((string) $amount, $currency);
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function toFloat(): float
    {
        return (float) $this->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function greaterThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return bccomp($this->amount, $other->amount, 2) > 0;
    }

    public function lessThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return bccomp($this->amount, $other->amount, 2) < 0;
    }

    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            bcadd($this->amount, $other->amount, 2),
            $this->currency
        );
    }

    public function subtract(Money $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            bcsub($this->amount, $other->amount, 2),
            $this->currency
        );
    }

    public function __toString(): string
    {
        return "{$this->currency} {$this->amount}";
    }

    private function validate(): void
    {
        if (! is_numeric($this->amount)) {
            throw new InvalidMoneyException("Invalid money amount: {$this->amount}");
        }

        if (bccomp($this->amount, '0', 2) < 0) {
            throw new InvalidMoneyException('Money amount cannot be negative');
        }

        if (empty($this->currency) || strlen($this->currency) !== 3) {
            throw new InvalidMoneyException("Invalid currency code: {$this->currency}");
        }
    }

    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidMoneyException(
                "Cannot perform operation on different currencies: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
