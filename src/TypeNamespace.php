<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator;

final class TypeNamespace
{
    private readonly array $pieces;
    private readonly string $separator;
    private readonly int $size;

    public function __construct(string|array $name, string $separator)
    {
        if (\is_string($name)) {
            $this->pieces = \explode($separator, \trim($name));
        } else {
            // User input may have been created with another language separator
            // that is not the output one, this fixes that.
            $pieces = [];
            foreach ($name as $segment) {
                foreach (\explode($separator, $segment) as $piece) {
                    if ($piece) {
                        $pieces[] = $piece;
                    }
                }
            }
            $this->pieces = $pieces;
        }
        $this->separator = $separator;
        $this->size = \count($this->pieces);
    }

    /**
     * Creates an empty instance.
     */
    public static function empty(string $separator = '/'): self
    {
        return new self([], $separator);
    }

    /**
     * Get namespace size.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Other starts with.
     */
    public function startsWith(string|self $other): bool
    {
        $other = \is_string($other) ? new self($other, $this->separator) : $other;

        if ($this->size < $other->size) {
            return false;
        }
        for ($i = 0; $i < $other->size; ++$i) {
            if ($this->pieces[$i] !== $other->pieces[$i]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get segment at.
     */
    public function getSegmentAt(int $index): string
    {
        if (empty($this->pieces) || $index < 0 || \count($this->pieces) < $index) {
            throw new \OutOfBoundsException();
        }
        return $this->pieces[$index];
    }

    /**
     * Get last segment at.
     */
    public function getLastSegment(): string
    {
        if (empty($this->pieces)) {
            throw new \OutOfBoundsException();
        }
        return $this->pieces[\count($this->pieces) - 1];
    }

    /**
     * Remove trailing elements.
     */
    public function pop(int $howMany = 1): self
    {
        if (\count($this->pieces) < $howMany) {
            throw new \OutOfBoundsException("Cant pop more elements than namespace size.");
        }
        return new self(\array_slice($this->pieces, 0, 0 - $howMany), $this->separator);
    }

    /**
     * Compute a relative namespace from this one which inherits from this
     * instance separator.
     */
    public function relative(string|self $other, ?string $here = '.', string $back = '..'): self
    {
        $other = \is_string($other) ? new self($other, $this->separator) : $other;

        if ($this->isEmpty()) {
            return $other->convert($this->separator);
        }

        $thisCount = \count($this->pieces);
        $otherCount = \count($other->pieces);
        $min = \min($thisCount, $otherCount);
        $equalsUntil = 0;

        for ($i = 0; $i < $min; ++$i) {
            if ($this->pieces[$i] === $other->pieces[$i]) {
                $equalsUntil++;
            } else {
                break;
            }
        }

        $ret = [];
        if ($thisCount <= $otherCount) {
            // Current path is shorter.
            if ($equalsUntil < $thisCount) {
                $ret = \array_fill(0, $thisCount - $equalsUntil, $back);
            } else if ($here) {
                $ret[] = $here;
            }
        } else {
            // Current path is longer, this means we always
            // start by the back sequence to go back in path.
            $ret = \array_fill(0, $thisCount - $equalsUntil, $back);
        }

        foreach (\array_slice($other->pieces, $equalsUntil) as $piece) {
            $ret[] = $piece;
        }

        return new self($ret, $this->separator);
    }

    /**
     * Concat one or more instances to this one.
     */
    public function concat(string|self ...$others): self
    {
        $new = $this->pieces;
        foreach ($others as $other) {
            if (\is_string($other)) {
                foreach (\explode($this->separator, $other) as $piece) {
                    $new[] = $piece;
                }
            } else {
                foreach ($other->pieces as $piece) {
                    $new[] = $piece;
                }
            }
        }
        return new self($new, $this->separator);
    }

    /**
     * Remove the given prefix if it matches from this instance.
     */
    public function shift(string|self $prefix): self
    {
        $prefix = \is_string($prefix) ? new self($prefix, $this->separator) : $prefix;

        $matches = true;

        $new = [];
        foreach ($this->pieces as $index => $piece) {
            if (isset($prefix->pieces[$index])) {
                if ($prefix->pieces[$index] !== $piece) {
                    $matches = false;
                    break;
                }
            } else {
                $new[] = $piece;
            }
        }

        if ($matches) {
            return new self($new, $this->separator);
        }

        return $this;
    }

    /**
     * Adds the given prefix to this instance.
     */
    public function unshift(string|self $prefix): self
    {
        $prefix = \is_string($prefix) ? new self($prefix, $this->separator) : $prefix;

        return $prefix->concat($this)->convert($this->separator);
    }

    /**
     * Convert to another separator.
     */
    public function convert(?string $separator): self
    {
        if ($separator === $this->separator) {
            return $this;
        }
        return new self($this->pieces, $separator);
    }

    /**
     * Is root namespace.
     */
    public function isEmpty(): bool
    {
        return empty($this->pieces);
    }

    /**
     * Check for equality.
     */
    public function equals(string|self $other): bool
    {
        if (\is_string($other)) {
            return $other === (string) $this;
        }
        return ((string) $other) === (string) $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return \implode($this->separator, $this->pieces);
    }
}
