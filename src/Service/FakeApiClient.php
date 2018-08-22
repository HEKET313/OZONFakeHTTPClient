<?php

namespace App\Service;

use App\Client\HttpClient;
use App\Converter\UserConverter;
use App\Entity\User;

class FakeApiClient
{
    private $client;
    private $converter;

    public function __construct(HttpClient $client, UserConverter $converter)
    {
        $this->converter = $converter;
        $this->client = $client;
    }

    public function getUser(int $id): User
    {
        $response = $this->client->get('/user/' . $id);
        $data = json_decode($response->getBody()->getContents(), true);
        if (json_last_error() || !is_array($data)) {
            throw new \Exception('Invalid response content');
        }
        return $this->converter->toEntity($data);
    }

    public function createUser(User $user)
    {
        $this->client->post('/user', json_encode($this->converter->toArray($user)));
    }

    public function updateUser(User $user)
    {
        $this->client->update('/user/' . $user->getId(), json_encode($this->converter->toArray($user)));
    }

    public function deleteUser(int $id)
    {
        $this->client->delete('/user/' . $id);
    }
}
