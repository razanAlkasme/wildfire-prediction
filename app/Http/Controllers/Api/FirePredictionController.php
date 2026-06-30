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

        // 3.الاتصال بسيرفر البايثون (الجسر)ق bridge.py
        try {
            $aiResponse = Http::post('http://127.0.0.1:5000/predict_ai', [
                'temp'       => $temp,
                'humidity'   => $humidity,
                'wind_speed' => $windSpeed,
                'rain'       => $rain
            ]);

            if ($aiResponse->failed()) {
                 return response()->json(['message' => 'AI Server is not responding'], 500);
            }

            $prediction = $aiResponse->json()['prediction'];
            
            // --- حساب النسبة المئوية للاحترافية ---
            // نفترض أن أعلى قيمة خطر للمودل هي 100 بناءً على تدريبك (عدلي الرقم إذا لزم الأمر)
            $maxRiskValue = 100; 


            // معادلة النسبة المئوية

            $prediction = $aiResponse->json()['prediction'];

// 1. تطبيق التحويل اللوغاريتمي لتقليل التشتت الرياضي
$transformed_output = log($prediction + 1);

// 2. حساب اللوغاريتم لأقصى مساحة مرجعية في منطقة الدراسة (مثلاً 5000 هكتار)
$max_reference_area = log(5000 + 1); 

// 3. حساب النسبة المئوية اللوغاريتمية المعايرة
$percentage = ($transformed_output / $max_reference_area) * 100;

// التأكد من بقاء النسبة بين 0 و 100
if ($percentage > 100) $percentage = 100;
if ($percentage < 0) $percentage = 0;

// تقريب النسبة لرقمين عشريين
$percentage = round($percentage, 2);

// 4. تحديد فئات الخطورة بناءً على النسبة اللوغاريتمية الجديدة
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
            return response()->json(['message' => 'Could not connect to AI Bridge'], 500);
          }

        // 4. حفظ البيانات (مرة واحدة فقط وبشكل كامل)
        $now = now();
        DB::table('fire_predictions')->insert([
            'latitude'    => $lat,
            'longitude'   => $lon,
            'temperature' => $temp,
            'humidity'    => $humidity,
            'wind_speed'  => $windSpeed,
            'rainfall'    => $rain,
            'prediction'  => $percentage, // النتيجة القادمة من الذكاء الاصطناعي
            'risk_level'  => $risk_level, // حفظ فئة الخطورة (Low, Medium, High)
            'hour'        => $now->hour,
            'prediction_date' => $now->toDateString(),
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // 5. الرد النهائي على Postman والفرونت إند
        return response()->json([
            'status' => 'success',
            'data' => [
                'temperature' => $temp,
                'humidity'    => $humidity,
                'wind_speed'  => $windSpeed,
                'rainfall'    => $rain,
                'prediction_result' => $percentage,
                'risk_level'  => $risk_level, // يعيد فئة الخطورة
'badge_color' => $color_code, // يعيد كود اللون للاحترافية
                'time' => $now->format('H:i'),
                'date' => $now->toDateString()
            ]
        ]);
    }
        public function history()
        {
            $history=DB::table('fire_predictions')
            ->orderby('created_at','desc')
            ->get();

            return response()->json([
                'status'=>'success',
                'data'=>$history
            ]);
        }
    
}