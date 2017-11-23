<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User_Email extends Model
{
    protected $table = "user_emails";
    protected $fillable = [
    	"email","user_id", "primary", "verification_token","verified_at"
    ];

    public function hasVerified()
    {
        $this->verified_at = 2;
        $this->save();
    }
}
