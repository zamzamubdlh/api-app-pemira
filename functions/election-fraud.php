<?php
function reportFraud() {
    $conn = connectDB();

    session_start();

    $userData  = checkAuthorization($conn);

    $loggedInUserId = $userData['id'];

    $password = isset($_POST['password']) ? $_POST['password'] : null;
    $date = isset($_POST['date']) ? $_POST['date'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;

    if ($password === null || $date === null || $description === null) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required data"));
        return;
    }

    $isPasswordValid = password_verify($password, $userData['password']);

    if (!$isPasswordValid) {
        http_response_code(403);
        echo json_encode(array("message" => "Incorrect password"));
        return;
    }

    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO election_frauds (user_id, date, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $loggedInUserId, $date, $description, $created_at, $updated_at);

    try {
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Fraud report submitted successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to submit fraud report"));
        }
    } catch (mysqli_sql_exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error occurred: " . $e->getMessage()));
    }

    $stmt->close();
    $conn->close();
}

function checkAuthorization($conn) {
    if (!isset($_POST['token']) || empty($_POST['token'])) {
        http_response_code(401);
        echo json_encode(array("message" => "Missing session token"));
        exit;
    }

    $receivedToken = isset($_POST['token']) ? $_POST['token'] : null;

    if ($receivedToken == null) {
        http_response_code(401);
        echo json_encode(array("message" => "Invalid session token"));
        exit;
    }

    $loggedInUserId = $_POST['user_id'];
    $userData = getUserData($conn, $loggedInUserId);

    return $userData;
}