<?php

use App\Helpers\ImportHelperFacade;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/order', function (Request $request) {
    $path = '/var/www/open-source/looper/customers.csv';

    $users = ImportHelperFacade::importUsers($path);


    if (!empty($users['error'])) {
        $err = true;
        $errMessage = $users['error']['message'];
    } elseif (!empty($users['users'])) {
        $users = ImportHelperFacade::addData($users['users'], 'user');
    }



    return $users;
    // return ImportHelperFacade::import($contents, 'user');
});
