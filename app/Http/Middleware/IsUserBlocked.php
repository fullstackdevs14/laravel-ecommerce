<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Illuminate\Contracts\Routing\ResponseFactory;

class IsUserBlocked
{

    public function __construct(ResponseFactory $response)
    {
        $this->response = $response;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user->is_blocked) {
            $role_query = DB::table('role_user')
                ->leftJoin('roles', 'roles.id','=','role_user.role_id')
                ->where('role_user.user_id', $user->id)
                ->first();
            if($role_query)
            {
                $user->role = $role_query->name;
            }
            $request->user = $user;
            return $next($request);
        } else {
            return $this->response->json([
                'error' => 'Account is blocked!',
                'blocked' => 1
            ], 401);
        }
    }
}
