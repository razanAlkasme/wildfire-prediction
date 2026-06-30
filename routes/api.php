<?php

use Illuminate\Support\Facades\Route;
// لاحظي المسار الجديد للكنترولر الاحترافي
use App\Http\Controllers\Api\FirePredictionController;

// هذا هو الرابط الذي سيطلبه فريق الفرونت إند
Route::post('/predict', [FirePredictionController::class, 'predict']);


//رابط جديد لجلب سجل عمليات التنبؤ الأخيرة 
Route::get('/history',[FirePredictionController::class, 'history']);