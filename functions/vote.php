<?php

function vote() {
    // Periksa apakah pengguna sudah login
    if (!checkLoginStatus()) {
        http_response_code(401);
        echo json_encode(array("message" => "User not logged in"));
        return;
    }

    // Dapatkan ID pengguna dari sesi
    $userId = $_SESSION['id'];
    $currentYear = date('Y');

    // Periksa apakah pengguna sudah memberikan suara untuk tahun ini
    if (hasVotedThisYear($userId, $currentYear)) {
        http_response_code(400);
        echo json_encode(array("message" => "User has already voted this year"));
        return;
    }

    // Ambil ID kandidat yang dipilih dari input
    $candidateId = isset($_POST['candidate_id']) ? $_POST['candidate_id'] : null;

    // Periksa apakah kandidat yang dipilih valid
    if ($candidateId === null) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid candidate selection"));
        return;
    }

    // Tambahkan entri suara ke database
    if (addVoteEntry($userId, date('Y-m-d H:i:s'), $candidateId)) {
        http_response_code(200);
        echo json_encode(array("message" => "Vote submitted successfully"));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to add vote entry"));
    }
}

// Fungsi untuk memeriksa apakah pengguna sudah memberikan suara pada tahun ini
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

// Fungsi untuk menambahkan entri suara ke database
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
    
    $conn->close();
}