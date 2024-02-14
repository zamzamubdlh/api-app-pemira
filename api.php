<?php

require_once('./connection.php');
require_once('./functions/auth.php');
require_once('./functions/account.php');
require_once('./functions/candidate.php');
require_once('./functions/vote.php');
require_once('./functions/election-fraud.php');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = explode('/', $path);

$path = array_filter($path);
$action = end($path);

$method = $_SERVER['REQUEST_METHOD'];

// Router
switch ($method) {
    case 'POST':
        switch ($action) {
            case "login":
                login();
                break;
            case "register":
                register();
                break;
            case "register-candidate":
                registerCandidate();
                break;
            case "vote":
                vote();
                break;
            case "report-fraud":
                reportFraud();
                break;
            case "logout":
                logout();
                break;
            case "list-previous-votes":
                listPreviousVote();
                break;
            case "get-candidate-by-current-year":
                getCandidateByCurrentYear();
                break;
        }
        break;
    case 'GET':
        switch ($action) {
            case "get-candidate-profile":
                getCandidateProfile();
                break;
            case "get-voting-results":
                getVotingResults();
                break;
        }
        break;
    case 'PUT':
        switch ($action) {
            case "update-account":
                updateAccount();
                break;
        }
        break;
    default:
        // Handle unsupported methods
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
