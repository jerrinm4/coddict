<?php
include('./components/head.php');
include('../db_con.php');

$query = "SELECT * FROM quizzes";
$result = $conn->query($query);

if ($result === false) {
    die("Error executing query: " . $conn->error);
}
?>
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title fw-semibold mb-4">Quizzes List</h5>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createQuizModal">Add Quiz</button>
            <div class="table-responsive">
                <table class="table text-nowrap mb-0 align-middle">
                    <thead class="text-dark fs-4 table-primary">
                        <tr>
                            <th>Quiz ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Allocated Time (min)</th>
                            <th>Total Marks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<tr data-id="' . htmlspecialchars($row['quiz_id']) . '">';
        echo '<td>' . htmlspecialchars($row['quiz_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['title']) . '</td>';
        
        // Use color codes for status
        $statusColor = $row['status'] == 'enabled' ? 'success' : 'danger';
        echo '<td><span class="badge bg-' . $statusColor . '">' . htmlspecialchars($row['status']) . '</span></td>';
        
        echo '<td>' . htmlspecialchars($row['allocated_time']) . '</td>';
        echo '<td>' . htmlspecialchars($row['total_marks']) . '</td>';
        
        echo '<td>';
        // Add buttons without icons and proper alignment
        echo '<div class="d-flex flex-column">';
        echo '<div class="mb-1">';
        echo '<button class="btn btn-warning btn-sm edit-btn me-1" data-id="' . htmlspecialchars($row['quiz_id']) . '" data-title="' . htmlspecialchars($row['title']) . '" data-description="' . htmlspecialchars($row['description']) . '" data-status="' . htmlspecialchars($row['status']) . '" data-allocated_time="' . htmlspecialchars($row['allocated_time']) . '" data-total_marks="' . htmlspecialchars($row['total_marks']) . '" data-bs-toggle="modal" data-bs-target="#editQuizModal">Edit</button>';
        echo '<button class="btn btn-danger btn-sm delete-btn me-1" data-id="' . htmlspecialchars($row['quiz_id']) . '" data-title="' . htmlspecialchars($row['title']) . '">Delete</button>';
        
        // Change toggle button color based on current status
        $toggleButtonColor = $row['status'] == 'enabled' ? 'btn-secondary' : 'btn-success';
        echo '<button class="btn ' . $toggleButtonColor . ' btn-sm toggle-btn" data-id="' . htmlspecialchars($row['quiz_id']) . '" data-status="' . htmlspecialchars($row['status']) . '">' . ($row['status'] == 'enabled' ? 'Disable' : 'Enable') . '</button>';
        echo '</div>'; // Closing the first div

        // Add a new button to redirect to the edit quiz questions page below the others
        echo '<div class="mt-1">';
        echo '<a href="edit_quiz_questions.php?quiz_id=' . htmlspecialchars($row['quiz_id']) . '" class="btn btn-primary btn-sm">Edit Questions</a>';
        echo '</div>'; // Closing the second div

        echo '</div>'; // Closing the flex column div
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="7" class="text-center">No quizzes found.</td></tr>';
}
?>


                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Quiz Modal -->
<div class="modal fade" id="createQuizModal" tabindex="-1" aria-labelledby="createQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createQuizForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="createQuizModalLabel">Create Quiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="quizTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="quizTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="quizDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="quizDescription"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="quizStatus" class="form-label">Status</label>
                        <select class="form-select" id="quizStatus" required>
                            <option value="enabled">Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="allocatedTime" class="form-label">Allocated Time (min)</label>
                        <input type="number" class="form-control" id="allocatedTime" required>
                    </div>
                    <div class="mb-3">
                        <label for="totalMarks" class="form-label">Total Marks</label>
                        <input type="number" class="form-control" id="totalMarks" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Quiz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Quiz Modal -->
<div class="modal fade" id="editQuizModal" tabindex="-1" role="dialog" aria-labelledby="editQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editQuizModalLabel">Edit Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editQuizForm">
                    <input type="hidden" id="editQuizId" name="quiz_id">
                    <div class="mb-3">
                        <label for="editQuizTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="editQuizTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editQuizDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editQuizDescription" name="description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editQuizStatus" class="form-label">Status</label>
                        <select class="form-select" id="editQuizStatus" name="status" required>
                            <option value="enabled">Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editAllocatedTime" class="form-label">Allocated Time (min)</label>
                        <input type="number" class="form-control" id="editAllocatedTime" name="allocated_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTotalMarks" class="form-label">Total Marks</label>
                        <input type="number" class="form-control" id="editTotalMarks" name="total_marks" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle the Create Quiz Form submission
    $('#createQuizForm').on('submit', function(event) {
        event.preventDefault();
        const data = {
            action: 'create',
            title: $('#quizTitle').val(),
            description: $('#quizDescription').val(),
            status: $('#quizStatus').val(),
            allocated_time: $('#allocatedTime').val(),
            total_marks: $('#totalMarks').val()
        };

        $.post('./admin_quiz_api.php', data, function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                location.reload();
            } else {
                alert(res.message);
            }
        });
    });

    // Handle the Edit Quiz Form submission
    $('#editQuizForm').on('submit', function(event) {
        event.preventDefault();
        const data = {
            action: 'edit',
            quiz_id: $('#editQuizId').val(),
            title: $('#editQuizTitle').val(),
            description: $('#editQuizDescription').val(),
            status: $('#editQuizStatus').val(),
            allocated_time: $('#editAllocatedTime').val(),
            total_marks: $('#editTotalMarks').val()
        };

        $.post('./admin_quiz_api.php', data, function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                location.reload();
            } else {
                alert(res.message);
            }
        });
    });

    // Handle Delete Quiz Button Click
    $(document).on('click', '.delete-btn', function() {
    const quizId = $(this).data('id');
    const quizTitle = $(this).data('title');

    // Create a custom confirmation modal
    const confirmationText = prompt(`Type "delete" to confirm deleting the quiz "${quizTitle}":`);

    if (confirmationText === 'delete') {
        const data = {
            action: 'delete',
            quiz_id: quizId
        };
        $.post('./admin_quiz_api.php', data, function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                location.reload(); // Reload the page to reflect the deletion
            } else {
                alert(res.message);
            }
        });
    } else {
        alert('Deletion canceled. Please type "delete" to confirm.');
    }
});

    // Toggle Quiz Status
    $(document).on('click', '.toggle-btn', function() {
        const quizId = $(this).data('id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus === 'enabled' ? 'disabled' : 'enabled';

        const data = {
            action: 'toggle_status',
            quiz_id: quizId,
            status: newStatus
        };
        $.post('./admin_quiz_api.php', data, function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                location.reload();
            } else {
                alert(res.message);
            }
        });
    });

    // Pre-fill the edit form when editing a quiz
    $(document).on('click', '.edit-btn', function() {
        $('#editQuizId').val($(this).data('id'));
        $('#editQuizTitle').val($(this).data('title'));
        $('#editQuizDescription').val($(this).data('description'));
        $('#editQuizStatus').val($(this).data('status'));
        $('#editAllocatedTime').val($(this).data('allocated_time'));
        $('#editTotalMarks').val($(this).data('total_marks'));
    });
});
</script>

<?php
include('./components/foot.php');
?>