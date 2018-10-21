<?php

namespace SolarAbyss\Auth;

use Illuminate\Database\Eloquent\Model;
use App\User;

use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class Profile extends Model implements HasMedia
{
    use HasMediaTrait;

    protected $fillable = ['name', 'email'];

    public function user() {
        return $this->hasOne(User::class);
    }
}

