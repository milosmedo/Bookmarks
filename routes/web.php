<?php

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
    return view('home');
});

//Route::post('/register', 'RegisterController@create');
//Auth::routes();
//
//Route::get('/home', 'HomeController@index')->name('home');
////Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
////Route::post('login', 'Auth\LoginController@login');
////Route::post('logout', 'Auth\LoginController@logout')->name('logout');
//Auth::routes();
//
//Route::get('/home', 'HomeController@index')->name('home');
