<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;

/** Exercises the session guard and password hashing without querying web_users. */
class InMemoryUserProvider extends EloquentUserProvider
{
    public function __construct(private User $user)
    {
        parent::__construct(app('hash'), User::class);
    }

    public function retrieveById($identifier)
    {
        return (string) $identifier === (string) $this->user->getAuthIdentifier() ? $this->user : null;
    }

    public function retrieveByCredentials(array $credentials)
    {
        foreach ($credentials as $key => $value) {
            if ($key !== 'password' && $this->user->getAttribute($key) !== $value) {
                return null;
            }
        }

        return $this->user;
    }

    public function updateRememberToken(\Illuminate\Contracts\Auth\Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
    }
}
