<?php
include('../db_con.php');
include('./components/head.php');

$quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : null;
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$quiz_id) {
    die("Quiz ID is required.");
}

// Fetch quiz details
$quiz_query = "SELECT title, description FROM quizzes WHERE quiz_id = ?";
$stmt = $conn->prepare($quiz_query);
$stmt->bind_param("s", $quiz_id);
$stmt->execute();
$quiz_result = $stmt->get_result()->fetch_assoc();

// Fetch questions
$questions_query = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_id";
$stmt = $conn->prepare($questions_query);
$stmt->bind_param("s", $quiz_id);
$stmt->execute();
$questions_result = $stmt->get_result();

// Fetch user details
$user_details = null;
if ($user_id) {
    $user_query = "SELECT name, college, u_name FROM user WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_details = $stmt->get_result()->fetch_assoc();
}

$u_name = isset($user_details['u_name']) ? $user_details['u_name'] . '_' . $user_details['name'] : '';

// Initialize counters
$correct_count = 0;
$incorrect_count = 0;
$unanswered_count = 0;

?>
<style>
    pre {
        color: black;
        font-size: small;
    }
</style>
<div class="container-fluid">
    <div class="card-body">
        <?php if ($user_id): ?>
            <div class="alert alert-info mb-4">
                <h4 class="card-title fw-semibold mb-4"><?php echo htmlspecialchars($quiz_result['title']); ?> - Answer Sheet</h4>
                <p class="mb-0"><strong>Name:</strong> <?php echo htmlspecialchars($user_details['name']); ?></p>
                <p class="mb-0"><strong>College:</strong> <?php echo htmlspecialchars($user_details['college']); ?></p>
                <p class="mb-0"><strong>Username:</strong> <?php echo htmlspecialchars($user_details['u_name']); ?></p>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-4">
                <h4 class="card-title fw-semibold mb-4"><?php echo htmlspecialchars($quiz_result['title']); ?> - Answer Sheet</h4>
            </div>
        <?php endif; ?>
        
        <button class="btn btn-primary btn-lg mb-4" id="export-pdf">
            <i class="fas fa-file-pdf me-2"></i>Export as PDF
        </button>

        <?php
        $question_number = 1;
        while ($question = $questions_result->fetch_assoc()):
            $user_answer = null; // Initialize user answer variable for each question
        ?>
            <div class="question mb-4">
                <h6 class="fw-semibold">Question <?php echo $question_number; ?>:</h6>
                <pre class="bg-light p-3 rounded"><?php echo ($question['question_text']); ?></pre>
                <?php
                $options = ['A', 'B', 'C', 'D'];
                foreach ($options as $option):
                    $option_class = 'option p-2 rounded mb-2';
                    if ($question['correct_answer'] == $option) {
                        $option_class .= ' bg-success text-white';
                        $correct_count++; // Increment correct count
                    }
                    if ($user_id) {
                        $answer_query = "SELECT selected_answer FROM quiz_answers WHERE user_id = ? AND quiz_id = ? AND question_id = ?";
                        $stmt = $conn->prepare($answer_query);
                        $stmt->bind_param("isi", $user_id, $quiz_id, $question['question_id']);
                        $stmt->execute();
                        $answer_result = $stmt->get_result()->fetch_assoc();
                        $user_answer = $answer_result ? $answer_result['selected_answer'] : null;

                        if ($user_answer == $option && $user_answer != $question['correct_answer']) {
                            $option_class .= ' bg-danger text-white';
                            $incorrect_count++; // Increment incorrect count
                        }
                    }
                ?>
                    <div class="<?php echo $option_class; ?>">
                        <strong><?php echo $option; ?>:</strong>
                        <code>
                            <pre><?php echo htmlspecialchars($question['option_' . strtolower($option)]); ?></pre>
                        </code>
                        <?php if ($question['correct_answer'] == $option): ?>
                            <i class="fas fa-check-circle float-end"></i>
                        <?php endif; ?>
                        <?php if ($user_id && $user_answer == $option && $user_answer != $question['correct_answer']): ?>
                            <i class="fas fa-times-circle float-end"></i>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($user_id): ?>
                    <div class="user-answer mt-2">
                        <?php if ($user_answer): ?>
                            <span class="<?php echo $user_answer == $question['correct_answer'] ? 'text-success' : 'text-danger'; ?>">
                                Your answer: <strong><?php echo $user_answer; ?></strong>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">Not answered</span>
                            <?php $unanswered_count++; // Increment unanswered count ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php
            $question_number++;
        endwhile;
        ?>

        <!-- Summary of results -->
        <div class="summary mt-4">
            <h5>Summary:</h5>
            <p>Correct Answers: <strong><?php echo $correct_count; ?></strong></p>
            <p>Incorrect Answers: <strong><?php echo $incorrect_count; ?></strong></p>
            <p>Unanswered Questions: <strong><?php echo $unanswered_count; ?></strong></p>
        </div>
    </div>
</div>

<script src="../assets/js/html2pdf.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const exportButton = document.getElementById('export-pdf');
        if (exportButton) {
            exportButton.addEventListener('click', function() {
                const content = document.querySelector('.card-body');

                const opt = {
                    margin: [10, 10, 10, 10],
                    filename: 'answer-sheet-<?php echo $quiz_result["title"]; ?>-<?php echo $u_name; ?>.pdf',
                    image: {
                        type: 'jpeg',
                        quality: 0.98
                    },
                    html2canvas: {
                        scale: 2
                    },
                    jsPDF: {
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'portrait'
                    },
                    pagebreak: {
                        mode: ['avoid-all', 'css', 'legacy'],
                        before: '.question',
                        avoid: 'img'
                    }
                };

                const contentClone = content.cloneNode(true);
                const exportButtonClone = contentClone.querySelector('#export-pdf');
                if (exportButtonClone) {
                    exportButtonClone.remove();
                }

                const styleAdjustments = `
                <style>
                    body { font-family: Arial, sans-serif; }
                    .card-body { padding: 20px; }
                    .question { margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; }
                    .option { margin-bottom: 10px; padding: 5px; }
                    .bg-success { background-color: #d4edda; color: #155724; }
                    .bg-danger { background-color: #f8d7da; color: #721c24; }
                    .user-answer { margin-top: 10px; font-style: italic; }
                </style>
            `;

                contentClone.innerHTML = styleAdjustments + contentClone.innerHTML;

                html2pdf()
                    .from(contentClone)
                    .set(opt)
                    .save();
            });
        }
    });
</script>

<?php
include('./components/foot.php');
?>
