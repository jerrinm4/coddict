<?php
session_start();
header('Content-Type: application/json');


// Check if user is logged in
if (!isset($_SESSION['coddict_uid'])) {
    die(json_encode(['error' => 'User not logged in']));
}
include('./db_con.php');
$user_id = $_SESSION['coddict_uid'];

// Handle GET request (fetch quiz data)
function shuffle_with_seed(array &$array, $seed)
{
    mt_srand($seed); // Set the seed
    for ($i = count($array) - 1; $i > 0; $i--) {
        // Use mt_rand instead of rand to get a seeded random number
        $j = mt_rand(0, $i);
        // Swap positions
        $temp = $array[$i];
        $array[$i] = $array[$j];
        $array[$j] = $temp;
    }
    mt_srand();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['quiz_id'])) {
        $quiz_id = $conn->real_escape_string($_GET['quiz_id']);

        // Fetch quiz details
        $quiz_query = "SELECT q.*, u.name as u_name FROM quizzes q 
                       JOIN user u ON u.id = ? 
                       WHERE q.quiz_id = ?";
        $stmt = $conn->prepare($quiz_query);
        $stmt->bind_param("is", $user_id, $quiz_id);
        $stmt->execute();
        $quiz_result = $stmt->get_result()->fetch_assoc();

        if (!$quiz_result) {
            die(json_encode(['error' => 'Quiz not found']));
        }
        $questions_query = "SELECT question_id, quiz_id, question_text, option_a, option_b, option_c, option_d, created_at, updated_at 
        FROM quiz_questions 
        WHERE quiz_id = ?";
        $stmt = $conn->prepare($questions_query);
        $stmt->bind_param("s", $quiz_id);
        $stmt->execute();
        $questions_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


        // Fetch user's answers
        $answers_query = "SELECT question_id, selected_answer FROM quiz_answers 
                          WHERE user_id = ? AND quiz_id = ?";
        $stmt = $conn->prepare($answers_query);
        $stmt->bind_param("is", $user_id, $quiz_id);
        $stmt->execute();
        $answers_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $answered_questions = [];
        foreach ($answers_result as $answer) {
            $answered_questions[$answer['question_id']] = $answer['selected_answer'];
        }

        // Calculate remaining time
        $attempt_query = "SELECT start_time,seed FROM quiz_attempts 
                          WHERE user_id = ? AND quiz_id = ? AND status = 'started'";
        $stmt = $conn->prepare($attempt_query);
        $stmt->bind_param("is", $user_id, $quiz_id);
        $stmt->execute();
        $attempt_result = $stmt->get_result()->fetch_assoc();

        $remaining_time = $quiz_result['allocated_time'] * 60;
        if ($attempt_result) {
            $elapsed_time = time() - strtotime($attempt_result['start_time']);
            $remaining_time = max(0, $remaining_time - $elapsed_time);
        }

        // Shuffle the questions array
        // shuffle($questions_result);
        $seed = (int) 12345678; // Set your seed
        shuffle_with_seed($questions_result, $seed);
        echo json_encode([
            'quiz' => $quiz_result,
            'questions' => $questions_result,
            'answered_questions' => $answered_questions,
            'remaining_time' => $remaining_time
        ]);
    } else {
        die(json_encode(['error' => 'Quiz ID not provided']));
    }
}
// Handle POST request (submit answer or finish quiz)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['quiz_id']) && isset($data['question_id']) && isset($data['selected_answer'])) {
        $quiz_id = $conn->real_escape_string($data['quiz_id']);
        $question_id = $conn->real_escape_string($data['question_id']);
        $selected_answer = $conn->real_escape_string($data['selected_answer']);
        $fetch_attempt_query = "
            SELECT id FROM quiz_attempts 
            WHERE user_id = ? AND quiz_id = ? 
            AND status != 'completed'";


        $stmt = $conn->prepare($fetch_attempt_query);
        $stmt->bind_param("is", $user_id, $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $quiz_attempt_id = $row['id'];
            $submit_answer_query = "
            INSERT INTO quiz_answers (user_id, quiz_id, question_id, selected_answer, quiz_attempt_id) 
            VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE selected_answer = ?";

            $stmt = $conn->prepare($submit_answer_query);
            $stmt->bind_param("isissi", $user_id, $quiz_id, $question_id, $selected_answer, $quiz_attempt_id, $selected_answer);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
                exit();
            }
        }
        echo json_encode(['error' => 'Failed to submit answer']);
    } elseif (isset($data['quiz_id']) && isset($data['action']) && $data['action'] === 'finish') {
        $quiz_id = $conn->real_escape_string($data['quiz_id']);

        // Finish the quiz attempt
        $finish_attempt_query = "UPDATE quiz_attempts SET status = 'completed', finish_time = CURRENT_TIMESTAMP 
                                 WHERE user_id = ? AND quiz_id = ? AND status = 'started'";
        $stmt = $conn->prepare($finish_attempt_query);
        $stmt->bind_param("is", $user_id, $quiz_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to finish quiz']);
        }
    } elseif (isset($data['quiz_id']) && isset($data['action']) && $data['action'] === 'reset') {
        $quiz_id = $conn->real_escape_string($data['quiz_id']);

        // Finish the quiz attempt
        $finish_attempt_query = "delete from quiz_answers where user_id=?  and quiz_id = ? and question_id =?;";
        $stmt = $conn->prepare($finish_attempt_query);
        $stmt->bind_param("iis", $user_id, $quiz_id, $data['question_id']);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to delete quiz']);
        }
    } else {
        die(json_encode(['error' => 'Invalid request data']));
    }
}

$conn->close();
