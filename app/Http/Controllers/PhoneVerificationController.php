<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Validation\ValidationException;
use Twilio\Rest\Client;

class PhoneVerificationController extends Controller
{
    public function show(Request $request)
    {
        return $request->user()->hasVerifiedPhone()
            ? redirect()->route('home')
            : view('verifyphone');
    }

    // public function verify(Request $request)
    // {
    //     if ($request->user()->verification_code !== $request->code) {
    //         throw ValidationException::withMessages([
    //             'code' => ['The code your provided is wrong. Please try again or request another call.'],
    //         ]);
    //     }

    //     if ($request->user()->hasVerifiedPhone()) {
    //         return redirect()->route('home');
    //     }

    //     $request->user()->markPhoneAsVerified();

    //     return redirect()->route('home')->with('status', 'Your phone was successfully verified!');
    // }
    protected function verify(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'numeric'],
            // 'phone_number' => ['required', 'string'],
        ]);
        /* Get credentials from .env */
        $token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_sid = getenv("TWILIO_SID");
        $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $twilio = new Client($twilio_sid, $token);
        $verification = $twilio->verify->v2->services($twilio_verify_sid)
            ->verificationChecks
            ->create($data['code'], array('to' => $request->user()->phone));
        if ($verification->valid) {
            $user = tap(User::where('phone', $data['phone']))->update(['phone_verified_at' => now()]);
            /* Redirect User */

            return redirect()->route('home')->with(['message' => 'Phone number verified']);
        }
        return back()->with(['phone' => $request->user()->phone, 'error' => 'Invalid verification code entered!']);
    }

    public function buildTwiMl($code)
    {
        $code = $this->formatCode($code);
        $response = new VoiceResponse();
        $response->say("Hi, thanks for Joining. This is your verification code. {$code}. I repeat, {$code}.");
        echo $response;
    }

    public function formatCode($code)
    {
        $collection = collect(str_split($code));
        return $collection->reduce(
            function ($carry, $item) {
                return "{$carry}. {$item}";
            }
        );
    }
}
