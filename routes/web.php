<?php

use App\Http\Middleware\SetLocale;
use App\Livewire\AccountStatements;
use App\Livewire\Auth\Login;
use App\Livewire\Collections;
use App\Livewire\Customers;
use App\Livewire\Dashboard;
use App\Livewire\Inventory;
use App\Livewire\Invoices;
use App\Livewire\POS;
use App\Livewire\Reports;
use App\Livewire\Treasury;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(in_array($locale, SetLocale::LOCALES, true), 404);
    session(['locale' => $locale]);
    return redirect()->back(fallback: route('dashboard')); // keeps the current page & its query string
})->name('lang');

Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', Dashboard\Index::class)->name('dashboard');
    Route::get('/customers', Customers\Index::class)->name('customers');
    Route::get('/inventory', Inventory\Index::class)->name('inventory');
    Route::get('/pos', POS\Workspace::class)->name('pos');
    Route::get('/invoices', Invoices\Index::class)->name('invoices');
    Route::get('/statements', AccountStatements\Index::class)->name('statements');
    Route::get('/collections', Collections\Index::class)->name('collections');
    Route::get('/treasury', Treasury\Index::class)->name('treasury');
    Route::get('/reports', Reports\Index::class)->name('reports');
});
