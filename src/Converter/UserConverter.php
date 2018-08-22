<?php

namespace App\Converter;

use App\Entity\User;
use Psr\Http\Message\ResponseInterface;

class UserConverter implements ConverterInterface
{
    private $addressConverter;

    public function __construct(AddressConverter $addressConverter)
    {
        $this->addressConverter = $addressConverter;
    }

    /**
     * @param array $data
     * @return \object|User
     */
    public function toEntity(array $data): object
    {
        $user = new User();
        $user->setAddress($this->addressConverter->toEntity($data['address']));
        $user->setId($data['id']);
        $user->setLastName($data['last_name']);
        $user->setName($data['name']);
        return $user;
    }

    /**
     * @param \object|User $entity
     * @return array
     */
    public function toArray(object $entity): array
    {
        return [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'last_name' => $entity->getLastName(),
            'address' => $this->addressConverter->toArray($entity->getAddress())
        ];
    }
}
