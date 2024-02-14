<?php

function updateAccount() {
    $conn = connectDB();
    
    $conn->close();
}

function listPreviousVote() {
    $conn = connectDB();
    
    // Periksa apakah pengguna sudah login
    if (!checkLoginStatus()) {
        http_response_code(401);
        echo json_encode(array("message" => "User not logged in"));
        return;
    }

    $conn = connectDB();

    // Dapatkan ID pengguna dari sesi
    $userId = $_SESSION['id'];

    // Lakukan query untuk mendapatkan daftar vote berdasarkan ID pengguna dengan join candidates
    $stmt = $conn->prepare("SELECT v.id, v.year, c.name AS candidate_name 
                            FROM votes v 
                            INNER JOIN candidates c ON v.candidate_id = c.id 
                            WHERE v.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Siapkan array untuk menyimpan hasil
    $votes = array();

    // Loop melalui hasil query dan tambahkan ke array votes
    while ($row = $result->fetch_assoc()) {
        $votes[] = $row;
    }

    // Kembalikan daftar vote dalam bentuk JSON
    echo json_encode($votes);

    // Tutup koneksi database dan statement
    $stmt->close();
    $conn->close();
}