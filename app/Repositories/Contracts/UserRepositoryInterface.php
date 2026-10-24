<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Prettus\Repository\Contracts\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createUser(array $attributes): User;
}
