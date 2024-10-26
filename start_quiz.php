<?php
session_start();
include('./db_con.php');
if (!isset($_SESSION['coddict_uid'])) {
    header("Location: ./login.php");
    exit();
}

$user_id = $_SESSION['coddict_uid'];
if (!isset($_GET['quiz_id']) || empty($_GET['quiz_id'])) {
    header("Location: ./");
    exit();
}

$quiz_id = $_GET['quiz_id'];
$quiz_query = "SELECT * FROM quizzes WHERE quiz_id = ?";
$stmt = $conn->prepare($quiz_query);
$stmt->bind_param("s", $quiz_id);
$stmt->execute();
$quiz_result = $stmt->get_result()->fetch_assoc();

if (!$quiz_result) {
    header("Location: ./");
    exit();
}
$attempt_query = "SELECT * FROM quiz_attempts WHERE user_id = ? AND quiz_id = ?";
$stmt = $conn->prepare($attempt_query);
$stmt->bind_param("is", $user_id, $quiz_id);
$stmt->execute();
$attempt_result = $stmt->get_result()->fetch_assoc();

if (!$attempt_result) {
    $start_time = date('Y-m-d H:i:s');
    $start_attempt_query = "INSERT INTO quiz_attempts (user_id, quiz_id, status, start_time,seed) 
                            VALUES (?, ?, 'started', ?,?)";
    $stmt = $conn->prepare($start_attempt_query);
    $randomNumber = random_int(10000000, 99999999);
    $stmt->bind_param("issi", $user_id, $quiz_id, $start_time,$randomNumber);
    $stmt->execute();
    $stmt = $conn->prepare($attempt_query);
    $stmt->bind_param("is", $user_id, $quiz_id);
    $stmt->execute();
    $attempt_result = $stmt->get_result()->fetch_assoc();
}
$current_time = time();
$start_time = strtotime($attempt_result['start_time']);
$time_limit = $quiz_result['allocated_time'] * 60;

