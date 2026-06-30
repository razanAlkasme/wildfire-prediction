<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FirePredictionController extends Controller
{
    public function predict(Request $request)
    {
        // 1. التحقق من صحة البيانات القادمة
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);

        $lat = $request->input('lat');
        $lon = $request->input('lon');

        // 2. جلب بيانات الطقس من OpenWeatherMap
        $apiKey = env('OPENWEATHERMAP_API_KEY');
        $weatherUrl = "https://api.openweathermap.org/data/2.5/weather";

        $response = Http::get($weatherUrl, [
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $apiKey,
            'units' => 'metric'
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Failed to fetch weather data from API'], 500);
        }

        $weatherData = $response->json();
        $temp = $weatherData['main']['temp'];
        $humidity = $weatherData['main']['humidity'];
        $windSpeed = $weatherData['wind']['speed'];
        $rain = $weatherData['rain']['1h'] ?? 0;

        // 3. الحسبة الرياضية البديلة لنموذج الذكاء الاصطناعي (مباشرة داخل لارافل)
        try {
            // حساب مؤشر الجفاف التقديري بناءً على الحرارة والرطوبة
            $dry_index = $temp / ($humidity + 1);

            // حساب عامل تأثير الرياح مع الجفاف
            $wind_dry_factor = $windSpeed * $dry_index;

            // معادلة النموذج الرياضي لحساب مساحة الحريق المتوقعة (بناءً على الأوزان البيئية)
            // تزداد المساحة بزيادة الحرارة والرياح، وتقل بوجود المطر
            $prediction = ($temp * 2.5) + ($wind_dry_factor * 1.8) - ($rain * 8);
            
            // التأكد من أن المساحة المتوقعة لا تقل عن الصفر
            if ($prediction < 0) {
                $prediction = 0;
            }

            // تطبيق التحويل اللوغاريتمي لتقليل التشتت الرياضي (كما هو في كودك الأصلي)
            $transformed_output = log($prediction + 1);

            // حساب اللوغاريتم لأقصى مساحة مرجعية في منطقة الدراسة (مثلاً 5000 هكتار)
            $max_reference_area = log(5000 + 1); 

            // حساب النسبة المئوية اللوغاريتمية المعايرة للخطورة
            $percentage = ($transformed_output / $max_reference_area) * 100;

            // التأكد من بقاء النسبة بدقة بين 0 و 100
            if ($percentage > 100) $percentage = 100;
            if ($percentage < 0) $percentage = 0;

            // تقريب النسبة لرقمين عشريين
            $percentage = round($percentage, 2);

            // تحديد فئات الخطورة بناءً على النسبة اللوغاريتمية المعايرة
            if ($percentage >= 0 && $percentage <= 33.33) {
                $risk_level = 'Low';
                $color_code = '#28a745';
            } elseif ($percentage > 33.33 && $percentage <= 66.66) {
                $risk_level = 'Medium';
                $color_code = '#ffc107';
            } else {
                $risk_level = 'High';
                $color_code = '#dc3545';
            }
               
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error processing AI prediction logic'], 500);
        }

        // 4. حفظ البيانات في قاعدة البيانات
        $now = now();
        DB::table('fire_predictions')->insert([
            'latitude'        => $lat,
            'longitude'       => $lon,
            'temperature'     => $temp,
            'humidity'        => $humidity,
            'wind_speed'      => $windSpeed,
            'rainfall'        => $rain,
            'prediction'      => $percentage, // النتيجة المحسوبة مباشرة
            'risk_level'      => $risk_level, // حفظ فئة الخطورة (Low, Medium, High)
            'hour'            => $now->hour,
            'prediction_date' => $now->toDateString(),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // 5. الرد النهائي المستقر والمضمون على Postman والفرونت إند
        return response()->json([
            'status' => 'success',
            'data' => [
                'temperature'       => $temp,
                'humidity'          => $humidity,
                'wind_speed'        => $windSpeed,
                'rainfall'          => $rain,
                'prediction_result' => $percentage,
                'risk_level'        => $risk_level, 
                'badge_color'       => $color_code, 
                'time'              => $now->format('H:i'),
                'date'              => $now->toDateString()
            ]
        ]);
    }

    public function history()
    {
        $history = DB::table('fire_predictions')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $history
        ]);
    }
}