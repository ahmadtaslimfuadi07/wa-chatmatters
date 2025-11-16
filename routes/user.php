<?php 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User as USER;

Route::group(['prefix' => 'user', 'as' => 'user.', 'middleware' => ['auth','user','saas']], function (){
   
   //all dashboard routes
   Route::get('dashboard',                       [USER\DashboardController::class, 'index'])->name('dashboard.index');
   Route::get('dashboard-static-data',           [USER\DashboardController::class, 'dashboardData'])->name('dashboard.static');

   //webhook
   Route::resource('webhook',       			USER\WebhookController::class)->middleware('webhook');
   Route::post('/webhook-statics',              [USER\WebhookController::class,'webhookStatics']);
   //Route::post('webhook/store',                 [USER\WebhookController::class,'store'])->name('webhook.store');
   Route::get('webhook/status/{id}',                 [USER\WebhookController::class,'status'])->name('webhook.status');

   //device routes
   Route::resource('device',                    USER\DeviceController::class);
   Route::get('device/{id}/qr',                 [USER\DeviceController::class,'scanQr'])->name('device.scan');
   Route::post('create-session/{id}',           [USER\DeviceController::class,'getQr']);
   Route::post('check-session/{id}',            [USER\DeviceController::class,'checkSession']);
   Route::post('/logout-session/{id}',          [USER\DeviceController::class,'logoutSession']);
   Route::post('/device-statics',               [USER\DeviceController::class,'deviceStatics']);
   Route::post('device-import',                [USER\DeviceController::class,'importDevice'])->name('device.import');
  
   Route::get('/device/chats/{uuid}',           [USER\ChatController::class,'chats']);
   Route::post('/get-chats/{uuid}',             [USER\ChatController::class,'chatHistory']);
   Route::get('/get-chats/{uuid}',             [USER\ChatController::class,'chatHistory']);
   //Route::post('/get-chats/{uuid}',             [USER\ChatController::class,'userContacts']);
   Route::post('/get-chat-details/{uuid}',      [USER\ChatController::class,'chatDetails']);
   //Route::post('/get-chat-details/{uuid}',      [USER\ChatController::class,'userContactChats']);
   Route::post('/send-message/{uuid}',          [USER\ChatController::class,'sendMessage'])->name('chat.send-message');

   Route::get('/device/groups/{uuid}',          [USER\ChatController::class,'groups']);
   Route::post('/get-groups/{uuid}',            [USER\ChatController::class,'groupHistory']);
   Route::post('/send-group-message/{uuid}',    [USER\ChatController::class,'sendGroupMessage'])->name('group.send-message');
   Route::post('/send-group-bulk-message/{uuid}',    [USER\ChatController::class,'sendGroupBulkMessage'])->name('group.bulk.send-message');
   
   Route::post('/get-group-metadata',      [USER\ChatController::class,'getGroupMetaData'])->name('group.matadata');

   //downline routes
   Route::resource('downline', USER\DownlineController::class)->middleware('check-user-position');
   Route::get('/downline/device/{id}', [USER\DownlineController::class,'downlineDevices'])->name('downline.device')->middleware('check-user-position');
   Route::get('/downline/device/chat/{uuid}', [USER\DownlineController::class,'downlineChats'])->name('downline.device.chat')->middleware('check-user-position');
   Route::post('/downline/device/contacts/{uuid}', [USER\DownlineController::class,'downlineContacts']);
   Route::post('/downline/device/chats/{uuid}', [USER\DownlineController::class,'downlineContactChats'])->name('downline.device.contactChat');
   Route::post('/downline-data', [USER\DownlineController::class,'downlineData']);

   //app routes
   Route::resource('apps',                      USER\AppsController::class)->middleware('check-user-position');
   Route::get('/app/integration/{uuid}',        [USER\AppsController::class,'integration'])->name('app.integration')->middleware('check-user-position');
   Route::get('/app/messages-logs/{uuid}',      [USER\AppsController::class,'logs'])->name('app.logs')->middleware('check-user-position');

   //template routes
   Route::resource('template',                  USER\TemplateController::class)->middleware('check-user-position');
   Route::post('/template/store/{type}',        [USER\TemplateController::class,'store'])->name('template.store-now');

   //single send or custom text routes
   Route::get('/sent-text-message',                [USER\CustomTextController::class,'index'])->middleware('check-user-position');
   Route::post('/sent-whatsapp-custom-text/{type}',[USER\CustomTextController::class,'sentCustomText'])->name('sent.customtext');

   //bulk sender routes
   Route::post('/bulk-messages',                          [USER\BulkController::class,'store'])->name('bulk-message.store');
   Route::resource('/bulk-message',                       USER\BulkController::class)->middleware('check-user-position');
   Route::get('bulk-message/template-with-message/create',[USER\BulkController::class,'templateWithMessage'])->middleware('check-user-position');
   Route::get('/sent-bulk-with-template/{id}/{groupid}/{deviceid}', [USER\BulkController::class,'sendBulkToContacts'])->middleware('check-user-position');
   Route::post('/sent-message-with-template',             [USER\BulkController::class,'sendMessageToContact']);
   //schedule message routes
   Route::resource('schedule-message',                    USER\ScheduleController::class)->middleware('check-user-position');
   //schedule message routes
   Route::resource('contact',                             USER\ContactController::class);
   Route::post('contact',                                 [USER\ContactController::class,'sendtemplateBulk'])->name('contact.send-template-bulk');
   Route::post('contact/store',                           [USER\ContactController::class,'store'])->name('contact.store');
   Route::post('contact-import',                          [USER\ContactController::class,'import'])->name('contact.import');

   
   //chatbot route
   Route::resource('chatbot',                             USER\ChatbotController::class)->middleware('check-user-position');
   //log report route
   Route::resource('logs',                                USER\LogController::class);
   //profile settings
   Route::get('profile',                                 [USER\ProfileController::class,'settings']);
   Route::put('profile/update/{type}',                   [USER\ProfileController::class,'update'])->name('profile.update');
   Route::get('auth-key',                                [USER\ProfileController::class,'authKey']);
   //help and support routes
   Route::resource('support',                            USER\SupportController::class);
   //subscription / plan route
   Route::resource('subscription',                       USER\SubscriptionController::class);
   Route::post('make-subscribe/{gateway_id}/{plan_id}',  [USER\SubscriptionController::class,'subscribe'])->name('make-payment');
   Route::get('/subscription/plan/{status}',             [USER\SubscriptionController::class,'status']);
   Route::get('/subscriptions/log',                      [USER\SubscriptionController::class,'log']);
   Route::get('/subscription-history',                   [USER\SubscriptionController::class,'log']);
   Route::resource('notifications',                      USER\NotificationController::class);
   Route::resource('group',                             USER\GroupController::class)->middleware('check-user-position');
   //app routes
   Route::resource('broadcast',                               USER\BroadcastController::class)->middleware(['check-user-position', 'broadcast']);
   // report routes
   Route::get('report', [USER\ReportController::class, 'index'])->name('report.index');
   Route::post('report/export', [USER\ReportController::class, 'export'])->name('report.export');
   // downline device routes
   Route::get('downline-device', [USER\DownlineDeviceController::class, 'index'])->name('downline-device.index');
});




?>
