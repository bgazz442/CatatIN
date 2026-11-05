<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CatatanAttachmentController;
use App\Http\Controllers\CatatanController;
use App\Http\Controllers\DailyActivityController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\LearningCategoryController;
use App\Http\Controllers\MediaFavoriteController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - CatatIn
|--------------------------------------------------------------------------
*/

// --- Halaman Login ---
Route::get('/login', function (Request $request) {
    $redirectTo = $request->query('redirect_to');
    if (is_string($redirectTo)
        && str_starts_with($redirectTo, '/')
        && ! str_starts_with($redirectTo, '//')
        && ! str_contains($redirectTo, '\\')
        && ! preg_match('/[\x00-\x1F\x7F]/', $redirectTo)
        && ! parse_url($redirectTo, PHP_URL_SCHEME)
        && ! parse_url($redirectTo, PHP_URL_HOST)) {
        $request->session()->put('url.intended', url($redirectTo));
    }

    return view('auth.login', ['showLoginRequiredModal' => $request->boolean('login_required')]);
})->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings');

// --- Dashboard & Catatan ---
Route::get('/', [CatatanController::class, 'dashboard'])->name('dashboard');
Route::get('/dashboard', [CatatanController::class, 'dashboard']);

// --- CATATIN AI ---
Route::middleware('auth')->group(function () {
    Route::post('/pengaturan/tampilan', [SettingsController::class, 'updateTheme'])->name('settings.theme.update');

    Route::get('/ai', [AiController::class, 'index'])->name('ai.index');
    Route::post('/ai/chat', [AiController::class, 'chat'])->name('ai.chat');
    Route::get('/ai/conversations/{conversation}', [AiController::class, 'history'])->name('ai.conversation.show');
    Route::delete('/ai/conversations/{conversation}', [AiController::class, 'destroy'])->name('ai.conversation.destroy');
});

Route::middleware('auth')->group(function () {
    // --- Halaman Favorit ---
    Route::get('/catatan/favorit', [CatatanController::class, 'favorit'])->name('catatan.favorit');
    Route::get('/favorit', fn () => redirect()->route('catatan.favorit'));

    // --- Resource Catatan Belajar ---
    Route::get('/catatan', [CatatanController::class, 'index'])->name('catatan.index');
    Route::get('/catatan/create', [CatatanController::class, 'create'])->name('catatan.create');
    Route::post('/catatan', [CatatanController::class, 'store'])->name('catatan.store');
    Route::get('/catatan/{id}', [CatatanController::class, 'show'])->name('catatan.show');
    Route::get('/catatan/{id}/edit', [CatatanController::class, 'edit'])->name('catatan.edit');
    Route::put('/catatan/{id}', [CatatanController::class, 'update'])->name('catatan.update');
    Route::delete('/catatan/{id}', [CatatanController::class, 'destroy'])->name('catatan.destroy');

    // --- Toggle Status Favorit ---
    Route::patch('/catatan/{id}/favorit', [CatatanController::class, 'toggleFavorit'])->name('catatan.toggle-favorit');
});
Route::middleware('auth')->group(function () {
    Route::get('/tugas', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tugas/tambah', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tugas', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tugas/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tugas/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tugas/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status.update');
    Route::delete('/tugas/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::get('/aktivitas', [DailyActivityController::class, 'index'])->name('activities.index');
    Route::get('/aktivitas/tambah', [DailyActivityController::class, 'create'])->name('activities.create');
    Route::post('/aktivitas', [DailyActivityController::class, 'store'])->name('activities.store');
    Route::get('/aktivitas/{activity}', [DailyActivityController::class, 'show'])->name('activities.show');
    Route::get('/aktivitas/{activity}/edit', [DailyActivityController::class, 'edit'])->name('activities.edit');
    Route::put('/aktivitas/{activity}', [DailyActivityController::class, 'update'])->name('activities.update');
    Route::delete('/aktivitas/{activity}', [DailyActivityController::class, 'destroy'])->name('activities.destroy');

    Route::get('/target', [GoalController::class, 'index'])->name('goals.index');
    Route::get('/target/buat', [GoalController::class, 'create'])->name('goals.create');
    Route::post('/target', [GoalController::class, 'store'])->name('goals.store');
    Route::get('/target/{goal}/gambar', [GoalController::class, 'image'])->name('goals.image');
    Route::get('/target/{goal}', [GoalController::class, 'show'])->name('goals.show');
    Route::get('/target/{goal}/edit', [GoalController::class, 'edit'])->name('goals.edit');
    Route::put('/target/{goal}', [GoalController::class, 'update'])->name('goals.update');
    Route::post('/target/{goal}/progres', [GoalController::class, 'addProgress'])->name('goals.progress.store');
    Route::delete('/target/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');

    Route::get('/media-favorit', [MediaFavoriteController::class, 'index'])->name('media-favorites.index');
    Route::get('/media-favorit/tambah', [MediaFavoriteController::class, 'create'])->name('media-favorites.create');
    Route::post('/media-favorit', [MediaFavoriteController::class, 'store'])->name('media-favorites.store');
    Route::get('/media-favorit/{mediaFavorite}/edit', [MediaFavoriteController::class, 'edit'])->name('media-favorites.edit');
    Route::get('/media-favorit/{mediaFavorite}', [MediaFavoriteController::class, 'show'])->name('media-favorites.show');
    Route::put('/media-favorit/{mediaFavorite}', [MediaFavoriteController::class, 'update'])->name('media-favorites.update');
    Route::delete('/media-favorit/{mediaFavorite}', [MediaFavoriteController::class, 'destroy'])->name('media-favorites.destroy');
    Route::patch('/media-favorit/{mediaFavorite}/favorite', [MediaFavoriteController::class, 'toggleFavorite'])->name('media-favorites.toggle-favorite');
    Route::get('/kategori', [LearningCategoryController::class, 'index'])->name('categories.index');
    Route::get('/kategori/tambah', [LearningCategoryController::class, 'create'])->name('categories.create');
    Route::post('/kategori', [LearningCategoryController::class, 'store'])->name('categories.store');
    Route::get('/kategori/{category}/edit', [LearningCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/kategori/{category}', [LearningCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/kategori/{category}', [LearningCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/lampiran/{attachment}/preview', [CatatanAttachmentController::class, 'preview'])->name('attachments.preview');
    Route::get('/lampiran/{attachment}/download', [CatatanAttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/lampiran/{attachment}', [CatatanAttachmentController::class, 'destroy'])->name('attachments.destroy');
});
