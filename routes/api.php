<?php



Route::post('login', 'API\AuthController@login');
Route::post('forgotten-password', 'UserController@resetPassword');
Route::middleware('jwt.auth')->group(function(){

    Route::get('logout', 'API\AuthController@logout');
    Route::get('profile', 'UserController@showProfile');
    Route::patch('profile', 'UserController@editProfile');
    Route::patch('users/changepassword', 'UserController@changePassword');
    Route::post('categories', 'CategoryController@createCategory');
    Route::patch('categories/{id}', 'CategoryController@editCategory');
    Route::delete('categories/{id}', 'CategoryController@deleteCategory');
    Route::get('categories', 'CategoryController@showAllCategories');
    Route::get('categories/{id}', 'CategoryController@showCategory');
    Route::delete('users/{id}', 'UserController@deleteUser')->middleware('admin');
        Route::patch('profile/{id}', 'UserController@editProfileAdmin')->middleware('admin');
        Route::patch('users/{id}/activate', 'UserController@activateUser')->middleware('admin');
        Route::patch('users/{id}/deactivate', 'UserController@deactivateUser')->middleware('admin');
});