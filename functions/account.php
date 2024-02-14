<?php

function updateAccount() {
    $conn = connectDB();
    
    $conn->close();
}

function listPreviousVote() {
    $conn = connectDB();

    session_start();

    $userData  = checkAuthorization($conn);

    $loggedInUserId = $userData['id'];

    $stmt = $conn->prepare("SELECT v.id, v.year, c.name AS candidate_name 
                            FROM votes v 
                            INNER JOIN candidates c ON v.candidate_id = c.id 
                            WHERE v.user_id = ?");
    $stmt->bind_param("i", $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    $votes = array();

    while ($row = $result->fetch_assoc()) {
        $votes[] = $row;
    }

    echo json_encode(array(
        "data" => $votes
    ));

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