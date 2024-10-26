<?php
session_start();
if (!isset($_SESSION['coddict_admin'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}
require_once '../db_con.php';
header('Content-Type: application/json');

// Function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check if the request method is POST or GET
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_REQUEST['action']) ? sanitize_input($_REQUEST['action']) : '';

    switch ($action) {
        case 'add':
            add_question($conn);
            break;
        case 'update':
            update_question($conn);
            break;
        case 'delete':
            delete_question($conn);
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

function add_question($conn) {
    $quiz_id = sanitize_input($_POST['quiz_id']);
    $question_text = ($_POST['question_text']);
    $option_a = ($_POST['option_a']);
    $option_b = ($_POST['option_b']);
    $option_c = ($_POST['option_c']);
    $option_d = ($_POST['option_d']);
    $correct_answer = sanitize_input($_POST['correct_answer']);

    $query = "INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssss", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Question added successfully']);
    } else {
        echo json_encode(['error' => 'Error adding question: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function update_question($conn) {
    $question_id = sanitize_input($_POST['question_id']);
    $quiz_id = sanitize_input($_POST['quiz_id']);
    $question_text = ($_POST['question_text']);
    $option_a = ($_POST['option_a']);
    $option_b = ($_POST['option_b']);
    $option_c = ($_POST['option_c']);
    $option_d = ($_POST['option_d']);
    $correct_answer = sanitize_input($_POST['correct_answer']);

    $query = "UPDATE quiz_questions 
              SET quiz_id = ?, question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ? 
              WHERE question_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssi", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $question_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Question updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error updating question: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function delete_question($conn) {
    $question_id = sanitize_input($_POST['id']);

    $query = "DELETE FROM quiz_questions WHERE question_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $question_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error deleting question: ' . $stmt->error]);
    }
    
    $stmt->close();
}


// Close the database connection
$conn->close();
?>