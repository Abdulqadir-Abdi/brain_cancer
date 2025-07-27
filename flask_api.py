# -------------------------------------------------------
# Flask API: MRI Check + Cancer Prediction + Analysis
# -------------------------------------------------------

from flask import Flask, request, jsonify
import os
import numpy as np
import cv2
from tensorflow.keras.models import load_model
import mysql.connector

# --- Settings
image_size = 240
UPLOAD_FOLDER = 'uploads'
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# --- Load Models
mri_model_path = r'C:\Users\ADMIN\Downloads\Brain Cancer Detection\models\mri_detector_model.h5'
cancer_model_path = r'C:\Users\ADMIN\Downloads\Brain Cancer Detection\models\cnn-parameters-improvement-02-0.96.keras'

mri_detector_model = load_model(mri_model_path)
brain_cancer_model = load_model(cancer_model_path)

# --- Create Flask app
app = Flask(__name__)
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER

# --- MySQL Connection Function
def get_db_connection():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='brain_cancer_db'
    )

# --- Crop brain
def crop_brain_contour(image):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    blurred = cv2.GaussianBlur(gray, (5, 5), 0)
    thresh = cv2.threshold(blurred, 45, 255, cv2.THRESH_BINARY)[1]
    thresh = cv2.erode(thresh, None, iterations=2)
    thresh = cv2.dilate(thresh, None, iterations=2)
    cnts = cv2.findContours(thresh.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    cnts = cnts[0] if len(cnts) == 2 else cnts[1]
    if len(cnts) == 0:
        return image
    c = max(cnts, key=cv2.contourArea)
    x, y, w, h = cv2.boundingRect(c)
    cropped = image[y:y+h, x:x+w]
    return cropped if cropped.size else image

# --- Preprocessing
def preprocess_for_mri_check(image):
    img_resized = cv2.resize(image, (image_size, image_size))
    img_normalized = img_resized / 255.0
    return np.expand_dims(img_normalized, axis=0)

def preprocess_for_cancer_detection(image):
    cropped = crop_brain_contour(image)
    img_resized = cv2.resize(cropped, (image_size, image_size))
    img_normalized = img_resized / 255.0
    return np.expand_dims(img_normalized, axis=0)

# --- Routes
@app.route('/')
def home():
    return "✅ Brain MRI Scan and Cancer Detection API is running."

@app.route('/predict', methods=['POST'])
def predict():
    if 'file' not in request.files:
        return jsonify({'error': 'No file part in request'}), 400

    file = request.files['file']
    if file.filename == '':
        return jsonify({'error': 'No selected file'}), 400

    filepath = os.path.join(app.config['UPLOAD_FOLDER'], file.filename)
    file.save(filepath)

    try:
        image = cv2.imread(filepath)
        if image is None:
            raise ValueError("Error loading image. Check file format.")

        # Step 1: MRI Check
        mri_input = preprocess_for_mri_check(image)
        mri_prediction = mri_detector_model.predict(mri_input)[0][0]

        if mri_prediction <= 0.5:
            return jsonify({
                'result': 'Not a Brain MRI',
                'confidence': round(1 - mri_prediction, 3)
            })

        # Step 2: Cancer Prediction
        cancer_input = preprocess_for_cancer_detection(image)
        cancer_prediction = brain_cancer_model.predict(cancer_input)[0][0]
        threshold = 0.5
        diagnosis = 'Cancer Detected' if cancer_prediction > threshold else 'No Cancer Detected'
        confidence = cancer_prediction if diagnosis == 'Cancer Detected' else 1 - cancer_prediction

        return jsonify({
            'result': 'Brain MRI Scan',
            'diagnosis': diagnosis,
            'confidence': round(float(confidence), 3)
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500

# --- New Analysis Endpoint
@app.route('/analyze', methods=['GET'])
def analyze():
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT prediction FROM predictions")
        rows = cursor.fetchall()

        total = len(rows)
        cancer = sum(1 for r in rows if r['prediction'] == 'Cancer Detected')
        no_cancer = total - cancer

        percent_cancer = round((cancer / total) * 100, 2) if total > 0 else 0
        percent_no_cancer = round((no_cancer / total) * 100, 2) if total > 0 else 0

        return jsonify({
            "total_predictions": total,
            "cancer_detected": cancer,
            "no_cancer": no_cancer,
            "percentage_cancer": percent_cancer,
            "percentage_no_cancer": percent_no_cancer
        })

    except Exception as e:
        return jsonify({"error": str(e)}), 500

    finally:
        cursor.close()
        conn.close()

# --- Main
if __name__ == '__main__':
    app.run(debug=True)
