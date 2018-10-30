<?php

namespace SolarAbyss\Auth;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use App\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
    protected $repsonse;

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


    private function registerProvider($user_provider, $username) {

        $user_attributes = $user_provider->attributes;

        $profile = [
            'name' => $user_attributes->name,
            'username' => $username
        ];
        
        $user = User::where('provider_id', '=', $user_provider->id)->first();
        if ($user === null) {
            DB::transaction(function () use ($user_attributes, $user_provider, $profile, $username) {
                $user = new User([ 
                    'email' => $user_attributes->email,
                    'provider_id' => $user_provider->id,
                ]);
                $profile = new Profile($profile);
                $profile->save();
                $user->profile()->associate($profile->id);
                $user->save();
                $this->user = $user;
            });
        } else {
            $user->email = $email;
            $user->profile->fill($profile);
            $user->push();
            $this->user = $user;
        }

        return $this->user;
    }

    private function attemptRegisterProvider() {

        $body = $this->body[0];

        $profile = [
            'name' => $body->name,
            'username' => 'admin'
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

        if(Auth::loginUsingId($user->id)){
            $user->syncRoles($this->roles);
            $user->syncPermissions($this->permissions);
        }


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

    public function SyncPermissionsAndRoles() {

        $response = json_decode($this->client->request('POST', 'oauth/token', ['form_params' => [
            'client_id' => $this->client_id, 
            'client_secret' => $this->client_secret, 
            'grant_type' => 'client_credentials',
            'scope' => '*',
        ]])->getBody());

        $headers = [
            'Authorization' => 'Bearer ' . $response->access_token,        
            'Accept'        => 'application/json',
        ];

        $roles = json_decode($this->client->request('POST', 'api/permissions', [
            'headers' => $headers
        ])->getBody());

        app()['cache']->forget('spatie.permission.cache');

        foreach($roles as $role) {
            try{
                $local_role = Role::create(['name' => $role->name, 'guard_name' => $role->guard_name]);
            } catch(RoleAlreadyExists $exception) {
                $local_role = Role::where('name', '=', $role->name)->get()->first();
            }
            $role_perms = [];
            foreach($role->permissions as $permission) {
                try{
                    $local_permission = Permission::create(['name' => $permission->name]);
                } catch(PermissionAlreadyExists $exception) {
                   continue;
                }
                $role_perms[] = [
                    'name' => $permission->name
                ];
            }
            $local_role->syncPermissions($role_perms);
        }

        dd(Role::all(), Permission::all());

    }

    public function createUser($args) {
        $request = new Request();
        $request->replace($args);
        $request->setMethod('POST');
        return $this->register($request);
    }

    /**
     * Account Creation with SolarAbyss Identity Server
     */
    public function register(Request $request) {

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|unique:profiles|max:255',
            'email' => 'required|unique:users|max:255',
            'password' => 'required|confirmed|min:6',
        ]);

        $response = $this->identityRequest('api/auth/register', $request->all());
        $user = $this->registerProvider($response->user, $request->username);
        $auth_token = $response->token;
        return compact('user', 'auth_token');
    }

    private function identityRequest($endpoint, $params = [], $type = 'POST', $grant_type = 'client_credentials', $scope = '*'){
        
        $forum_params = array_merge(
            [
                'client_id' => $this->client_id, 
                'client_secret' => $this->client_secret, 
                'grant_type' => $grant_type,
                'scope' => $scope,
            ],
            $params
        );

        $headers = [
            'Accept'  => 'application/json',
        ];

        return json_decode($this->client->request($type, $endpoint, ['headers' => $headers, 'form_params' => $forum_params])->getBody());
    }

    public function checkIfUsernameExists($username)
    {
        return $this->checkIfExists('username', $username, Profile::class);
    }

    public function checkIfEmailExists($email) {

        return json_decode($this->client->request('POST', 'api/users/exists/email', ['form_params' => [
            'email' => $email, 
        ]])->getBody());

    }
    
    public function checkIfExists($property, $value, $model = null){
        $model = $model ? app($model) : app(User::class);
        return ['exists' => $model->where($property, '=', $value)->exists() ?? false];
    }

}