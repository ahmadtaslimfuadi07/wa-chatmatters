<?php

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

Route::group(['middleware' => ['throttle:api']], function (){

    Route::post('create-message','App\Http\Controllers\Api\BulkController@submitRequest')->name('api.create.message');
    Route::post('/set-device-status/{device_id}/{status}','App\Http\Controllers\Api\BulkController@setStatus');
    Route::post('/send-webhook/{device_id}','App\Http\Controllers\Api\BulkController@webHook');
    Route::post('/batch-webhook/{device_id}','App\Http\Controllers\Api\BulkController@batchWebHook');
    Route::post('contact/create','App\Http\Controllers\Api\ContactController@create')->name('api.create.contact');
    Route::post('contact/update','App\Http\Controllers\Api\ContactController@update')->name('api.update.contact');
    Route::post('contact/webhook/{device_id}','App\Http\Controllers\Api\ContactController@webhook')->name('api.contact.webhook');
    Route::post('chat/list','App\Http\Controllers\Api\ChatController@list')->name('api.chat.list');
    Route::post('/chat/update-status','App\Http\Controllers\Api\ChatController@updateStatus')->name('api.chat.updateStatus');

});
