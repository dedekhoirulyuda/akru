<?php

namespace App\Support\ValueObjects;

use InvalidArgumentException;

/**
 * Money — immutable value object for monetary amounts.
 *
 * Blueprint §1.9: "Nilai uang menggunakan tipe decimal, bukan floating point."
 * All arithmetic uses bcmath to avoid floating point errors.
 */
final class Money
{
    private string $amount;
    private string $currency;
    private int $scale;

    public function __construct(string|int|float $amount, string $currency = 'IDR', int $scale = 2)
    {
        $this->amount = bcadd((string) $amount, '0', $scale);
        $this->currency = strtoupper($currency);
        $this->scale = $scale;
    }

    public static function zero(string $currency = 'IDR'): self
    {
        return new self('0', $currency);
    }

    public static function fromCents(int $cents, string $currency = 'IDR'): self
    {
        return new self(bcdiv((string) $cents, '100', 2), $currency);
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self(bcadd($this->amount, $other->amount, $this->scale), $this->currency, $this->scale);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self(bcsub($this->amount, $other->amount, $this->scale), $this->currency, $this->scale);
    }

    public function multiply(string|int|float $factor): self
    {
        return new self(bcmul($this->amount, (string) $factor, $this->scale), $this->currency, $this->scale);
    }

    public function divide(string|int|float $divisor): self
    {
        if (bccomp((string) $divisor, '0', $this->scale) === 0) {
            throw new InvalidArgumentException('Division by zero.');
        }
        return new self(bcdiv($this->amount, (string) $divisor, $this->scale), $this->currency, $this->scale);
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', $this->scale) === 0;
    }

    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', $this->scale) > 0;
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', $this->scale) < 0;
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency
            && bccomp($this->amount, $other->amount, $this->scale) === 0;
    }

    public function format(): string
    {
        return $this->currency . ' ' . number_format((float) $this->amount, $this->scale, ',', '.');
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
