<?php

namespace App\Http\Controllers;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Utils\Response;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
        ]);
        $user = new User([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
        ]);
        $user->save();
        return response()->json([
            'message' => 'Successfully created user!'], 201);
    }

    public function login(Request $request)
    {
        $response = new Response();
        $request->validate([
            'email'       => 'required|string|email',
            'password'    => 'required|string'
        ]);
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            $response->setSuccess(false);
            $response->setMessage('Acceso no autorizado');
            return response()->json($response->toArray(), 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }
        $token->save();

        $access = [
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString(),
        ];

        $response->setSuccess(true);
        $response->setEntities($access);
        $response->setMessage('OK');
        return response()->json($response->toArray(), 200);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function user(Request $request)
    {
        $response = new Response();

        try
        {
            $user = User::select('id','name','email','sys_user_id')->find($request->user()->id);
            $roles = $user->roles;
            $user_roles  = [];
            foreach($roles as $key => $rol){
                $user_roles[$key]['id']  = $rol->id;
                $user_roles[$key]['name']  = $rol->name;
                $user_roles[$key]['permisos'] = $rol->permissions()->pluck('name');
            }
            $user->roles = $user_roles;

            $response->setSuccess(true);
            $response->setEntities($user);
            $response->setMessage('OK');
        }
        catch(\Exception $e)
        {
            $response->setSuccess(false);
            $response->setError($e->getMessage());
        }

        return response()->json($response->toArray());
    }
}