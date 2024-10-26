<?php
session_start();
include('./db_con.php');

if (!isset($_SESSION['coddict_uid'])) {
    header("Location: ./login.php");
    exit();
}

$user_id = $_SESSION['coddict_uid']; // Assuming user_id is stored in session

// Fetch only activated quizzes
$query = "SELECT q.quiz_id, q.title, q.allocated_time,q.description, q.total_marks, qa.start_time, qa.finish_time 
          FROM quizzes q 
          LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id AND qa.user_id = '$user_id'
          WHERE q.status = 'enabled'
          ORDER BY q.created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Codeaddicts</title>
    <link rel="shortcut icon" type="image/png" href="./assets/images/logos/favicon.png" />
    <link rel="stylesheet" href="./assets/css/styles.min.css" />
    <script src="./assets/libs/jquery/dist/jquery.min.js"></script>
    <style>
        body {
            background-color: #f0f2f5;
        }

        .app-header {
            background-color: #0d6efd !important;
            width: 100%;
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: bold;
            color: #fff;
            margin-right: auto;
        }

        .tagline {
            font-size: 0.9rem;
            font-weight: 300;
            color: #fff;
            margin-top: -15px;
            margin-left: 3px;
        }

        .navbar-nav .btn {
            margin-left: 10px;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }

        .card-title {
            color: #0d6efd;
            font-weight: bold;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead {
            background-color: #0d6efd;
            color: white;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn {
            border-radius: 20px;
            padding: 0.375rem 1rem;
        }
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .modal-header {
            background-color: #0d6efd;
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1.5rem;
        }
        .modal-title {
            font-weight: bold;
            color: #f0f2f5;
        }
        .modal-body {
            padding: 2rem;
        }
        .quiz-info-card, .rules-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            height: 100%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .quiz-info-card h6, .rules-card h6 {
            color: #0d6efd;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .quiz-info-item {
            margin-bottom: 0.5rem;
        }
        .rules-list {
            padding-left: 1.5rem;
        }
        .rules-list li {
            margin-bottom: 0.5rem;
        }
        .modal-footer {
            border-top: none;
            padding: 1.5rem 2rem;
        }
    </style>
</head>

<body>
    <div class="page-wrapper" id="main-wrapper" data-navbarbg="skin6" data-header-position="fixed">
        <div class="body-wrapper">
            <header class="app-header">
                <nav class="navbar navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <div class="d-flex flex-column">
                            <a class="navbar-brand" href="#">Coddicts</a>
                            <span class="tagline">Just for Code addicts</span>
                        </div>

                        <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
                            <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
                                <span class="text-white">Hello, <?php echo $_SESSION['u_name'] ?></span>
                                <a href="./logout.php" class="btn btn-outline-light mx-3 mt-2 d-block">Logout</a>
                            </ul>
                        </div>
                    </div>
                </nav>
            </header>
            <div class="container mt-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Available Quizzes</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">Title</th>
                                        <th scope="col">Time (Minutes)</th>
                                        <th scope="col">Max Score</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $quiz_id = $row['quiz_id'];
                                        $title = $row['title'];
                                        $time = $row['allocated_time'];
                                        $marks = $row['total_marks'];
                                        $descs = $row['description'];
                                        $start_time = $row['start_time'];
                                        $finish_time = $row['finish_time'];

                                        $current_time = time();
                                        $status = 'Not Started';
                                        $button_class = 'btn-primary';
                                        $button_text = 'Start';
                                        $button_action = "showRules('$quiz_id', '$title', '$time', '$marks','$descs')";
                                        $row_class = '';
                                        if ($finish_time) {
                                            $status = 'Completed';
                                            $button_class = 'btn-secondary disabled';
                                            $button_text = 'Finished';
                                            $row_class = 'table-success';
                                        } elseif ($start_time && !$finish_time) {
                                            $elapsed_time = $current_time - strtotime($start_time);
                                            if ($elapsed_time > $time * 60) {
                                                $status = 'Ended';
                                                $button_class = 'btn-danger disabled';
                                                $button_text = 'Ended';
                                                $row_class = 'table-danger';
                                            } else {
                                                $status = 'In Progress';
                                                $button_class = 'btn-warning';
                                                $button_text = 'Continue';
                                                $button_action = "window.location.href='start_quiz.php?quiz_id=$quiz_id'";
                                                $row_class = 'table-warning';
                                            }
                                        }

                                        echo "<tr class='$row_class'>";
                                        echo "<td>" . htmlspecialchars($title) . "</td>";
                                        echo "<td>$time</td>";
                                        echo "<td>$marks</td>";
                                        echo "<td>$status</td>";
                                        echo "<td><button class='btn $button_class' onclick=\"$button_action\">$button_text</button></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal for quiz rules and confirmation -->
            <div class="modal fade" id="rulesModal" tabindex="-1" aria-labelledby="rulesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rulesModalLabel">Quiz Details & Rules</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="quiz-info-card">
                                <h6>Quiz Information</h6>
                                <div id="quizInfo"></div>
                                <div class="mt-4">
                                    <h6>Description</h6>
                                    <p id="descriptionText" class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rules-card">
                                <h6>Rules</h6>
                                <ul class="rules-list">
                                    <li>No cheating or external assistance allowed.</li>
                                    <li>Timer starts immediately upon clicking 'Start Quiz'.</li>
                                    <li>All questions must be answered.</li>
                                    <li>Ensure to click 'Finish' before the time expires.</li>
                                    <li>Review your answers carefully before submission.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="startQuizBtn" href="#" class="btn btn-primary">Accept & Start Quiz</a>
                </div>
            </div>
        </div>
    </div>

            <script src="./assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
            <script src="./assets/js/sidebarmenu.js"></script>
            <script src="./assets/js/app.min.js"></script>
            <script src="./assets/libs/simplebar/dist/simplebar.js"></script>

            <script>
                function showRules(quiz_id, title, time, marks, desc) {
                    const quizInfo = `
                <strong>Title:</strong> ${title}<br>
                <strong>Time:</strong> ${time} minutes<br>
                <strong>Max Score:</strong> ${marks}
            `;
                    document.getElementById('quizInfo').innerHTML = quizInfo;
                    document.getElementById('descriptionText').innerText = desc || 'No description available.';
                    document.getElementById('startQuizBtn').href = 'start_quiz.php?quiz_id=' + quiz_id;
                    new bootstrap.Modal(document.getElementById('rulesModal')).show();
                }
            </script>
        </div>
    </div>
</body>

</html>