<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContentController;
use Illuminate\Support\Facades\Route;

Route::get('/bantuan/faq', [ContentController::class, 'index'])->defaults('kind', 'help');
Route::get('/cari', [ContentController::class, 'search']);
$contentRoutes = ['/artikel' => 'article', '/belajar/ebook' => 'ebook', '/belajar/podcast' => 'podcast', '/belajar/video-pendek' => 'short', '/komunitas/diskusi' => 'discussion', '/komunitas/showcase' => 'showcase', '/komunitas/galeri' => 'gallery', '/komunitas/mentorship' => 'mentorship', '/program/event' => 'event', '/program/bootcamp' => 'bootcamp', '/program/kompetisi' => 'competition', '/program/karier' => 'job', '/program/partner' => 'partner', '/bantuan' => 'help'];
foreach ($contentRoutes as $path => $kind) {
    Route::get($path, [ContentController::class, 'index'])->defaults('kind', $kind);
    Route::get($path.'/{item:slug}', [ContentController::class, 'show']);
}
Route::get('/dokumentasi', [ContentController::class, 'documentation']);
Route::get('/kebijakan/{item:slug}', [ContentController::class, 'show']);
foreach (['tentang', 'syarat-ketentuan', 'kebijakan-privasi', 'kebijakan-refund', 'aturan-komunitas', 'aksesibilitas'] as $slug) {
    Route::get('/'.$slug, [ContentController::class, 'staticPage'])->defaults('slug', $slug);
}
Route::get('/kontak', [ContentController::class, 'support']);
Route::get('/whistleblowing', [ContentController::class, 'support'])->defaults('kind', 'whistleblowing');
Route::post('/support/tickets', [ContentController::class, 'createTicket'])->middleware('throttle:5,1');
Route::post('/newsletter', [ContentController::class, 'newsletter'])->middleware('throttle:5,1');
Route::match(['get', 'post'], '/newsletter/unsubscribe/{token}', [ContentController::class, 'unsubscribe'])->middleware('throttle:10,1');
Route::match(['get', 'post'], '/newsletter/confirm/{token}', [ContentController::class, 'confirmNewsletter'])->middleware('throttle:10,1');
Route::get('/api/v1/content', [ContentController::class, 'api'])->middleware('throttle:60,1');
Route::get('/content/{item}/attachments/{attachment}', [ContentController::class, 'downloadAttachment'])->middleware('throttle:30,1');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/content/{item}/comments', [ContentController::class, 'comment'])->middleware('throttle:15,1');
    Route::delete('/comments/{comment}', [ContentController::class, 'deleteComment']);
    Route::post('/komunitas/posts', [ContentController::class, 'post'])->middleware('throttle:5,1');
    Route::post('/content/{item}/report', [ContentController::class, 'report'])->middleware('throttle:5,1');
    Route::post('/programs/{item}/register', [ContentController::class, 'registerProgram']);
    Route::post('/programs/registrations/{registration}', [ContentController::class, 'updateRegistration']);
    Route::post('/support/tickets/{ticket}/reply', [ContentController::class, 'replyTicket'])->middleware('throttle:10,1');
    Route::post('/newsletter/preference', [ContentController::class, 'newsletterPreference']);
    Route::get('/dashboard/notifikasi', [ContentController::class, 'notifications']);
    Route::get('/dashboard/bootcamp', [ContentController::class, 'myPrograms']);
    Route::post('/dashboard/notifikasi/read', [ContentController::class, 'readNotifications']);
    Route::get('/admin', [AdminController::class, 'index']);
    Route::get('/admin/content', [AdminController::class, 'content']);
    Route::post('/admin/content', [AdminController::class, 'saveContent']);
    Route::put('/admin/content/{item}', [AdminController::class, 'saveContent']);
    Route::delete('/admin/content/{item}', [AdminController::class, 'archiveContent']);
    Route::post('/admin/content/{item}/attachments', [AdminController::class, 'uploadAttachment']);
    Route::delete('/admin/attachments/{attachment}', [AdminController::class, 'removeAttachment']);
    Route::get('/admin/newsletter', [AdminController::class, 'newsletter']);
    Route::post('/admin/newsletter', [AdminController::class, 'saveCampaign']);
    Route::put('/admin/newsletter/{campaign}', [AdminController::class, 'saveCampaign']);
    Route::post('/admin/newsletter/{campaign}/queue', [AdminController::class, 'queueCampaign']);
    Route::post('/admin/newsletter/{campaign}/cancel', [AdminController::class, 'cancelCampaign']);
    Route::post('/admin/newsletter/{campaign}/retry', [AdminController::class, 'retryCampaign']);
    Route::get('/admin/moderation', [AdminController::class, 'moderation']);
    Route::post('/admin/moderation', [AdminController::class, 'moderate']);
    Route::post('/admin/registrations/{registration}/review', [AdminController::class, 'reviewRegistration']);
    Route::get('/admin/support', [AdminController::class, 'support']);
    Route::get('/admin/whistleblowing',[AdminController::class, 'support'])->defaults('whistle',true);
    Route::post('/admin/support/{ticket}/status',[AdminController::class, 'ticketStatus']);
});
