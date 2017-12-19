<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('now', function () {
    return date("Y-m-d H:i:s");
});

Route::auth();
Route::controller('access','AccessController');
Route::post('/quicklogin', 'AccessController@quickLogin');
Route::post('/access', 'AccessController@login');
Route::post('/updateUser','AccessController@update');
Route::get('/test', 'AccessController@test');

Route::controller('tutorial','TutorialController');
Route::post('/upchar', 'TutorialController@createChar');
Route::post('/passtu', 'TutorialController@passTu');
Route::post('/logout', 'AccessController@logout');
Route::post('/uservalue','AccessController@showStatus');

Route::controller('baggage','BaggageController');
Route::post('/baggage','BaggageController@baggage');
Route::post('/getItemInfo','BaggageController@getItemInfo');
Route::post('/sellItem','BaggageController@sellItem');
Route::post('/scrollMerage','BaggageController@scrollMerage');
Route::post('/equipmentUpgrade','BaggageController@equipmentUpgrade');

Route::controller('workshop','WorkshopController');
Route::post('/workshop','WorkshopController@workshop');
Route::post('/showEquipmentInfo','WorkshopController@showEquipmentInfo');
Route::post('/showSkillInfo','WorkshopController@showSkillInfo');
Route::post('/compareEquipment','WorkshopController@compareEquipment');
Route::post('/equipEquipment','WorkshopController@equipEquipment');

Route::controller('shop','ShopController');
Route::post('/shoplist','ShopController@shopCoin');
Route::post('/shop','ShopController@shop');
Route::post('/buyResource','ShopController@buyResouceBYCoin');
Route::post('/rareResourceList','ShopController@rareResourceList');
Route::post('/refresh','ShopController@refreshResource');
Route::post('/buyCoin','ShopController@buyCoin');
Route::post('/coinList','ShopController@getCoinList');
Route::post('/gemList','ShopController@getGemList');

Route::controller('luckdraw','LuckdrawController');
Route::post('/showluck', 'LuckdrawController@showLuck');
Route::post('/onedraw', 'LuckdrawController@oneDraw');
Route::post('/multidraw', 'LuckdrawController@multiDraw');


Route::controller('match','MatchController');
Route::post('/battlematch', 'MatchController@match');

Route::controller('load','LoadBattleController');
Route::post('/load', 'LoadBattleController@loadingGame');

Route::controller('battle','BattleController');
Route::post('/testbattle', 'BattleController@battle');

Route::controller('friend','FriendController');
Route::post('/addfriend', 'FriendController@addFriend');
Route::post('/removefriend', 'FriendController@removeFriend');
Route::post('/friendlist', 'FriendController@friend_list');
Route::post('/getFriendRequest', 'FriendController@get_friend_request');
Route::post('/delFriendRequest', 'FriendController@reject_request');
Route::post('/sendRequest', 'FriendController@send_friendrequest');
Route::post('/friendSendCoin', 'FriendController@sendCoin');
Route::post('/friendRecevieCoin', 'FriendController@recieveCoin');
Route::post('/coinlist', 'FriendController@recieveCoinList');
Route::post('/suggest_friend', 'FriendController@suggest_friend');
Route::post('/search_friend', 'FriendController@searchFriend');
Route::post('/like_friend','FriendController@like_friend');
Route::post('/friendDetails','FriendController@friend_details');
Route::post('/sendMessage','FriendController@sendMessage');
Route::post('/receiveMessage','FriendController@receiveMessage');

Route::controller('loginreward','LoginRewardController');
Route::post('/loginrewardslist', 'LoginRewardController@getLoginReward');
Route::post('/gettoday', 'LoginRewardController@getToday');





// Route::group(['middleware' => 'auth', 'namespace' => 'Admin', 'prefix' => 'admin'], function() {
  
// });
// Route::auth();post

Route::get('/', 'HomeController@index');
