<?php
namespace SolarAbyss\Auth\Traits;

use SolarAbyss\Models\Profile;
use SolarAbyss\Models\Provider;

trait HasSolarAuth {

    public function profile () {
        return $this->hasOne(Profile::class);
    }

}

