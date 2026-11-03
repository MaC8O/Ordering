<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable implements AuthenticatableContract
{
    protected $fillable = [
        'username',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * The admins table stores the hash in `password_hash`, not `password`.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
