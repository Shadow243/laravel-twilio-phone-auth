<?php

namespace App;

use Twilio\Rest\Client;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'phone', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'phone_verified_at' => 'datetime',
    ];

    public function hasVerifiedPhone()
    {
        return !is_null($this->phone_verified_at);
    }

    public function markPhoneAsVerified()
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function callToVerify()
    {
        // $code = random_int(100000, 999999);

        // $this->forceFill([
        //     'verification_code' => $code
        // ])->save();



        /* Get credentials from .env */

        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $verification = $twilio->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create($this->phone, "sms");
        //force fill in to the database
        $this->forceFill([
            'verification_code' => $verification->sid
        ])->save();

        ////to make a call
        //$client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        // $client->calls->create(
        //     $this->phone,
        //     "+243993002040", // REPLACE WITH YOUR TWILIO NUMBER
        //     ["url" => config('app.url') . "/build-twiml/{$code}"]
        // );
    }
}
