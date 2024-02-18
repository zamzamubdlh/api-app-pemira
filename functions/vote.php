<?php

function vote() {
    $conn = connectDB();
    
    session_start();

    $userData  = checkAuthorization($conn);

    $userId = $userData['id'];
    $currentYear = date('Y');

    if (hasVotedThisYear($userId, $currentYear)) {
        http_response_code(400);
        echo json_encode(array("message" => "User has already voted this year"));
        return;
    }

    $candidateId = isset($_POST['candidate_id']) ? $_POST['candidate_id'] : null;

    if ($candidateId === null) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid candidate selection"));
        return;
    }

    if (addVoteEntry($userId, date('Y-m-d H:i:s'), $candidateId)) {
        http_response_code(200);
        echo json_encode(array("message" => "Vote submitted successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to add vote entry"));
    }
}

function hasVotedThisYear($userId, $year) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ? AND year = ?");
    $stmt->bind_param("ii", $userId, $year);
    $stmt->execute();
    $stmt->store_result();
    $rowCount = $stmt->num_rows;
    $stmt->close();
    $conn->close();

    return $rowCount > 0;
}

function addVoteEntry($userId, $year, $candidateId) {
    $conn = connectDB();
    
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO votes (year, candidate_id, user_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $year, $candidateId, $userId, $created_at, $updated_at);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

function getVotingResults() {
    $conn = connectDB();

    $query = "SELECT c.id as candidate_id, c.name as candidate_name, COUNT(v.id) as total_votes 
              FROM candidates c
              LEFT JOIN votes v ON c.id = v.candidate_id
              GROUP BY c.id";

    $result = $conn->query($query);

    if ($result) {
        $votingResults = array();

        while ($row = $result->fetch_assoc()) {
            $votingResults[] = array(
                "candidate_id" => $row['candidate_id'],
                "candidate_name" => $row['candidate_name'],
                "total_votes" => $row['total_votes']
            );
        }

        $conn->close();

        http_response_code(200);
        echo json_encode(array(
            "data" => $votingResults
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to fetch voting results"));
    }
}