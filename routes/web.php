<?php

use App\Http\Controllers\Admin\ManageUsersController;
use App\Http\Controllers\Admin\UpdateController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\AutoreplyController;
use App\Http\Controllers\BlastController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\GoogleSheetController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageMonitorController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\MessagesHistoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PluginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RestapiController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShowMessageController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

require_once 'custom-route.php';

Route::group(
    ['prefix' => LaravelLocalization::setLocale()],
    function () {

        Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {

            \UniSharp\LaravelFilemanager\Lfm::routes();
        });
        Route::get('/', function () {
            return Redirect::to('/login');
            // OR: return Redirect::intended('/bands'); // if using authentication
        });
        Route::middleware('auth')->group(function () {

            Route::get('/home', [HomeController::class, 'index'])->name('home');
            Route::get('/file-manager', [FileManagerController::class, 'index'])->name('file-manager');
            Route::post('/home/setSessionSelectedDevice', [HomeController::class, 'setSelectedDeviceSession'])->name('home.setSessionSelectedDevice');
            Route::post('/home/sethook', [HomeController::class, 'setHook'])->name('setHook');
            Route::post('/home/setavailable', [HomeController::class, 'setAvailable'])->name('setAvailable');
            Route::post('/home/setdelay', [HomeController::class, 'setDelay'])->name('setDelay');
            Route::post('/home/sethookread', [HomeController::class, 'setHookRead'])->name('setHookRead');
            Route::post('/home/sethookreject', [HomeController::class, 'setHookReject'])->name('setHookReject');
            Route::post('/home/sethooktyping', [HomeController::class, 'setHookTyping'])->name('setHookTyping');
            Route::post('/home/setIsActive', [HomeController::class, 'setIsActive'])->name('setIsActive');
            Route::post('/home', [HomeController::class, 'store'])->name('addDevice');
            Route::delete('/home', [HomeController::class, 'destroy'])->name('deleteDevice');

            Route::get('/scan/{number:body}', [ScanController::class, 'scan'])->name('scan');
            Route::get('/code/{number:body}', [ScanController::class, 'code'])->name('connect-via-code');

            Route::get('/plugins', [PluginController::class, 'index'])->name('plugins');
            Route::post('/plugins', [PluginController::class, 'store'])->name('plugins.store');
            Route::put('/plugins/{plugin}', [PluginController::class, 'update'])->name('plugins.update');
            Route::get('/plugins/{plugin}/edit-data', [PluginController::class, 'editData'])->name('plugins.editData');

            Route::get('/autoreply', [AutoreplyController::class, 'index'])->name('autoreply');
            Route::get('/autoreply-edit/{id}', [AutoreplyController::class, 'edit'])->name('autoreply.edit');
            Route::post('/autoreply-edit', [AutoreplyController::class, 'editUpdate'])->name('autoreply.edit.update');
            Route::post('/autoreply', [AutoreplyController::class, 'store'])->name('autoreply');
            Route::delete('/autoreply', [AutoreplyController::class, 'destroy'])->name('autoreply.delete');
            Route::post('auto-reply/update/{autoreply:id}', [AutoreplyController::class, 'update'])->name('autoreply.update');

            Route::get('/phonebook', [TagController::class, 'index'])->name('phonebook');
            Route::get('/get-phonebook', [TagController::class, 'getPhonebook'])->name('getPhonebook');
            Route::delete('/clear-phonebook', [TagController::class, 'clearPhonebook'])->name('clearPhonebook');
            Route::get('get-contact/{id}', [ContactController::class, 'getContactByTagId']);
            Route::post('/contact/store', [ContactController::class, 'store'])->name('contact.store');
            Route::delete('/contact/delete/{contact:id}', [ContactController::class, 'destroy'])->name('contact.delete');
            Route::delete('/contact/delete-all/{id}', [ContactController::class, 'DestroyAll'])->name('deleteAll');
            Route::post('/contact/import', [ContactController::class, 'import'])->name('import');
            Route::get('/contact/export/{id}', [ContactController::class, 'export'])->name('exportContact');

            Route::post('/tags', [TagController::class, 'store'])->name('tag.store');
            Route::delete('/tags', [TagController::class, 'destroy'])->name('tag.delete');
            Route::post('fetch-groups', [TagController::class, 'fetchGroups'])->name('fetch.groups');

            Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
            Route::get('/campaign/create', [CampaignController::class, 'create'])->name('campaign.create');
            Route::post('/campaign/store', [CampaignController::class, 'store'])->name('campaign.store');
            Route::get('/get-phonebook-list', [CampaignController::class, 'getPhonebookList'])->name('getPhonebookList');
            Route::post('/campaign/pause/{id}', [CampaignController::class, 'pause'])->name('campaign.pause');
            Route::post('/campaign/resume/{id}', [CampaignController::class, 'resume'])->name('campaign.resume');
            Route::delete('/campaign/delete/{id}', [CampaignController::class, 'destroy'])->name('campaign.delete');
            Route::get('/campaign/show/{id}', [CampaignController::class, 'show'])->name('campaign.show');
            Route::delete('/campaign/clear', [CampaignController::class, 'destroyAll'])->name('campaigns.delete.all');
            Route::get('/campaign/blast/{campaign:id}', [BlastController::class, 'index'])->name('campaign.blasts');

            Route::post('/preview-message', [ShowMessageController::class, 'index'])->name('previewMessage');
            Route::get('/form-message/{type}', [ShowMessageController::class, 'getFormByType'])->name('formMessage');
            Route::get('/form-message-edit/{type}', [ShowMessageController::class, 'showEdit'])->name('formMessageEdit');

            Route::get('/message/test', [MessagesController::class, 'index'])->name('messagetest');
            Route::post('/message/test', [MessagesController::class, 'store'])->name('messagetest');

            Route::get('/api-docs', RestapiController::class)->name('rest-api');

            Route::get('/user/settings', [UserController::class, 'settings'])->name('user.settings');
            Route::post('/user/change-password', [UserController::class, 'changePasswordPost'])->name('changePassword');
            Route::post('/user/setting/apikey', [UserController::class, 'generateNewApiKey'])->name('generateNewApiKey');
            Route::post('/settings/generate-ssl', [SettingController::class, 'generateSslCertificate'])->name('generateSsl');

            Route::get('/admin/settings', [SettingController::class, 'index'])->name('admin.settings');
            Route::post('/settings/server', [SettingController::class, 'setServer'])->name('setServer');

            Route::get('/admin/update', [UpdateController::class, 'checkUpdate'])->name('update');
            Route::post('/admin/update/install', [UpdateController::class, 'installUpdate'])->name('update.install');

            Route::get('/admin/manage-users', [ManageUsersController::class, 'index'])->name('admin.manage-users')->middleware('admin');
            Route::post('/admin/user/store', [ManageUsersController::class, 'store'])->name('user.store')->middleware('admin');
            Route::delete('/admin/user/delete/{id}', [ManageUsersController::class, 'delete'])->name('user.delete')->middleware('admin');
            Route::get('admin/user/edit', [ManageUsersController::class, 'edit'])->name('user.edit')->middleware('admin');
            Route::post('admin/user/update', [ManageUsersController::class, 'update'])->name('user.update')->middleware('admin');

            Route::get('/messages-history', [MessagesHistoryController::class, 'index'])->name('messages.history');
            Route::post('/resend-message', [MessagesHistoryController::class, 'resend'])->name('resend.message');
            Route::delete('/messages/clear', [MessagesHistoryController::class, 'clearAll'])->name('clear.messages');

            Route::post('/logout', LogoutController::class)->name('logout');

            // Google Sheets & Drive
            Route::get('/google/folder', [GoogleSheetController::class, 'readFolder'])->name('google.folder');
            Route::get('/google/folder/{folderId}', [GoogleSheetController::class, 'readFolder'])->name('google.folder.specific');
            Route::get('/google/sheets-in-folder', [GoogleSheetController::class, 'readSheetsInFolder'])->name('google.sheets.folder');
            Route::get('/google/sheets-in-folder/{folderId}', [GoogleSheetController::class, 'readSheetsInFolder'])->name('google.sheets.folder.specific');
            Route::get('/google/read-sheet/{spreadsheetId}', [GoogleSheetController::class, 'readSpecificSheetFromFolder'])->name('google.read.sheet');
            Route::get('/google/read-sheet/{spreadsheetId}/{sheetName}', [GoogleSheetController::class, 'readSpecificSheetFromFolder'])->name('google.read.sheet.name');
            Route::get('/google/original-sheet', [GoogleSheetController::class, 'readSheet'])->name('google.original.sheet');
            Route::get('/google/sheet-by-url', [GoogleSheetController::class, 'readSheetByUrl'])->name('google.sheet.url');
            Route::get('/google/sheet-by-url/{spreadsheetUrl}', [GoogleSheetController::class, 'readSheetByUrl'])->name('google.sheet.url.specific');
            Route::get('/google/sheet-by-url/{spreadsheetUrl}/{sheetName}', [GoogleSheetController::class, 'readSheetByUrl'])->name('google.sheet.url.sheet');
            Route::get('/google/settings-message', [GoogleSheetController::class, 'readSettingMessage'])->name('google.settings.message');
            Route::get('/google/settings-message/{sheetName}', [GoogleSheetController::class, 'readSettingMessage'])->name('google.settings.message.sheet');
            Route::get('/google/test-connection', [GoogleSheetController::class, 'testConnection'])->name('google.test');
            Route::get('/google/service-account-info', [GoogleSheetController::class, 'getServiceAccountInfo'])->name('google.service.account.info');
            Route::get('/google/read-excel/{fileId}', [GoogleSheetController::class, 'readExcelFile'])->name('google.read.excel');
            Route::post('/google/convert-excel/{fileId}', [GoogleSheetController::class, 'convertExcelToSheets'])->name('google.convert.excel');
            Route::get('/google/form-order', [GoogleSheetController::class, 'readFormOrder'])->name('google.form.order');
            Route::get('/google/form-order/{sheetName}', [GoogleSheetController::class, 'readFormOrder'])->name('google.form.order.sheet');

        
            Route::get('/messagemonitor', [MessageMonitorController::class, 'index'])->name('messagemonitor');
            Route::get('/monitor/messages', [MessageMonitorController::class, 'getMessages']);
            Route::post('/monitor/mark-read', [MessageMonitorController::class, 'markAsRead']);

            
            Route::get('/panel-campaigns', [CampaignController::class, 'indexPanel'])->name('panel.campaigns');
            Route::get('/panel-campaign/create', [CampaignController::class, 'createPanel'])->name('panel.campaign.create');
            Route::post('/panel-campaign/store', [CampaignController::class, 'storePanel'])->name('panel.campaign.store');
            Route::post('/panel-campaign/pause/{id}', [CampaignController::class, 'pausePanel'])->name('panel.campaign.pause');
            Route::post('/panel-campaign/resume/{id}', [CampaignController::class, 'resumePanel'])->name('panel.campaign.resume');
            Route::delete('/panel-campaign/delete/{id}', [CampaignController::class, 'destroyPanel'])->name('panel.campaign.delete');
            Route::get('/panel-campaign/show/{id}', [CampaignController::class, 'showPanel'])->name('panel.campaign.show');
            Route::delete('/panel-campaign/clear', [CampaignController::class, 'destroyAllPanels'])->name('panel.campaigns.delete.all');

            Route::get('/order/create', [OrderController::class, 'create'])->name('order.create');
            Route::get('/order/json-setting/{sheetName?}', [OrderController::class, 'getJsonSetting'])->name('order.json.setting');
            Route::get('/order/json-folder-cs/{folderId?}', [OrderController::class, 'getJsonFolderCS'])->name('order.json.folder.cs');
            Route::get('/order/json-read-excel/{fileId}', [OrderController::class, 'getJsonReadExcelFile'])->name('order.json.read.excel');
            Route::post('/order/store', [OrderController::class, 'store'])->name('order.store');
        });

        Route::middleware('guest')->group(function () {
            Route::get('/login', [LoginController::class, 'index'])->name('login');
            Route::get('/register', [RegisterController::class, 'index'])->name('register');
            Route::post('/register', [RegisterController::class, 'store'])->name('register');
            Route::post('/login', [LoginController::class, 'store'])->name('login')->middleware('throttle:5,1');
        });

        Route::get('/install', [SettingController::class, 'install'])->name('setting.install_app');
        Route::post('/install', [SettingController::class, 'install'])->name('settings.install_app');

        Route::post('/settings/check_database_connection', [SettingController::class, 'test_database_connection'])->name('connectDB');
        Route::post('/settings/activate_license', [SettingController::class, 'activate_license'])->name('activateLicense');
    }
);

Route::get('/general-order/create', [OrderController::class, 'createGeneral'])->name('general.order.create');
