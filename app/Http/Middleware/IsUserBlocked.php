<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
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
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $dbUser = User::find($user->id);
            if ($dbUser) {
                if (!$dbUser->is_blocked) {
                    $request->user = $dbUser;
                    return $next($request);
                } else {
                    return $this->response->json([
                        'error' => 'Account is blocked!',
                        'blocked' => 1
                    ], 401);
                }
            } else {
                return $this->response->json([
                    'error' => 'Unauthorized token'
                ], 401);
            }
        } catch(Exception $e) {
            return $this->response->json([
                'error' => 'Unauthorized token'
            ], 401);
        }
    }
}
