<?php
session_start();
if (!isset($_SESSION['coddict_admin'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

include('../db_con.php'); // Include your database connection file

function generateUniqueQuizId($conn) {
    $count = 0;
    do {
        $quizId = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM quizzes WHERE quiz_id = ?");
        $stmt->bind_param("s", $quizId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    } while ($count > 0);
    return $quizId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $quizId = generateUniqueQuizId($conn);
            $title = $_POST['title'];
            $description = $_POST['description'];
            $status = $_POST['status'];
            $allocated_time = $_POST['allocated_time'];
            $total_marks = $_POST['total_marks'];

            $stmt = $conn->prepare("INSERT INTO quizzes (quiz_id, title, description, status, allocated_time, total_marks) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $quizId, $title, $description, $status, $allocated_time, $total_marks);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Quiz created successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create quiz: ' . $stmt->error]);
            }
            $stmt->close();
            break;

        case 'edit':
            $quiz_id = $_POST['quiz_id'];
            $title = $_POST['title'];
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $status = $_POST['status'];
            $allocated_time = $_POST['allocated_time'];
            $total_marks = $_POST['total_marks'];

            $stmt = $conn->prepare("UPDATE quizzes SET title = ?, description = ?, status = ?, allocated_time = ?, total_marks = ? WHERE quiz_id = ?");
            $stmt->bind_param("ssssis", $title, $description, $status, $allocated_time, $total_marks, $quiz_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Quiz updated successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update quiz: ' . $stmt->error]);
            }
            $stmt->close();
            break;

        case 'delete':
            $quiz_id = $_POST['quiz_id'];
            
            // Start a transaction
            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare("DELETE FROM quizzes WHERE quiz_id = ?");
                $stmt->bind_param("s", $quiz_id);
                $stmt->execute();
                $stmt->close();

                // Commit the transaction
                $conn->commit();

                echo json_encode(['status' => 'success', 'message' => 'Quiz and associated questions deleted successfully.']);
            } catch (Exception $e) {
                // An error occurred, rollback the transaction
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete quiz: ' . $e->getMessage()]);
            }
            break;

        case 'toggle_status':
            $quiz_id = $_POST['quiz_id'];
            $new_status = $_POST['status'];

            $stmt = $conn->prepare("UPDATE quizzes SET status = ? WHERE quiz_id = ?");
            $stmt->bind_param("ss", $new_status, $quiz_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Quiz status updated successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update quiz status: ' . $stmt->error]);
            }
            $stmt->close();
            break;

        case 'get_quiz':
            $quiz_id = $_POST['quiz_id'];

            $stmt = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
            $stmt->bind_param("s", $quiz_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $quiz = $result->fetch_assoc();
            $stmt->close();

            if ($quiz) {
                echo json_encode(['status' => 'success', 'quiz' => $quiz]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Quiz not found.']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
            break;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

$conn->close(); // Close the database connection
?>