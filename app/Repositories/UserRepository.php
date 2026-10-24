<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use LogicException;
use Prettus\Repository\Eloquent\BaseRepository;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function model(): string
    {
        return User::class;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createUser(array $attributes): User
    {
        $user = $this->create($attributes);

        if (! $user instanceof User) {
            throw new LogicException('The user repository did not return a User model.');
        }

        return $user;
    }
}
