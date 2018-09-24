<?php

namespace SolarAbyss\Auth;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use App\User;

class Solarize
{

    protected $client;
    protected $client_id;
    protected $client_secret;    
    protected $host;
    protected $data;
    protected $provider_id;
    protected $access_token;
    protected $refresh_token;
    protected $expires_in;
    protected $recent_status_code;
    protected $errors;
    protected $user;

    public function __construct($host = null) {
        $this->init($host);
    }

    public function init($host) {
        $this->host = config('identity.host');
        $this->client = new GuzzleClient(['base_uri' => $this->host]);
        $this->client_id = config('identity.client_id');
        $this->client_secret = config('identity.client_secret');
        $this->errors = [];
    }


    public function Auth(Request $request){

        $validatedData = $request->validate([
            'username' => 'required|email',
            'password' => 'required|min:3',
        ]);

        try {

            if(!$this->client_id){
                $this->errors[] = 'Identity client id is not set.';
            }
    
            if(!$this->client_secret){
                $this->errors[] = 'Identity client secret is not set.';
            }
    
            if(!$this->host){
                $this->errors[] = 'Identity host is not set.';
            }
    
            if(!$this->client){
                $this->errors[] = 'Guzzle Client not establsihed.';
            }

            $this->setResponse($this->client->request('POST', 'api/oauth/token', ['form_params' => [
                'client_id' => $this->client_id, 
                'client_secret' => $this->client_secret, 
                'grant_type' => 'password',
                'username' => $request->username,
                'password' => $request->password
            ]]));

            if($this->access_token && $this->recent_status_code < 500){
                $this->provider_id = $this->body[0]->id;
                $this->attemptRegisterProvider();
            }

        } catch(ServerException $e) {
            return response()->json($e->getMessage(), 500);
        }

        return response()->json($this->body, $this->recent_status_code);

    }

    private function setResponse($response) {
        $this->body = json_decode($response->getBody());
        $this->recent_status_code = json_decode($response->getStatusCode());
        $this->setBody($this->body[0]);

        return $this;
    }

    private function setBody($body) { 
        $this->provider_id = $body->id;
        $this->access_token = $body->access_token;
        $this->refresh_token = $body->refresh_token;
        $this->expires_in = $body->expires_in;
        $this->permissions = $body->permissions;
        $this->roles = $body->roles;

        return $this;
    }


    private function attemptRegisterProvider() {

        $body = $this->body[0];

        $profile = [
            'name' => $body->name,
        ];
        
        $user = User::where('provider_id', '=', $this->provider_id)->first();
        if ($user === null) {
            $user = new User([
                'email' => $body->email,
            ]);
            $user->provider_id = $this->provider_id;
            $profile = new Profile($profile);
            $profile->save();
            $user->profile()->associate($profile->id);
            $user->save();
        } else {
            $user->email = $body->email;
            $user->profile->fill($profile);
            $user->push();
        }

        $user->syncRoles($this->roles);
        $user->syncPermissions($this->permissions);

        $this->user = $user;
        
        return $this;

    }

    public function user(){
        return $this->user;
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
    }

}