# Brain Cancer Detection Web Platform

## Overview
This project is a web-based brain cancer detection system powered by machine learning. It analyzes MRI images uploaded by users to predict the presence of cancer, providing instant feedback using a pre-trained deep learning model. The platform is designed to assist doctors, researchers, and patients in early diagnosis and treatment.

## Features
- User authentication (login, signup, profile management)
- Upload MRI images for analysis
- Instant brain MRI and cancer prediction using deep learning
- Dashboard with real-time charts and statistics
- Gender-based and age-range insights
- Export results as PDF
- Admin and user roles

## Tech Stack
- **Frontend/Backend:** PHP, HTML, Bootstrap, JavaScript
- **Machine Learning API:** Python (Flask, TensorFlow/Keras, OpenCV, NumPy)
- **Database:** MySQL

## How It Works
1. **User uploads an MRI image** via the web interface.
2. The image is sent to a Flask API (`flask_api.py`).
3. The API first checks if the image is a valid brain MRI.
4. If valid, the API predicts the presence of cancer using a deep learning model.
5. Results are displayed instantly, and statistics are updated in the dashboard.

## Setup Instructions
### Prerequisites
- XAMPP/WAMP or any Apache + PHP + MySQL stack
- Python 3.7+
- pip (Python package manager)
- MySQL server

### 1. Clone the Repository
```
git clone <your-repo-url>
cd brain_cancar_project_with_ml
```

### 2. PHP & MySQL Setup
- Place the project folder in your web server directory (e.g., `htdocs` for XAMPP).
- Create a MySQL database named `brain_cancer_db`.
- Import your database schema and sample data if available.
- Update database credentials in PHP files if needed (default: user `root`, password empty).

### 3. Python Environment & Flask API
- Install dependencies:
```
pip install flask tensorflow keras opencv-python numpy mysql-connector-python
```
- Place your trained model files at the paths specified in `flask_api.py`:
  - `mri_detector_model.h5`
  - `cnn-parameters-improvement-02-0.96.keras`
- Run the Flask API:
```
python flask_api.py
```

### 4. Start the Web Application
- Start Apache and MySQL from XAMPP/WAMP.
- Access the app at `http://localhost/brain_cancar_project_with_ml/home.php`

## Usage
- **Sign up** for a new account or log in.
- **Upload an MRI image** on the prediction page.
- **View results** and statistics on the dashboard.
- **Admins** can manage users and view all reports.

## API Endpoints
- `POST /predict` — Upload an MRI image for prediction. Returns JSON with result, diagnosis, and confidence.
- `GET /analyze` — Returns statistics on all predictions in the database.

## Sample Data
Sample MRI images are available in the `uploads/` directory for testing.

## Notes
- Ensure your model files are present and paths are correct in `flask_api.py`.
- The system requires both the PHP web server and the Flask API to be running.
- For best results, use clear, high-quality MRI images. test


## License
[MIT License](LICENSE) (or specify your license) 