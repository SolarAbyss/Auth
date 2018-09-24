<?php
namespace SolarAbyss\Auth\Traits;

use SolarAbyss\Profile;

trait HasSolarAuth {

    public function profile () {
        return $this->hasOne(Profile::class);
    }

}

