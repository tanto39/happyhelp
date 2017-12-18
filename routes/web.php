<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

/**
 * Group routes for admin panel
 */
Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => 'auth'], function() {
    Route::get('/', 'DashboardController@dashboard')->name('admin.index');

    // Resource
    Route::resource('/category', 'CategoryController', ['as'=>'admin']);
    Route::resource('/item', 'ItemController', ['as'=>'admin']);
    Route::resource('/property', 'PropertyController', ['as'=>'admin']);
    Route::resource('/propgroup', 'PropGroupController', ['as'=>'admin']);
    Route::resource('/review', 'ReviewController', ['as'=>'admin']);
    Route::resource('/user', 'UserController', ['as'=>'admin']);
    Route::resource('/order', 'OrderController', ['as'=>'admin']);
    Route::resource('/menu', 'MenuController', ['as'=>'admin']);
    Route::resource('/menuitem', 'MenuItemController', ['as'=>'admin']);

    // Sorting and filters
    Route::post('/category/filter','CategoryController@filter')->name('admin.category.filter');
    Route::post('/item/filter','ItemController@filter')->name('admin.item.filter');
    Route::post('/propgroup/filter','PropGroupController@filter')->name('admin.propgroup.filter');
    Route::post('/property/filter','PropertyController@filter')->name('admin.property.filter');
    Route::post('/review/filter','ReviewController@filter')->name('admin.review.filter');
    Route::post('/user/filter','UserController@filter')->name('admin.user.filter');
    Route::post('/order/filter','OrderController@filter')->name('admin.order.filter');
    Route::post('/menu/filter','MenuController@filter')->name('admin.menu.filter');
    Route::post('/menuitem/filter','MenuItemController@filter')->name('admin.menuitem.filter');
});

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
