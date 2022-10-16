<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

const OAUTH_CLIENT_ID = '97777af1-4c32-42c9-afeb-c4e23d66af9c';
const OAUTH_CLIENT_SECRET = 'kiRbWEpI4lI3l2mvy4Pj7ScpkDWbcXqMURoyY53t';


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/auth', function (Request $request) {
    if ($request->user()) {
        dd($request->user());
    }

    $query = http_build_query([
        'client_id' => OAUTH_CLIENT_ID,
        'response_type' => 'code',
    ]);

    $url = 'http://localhost:8010/oauth/authorize';

    return redirect($url . '?' . $query);
})->name('auth');

Route::get('/oauth/callback', function (Request $request) {
    $base = 'http://auth-service_server_1';

    $tokenResponse = Http::post($base . '/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => OAUTH_CLIENT_ID,
        'client_secret' => OAUTH_CLIENT_SECRET,
        'code' => $request->get('code'),
    ]);

    if ($tokenResponse->status() !== 200) {
        return redirect('auth');
    }

    $data = $tokenResponse->json();

    $token = $data['access_token'];

    $userResponse = Http::withToken($token)->get($base . '/api/user');

    if ($userResponse->status() !== 200) {
        return redirect('auth');
    }

    $userData = $userResponse->json();

    $publicId = $userData['public_id'];

    $user = User::where('public_id', $publicId)->first();

    abort_unless($user, 401);

    Auth::loginUsingId($user->id);

    return redirect('home');
});

// TODO: Роуты для аналитики
