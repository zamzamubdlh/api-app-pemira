<?php

function updateAccount() {
    $conn = connectDB();

    session_start();

    $userData  = checkAuthorization($conn);

    $requestData = json_decode(file_get_contents('php://input'), true);

    if (empty($requestData)) {
        http_response_code(400);
        echo json_encode(array("message" => "Tidak ada data yang diterima."));
        return;
    }

    $loggedInUserId = $userData['id'];

    $query = "UPDATE users SET";
    $queryParams = array();

    $newName = $requestData['name'] ?? null;
    $newEmail = $requestData['email'] ?? null;
    $newPhone = $requestData['phone'] ?? null;
    $newAge = $requestData['age'] ?? null;

    if (empty($newName) && empty($newEmail) && empty($newPhone) && empty($newAge)) {
        http_response_code(400);
        echo json_encode(array("message" => "Setidaknya satu data yang diperlukan untuk diperbarui."));
        return;
    }

    $bindParamTypes = '';

    if (!empty($newName)) {
        $query .= " name = ?,";
        $bindParamTypes .= 's';
        $queryParams[] = $newName;
    }

    if (!empty($newEmail)) {
        $query .= " email = ?,";
        $bindParamTypes .= 's';
        $queryParams[] = $newEmail;
    }

    if (!empty($newPhone)) {
        $query .= " phone = ?,";
        $bindParamTypes .= 's';
        $queryParams[] = $newPhone;
    }

    if (!empty($newAge)) {
        $query .= " age = ?,";
        $bindParamTypes .= 'i';
        $queryParams[] = $newAge;
    }

    $query = rtrim($query, ",");

    $query .= " WHERE id = ?";
    $bindParamTypes .= 'i';
    $queryParams[] = $loggedInUserId;

    $stmt = $conn->prepare($query);

    $stmt->bind_param($bindParamTypes, ...$queryParams);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(array("message" => "Akun berhasil diperbarui."));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Gagal memperbarui akun. Silakan coba lagi."));
    }

    $stmt->close();
    $conn->close();
}

function listPreviousVote() {
    $conn = connectDB();

    session_start();

    $userData  = checkAuthorization($conn);

    $loggedInUserId = $userData['id'];

    $stmt = $conn->prepare("SELECT v.id, v.year, c.name AS candidate_name, v.user_id, c.name,
                            c.age, c.program_study, c.short_description, c.vision, c.mission,
                            c.photo, c.reason_for_choice, c.created_at, c.updated_at 
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