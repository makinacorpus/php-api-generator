<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator;

final class TypeNamespace
{
    private readonly array $pieces;
    private readonly string $separator;

    public function __construct(string|array $name, string $separator)
    {
        $this->pieces = \is_array($name) ? \array_values($name) : \explode($separator, \trim($name, $separator));
        $this->separator = $separator;
    }

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

    public function shift(string|self $prefix): self
    {
        $matches = true;

        if (\is_string($prefix)) {
            $prefix = new self($prefix, $this->separator);
        }

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

    public function unshift(string|self $prefix): self
    {
        if (\is_string($prefix)) {
            $prefix = new self($prefix, $this->separator);
        }

        return $prefix->concat($this)->convert($this->separator);
    }

    public function convert(?string $separator): self
    {
        return new self($this->pieces, $separator);
    }

    public function isEmpty(): bool
    {
        return empty($this->pieces);
    }

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