if ($attempt_result['status'] === 'completed') {
    header("Location: ./");
    exit();
} elseif (($current_time - $start_time) > $time_limit) {
    $update_query = "UPDATE quiz_attempts SET status = 'completed', finish_time = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $finish_time = date('Y-m-d H:i:s');
    $stmt->bind_param("si", $finish_time, $attempt_result['id']);
    $stmt->execute();
    header("Location: ./");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coddicts-Quiz</title>
    <link rel="shortcut icon" type="image/png" href="./assets/images/logos/favicon.png" />
    <link rel="stylesheet" href="./assets/css/styles.min.css" />
    <style>
        #navigator-container {
            flex: 1;
            padding: 20px;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            max-width: 300px;
        }

        #timer-finish-container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .time-remaining {
            font-size: 1.5rem;
            font-weight: bold;
            color: black;
            text-align: center;
            margin-bottom: 20px;
        }

        #finish-button {
            background-color: #dc3545;
            border-color: #dc3545;
            margin-top: auto;
        }

        #question-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px;
            max-height: 300px;
            overflow-y: auto;
            padding: 1px;
        }

        .question-button {
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
            border-radius: 8px;
            border: 2px solid #007bff;
            background-color: #fff;
            color: #007bff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .question-button:hover {
            background-color: #007bff;
            color: #fff;
        }

        .question-button.answered {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
        }

        .question-button.current {
            border: 3px solid red;
        }


        .navigator-instructions {
            margin-bottom: 15px;
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .indicator-row {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .indicator {
            width: 15px;
            height: 15px;
            display: inline-block;
            border-radius: 4px;
        }

        .green-indicator {
            background-color: #28a745;
        }

        .blue-indicator {
            background-color: #007bff;
        }

        .white-indicator {
            background-color: #fff;
            border: 2px solid #007bff;
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

        #quiz-container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
        }

        #question-container {
            flex: 3;
            padding: 20px;
        }

        #navigator-container {
            flex: 1;
            padding: 20px;
            background-color: #f8f9fa;
        }

        .question-button {
            width: 40px;
            height: 40px;
            margin: 5px;
            position: relative;
        }

        .answered {
            background-color: #28a745;
            color: white;
        }

        .current {
            border: 3px solid #007bff;
        }

        .option-container {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .option-label {
            font-weight: bold;
            margin-right: 10px;
            width: 30px;
        }

        .option-button {
            flex-grow: 1;
            text-align: left;
            color: black;
        }

        .selected {
            background-color: #007bff;
            color: white;
        }

        #question-text {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: black;
        }

        #time-remaining {
            font-size: 1.5rem;
            font-weight: bold;
            color: #dc3545;
        }

        #timer-finish-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        #finish-button {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        #navigator-container {
            flex: 1;
            padding: 20px;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
        }

        #timer-finish-container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .time-remaining {
            font-size: 1.5rem;
            font-weight: bold;
            color: black;
            text-align: center;
            margin-bottom: 20px;
        }

        #finish-button {
            background-color: #dc3545;
            border-color: #dc3545;
            margin-top: auto;
        }

        
        .navigator-instructions {
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .indicator {
            width: 10px;
            height: 10px;
            display: inline-block;
            margin-right: 5px;
        }

        .green-indicator {
            background-color: #28a745;
        }

        .blue-indicator {
            background-color: #fff;
            border: 2px solid red;
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
                            <span class="tagline">Just for Codeaddicts</span>
                        </div>
                        <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
                            <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
                                <span class="text-white" id="user-name"></span>
                                <a href="./logout.php" class="btn btn-outline-primary mx-3 mt-2 d-block text-white">Logout</a>
                            </ul>
                        </div>
                    </div>
                </nav>
            </header>

            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title" id="quiz-title"></h5>
                        <div id="quiz-container">
                            <div id="question-container">
                                <pre id="question-text"></pre>
                                <div id="options-container"></div>
                                <button id="prev-button" class="btn btn-secondary">Previous</button>
                                <button id="next-button" class="btn btn-primary">Next</button>
                                <button id="reset-button" class="btn btn-warning">Reset</button>
                            </div>
                            <div id="navigator-container">
                                <div id="timer-finish-container">
                                    <div class="time-remaining">Time: <span id="time-remaining"></span></div>
                                    <div class="navigator-instructions">
                                        <div class="indicator-row">
                                            <span class="indicator white-indicator"></span>
                                            <span>Unanswered</span>
                                        </div>
                                        <div class="indicator-row">
                                            <span class="indicator green-indicator"></span>
                                            <span>Answered</span>
                                        </div>
                                        <div class="indicator-row">
                                            <span class="indicator blue-indicator"></span>
                                            <span>Current Question</span>
                                        </div>
                                    </div>
                                    <div id="question-buttons"></div>
                                    <button id="finish-button" class="btn btn-danger btn-block">Finish Quiz</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="./assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="./assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/sidebarmenu.js"></script>
    <script src="./assets/js/app.min.js"></script>
    <script src="./assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const quizId = urlParams.get('quiz_id');
            let quizData, currentQuestionIndex = 0;

            // Fetch quiz data
            function fetchQuizData() {
                $.getJSON(`./quiz_api.php?quiz_id=${quizId}`, function(data) {
                    quizData = data;
                    initializeQuiz();
                });
            }

            // Initialize quiz
            function initializeQuiz() {
                startTimer(quizData.remaining_time);
                $('#quiz-title').text(quizData.quiz.title);
                $('#user-name').text(`Hello, ${quizData.quiz.u_name}`);
                createQuestionNavigator();
                showQuestion(0);
            }

            // Shuffle options
            function shuffleOptions(question) {
                const options = ['A', 'B', 'C', 'D'];
                const shuffledOptions = options.sort(() => Math.random() - 0.5);
                const shuffledQuestion = {
                    ...question
                };

                const originalOptions = {};
                options.forEach(option => {
                    if (question[`option_${option.toLowerCase()}`]) {
                        originalOptions[option] = question[`option_${option.toLowerCase()}`];
                    }
                });

                shuffledOptions.forEach((newOption, index) => {
                    const oldOption = options[index];
                    if (originalOptions[oldOption]) {
                        shuffledQuestion[`option_${newOption.toLowerCase()}`] = originalOptions[oldOption];
                    } else {
                        delete shuffledQuestion[`option_${newOption.toLowerCase()}`];
                    }
                });

                return shuffledQuestion;
            }
            // Function to store the current selected question
            function storeCurrentQuestion(questionId, selectedAnswer) {
                // Assuming you have a quizData object to store the current selected question
                quizData.answered_questions[questionId] = selectedAnswer;
            }
            // Create question navigator
            function createQuestionNavigator() {
                const $navigatorContainer = $('#question-buttons');
                $navigatorContainer.empty();

                $.each(quizData.questions, function(index, question) {
                    const $button = $('<button>')
                        .text(index + 1)
                        .addClass('question-button')
                        .on('click', function() {
                            showQuestion(index);
                        });

                    if (quizData.answered_questions[question.question_id]) {
                        $button.addClass('answered');
                    }
                    $navigatorContainer.append($button);
                });

                updateQuestionNavigator();
            }

            function updateQuestionNavigator() {
                $('.question-button').removeClass('current');
                $('.question-button').eq(currentQuestionIndex).addClass('current');
            }

            // Show question
            function showQuestion(index) {
                currentQuestionIndex = index;
                const question = shuffleOptions(quizData.questions[index]);
                $('#question-text').text(`Question ${index + 1}:\n ${question.question_text}`);
                const $optionsContainer = $('#options-container').empty();

                $.each(['A', 'B', 'C', 'D'], function(_, option) {
                    if (question[`option_${option.toLowerCase()}`]) {
                        const $optionContainer = $('<div>').addClass('option-container');
                        const $label = $('<span>').addClass('option-label').text(option);
                        const $button = $('<button>')
                            .html('<pre>' + question[`option_${option.toLowerCase()}`].trim() + '</pre>')
                            .addClass('btn btn-outline-secondary option-button')
                            .on('click', function() {
                                storeCurrentQuestion(quizData.questions[index].question_id, option);
                                selectAnswer(quizData.questions[index].question_id, option);
                            });

                        if (quizData.answered_questions[quizData.questions[index].question_id] === option) {
                            $button.addClass('selected');
                        }
                        $optionContainer.append($label, $button);
                        $optionsContainer.append($optionContainer);
                    }
                });

                // Apply syntax highlighting to code in options
                $('pre code').each(function(i, block) {
                    hljs.highlightBlock(block);
                });

                updateNavigationButtons();
                updateQuestionNavigator();
            }

            // Select answer
            function selectAnswer(questionId, selectedAnswer) {
                $.ajax({
                    url: './quiz_api.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        quiz_id: quizId,
                        question_id: questionId,
                        selected_answer: selectedAnswer
                    }),
                    success: function(result) {
                        if (result.success) {
                            quizData.answered_questions[questionId] = selectedAnswer;
                            updateQuestionButton(currentQuestionIndex);
                            showQuestion(currentQuestionIndex);
                        }
                    }
                });
            }

            // Update question button
            function updateQuestionButton(index) {
                $('.question-button').eq(index).addClass('answered');
            }

            // Update navigation buttons
            function updateNavigationButtons() {
                $('#prev-button').prop('disabled', currentQuestionIndex === 0);
                $('#next-button').prop('disabled', currentQuestionIndex === quizData.questions.length - 1);
            }

            // Update question navigator
            function updateQuestionNavigator() {
                $('.question-button').removeClass('current');
                $('.question-button').eq(currentQuestionIndex).addClass('current');
            }

            // Reset answer
            function resetAnswer() {
                const questionId = quizData.questions[currentQuestionIndex].question_id;
                $.ajax({
                    url: './quiz_api.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        quiz_id: quizId,
                        question_id: questionId,
                        action: 'reset'
                    }),
                    success: function(result) {
                        if (result.success) {
                            delete quizData.answered_questions[questionId];
                            $('.question-button').eq(currentQuestionIndex).removeClass('answered');
                            showQuestion(currentQuestionIndex);
                        } else {
                            alert('Failed to reset answer: ' + result.error);
                        }
                    }
                });
            }

            // Reset button click handler
            $('#reset-button').on('click', function() {
                if (confirm('Are you sure you want to reset your answer for this question?')) {
                    resetAnswer();
                }
            });

            // Navigation button handlers
            $('#prev-button').on('click', function() {
                if (currentQuestionIndex > 0) {
                    showQuestion(currentQuestionIndex - 1);
                }
            });

            $('#next-button').on('click', function() {
                if (currentQuestionIndex < quizData.questions.length - 1) {
                    showQuestion(currentQuestionIndex + 1);
                }
            });

            // Finish quiz
            $('#finish-button').on('click', function() {
                if (confirm('Are you sure you want to finish the quiz?')) {
                    $.ajax({
                        url: './quiz_api.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            quiz_id: quizId,
                            action: 'finish'
                        }),
                        success: function(result) {
                            if (result.success) {
                                alert('Quiz submitted successfully!');
                                window.location.href = `./`;
                            } else {
                                alert('Failed to submit quiz: ' + result.message);
                            }
                        }
                    });
                }
            });

            // Start timer
            function startTimer(duration) {
                let timer = duration;
                const $timeRemaining = $('#time-remaining');

                const interval = setInterval(function() {
                    const minutes = Math.floor(timer / 60);
                    const seconds = timer % 60;

                    $timeRemaining.text(`${minutes}:${seconds.toString().padStart(2, '0')}`);

                    if (--timer < 0) {
                        clearInterval(interval);
                        $.ajax({
                            url: './quiz_api.php',
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                quiz_id: quizId,
                                action: 'finish'
                            }),
                            success: function(result) {
                                if (result.success) {
                                    alert('Quiz submitted successfully!');
                                    window.location.href = `./`;
                                } else {
                                    alert('Failed to submit quiz: ' + result.message);
                                }
                            }
                        });
                    }
                }, 1000);
            }

            // Initialize the quiz
            fetchQuizData();
        });
    </script>


</body>

</html>