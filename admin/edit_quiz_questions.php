<?php
include('./components/head.php');

// Get the quiz_id from GET parameter
$quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : '';

if (empty($quiz_id)) {
    die("Error: No quiz ID provided");
}

// Fetch quiz questions from the database
$query = "SELECT question_id, question_text, option_a, option_b, option_c, option_d, correct_answer
          FROM quiz_questions
          WHERE quiz_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title fw-semibold mb-4">Quiz Questions</h5>

            <!-- Button to Open Add Quiz Modal -->

            <button type="button" class="btn btn-success mb-3 ms-2" data-bs-toggle="modal" data-bs-target="#importQuestionsModal">
                Import Questions
            </button>
            <button type="button" class="btn btn-primary mb-3 ms-2" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                Add New Question
            </button><a href="./answersheet.php?quiz_id=<?php echo ($quiz_id); ?>">
                <button type="button" class="btn btn-outline-danger mb-3 ms-2" data-bs-toggle="modal">
                    Export Question
                </button></a>
            <!-- Table to Display Quiz Questions -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Question ID</th>
                        <th>Question</th>
                        <th>Option A</th>
                        <th>Option B</th>
                        <th>Option C</th>
                        <th>Option D</th>
                        <th>Correct Answer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr data-id="' . htmlspecialchars($row['question_id']) . '">';
                            echo '<td>' . htmlspecialchars($row['question_id']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['question_text']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['option_a']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['option_b']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['option_c']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['option_d']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['correct_answer']) . '</td>';
                            echo '<td>';
                            echo '<button class="btn btn-warning btn-sm edit-btn me-2" data-id="' . htmlspecialchars($row['question_id']) . '">Edit</button>';
                            echo '<button class="btn btn-danger btn-sm delete-btn" data-id="' . htmlspecialchars($row['question_id']) . '">Delete</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center">No questions found for this quiz.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- Import Questions Modal -->
<div class="modal fade" id="importQuestionsModal" tabindex="-1" aria-labelledby="importQuestionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importQuestionsModalLabel">Import Questions from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importQuestionsForm">
                    <input type="hidden" id="importQuizId" value="<?php echo htmlspecialchars($quiz_id); ?>">
                    <div class="mb-3">
                        <label for="excelFile" class="form-label">Select Excel File</label>
                        <input type="file" class="form-control" id="excelFile" accept=".xlsx, .xls" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Import Questions</button>
                </form>
                <div id="importStatus" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <p>Excel Format:Column order: Question, Option A, Option B, Option C, Option D, Correct Answer (A/B/C/D)</p>
                <a href="./egexcel/quiz_question_template.xlsx" download class="btn btn-outline-secondary">Download Template</a>
            </div>
        </div>
    </div>
</div>

<!-- Add Quiz Modal -->
<div class="modal fade" id="addQuizModal" tabindex="-1" aria-labelledby="addQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addQuizModalLabel">Add New Quiz Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addQuizForm">
                    <input type="hidden" id="quizId" value="<?php echo htmlspecialchars($quiz_id); ?>">
                    <div class="mb-3">
                        <label for="questionText" class="form-label">Question</label>
                        <textarea class="form-control" id="questionText" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="optionA" class="form-label">Option A</label>
                        <textarea class="form-control" id="optionA" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="optionB" class="form-label">Option B</label>
                        <textarea class="form-control" id="optionB" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="optionC" class="form-label">Option C</label>
                        <textarea class="form-control" id="optionC"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="optionD" class="form-label">Option D</label>
                        <textarea class="form-control" id="optionD"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="correctAnswer" class="form-label">Correct Answer</label>
                        <select class="form-select" id="correctAnswer" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Question</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Quiz Modal -->
<div class="modal fade" id="editQuizModal" tabindex="-1" aria-labelledby="editQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editQuizModalLabel">Edit Quiz Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editQuizForm">
                    <input type="hidden" id="editQuestionId">
                    <input type="hidden" id="editQuizId" value="<?php echo htmlspecialchars($quiz_id); ?>">
                    <div class="mb-3">
                        <label for="editQuestionText" class="form-label">Question</label>
                        <textarea class="form-control" id="editQuestionText" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editOptionA" class="form-label">Option A</label>
                        <textarea class="form-control" id="editOptionA" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editOptionB" class="form-label">Option B</label>
                        <textarea class="form-control" id="editOptionB" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editOptionC" class="form-label">Option C</label>
                        <textarea class="form-control" id="editOptionC"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editOptionD" class="form-label">Option D</label>
                        <textarea class="form-control" id="editOptionD"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editCorrectAnswer" class="form-label">Correct Answer</label>
                        <select class="form-select" id="editCorrectAnswer" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Question</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/xlsx.full.min.js"></script>

