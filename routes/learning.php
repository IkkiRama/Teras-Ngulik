<?php

use App\Http\Controllers\LearningController as L;
use Illuminate\Support\Facades\Route;

Route::get('/belajar/course', [L::class, 'courses']);
Route::get('/belajar/akademi', [L::class, 'academies']);
Route::get('/belajar/akademi/{slug}', [L::class, 'academy']);
Route::get('/belajar/learning-path', [L::class, 'paths']);
Route::get('/belajar/course/{course:slug}', [L::class, 'course']);
Route::get('/belajar/course/{course:slug}/lesson/{lesson:slug}', [L::class, 'lesson'])->scopeBindings();
Route::get('/lessons/{lesson}/scrim-audio/{revision}', [L::class, 'scrimAudio']);
Route::get('/belajar/kuis', [L::class, 'standaloneQuizzes']);
Route::get('/belajar/kuis/{slug}', [L::class, 'standaloneQuiz']);
Route::get('/sertifikat/{credential}', [L::class, 'certificate']);
Route::get('/labs/playground', [L::class, 'playground']);
Route::get('/labs/game', [L::class, 'games']);
Route::get('/labs/coding-lab', [L::class, 'labs']);
Route::get('/labs/challenge', [L::class, 'challenges']);
Route::get('/labs/code-review', [L::class, 'codeReviews']);
Route::get('/proyek/{slug}', [L::class, 'sharedProject']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/belajar/course/{course:slug}/enroll', [L::class, 'enroll']);
    Route::post('/lessons/{lesson}/progress', [L::class, 'progress']);
    Route::post('/lessons/{lesson}/complete', [L::class, 'complete']);
    Route::post('/lessons/{lesson}/quiz', [L::class, 'quiz']);
    Route::post('/lessons/{lesson}/quiz/start', [L::class, 'startQuiz']);
    Route::post('/lessons/{lesson}/assignment', [L::class, 'assignment']);
    Route::get('/assignments/{id}/file', [L::class, 'assignmentFile']);
    Route::get('/dashboard/proyek', [L::class, 'projects']);
    Route::post('/projects', [L::class, 'saveProject']);
    Route::put('/projects/{project}', [L::class, 'updateProject']);
    Route::delete('/projects/{project}', [L::class, 'deleteProject']);
    Route::post('/projects/{project}/review', [L::class, 'requestReview']);
    Route::get('/dashboard/catatan', [L::class, 'notebook']);
    Route::post('/notebook', [L::class, 'saveNote']);
    Route::delete('/notebook/{entry}', [L::class, 'deleteNote']);
    Route::get('/notebook/export', [L::class, 'exportNotes']);
    Route::get('/dashboard/review', [L::class, 'review']);
    Route::post('/review/cards', [L::class, 'saveReviewCard']);
    Route::post('/review/cards/{card}', [L::class, 'rateReviewCard']);
    Route::get('/dashboard/sertifikat', [L::class, 'certificates']);
    Route::get('/dashboard/learning-path', [L::class, 'paths']);
    Route::post('/belajar/learning-path/{slug}/enroll', [L::class, 'enrollPath']);
    Route::post('/belajar/learning-path/{slug}/start/{course}', [L::class, 'launchPathCourse']);
    Route::post('/admin/learning/path', [L::class, 'savePath']);
    Route::get('/diagnostic', [L::class, 'diagnostic']);
    Route::post('/diagnostic', [L::class, 'saveDiagnostic']);
    Route::post('/belajar/kuis/{slug}', [L::class, 'submitStandaloneQuiz']);
    Route::get('/admin/learning', [L::class, 'admin']);
    Route::post('/admin/learning/course', [L::class, 'saveCourse']);
    Route::put('/admin/learning/course/{course}', [L::class, 'updateCourse']);
    Route::post('/admin/learning/lesson', [L::class, 'saveLesson']);
    Route::put('/admin/learning/lesson/{lesson}', [L::class, 'updateLesson']);
    Route::post('/admin/learning/lesson/{lesson}/scrim', [L::class, 'publishScrim']);
    Route::post('/admin/learning/assignments/{id}',[L::class, 'gradeAssignment']);
    Route::post('/admin/learning/projects/{project}/review',[L::class, 'giveReview']);
});
