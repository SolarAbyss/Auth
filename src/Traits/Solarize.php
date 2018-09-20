<?php
namespace SolarAbyss\Auth\Traits;

use SolarAbyss\Auth\Facades\Solarize as SolarizeFacade;

trait Solarize {

    public function authorize($password, $grant_type = 'password') {
        SolarizeFacade::Auth($this->email, $password, $grant_type);
    }

}