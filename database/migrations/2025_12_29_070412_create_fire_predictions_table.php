<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fire_predictions', function (Blueprint $table) {
            $table->id();
             // الموقع (خطوط الطول والعرض)
    $table->decimal('latitude', 10, 8);
    $table->decimal('longitude', 11, 8);
    
    // بيانات الطقس الحية من API
    $table->float('temperature');
    $table->float('humidity');
    $table->float('wind_speed');
    $table->float('rainfall')->default(0);
    
    // الوقت (طلب الدكتورة)
    $table->integer('hour'); // تخزين الساعة 
    $table->date('prediction_date'); // تخزين التاريخ
    
    // نتيجة التنبؤ (التي ستأتي من فريق الـ AI لاحقاً)
    $table->string('prediction_result')->nullable(); 
    $table->string('risk_level')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fire_predictions');
    }
};
