<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirePrediction extends Model
{
    // تحديد الحقول المسموح بحفظها وتعديلها داخل قاعدة البيانات
    protected $fillable = [
        'latitude', 
        'longitude', 
        'temperature', 
        'humidity', 
        'wind_speed', 
        'rainfall', 
        'prediction', 
        'risk_level', // الحقل الجديد المضاف
        'hour', 
        'prediction_date'
    ];
}