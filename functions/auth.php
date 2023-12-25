<?php

function register() {
    $conn = connectDB();

    if (checkLoginStatus()) {
        echo json_encode(array("message" => "User already logged in"));
        return;
    }

    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $email = isset($_POST['email']) ? $_POST['email'] : null;
    $phone = isset($_POST['phone']) ? $_POST['phone'] : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    if ($name === null || empty($name) ||
        $email === null || empty($email) ||
        $phone === null || empty($phone) ||
        $password === null || empty($password)) {
        echo json_encode(array("message" => "Missing required data"));
        return;
    }

    if (isDuplicateUser($conn, $email, $phone)) {
        echo json_encode(array("message" => "Email or phone number is already registered"));
        return;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $phone, $hashed_password, $created_at, $updated_at);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT id, name, email, phone, password, created_at, updated_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        echo json_encode(array(
            "message" => "Registration successful",
            "data" => $user
        ));
    } else {
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
        $userId = $_SESSION['id'];
        $userData = getUserData($conn, $userId);

        echo json_encode(array(
            "message" => "User already logged in",
            "data" => $userData
        ));
        return;
    }

    $email = isset($_POST['email']) ? $_POST['email'] : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;

    if ($email === null || empty($email) ||
        $password === null || empty($password)) {
        echo json_encode(array("message" => "Missing required data"));
        return; 
    }

    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    $stmt = $conn->prepare("SELECT id, name , email, phone, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $email, $phone, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['id'] = $id;

            echo json_encode(array(
                "message" => "Login successful",
                "user" => array(
                    "idid" => $id,
                    "name" => $name,
                    "email" => $email,
                    "phone" => $phone
                )
            ));
        } else {
            echo json_encode(array("message" => "Incorrect password"));
        }
    } else {
        echo json_encode(array("message" => "User not found"));
    }

    $stmt->close();
    $conn->close();
}

function getUserData($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();

    return $userData;
}

function logout() {
    $conn = connectDB();
    
    if (!checkLoginStatus()) {
        echo json_encode(array("message" => "User not logged in"));
        return;
    }

    session_unset();
    session_destroy();

    echo json_encode(array("message" => "Logout successful"));

    $conn->close();
}

?>