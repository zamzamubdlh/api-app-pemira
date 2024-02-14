<?php

function registerCandidate() {
    $conn = connectDB();
    
    if (!checkLoginStatus()) {
        http_response_code(401);
        echo json_encode(array("message" => "User not logged in"));
        return;
    }

    $userId = $_SESSION['id'];

    if (isCandidateForYear($userId, date('Y'))) {
        http_response_code(400);
        echo json_encode(array("message" => "User is already a candidate for this year"));
        return;
    }

    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $age = isset($_POST['age']) ? $_POST['age'] : null;
    $programStudy = isset($_POST['program_study']) ? $_POST['program_study'] : null;
    $shortDescription = isset($_POST['short_description']) ? $_POST['short_description'] : null;
    $vision = isset($_POST['vision']) ? $_POST['vision'] : null;
    $mission = isset($_POST['mission']) ? $_POST['mission'] : null;
    $reasonForChoice = isset($_POST['reason_for_choice']) ? $_POST['reason_for_choice'] : null;
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    $targetDirectory = "uploads/";
    $targetFile = $targetDirectory . basename($_FILES["photo"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["photo"]["tmp_name"]);
    if ($check === false) {
        http_response_code(400);
        echo json_encode(array("message" => "File is not an image."));
        return;
    }

    if (file_exists($targetFile)) {
        http_response_code(400);
        echo json_encode(array("message" => "Sorry, file already exists."));
        return;
    }

    if ($_FILES["photo"]["size"] > 50000) {
        http_response_code(500);
        echo json_encode(array("message" => "Sorry, your file is too large."));
        return;
    }

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        http_response_code(500);
        echo json_encode(array("message" => "Sorry, only JPG, JPEG, & PNG files are allowed."));
        return;
    }

    if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile)) {
        http_response_code(500);
        echo json_encode(array("message" => "Sorry, there was an error uploading your file."));
        return;
    }

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

function isCandidateForYear($userId, $year) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT created_at FROM candidates WHERE user_id = ? AND YEAR(created_at) = ?");
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $stmt->store_result();
    $rowCount = $stmt->num_rows;
    $stmt->close();
    $conn->close();

    return $rowCount > 0;
}

function getCandidateProfile() {
    $conn = connectDB();
    
    $conn->close();
}

function getCandidateByCurrentYear() {
    $conn = connectDB();
    
    if (!checkLoginStatus()) {
        http_response_code(401);
        echo json_encode(array("message" => "User not logged in"));
        return;
    }

    $currentYear = date('Y');

    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id, name, age, program_study, short_description, vision, mission, photo, reason_for_choice, created_at, updated_at FROM candidates WHERE YEAR(created_at) = ?");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $result = $stmt->get_result();

    $candidates = array();

    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }

    $stmt->close();
    $conn->close();

    http_response_code(200);
    echo json_encode(array(
        "data" => $candidates
    ));
}