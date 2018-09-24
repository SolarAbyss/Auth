<?php

namespace SolarAbyss\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Profile extends Model
{
    public function users() {
        return $this->belongsTo(User::class);
    }
}