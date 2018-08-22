<?php

namespace App\Converter;

interface ConverterInterface
{
    public function toArray(object $entity): array;
    public function toEntity(array $data): object;
}
