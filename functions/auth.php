<?php

ini_set('session.cookie_secure', true);

function register() {
    $conn = connectDB();

    if (checkLoginStatus()) {
        http_response_code(401);
        echo json_encode(array("message" => "User already logged in"));
        return;
    }

    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $email = isset($_POST['email']) ? $_POST['email'] : null;
    $phone = isset($_POST['phone']) ? $_POST['phone'] : null;
    $age = isset($_POST['age']) ? $_POST['age'] : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    if ($name === null || empty($name) ||
        $email === null || empty($email) ||
        $phone === null || empty($phone) ||
        $password === null || empty($password)) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required data"));
        return;
    }

    if (isDuplicateUser($conn, $email, $phone)) {
        http_response_code(403);
        echo json_encode(array("message" => "Email or phone number is already registered"));
        return;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, age, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $email, $phone, $age, $hashed_password, $created_at, $updated_at);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT id, name, email, phone, age, password, created_at, updated_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        http_response_code(201);
        echo json_encode(array(
            "message" => "Registration successful",
            "data" => $user
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Registration failed"));
    }

    $stmt->close();
    $conn->close();     
}

function isDuplicateUser($conn, $email, $phone) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $stmt->store_result();
    $duplicate = $stmt->num_rows > 0;
    $stmt->close();

    return $duplicate;
}

function login() {
    $conn = connectDB();
    
    if (checkLoginStatus()) {
        http_response_code(401);
        echo json_encode(array("message" => "User already logged in"));
        return;
    }

    $email = isset($_POST['email']) ? $_POST['email'] : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;

    if ($email === null || empty($email) ||
        $password === null || empty($password)) {
        http_response_code(400);
        echo json_encode(array("message" => "Missing required data"));
        return; 
    }

    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    $stmt = $conn->prepare("SELECT id, name , email, phone, age, password, created_at, updated_at FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $email, $phone, $age, $hashed_password, $createdAt, $updatedAt);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {

            $token = generateSessionToken($id);
            $userData = array(
                "id" => $id,
                "name" => $name,
                "email" => $email,
                "phone" => $phone,
                "age" => $age,
                "created_at" => $createdAt,
                "updated_at" => $updatedAt,
                "token" => $token
            );

            $_SESSION['id'] = $id;
            $_SESSION['token'] = $token;

            http_response_code(200);
            echo json_encode(array(
                "message" => "Login successful",
                "user" => $userData,
                "token" => $token,
                "id" => $userData['id']
            ));
        } else {
            http_response_code(403);
            echo json_encode(array("message" => "Incorrect password"));
        }
    } else {
        http_response_code(422);
        echo json_encode(array("message" => "User not found"));
    }

    $stmt->close();
    $conn->close();
}

function getUserData($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, name, email, phone, age, password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();

    return $userData;
}

function generateSessionToken($userId) {
    return md5(uniqid($userId, true));
}

function logout() {
    $conn = connectDB();
    
    if (!checkLoginStatus()) {
        http_response_code(401);
        echo json_encode(array("message" => "User not logged in"));
        return;
    }

    session_unset();
    session_destroy();

    http_response_code(200);
    echo json_encode(array("message" => "Logout successful"));

    $conn->close();
}