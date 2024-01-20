<?php

function connectDB() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "pemira_app";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

function checkLoginStatus() {
    session_start();

    if (isset($_SESSION['id']) && isset($_SESSION['token'])) {
        return true;
    } else {
        return false;
    }
}