<script>
    $(document).ready(function() {
        // Add new quiz question
        $('#addQuizForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'quiz_questions_api.php',
                type: 'POST',
                data: {
                    action: 'add',
                    quiz_id: $('#quizId').val(),
                    question_text: $('#questionText').val(),
                    option_a: $('#optionA').val(),
                    option_b: $('#optionB').val(),
                    option_c: $('#optionC').val(),
                    option_d: $('#optionD').val(),
                    correct_answer: $('#correctAnswer').val()
                },
                success: function(response) {
                    alert('Question added successfully');
                    location.reload();
                },
                error: function() {
                    alert('Error adding question');
                }
            });
        });

        // Load question data for editing
        $('.edit-btn').click(function() {
            var row = $(this).closest('tr');
            var questionId = row.data('id');

            // Populate the modal fields with data from the table
            $('#editQuestionId').val(questionId);
            $('#editQuestionText').val(row.find('td:eq(1)').text());
            $('#editOptionA').val(row.find('td:eq(2)').text());
            $('#editOptionB').val(row.find('td:eq(3)').text());
            $('#editOptionC').val(row.find('td:eq(4)').text());
            $('#editOptionD').val(row.find('td:eq(5)').text());
            $('#editCorrectAnswer').val(row.find('td:eq(6)').text());

            // Show the modal
            $('#editQuizModal').modal('show');
        });

        // Update quiz question
        $('#editQuizForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'quiz_questions_api.php',
                type: 'POST',
                data: {
                    action: 'update',
                    question_id: $('#editQuestionId').val(),
                    quiz_id: $('#editQuizId').val(),
                    question_text: $('#editQuestionText').val(),
                    option_a: $('#editOptionA').val(),
                    option_b: $('#editOptionB').val(),
                    option_c: $('#editOptionC').val(),
                    option_d: $('#editOptionD').val(),
                    correct_answer: $('#editCorrectAnswer').val()
                },
                dataType: 'json',
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        alert(response.message);
                        $('#editQuizModal').modal('hide');
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert('Error updating question: ' + response.error);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    alert('Error updating question. Please try again.');
                }
            });
        });
        $('#importQuestionsForm').submit(function(e) {
            e.preventDefault();
            var fileInput = $('#excelFile')[0];
            var file = fileInput.files[0];
            var reader = new FileReader();

            reader.onload = function(e) {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, {
                    type: 'array'
                });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];
                var jsonData = XLSX.utils.sheet_to_json(worksheet, {
                    header: 1
                });

                // Remove header row if present
                if (jsonData.length > 0 && jsonData[0][0].toLowerCase() === 'question') {
                    jsonData.shift();
                }

                var quizId = $('#importQuizId').val();
                var totalQuestions = jsonData.length;
                var importedCount = 0;
                var statusDiv = $('#importStatus');

                statusDiv.html('<p>Importing questions...</p>');

                function importNextQuestion(index) {
                    if (index >= jsonData.length) {
                        statusDiv.html('<p>Import completed. ' + importedCount + ' out of ' + totalQuestions + ' questions imported successfully.</p>');
                        return;
                    }

                    var row = jsonData[index];
                    if (row.length < 6) {
                        statusDiv.append('<p>Skipping row ' + (index + 1) + ': Insufficient data</p>');
                        importNextQuestion(index + 1);
                        return;
                    }

                    $.ajax({
                        url: 'quiz_questions_api.php',
                        type: 'POST',
                        data: {
                            action: 'add',
                            quiz_id: quizId,
                            question_text: row[0],
                            option_a: row[1],
                            option_b: row[2],
                            option_c: row[3],
                            option_d: row[4],
                            correct_answer: row[5]
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                importedCount++;
                                statusDiv.append('<p>Imported question ' + (index + 1) + '</p>');
                            } else {
                                statusDiv.append('<p>Failed to import question ' + (index + 1) + ': ' + response.error + '</p>');
                            }
                            importNextQuestion(index + 1);
                        },
                        error: function() {
                            statusDiv.append('<p>Error importing question ' + (index + 1) + '</p>');
                            importNextQuestion(index + 1);
                        }
                    });
                }

                importNextQuestion(0);
            };

            reader.readAsArrayBuffer(file);
        });

        // Delete quiz question
        $('.delete-btn').click(function() {
            if (confirm('Are you sure you want to delete this question?')) {
                var questionId = $(this).data('id');
                $.ajax({
                    url: 'quiz_questions_api.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        id: questionId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting question: ' + response.error);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX error:', textStatus, errorThrown);
                        alert('Error deleting question. Please try again.');
                    }
                });
            }
        });
    });
</script>

<?php
include('./components/foot.php');
?>