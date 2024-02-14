<?php

function registerCandidate() {
    $conn = connectDB();
    
    // Periksa apakah pengguna sudah login
    if (!checkLoginStatus()) {
        http_response_code(401);
        echo json_encode(array("message" => "User not logged in"));
        return;
    }

    // Dapatkan ID pengguna dari sesi
    $userId = $_SESSION['id'];

    // Periksa apakah pengguna adalah kandidat untuk tahun ini
    if (isCandidateForYear($userId, date('Y'))) {
        http_response_code(400);
        echo json_encode(array("message" => "User is already a candidate for this year"));
        return;
    }

    // Ambil data kandidat dari input
    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $age = isset($_POST['age']) ? $_POST['age'] : null;
    $programStudy = isset($_POST['program_study']) ? $_POST['program_study'] : null;
    $shortDescription = isset($_POST['short_description']) ? $_POST['short_description'] : null;
    $vision = isset($_POST['vision']) ? $_POST['vision'] : null;
    $mission = isset($_POST['mission']) ? $_POST['mission'] : null;
    $reasonForChoice = isset($_POST['reason_for_choice']) ? $_POST['reason_for_choice'] : null;
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    // Handle file upload for photo
    $targetDirectory = "uploads/";
    $targetFile = $targetDirectory . basename($_FILES["photo"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["photo"]["tmp_name"]);
    if ($check === false) {
        http_response_code(400);
        echo json_encode(array("message" => "File is not an image."));
        return;
    }

    // Check if file already exists
    if (file_exists($targetFile)) {
        http_response_code(400);
        echo json_encode(array("message" => "Sorry, file already exists."));
        return;
    }

    // Check file size
    if ($_FILES["photo"]["size"] > 500000) {
        http_response_code(500);
        echo json_encode(array("message" => "Sorry, your file is too large."));
        return;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        http_response_code(500);
        echo json_encode(array("message" => "Sorry, only JPG, JPEG, & PNG files are allowed."));
        return;
    }

    // Move uploaded file to target directory
    if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile)) {
        http_response_code(500);
        echo json_encode(array("message" => "Sorry, there was an error uploading your file."));
        return;
    }

    // Masukkan data kandidat ke dalam database
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO candidates (user_id, name, age, program_study, short_description, vision, mission, photo, reason_for_choice, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssss", $userId, $name, $age, $programStudy, $shortDescription, $vision, $mission, $targetFile, $reasonForChoice, $created_at, $updated_at);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("message" => "Candidate registration successful"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Candidate registration failed"));
    }

    $stmt->close();
    $conn->close();
}

// Fungsi untuk memeriksa apakah pengguna sudah menjadi kandidat untuk tahun tertentu
function isCandidateForYear($userId, $year) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT created_at FROM candidates WHERE user_id = ? AND YEAR(created_at) = ?");
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $stmt->store_result();
    $rowCount = $stmt->num_rows;
    $stmt->close();
    $conn->close();

    return $rowCount > 0; // Return true if a candidate entry exists for the given year, false otherwise
}

function getCandidateProfile() {
    $conn = connectDB();
    
    $conn->close();
}