<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use JWTAuth;
use Validator;
use JWTFactory;
use App\User;
class AuthController extends Controller
{
    /**
     * API Login, on success return JWT Auth token
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {

        $myTTL = 525000; //minutes
        JWTAuth::factory()->setTTL($myTTL);

        $user = User::where('email', $request->email)->first();
        if(!$user->isActive){
            return response()->json([
                'status' => 'error',
                'message' => 'Váš účet je deaktivovaný.',
            ], 403);
        }

        $credentials = $request->only('email', 'password');
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];
        $validator = Validator::make($credentials, $rules);
        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->messages()
            ]);
        }
        try {
            // Attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Zadali ste nesprávne údaje.'
                ], 401);
            }
        } catch (JWTException $e) {
            // Something went wrong with JWT Auth.
            return response()->json([
                'status' => 'error',
                'message' => 'Pokus o prihlásenie zlyhal. Skúste to, prosím, znovu.'
            ], 500);
        }
        // All good so return the token
        return response()->json([
            'status' => 'success',
            'data'=> [
                'token' => $token
                // You can add more details here as per you requirment.
            ]
        ]);
    }
    /**
     * Logout
     * Invalidate the token. User have to relogin to get a new token.
     * @param Request $request 'header'
     */
    public function logout(Request $request)
    {
        // Get JWT Token from the request header key "Authorization"
        $token = $request->header('Authorization');
        // Invalidate the token
        try {
            JWTAuth::invalidate($token);
            return response()->json([
                'status' => 'success',
                'message'=> "Používateľ bol odhlásený."
            ]);
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json([
                'status' => 'error',
                'message' => 'Problém pri odhlásení používateľa. Skúste to, prosím, ešte raz.'
            ], 500);
        }
    }
}