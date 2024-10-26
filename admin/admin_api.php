<?php
session_start(); // Start the session to access session variables
header('Content-Type: application/json');

// Check if the admin is logged in
if (!isset($_SESSION['coddict_admin'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

// Include database connection
include('../db_con.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for delete user request
    if (isset($_POST['deluserid'])) {
        $deleteId = $_POST['deluserid'];
        $query = "DELETE FROM user WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $deleteId);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delete failed.']);
        }
    }
    // Check if it's an update
    elseif (isset($_POST['userId'])) {
        // Update user
        $id = $_POST['userId'];
        $name = $_POST['userName'];
        $college = $_POST['userCollege'];
        $username = $_POST['userUsername'];
        $password = $_POST['userPassword'];

        $query = "UPDATE user SET name=?, college=?, u_name=?, passwd=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssi', $name, $college, $username, $password, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'data' => ['id' => $id, 'name' => $name, 'college' => $college, 'username' => $username,'passwd' => $password]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update failed.']);
        }
    }
    // Add new user
    elseif (isset($_POST['userName'])) {
        $name = $_POST['userName'];
        $college = $_POST['userCollege'];
        $username = $_POST['userUsername'];
        $password = $_POST['userPassword'];

        $query = "INSERT INTO user (name, college, u_name, passwd) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssss', $name, $college, $username, $password);

        if ($stmt->execute()) {
            $newId = $stmt->insert_id; // Get the ID of the newly inserted row
            echo json_encode(['status' => 'success', 'data' => ['id' => $newId, 'name' => $name, 'college' => $college, 'username' => $username,'passwd' => $password]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Adding user failed.']);
        }
    }

    elseif (isset($_POST['viewexamid'])) {
        $id = $_POST['viewexamid'];
        $query = "SELECT * FROM `quiz_attempts` WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        
        // Execute the query
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $examAttempts = [];
    
            // Fetch all attempts
            while ($attempt = $result->fetch_assoc()) {
                $examAttempts[] = $attempt; // Add each attempt to the array
            }
    
            if (count($examAttempts) > 0) {
                echo json_encode(['status' => 'success', 'data' => $examAttempts]); // Return all attempts
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No exam attempts found.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Query execution failed.']);
        }
    }
    
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
