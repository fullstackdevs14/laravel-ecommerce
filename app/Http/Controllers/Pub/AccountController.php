<?php

namespace App\Http\Controllers\Pub;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V1\AccountController as ApiAccountController;
use App\Models\Post;
use Illuminate\Support\Collection;
class AccountController extends \App\Http\Controllers\Controller
{
    public function verifyEmail(Request $request, $token)
    {
        return view('emails.verify-success');
    }
}