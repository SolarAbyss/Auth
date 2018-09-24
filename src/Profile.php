<?php

namespace SolarAbyss\Auth;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Profile extends Model
{

    protected $fillable = ['name', 'email'];

    public function user() {
        return $this->hasOne(User::class);
    }
}
