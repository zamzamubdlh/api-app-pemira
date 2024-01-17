<?php
function reportFraud() {
    $conn = connectDB();

    if (!checkLoginStatus()) {
        echo json_encode(array("message" => "User not logged in"));
        return;
    }

    $loggedInUserId = $_SESSION['id'];

    $password = isset($_POST['password']) ? $_POST['password'] : null;
    $date = isset($_POST['date']) ? $_POST['date'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;

    if ($password === null || $date === null || $description === null) {
        echo json_encode(array("message" => "Missing required data"));
        return;
    }

    $userData = getUserHashedPassword($conn, $loggedInUserId);

    $isPasswordValid = password_verify($password, $userData['password']);

    if (!$isPasswordValid) {
        echo json_encode(array("message" => "Incorrect password"));
        return;
    }

    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO election_frauds (user_id, date, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $loggedInUserId, $date, $description, $created_at, $updated_at);

    try {
        if ($stmt->execute()) {
            echo json_encode(array("message" => "Fraud report submitted successfully"));
        } else {
            echo json_encode(array("message" => "Failed to submit fraud report"));
        }
    } catch (mysqli_sql_exception $e) {
        echo json_encode(array("message" => "Error occurred: " . $e->getMessage()));
    }

    $stmt->close();
    $conn->close();
}

function getUserHashedPassword($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
    
    return $userData;
}