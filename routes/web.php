<?php

use App\Http\Controllers\DoorbellWebhookController;
use App\Http\Middleware\LogoutAdminOnKiosk;
use App\Livewire\Kiosk\AllOffScreen;
use App\Livewire\Kiosk\CountdownScreen;
use App\Livewire\Kiosk\EntryScreen;
use App\Livewire\Kiosk\GymScreen;
use App\Livewire\Kiosk\HubScreen;
use App\Livewire\Kiosk\MenuScreen;
use App\Livewire\Kiosk\ModuleScreen;
use App\Livewire\Kiosk\StartScreen;
use App\Livewire\Kiosk\WallPanelScreen;
use App\Livewire\NetworkMenuScreen;
use Illuminate\Support\Facades\Route;

Route::post('/api/webhook/doorbell', DoorbellWebhookController::class)
    ->middleware('throttle:30,1')
    ->name('webhook.doorbell');

Route::get('/menu', NetworkMenuScreen::class)->name('network.menu');

Route::middleware(LogoutAdminOnKiosk::class)->group(function (): void {
    Route::get('/', StartScreen::class)->name('kiosk.start');
    Route::get('/hub', HubScreen::class)->name('kiosk.hub');
    Route::get('/gym', GymScreen::class)->name('kiosk.gym');
    Route::get('/all-off', AllOffScreen::class)->name('kiosk.all-off');
    Route::get('/module/{module}', ModuleScreen::class)->name('kiosk.module');
    Route::get('/alarm-mode', MenuScreen::class)->name('kiosk.alarm-mode');
    Route::get('/entry', EntryScreen::class)->middleware('throttle:pin-page')->name('kiosk.entry');
    Route::get('/countdown', CountdownScreen::class)->name('kiosk.countdown');
    Route::get('/panel/{wallPanel:slug}', WallPanelScreen::class)->name('kiosk.wall-panel');
});
