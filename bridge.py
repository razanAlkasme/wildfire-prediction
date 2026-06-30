from flask import Flask, request, jsonify
import joblib
import pandas as pd
import os

app = Flask(__name__)

# Define the model path dynamically
base_path = os.path.dirname(__file__)
model_path = os.path.join(base_path, 'fire_model_regression.pkl')

# Initialize model variable
model = None

# Load the model once at startup
try:
    if os.path.exists(model_path):
        model = joblib.load(model_path)
        print(f"Model loaded successfully from: {model_path}")
    else:
        print(f"Error: Model file not found at {model_path}")
except Exception as e:
    print(f"Error loading model: {e}")

@app.route('/predict_ai', methods=['POST'])
def predict_ai():
    try:
        data = request.json
        
        # 1. استخراج القيم
        temp = float(data.get('temp', 0))
        humidity = float(data.get('humidity', 0))
        wind = float(data.get('wind_speed', 0))
        rain = float(data.get('rain', 0))
        month = 3  # شهر آذار
        
        # 2. الحسابات الإضافية
        dry_index = temp / (humidity + 1)
        wind_dry_factor = wind * dry_index
        
        # 3. بناء القاموس بالأسماء الصحيحة
        input_data = {
            'temperature': temp,
            'humidity': humidity,
            'wind': wind,
            'rain': rain,
            'month': month,
            'dry_index': dry_index,
            'wind_dry_factor': wind_dry_factor
        }

        # 4. الترتيب التلقائي (هذا هو الحل السحري)
        # سيقوم الكود بجلب الترتيب الذي يتوقعه المودل ويطبق بياناتنا عليه
        expected_features = model.feature_names_in_
        df = pd.DataFrame([input_data])[expected_features]

        # 5. التنبؤ
        prediction = model.predict(df)[0]
        
        return jsonify({'prediction': round(float(prediction), 2)})

    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    print("🚀 Bridge Server running on http://127.0.0.1:5000")
    app.run(host='127.0.0.1', port=5000, debug=True)