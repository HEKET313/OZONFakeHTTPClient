<?php

namespace App\Converter;

use App\Entity\Address;

class AddressConverter implements ConverterInterface
{
    /**
     * @param object|Address $entity
     * @return array
     */
    public function toArray(object $entity): array
    {
        return [
            'id' => $entity->getId(),
            'city' => $entity->getCity(),
            'country' => $entity->getCountry(),
            'iso_code' => $entity->getIsoCode()
        ];
    }

    /**
     * @param array $data
     * @return object|Address
     */
    public function toEntity(array $data): object
    {
        $address = new Address();
        $address->setId($data['id']);
        $address->setCountry($data['country']);
        $address->setCity($data['city']);
        $address->setIsoCode($data['iso_code']);
        return $address;
    }
}
