<?php
// Include database connection
include('../db_con.php');

// Function to get total count from a table
function getCount($conn, $table)
{
    $query = "SELECT COUNT(*) as count FROM $table";
    $result = $conn->query($query);
    return $result->fetch_assoc()['count'];
}

// Get counts
$userCount = getCount($conn, 'user');
$quizCount = getCount($conn, 'quizzes');
$questionCount = getCount($conn, 'quiz_questions');

// Get active quiz users
$activeUsersQuery = "SELECT COUNT(DISTINCT user_id) as active_users FROM quiz_attempts WHERE status = 'started'";
$activeUsersResult = $conn->query($activeUsersQuery);
$activeUsersCount = $activeUsersResult->fetch_assoc()['active_users'];

// Get active quizzes and their details
$activeQuizzesQuery = "
    SELECT q.quiz_id, q.title, COUNT(DISTINCT qa.user_id) as active_users
    FROM quizzes q
    JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id
    WHERE qa.status = 'started'
    GROUP BY q.quiz_id
";
$activeQuizzesResult = $conn->query($activeQuizzesQuery);

$activeQuizzesCount = $activeQuizzesResult->num_rows;
$activeQuizzes = [];
while ($row = $activeQuizzesResult->fetch_assoc()) {
    $activeQuizzes[] = $row;
}

// Get quizzes with question counts and completed users
$quizQuery = "
    SELECT q.quiz_id, q.title, COUNT(qq.question_id) as question_count,
           (SELECT COUNT(DISTINCT qa.user_id) FROM quiz_attempts qa WHERE qa.quiz_id = q.quiz_id AND qa.status = 'completed') as completed_users
    FROM quizzes q
    LEFT JOIN quiz_questions qq ON q.quiz_id = qq.quiz_id
    GROUP BY q.quiz_id";
$quizResult = $conn->query($quizQuery);

// Function to recalculate quiz attempt statuses
function recalculateQuizAttempts($conn)
{
    $currentTime = date('Y-m-d H:i:s'); // Get current time in MySQL datetime format

    $updateQuery = "UPDATE quiz_attempts qa
                    JOIN quizzes q ON qa.quiz_id = q.quiz_id
                    SET qa.status = 'completed',
                        qa.finish_time = CASE
                            WHEN TIMESTAMPADD(MINUTE, q.allocated_time, qa.start_time) < ?
                            THEN TIMESTAMPADD(MINUTE, q.allocated_time, qa.start_time)
                            ELSE ?
                        END
                    WHERE qa.status = 'started'
                    AND TIMESTAMPADD(MINUTE, q.allocated_time, qa.start_time) < ?";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('sss', $currentTime, $currentTime, $currentTime);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}
if (isset($_POST['recalculate'])) {
    $recalculateResult = recalculateQuizAttempts($conn);
    $_SESSION['recalculateMessage'] = $recalculateResult ? "Quiz attempts recalculated successfully." : "Error recalculating quiz attempts.";

    // Redirect to the same page to avoid form resubmission
    header("Location: ./");
    exit();
}
// Handle recalculation request
if (isset($_SESSION['recalculateMessage'])) {
    $recalculateMessage = $_SESSION['recalculateMessage'];
    unset($_SESSION['recalculateMessage']);
}
include('./components/head.php');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="my-4">Quiz System Dashboard</h1>
        </div>
    </div>

    <?php if (isset($recalculateMessage)): ?>
        <div class="alert alert-info" role="alert">
            <?php echo $recalculateMessage; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-users mr-2"></i> Total Users
                    </h5>
                    <p class="card-text display-4"><?php echo $userCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-clipboard-list mr-2"></i> Total Quizzes
                    </h5>
                    <p class="card-text display-4"><?php echo $quizCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-question-circle mr-2"></i> Total Questions
                    </h5>
                    <p class="card-text display-4"><?php echo $questionCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-user-clock mr-2"></i> Active Quiz Users
                    </h5>
                    <p class="card-text display-4"><?php echo $activeUsersCount; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-play-circle mr-2"></i> Active Quizzes
                    </h5>
                    <p class="card-text display-4"><?php echo $activeQuizzesCount; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recalculate Quiz Attempts</h5>
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <button type="submit" name="recalculate" class="btn btn-primary">
                            <i class="fas fa-sync-alt mr-2"></i> Recalculate
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Active Quizzes</h2>
        </div>
    </div>
    <div class="row">
        <?php foreach ($activeQuizzes as $quiz): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-file-alt mr-2"></i> <?php echo htmlspecialchars($quiz['title']); ?>
                        </h5>
                        <p class="card-text">
                            <i class="fas fa-users mr-2"></i> <?php echo $quiz['active_users']; ?> active users
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">All Quizzes Overview</h2>
        </div>
    </div>
    <div class="row">
        <?php while ($quiz = $quizResult->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-file-alt mr-2"></i> <?php echo htmlspecialchars($quiz['title']); ?>
                        </h5>
                        <p class="card-text">
                            <i class="fas fa-question mr-2"></i> <?php echo $quiz['question_count']; ?> questions
                        </p>
                        <p class="card-text">
                            <i class="fas fa-users mr-2"></i> <?php echo $quiz['completed_users']; ?> users completed
                        </p>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include('./components/foot.php'); ?>