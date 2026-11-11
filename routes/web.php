<?php

use App\Features\Admin\Controllers\AdminUserController;
use App\Features\Auth\Controllers\AuthController;
use App\Features\Post\Controllers\AdminPostController;
use App\Features\Post\Controllers\PostController;
use App\Features\Post\Controllers\PublicPostController;
use App\Features\Profile\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:1000-per-second'])->group(function () {
    // Public Routes (Tingkat 1 - dapat diakses oleh umum tanpa perlu login)
    Route::get('/', [PublicPostController::class, 'index'])->name('home');
    Route::get('/articles', [PublicPostController::class, 'index'])->name('articles.index');
    Route::get('/articles/{slug}', [PublicPostController::class, 'show'])->name('articles.show');

    // Guest Authentication Routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'createLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'storeLogin']);
        Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'storeRegister']);
    });

    // Authenticated Routes
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

        // Author Dashboard (Tingkat 2 - Anggota / Penulis)
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            // Excel & AI (placed before the wildcard {post} routes)
            Route::get('/posts/export', [PostController::class, 'exportExcel'])->name('posts.export');
            Route::post('/posts/import', [PostController::class, 'importExcel'])->name('posts.import');
            Route::post('/posts/ai-generate', [PostController::class, 'generateAi'])->name('posts.ai.generate');
            Route::post('/posts/ai-save', [PostController::class, 'saveAi'])->name('posts.ai.save');

            // Post Resource Routes
            Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
            Route::post('/posts', [PostController::class, 'store'])->name('posts.store');

            Route::middleware('owns.post')->group(function () {
                Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
                Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
                Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
                Route::patch('/posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
                Route::post('/posts/{post}/cover', [PostController::class, 'updateCover'])->name('posts.cover.update');
                Route::delete('/posts/{post}/cover', [PostController::class, 'deleteCover'])->name('posts.cover.delete');
            });
        });

        // User Profile Routes
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
        Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');

        // Admin Routes (Tingkat 3) protected by Role middleware
        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
            Route::get('/users', [AdminUserController::class, 'index'])->name('users');
            Route::get('/users/{user}/posts', [AdminUserController::class, 'posts'])->name('users.posts');
            Route::get('/users/{user}/posts/export', [AdminUserController::class, 'exportUserPosts'])->name('users.posts.export');
            Route::get('/posts', [AdminPostController::class, 'index'])->name('posts');
            Route::get('/posts/export', [AdminPostController::class, 'exportExcel'])->name('posts.export');
        });
    });
});
