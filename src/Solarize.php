<?php

namespace SolarAbyss\Auth;

use GuzzleHttp\Client as GuzzleClient;

class Solarize
{
    public function Auth($username, $password, $grant_type = 'password'){
        
        $client = new GuzzleClient();
        
        $host = config('identity.host');
        $client_id = config('identity.client_id');
        $client_secret = config('identity.client_secret');

        $response = $client->request('POST', $host, ['form_params' => [
            'client_id' => $client_id, 
            'client_secret' => $client_secret, 
            'grant_type' => $grant_type,
            'username' => $username,
            'password' => $password
        ]]);

        return response()->json(json_decode($response->getBody()), json_decode($response->getStatusCode()));

    }


}