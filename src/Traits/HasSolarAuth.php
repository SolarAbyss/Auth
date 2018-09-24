<?php
namespace SolarAbyss\Auth\Traits;

use SolarAbyss\Auth\Profile;

trait HasSolarAuth {

    public function profile () {
        return $this->belongsTo(Profile::class);
    }

}

