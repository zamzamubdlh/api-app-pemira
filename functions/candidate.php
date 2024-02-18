<?php

function registerCandidate() {
    $conn = connectDB();
    
    session_start();

    $userData  = checkAuthorization($conn);

    $userId = $userData['id'];

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

    $photoBase64 = isset($_POST['photo']) ? $_POST['photo'] : null;

    if ($photoBase64 === null) {
        http_response_code(400);
        echo json_encode(array("message" => "Photo data is missing"));
        return;
    }

    $photoBytes = base64_decode($photoBase64);

    $targetDirectory = "uploads/";
    $targetFile = $targetDirectory . uniqid() . '.jpg';

    if (!file_put_contents($targetFile, $photoBytes)) {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to save photo on server"));
        return;
    }

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

function getCandidateProfile($userId) {
    $conn = connectDB();

    $stmt = $conn->prepare("SELECT id, name, age, program_study, short_description, vision, mission, photo, reason_for_choice, created_at, updated_at FROM candidates WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $candidate = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    if ($candidate) {
        http_response_code(200);
        echo json_encode(array(
            "data" => $candidate
        ));
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Candidate not found"));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getCandidateProfile' && isset($_GET['userId'])) {
    $userId = $_GET['userId'];
    getCandidateProfile($userId);
}

function getCandidateByCurrentYear() {
    $conn = connectDB();
    
    session_start();

    $userData  = checkAuthorization($conn);

    $currentYear = date('Y');

    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id, user_id, name, age, program_study, short_description, vision, mission, photo, reason_for_choice, created_at, updated_at FROM candidates WHERE YEAR(created_at) = ?");
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