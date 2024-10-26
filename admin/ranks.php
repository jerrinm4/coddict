<?php
include('./components/head.php');
include('../db_con.php');

// Function to calculate user scores
function calculateUserScores($conn, $quizData)
{
    $quizIds = array_keys($quizData);
    $whereClause = !empty($quizIds) ? "WHERE qa.quiz_id IN ('" . implode("','", array_map([$conn, 'real_escape_string'], $quizIds)) . "')" : '';

    $query = "
        SELECT 
            u.id AS user_id,
            u.name AS user_name,
            u.u_name AS username,
            u.college AS college_name,
            qa.quiz_id,
            COUNT(DISTINCT qa.quiz_id) AS quizzes_taken,
            SUM(CASE WHEN qa.selected_answer = qq.correct_answer THEN 1 ELSE 0 END) AS correct_answers,
            SUM(CASE WHEN qa.selected_answer != qq.correct_answer AND qa.selected_answer IS NOT NULL THEN 1 ELSE 0 END) AS incorrect_answers
        FROM 
            user u
        LEFT JOIN 
            quiz_answers qa ON u.id = qa.user_id
        LEFT JOIN 
            quiz_questions qq ON qa.question_id = qq.question_id
        $whereClause
        GROUP BY 
            u.id, qa.quiz_id
    ";

    $result = $conn->query($query);
    if (!$result) {
        // Handle query error
        die('Query Error: ' . $conn->error);
    }

    $scores = [];

    while ($row = $result->fetch_assoc()) {
        $userId = $row['user_id'];
        if (!isset($scores[$userId])) {
            $scores[$userId] = [
                'user_name' => $row['user_name'],
                'username' => $row['username'],
                'college_name' => $row['college_name'],
                'quizzes_taken' => 0,
                'total_score' => 0,
                'quiz_scores' => []
            ];
        }

        $quizId = $row['quiz_id'];
        if (isset($quizData[$quizId])) {
            $scores[$userId]['quizzes_taken']++;
            $quizScore = $row['correct_answers'] * $quizData[$quizId]['positive'] +
                $row['incorrect_answers'] * $quizData[$quizId]['negative'];

            // Apply the rule to treat negative scores as zero for this quiz if selected
            if ($quizScore < 0 && $quizData[$quizId]['treat_negative_as_zero']) {
                $quizScore = 0;
            }

            $scores[$userId]['total_score'] += $quizScore;
            $scores[$userId]['quiz_scores'][$quizId] = [
                'score' => $quizScore,
                'correct' => $row['correct_answers'],
                'incorrect' => $row['incorrect_answers']
            ];
        }
    }

    // Sort and rank the scores
    uasort($scores, function ($a, $b) {
        // Return negative if $a's score is higher, positive if $b's score is higher
        if ($a['total_score'] == $b['total_score']) {
            return 0; // they are equal
        }
        return ($a['total_score'] < $b['total_score']) ? 1 : -1; // descending order
    });
    
    $rank = 1;
    foreach ($scores as &$score) {
        $score['rank'] = $rank++;
    }

    return $scores;
}

// Get all quizzes for the form
$quizzesQuery = "SELECT quiz_id, title FROM quizzes";
$quizzesResult = $conn->query($quizzesQuery);

// Initialize variables
$scores = [];
$selectedQuizzes = [];
$quizData = [];
$treatNegativeAsZeroPerQuiz = false;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['quiz_selection']) && is_array($_POST['quiz_selection'])) {
        $selectedQuizzes = $_POST['quiz_selection'];
    } else {
        // If nothing is selected, include all quizzes with default marks
        $quizzesResult->data_seek(0);
        while ($quiz = $quizzesResult->fetch_assoc()) {
            $selectedQuizzes[] = $quiz['quiz_id'];
        }
    }

    foreach ($selectedQuizzes as $quizId) {
        $quizData[$quizId] = [
            'positive' => isset($_POST['positive_' . $quizId]) ? floatval($_POST['positive_' . $quizId]) : 1,
            'negative' => isset($_POST['negative_' . $quizId]) ? floatval($_POST['negative_' . $quizId]) : 0,
            'treat_negative_as_zero' => isset($_POST['treat_negative_as_zero_' . $quizId])
        ];
    }

    $scores = calculateUserScores($conn, $quizData);
}

