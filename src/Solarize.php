<?php

namespace SolarAbyss\Auth;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;

class Solarize
{

    protected $client;
    protected $client_id;
    protected $client_secret;    
    protected $host;

    public function __construct($host = null) {

        $this->init($host);

    }

    public function init($host) {


        $this->host = config('identity.host');

        $this->client = new GuzzleClient(['base_uri' => $this->host]);

        $this->client_id = config('identity.client_id');
        $this->client_secret = config('identity.client_secret');
    }


    public function Auth($username, $password, $grant_type = 'password', $host = null){
        
        $response = $this->client->request('POST', 'api/oauth/token', ['form_params' => [
            'client_id' => $this->client_id, 
            'client_secret' => $this->client_secret, 
            'grant_type' => $grant_type,
            'username' => $username,
            'password' => $password
        ]]);

        return response()->json(json_decode($response->getBody()), json_decode($response->getStatusCode()));

    }

    public function isAuthenticated(Request $request){

        $headers = [
            'Authorization' => 'Bearer ' . $request->bearerToken(),        
            'Accept'        => 'application/json',
        ];

        $response = $this->client->request('POST', 'api/user/authorize', [
            'headers' => $headers
        ]);

        $response = json_decode($response->getBody());

        if(!$response->isAuthorized){
            return false;
        }

        return true;
        // return response()->json(json_decode($response->getBody()), json_decode($response->getStatusCode()));
    }

}