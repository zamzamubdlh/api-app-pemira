<?php

function updateAccount() {
    $conn = connectDB();
    
    $conn->close();
}

function listPreviousVote() {
    $conn = connectDB();

    if (!checkLoginStatus()) {
        http_response_code(401);
        echo json_encode(array("message" => "User not logged in"));
        return;
    }

    $userId = $_SESSION['id'];

    $stmt = $conn->prepare("SELECT v.id, v.year, c.name AS candidate_name 
                            FROM votes v 
                            INNER JOIN candidates c ON v.candidate_id = c.id 
                            WHERE v.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $votes = array();

    while ($row = $result->fetch_assoc()) {
        $votes[] = $row;
    }

    echo json_encode($votes);

    $stmt->close();
    $conn->close();
}