// Convert scores to JSON for JavaScript usage
$scoresJson = json_encode(array_values($scores));
$quizzesQuery = "SELECT quiz_id, title FROM quizzes";
$quizzesResult = $conn->query($quizzesQuery);

$quizData = []; // Initialize an empty array for quiz data
while ($quiz = $quizzesResult->fetch_assoc()) {
    $quizId = $quiz['quiz_id'];
    
    // Check if quizId exists in the quiz_selection array from POST
    if (isset($_POST['quiz_selection']) && in_array($quizId, $_POST['quiz_selection'])) {
        $quizData[$quizId] = [
            'title' => $quiz['title'], // Add the title here
            'positive' => isset($_POST['positive_' . $quizId]) ? floatval($_POST['positive_' . $quizId]) : 1,
            'negative' => isset($_POST['negative_' . $quizId]) ? floatval($_POST['negative_' . $quizId]) : 0,
            'treat_negative_as_zero' => isset($_POST['treat_negative_as_zero_' . $quizId]) ? (bool)$_POST['treat_negative_as_zero_' . $quizId] : false
        ];
    }
}


?>


<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title fw-semibold mb-4">User Rankings</h5>

            <form method="post" class="mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-3">Select Quizzes and Set Marks:</h6>
                                <?php
                                $quizzesResult->data_seek(0);
                                while ($quiz = $quizzesResult->fetch_assoc()):
                                    $quizId = htmlspecialchars($quiz['quiz_id']);
                                    $checked = in_array($quizId, $selectedQuizzes) ? 'checked' : '';
                                    $positiveValue = isset($quizData[$quizId]) ? $quizData[$quizId]['positive'] : 1;
                                    $negativeValue = isset($quizData[$quizId]) ? $quizData[$quizId]['negative'] : 0;
                                    $treatNegativeAsZero = isset($_POST['treat_negative_as_zero_' . $quizId]) && (bool)$_POST['treat_negative_as_zero_' . $quizId] ? 'checked' : '';

                                ?>
                                    <div class="form-check mb-3 d-flex align-items-center">
                                        <input type="checkbox" class="form-check-input me-1" id="quiz_<?php echo $quizId; ?>" name="quiz_selection[]" value="<?php echo $quizId; ?>" <?php echo $checked; ?>>
                                        <label class="form-check-label me-3" for="quiz_<?php echo $quizId; ?>"><?php echo htmlspecialchars($quiz['title']); ?></label>

                                        <!-- Positive marks input -->
                                        <div class="input-group input-group-sm me-3" style="max-width: 150px;">
                                            <span class="input-group-text">+</span>
                                            <input type="number" class="form-control" name="positive_<?php echo $quizId; ?>" value="<?php echo $positiveValue; ?>" step="0.1" min="0" placeholder="Positive marks">
                                        </div>

                                        <!-- Negative marks input -->
                                        <div class="input-group input-group-sm me-3" style="max-width: 150px;">
                                            <span class="input-group-text">-</span>
                                            <input type="number" class="form-control" name="negative_<?php echo $quizId; ?>" value="<?php echo $negativeValue; ?>" step="0.1" max="0" placeholder="Negative marks">
                                        </div>

                                        <!-- Treat negative score as 0 checkbox -->
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input me-2" id="treat_negative_as_zero_<?php echo $quizId; ?>" name="treat_negative_as_zero_<?php echo $quizId; ?>" <?php echo $treatNegativeAsZero; ?>>
                                            <label class="form-check-label" for="treat_negative_as_zero_<?php echo $quizId; ?>"> negative score as 0</label>
                                        </div>
                                    </div>

                                <?php endwhile; ?>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary w-100">Calculate Rankings</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <?php if (!empty($scores)): ?>
                <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <button id="exportPdf" class="btn btn-secondary">Export to PDF</button>
                        <button id="exportExcel" class="btn btn-secondary">Export to Excel</button>
                    </div>
                    <div>
                        <button id="customizeExport" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customizeModal">Customize Export</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="rankingsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th data-column="rank">Rank</th>
                                <th data-column="Name">Name</th>
                                <th data-column="username">Username</th>
                                <th data-column="college_name">College</th>
                                <th data-column="quizzes_taken">Quizzes Taken</th>
                                <th data-column="total_score">Total Score</th>
                                <?php foreach ($quizData as $quizId => $quiz): ?>
                                        <th data-column="quiz_<?php echo htmlspecialchars($quizId); ?>">
                                            <?php echo htmlspecialchars($quiz['title']); ?>
                                        </th>
                                    
                                <?php endforeach; ?>

                            </tr>
                        </thead>



                        <tbody>
                            <?php foreach ($scores as $score): ?>
                                <tr>
                                    <td><?php echo $score['rank']; ?></td>
                                    <td><?php echo htmlspecialchars($score['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($score['username']); ?></td>
                                    <td><?php echo htmlspecialchars($score['college_name']); ?></td>
                                    <td><?php echo $score['quizzes_taken']; ?></td>
                                    <td><?php echo $score['total_score']; ?></td>
                                    <?php foreach ($quizData as $quizId => $quiz): ?>
                                        <td>
                                            <?php
                                            // Get quiz score details
                                            $quizScore = isset($score['quiz_scores'][$quizId]) ? $score['quiz_scores'][$quizId] : null;
                                            $positiveMarks = isset($quizData[$quizId]['positive']) ? $quizData[$quizId]['positive'] : '-';
                                            $negativeMarks = isset($quizData[$quizId]['negative']) ? $quizData[$quizId]['negative'] : '-';

                                            if ($quizScore) {
                                                // Total mark calculation can be adjusted as per your needs
                                                $totalMarks = htmlspecialchars($quizScore['score']);
                                                $correctAnswers = htmlspecialchars($quizScore['correct']);
                                                $incorrectAnswers = htmlspecialchars($quizScore['incorrect']);
                                                echo "$totalMarks <br>P: $positiveMarks, N: $negativeMarks, <br>Correct: $correctAnswers, <br>Incorrect: $incorrectAnswers";
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customize Export Modal -->
    <div class="modal fade" id="customizeModal" tabindex="-1" aria-labelledby="customizeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customizeModalLabel">Customize Export</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Select Columns:</h6>
                    <div id="columnCheckboxes">
                        <!-- Column checkboxes will be dynamically added here -->
                    </div>
                    <hr>
                    <h6>Select Rows:</h6>
                    <div class="mb-3">
                        <label for="startRow" class="form-label">Start Row:</label>
                        <input type="number" class="form-control" id="startRow" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label for="endRow" class="form-label">End Row:</label>
                        <input type="number" class="form-control" id="endRow" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="applyCustomization">Apply</button>
                </div>
            </div>
        </div>
    </div>


    <script src="../assets/js/jspdf.umd.min.js"></script>
    <script src="../assets/js/jspdf.plugin.autotable.min.js"></script>
    <script src="../assets/js/xlsx.full.min.js"></script>

    <script>
        // Assuming $scores is a PHP variable containing scores data
        let scores = <?php echo json_encode($scores); ?>;
        let selectedColumns = [];
        let startRow = 1;
        let endRow = scores.length;

        console.log(scores); // Check the content of scores

        // Convert scores to an array if it's an object
        if (Array.isArray(scores)) {
            // Already an array, do nothing
        } else if (typeof scores === 'object') {
            // If it's an object, convert it to an array
            scores = Object.values(scores);
        } else {
            console.error('Scores is not an array or object:', scores);
            scores = []; // Assign an empty array if the structure is wrong
        }

        // Initialize the customize modal
        function initializeCustomizeModal() {
            const checkboxesContainer = document.getElementById('columnCheckboxes');
            if (!checkboxesContainer) {
                console.error('Checkboxes container not found');
                return;
            }
            checkboxesContainer.innerHTML = '';

            const columns = [
                'rank',
                'Name',
                'username',
                'college_name',
                'quizzes_taken',
                'total_score'
            ];

            // Add quiz score columns
            if (scores.length > 0) {
                Object.keys(scores[0].quiz_scores).forEach(quizId => {
                    columns.push(`quiz_${quizId}`);
                });
            }

            columns.forEach(col => {
                const checkbox = document.createElement('div');
                checkbox.innerHTML = `<input type="checkbox" value="${col}" checked> ${col.replace('_', ' ').toUpperCase()}`;
                checkboxesContainer.appendChild(checkbox);
            });
        }

        // Apply customizations when the button is clicked
        document.getElementById('applyCustomization').addEventListener('click', function() {
            selectedColumns = Array.from(document.querySelectorAll('#customizeModal input[type="checkbox"]:checked'))
                .map(checkbox => checkbox.value);

            startRow = parseInt(document.getElementById('startRow').value) || 1; // Default to 1
            endRow = parseInt(document.getElementById('endRow').value) || scores.length; // Default to scores.length

            // Validate row range
            if (startRow > endRow || startRow < 1 || endRow > scores.length) {
                alert('Invalid row range');
                return;
            }

            updateTableDisplay();
            $('#customizeModal').modal('hide');
        });

        // Update the table display based on selected columns and row range
        function updateTableDisplay() {
    const table = document.getElementById('rankingsTable');
    const headers = table.querySelectorAll('th');
    const rows = table.querySelectorAll('tbody tr');

    // Show all rows and columns on page load
    if (selectedColumns.length === 0) {
        rows.forEach(row => {
            row.style.display = '';
        });
        headers.forEach(header => {
            header.style.display = '';
        });
    } else {
        // Update header visibility
        headers.forEach((header, index) => {
            const columnKey = header.getAttribute('data-column');
            header.style.display = selectedColumns.includes(columnKey) ? '' : 'none';
        });

        // Update row visibility
        rows.forEach((row, rowIndex) => {
            // Show or hide the row based on the selected row range
            const rowShouldBeVisible = rowIndex >= startRow - 1 && rowIndex < endRow;
            row.style.display = rowShouldBeVisible ? '' : 'none';

            // Update cell visibility based on selected columns
            row.querySelectorAll('td').forEach((cell, cellIndex) => {
                const columnKey = headers[cellIndex].getAttribute('data-column');
                cell.style.display = selectedColumns.includes(columnKey) ? '' : 'none';
            });
        });
    }
}

        function getSelectedData() {
            return scores.slice(startRow - 1, endRow).map(score => {
                const row = {};
                selectedColumns.forEach(col => {
                    if (col.startsWith('quiz_')) {
                        const quizId = col.replace('quiz_', '');
                        row[col] = score.quiz_scores[quizId] ? score.quiz_scores[quizId].score : '-';
                    } else {
                        row[col] = score[col];
                    }
                });
                return row;
            });
        }

        // Export to PDF
        document.getElementById('exportPdf').addEventListener('click', function() {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'mm',
                format: 'a4',
                putOnlyUsedFonts: true
            });

            const currentDate = new Date().toLocaleString();

            // Title and Date
            doc.setFontSize(10);
            doc.text(`Generated on: ${currentDate}`, 14, 10);
            doc.setFontSize(18);
            doc.text('Coddict Rankings', 14, 20);

            const table = document.getElementById('rankingsTable');

            // Get the number of columns in the table
            const columns = table.rows[0].cells.length;

            // Create an object to store the column widths
            const columnWidths = {};

            // Loop through the columns and set the widths dynamically
            for (let i = 0; i < columns; i++) {
                columnWidths[i] = {
                    cellWidth: 'auto'
                }; // or a calculated width based on the column index
            }

            // Using autoTable to create the PDF table
            doc.autoTable({
                html: table,
                startY: 30, // Start below the title
                margin: {
                    top: 10,
                    bottom: 10,
                    left: 10,
                    right: 10
                },
                theme: 'striped',
                styles: {
                    fontSize: 12, // Smaller font size for better fit
                    cellPadding: 2,
                    overflow: 'linebreak', // Line breaks for overflow text
                    minCellHeight: 10,
                },
                columnStyles: columnWidths,
                didParseCell: function(data) {
                    const maxWidth = 30; // Adjust based on your needs
                    if (data.cell.text.length > maxWidth) {
                        data.cell.text[0] = data.cell.text[0].substring(0, maxWidth) + '...'; 
                    }
                },
                didDrawCell: function(data) {
                }
            });
            doc.save(`user_rankings_${formatDate(new Date())}.pdf`);
        });

        document.getElementById('exportExcel').addEventListener('click', () => {
            const table = document.getElementById('rankingsTable');
            const wb = XLSX.utils.table_to_book(table, {
                sheet: "Sheet1"
            }); // Use 'wb' here
            XLSX.writeFile(wb, `rankings_${formatDate(new Date())}.xlsx`); // This should refer to 'wb'
        });



        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');

            return `${year}-${month}-${day}_${hours}-${minutes}-${seconds}`;
        }
        $('#customizeModal').on('show.bs.modal', initializeCustomizeModal);
        updateTableDisplay(); 
    </script>





    <?php include('./components/foot.php'); ?>