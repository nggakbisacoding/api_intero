<!DOCTYPE html>
<html>
<head>
    <title>Dokumentasi API Reservasi Dokter</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-4">API Reservasi Dokter</h1>

        <div class="accordion mt-4" id="apiAccordion">
            
            <div class="card">
                <div class="card-header" id="authHeading">
                    <h2 class="mb-0">
                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#authCollapse" aria-expanded="true" aria-controls="authCollapse">
                            Autentikasi
                        </button>
                    </h2>
                </div>
                <div id="authCollapse" class="collapse show" aria-labelledby="authHeading" data-parent="#apiAccordion">
                    <div class="card-body">
<h3>POST /api/?endpoint=appointments</h3>
    <p>Autentikasi pengguna (pasien atau dokter) menggunakan rekam medis.</p>

    * **Request Body (JSON):**
        ```json
        {
            "medical_record_id": "MR12345",
            "date_of_birth": "1990-05-15"
        }
        ```
<br>
    * **Response (Success):**
        ```json
        {
            "medic_record": "your_medic_record"
        }
        ```
<br>
    * **Response (Error):**
        ```json
        {
            "error": "Invalid credentials"
        }
        ```

    <h3>POST /api/?endpoint=auth&action=register</h3>
    <p>Registrasi pasien baru.</p>

    * **Request Body (JSON):**
        ```json
        {
            "name": "Nama Pasien",,
            "date_of_birth": "1988-02-20",
            "gender": "Male",
            "citizenship_number": "1234567890123456",
            "call_number": "081234567890", 
            "blood_type": "A+"
        }
        ```
<br>
    * **Response (Success):**
        ```json
        {
            "token" : "JWT",
            "medical_record_id": "MR98765"
        }
        ```
<br>
    * **Response (Error):**
        ```json
        {
            "error": "Name and Data already in database" 
        }
        ```
                        </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="patientsHeading">
                    <h2 class="mb-0">
                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#patientsCollapse" aria-expanded="false" aria-controls="patientsCollapse">
                            Pasien
                        </button>
                    </h2>
                </div>
                <div id="patientsCollapse" class="collapse" aria-labelledby="patientsHeading" data-parent="#apiAccordion">
                    <div class="card-body">
 <h3>GET /api/?endpoint=patients</h3>
    <p>Mendapatkan profil pasien yang terautentikasi.</p>
    * **Authorization:** Memerlukan JWT token yang valid.

    <h3>PUT /api/?endpoint=patients</h3>
    <p>Memperbarui profil pasien yang terautentikasi.</p>
    * **Authorization:** Memerlukan JWT token yang valid.
                        </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="schedulesHeading">
                    <h2 class="mb-0">
                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#schedulesCollapse" aria-expanded="false" aria-controls="schedulesCollapse">
                            Jadwal Dokter
                        </button>
                    </h2>
                </div>
                <div id="schedulesCollapse" class="collapse" aria-labelledby="schedulesHeading" data-parent="#apiAccordion">
                    <div class="card-body">
 <h3>GET/PUT/DELETE /api/?endpoint=doctors</h3>
    <p>Mendapatkan jadwal dokter yang terautentikasi.</p>
    * **Authorization:** Memerlukan JWT token yang valid (khusus untuk dokter).

    <h3>GET /api/?endpoint=allschedules</h3>
    <p>Mendapatkan semua jadwal dokter yang tersedia.</p>

    <h3>GET /api/?endpoint=doctors&id={doctorId}</h3>
    <p>Mendapatkan profil dan jadwal dokter tertentu.</p>
                        </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="appointmentsHeading">
                    <h2 class="mb-0">
                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#appointmentsCollapse" aria-expanded="false" aria-controls="appointmentsCollapse">
                            Janji Temu
                        </button>
                    </h2>
                </div>
                <div id="appointmentsCollapse" class="collapse" aria-labelledby="appointmentsHeading" data-parent="#apiAccordion">
                    <div class="card-body">
    <h3>GET /api/?endpoint=appointments</h3>
    <p>Mendapatkan daftar janji temu pasien yang terautentikasi atau semua janji temu untuk admin.</p>
    * **Authorization:** Memerlukan JWT token yang valid.

    <h3>POST /api/?endpoint=appointments</h3>
    <p>Membuat janji temu baru.</p>
    * **Authorization:** Memerlukan JWT token yang valid.<br>
    * **Request Body (JSON):**
        ```json
        {
            "medical_record_id": "MR12345",
            "date_of_birth": "1990-05-15",
            "doctor_id": 2,
            "schedule_id": 1
        }
        ```

    <h3>GET /api/?endpoint=appointment_history</h3>
    <p>Melihat history janji temu.</p>
    * **Request Body (JSON):**
        ```json
        {
            "medical_record_id": "MR12345",
            "patient_name": "John Doe",
            "doctor_name": "Dr. Emily Davis",
            "reservation_date": "2024-06-03",
            "reservation_time": "03:30:00",
            "reservation_status": "completed"        }
        ```

    <h3>GET /api/?endpoint=allappointments</h3>
    <p>Melihat janji temu user lain.</p>
    * **Request Body (JSON):**
        ```json
        {
            "medical_record_number": "MR54321",
            "patient_name": "Jane Smith",
            "doctor_name": "Dr. Michael Brown",
            "queue_status": "pending",
            "reservation_time": "2024-06-04 04:00:00"       }
        ```


    <h3>PUT /api/?endpoint=appointments</h3>
    <p>Mengubah jadwal janji temu.</p>
    * **Authorization:** Memerlukan JWT token yang valid.

    <h3>DELETE /api/?endpoint=appointments</h3>
    <p>Membatalkan janji temu.</p>
    * **Authorization:** Memerlukan JWT token yang valid.
                        </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="historyHeading">
                    <h2 class="mb-0">
                        <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#historyCollapse" aria-expanded="false" aria-controls="historyCollapse">
                            Riwayat Janji Temu
                        </button>
                    </h2>
                </div>
                <div id="historyCollapse" class="collapse" aria-labelledby="historyHeading" data-parent="#apiAccordion">
                    <div class="card-body">
    <h3>GET /api/?endpoint=appointment_history</h3>
    <p>Mendapatkan riwayat janji temu pasien atau dokter yang terautentikasi.</p>
    * **Authorization:** Memerlukan JWT token yang valid.
                        </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
