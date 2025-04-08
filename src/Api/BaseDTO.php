<?php

namespace Mollsoft\LaravelMoneroModule\Api;

abstract class BaseDTO
{
    protected array $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;

        $this->init();
    }

    protected function init(): void
    {
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function getOrFail(string $name): mixed
    {
        if( !isset( $this->attributes[$name] ) ) {
            throw new \Exception('Attribute '.$name.' is not exists.');
        }

        return $this->attributes[$name];
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public static function make(array $attributes): static
    {
        return new static($attributes);
    }
}
