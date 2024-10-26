<?php
include('./components/head.php');

// Initialize pagination variables
$limit = 25; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$searchTerm = '';
if (isset($_GET['search'])) {
  $searchTerm = $_GET['search'];
}

// Prepare the query with search and pagination
$query = "SELECT id, name, college, u_name, passwd FROM user 
          WHERE name LIKE ? OR u_name LIKE ?
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$searchWildcard = '%' . $searchTerm . '%';
$stmt->bind_param('ssii', $searchWildcard, $searchWildcard, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
  die("Error executing query: " . $conn->error); // Output the error message and stop the script
}

// Count total records for pagination
$countQuery = "SELECT COUNT(*) AS total FROM user WHERE name LIKE ? OR u_name LIKE ?";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param('ss', $searchWildcard, $searchWildcard);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);
?>
<div class="container-fluid">
  <div class="col-lg-12 d-flex align-items-stretch">
    <div class="card w-100">
      <div class="card-body p-4">
        <h5 class="card-title fw-semibold mb-4">Users List</h5>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#importUsersModal">Import Users</button>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>


          <!-- Search Form -->
          <form method="GET" class="d-flex flex-grow-1 ms-3"> <!-- Added d-flex and flex-grow-1 -->
            <input type="text" name="search" class="form-control me-2" placeholder="Search by Name or Username" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit" class="btn btn-secondary me-2">Search</button>
            <button type="button" class="btn btn-outline-secondary" id="resetButton">Reset</button>
          </form>

          <script>
            document.getElementById('resetButton').addEventListener('click', function() {
              const searchInput = document.querySelector('input[name="search"]');
              searchInput.value = ''; // Clear the input field
              this.form.submit(); // Submit the form to reload the page
            });
          </script>


        </div>


        <div class="table-responsive">
          <table class="table text-nowrap mb-0 align-middle">
            <thead class="text-dark fs-4 table-primary">
              <tr>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Id</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Name</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">College</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Username</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Password</h6>
                </th>
                <th class="border-bottom-0">
                  <h6 class="fw-semibold mb-0">Action</h6>
                </th>
              </tr>
            </thead>
            <?php
            if ($result->num_rows > 0) {
              echo '<tbody>'; // Start table body
              // Fetch rows
              while ($row = $result->fetch_assoc()) {
                echo '<tr data-id="' . htmlspecialchars($row['id']) . '">';
                echo '<td class="border-bottom-0"><h6 class="fw-semibold mb-0">' . htmlspecialchars($row['id']) . '</h6></td>';
                echo '<td class="border-bottom-0"><h6 class="fw-semibold mb-1">' . htmlspecialchars($row['name']) . '</h6></td>';
                echo '<td class="border-bottom-0"><p class="fw-semibold mb-0 fs-4">' . htmlspecialchars($row['college']) . '</p></td>';
                echo '<td class="border-bottom-0"><span class="badge bg-primary rounded-3 fw-semibold">' . htmlspecialchars($row['u_name']) . '</span></td>';
                echo '<td class="border-bottom-0"><h6 class="fw-semibold mb-0 fs-4">' . htmlspecialchars($row['passwd']) . '</h6></td>';
                echo '<td class="border-bottom-0">';
                echo '<div class="d-flex justify-content-start align-items-center gap-2">';
                echo '<button class="btn btn-warning btn-sm edit-btn" 
                data-id="' . htmlspecialchars($row['id']) . '" 
                data-name="' . htmlspecialchars($row['name']) . '" 
                data-college="' . htmlspecialchars($row['college']) . '" 
                data-username="' . htmlspecialchars($row['u_name']) . '" 
                data-password="' . htmlspecialchars($row['passwd']) . '" 
                data-bs-toggle="modal" 
                data-bs-target="#editUserModal">
                <i class="ti ti-edit"></i> Edit
        </button>';
                echo '<button class="btn btn-danger btn-sm delete-btn" 
                data-id="' . htmlspecialchars($row['id']) . '" 
                data-name="' . htmlspecialchars($row['name']) . '">
                <i class="ti ti-trash"></i> Delete
        </button>';
                echo '<button class="btn btn-primary btn-sm viewexam-btn" data-id="' . htmlspecialchars($row['id']) . '"><i class="ti ti-files"></i> View Exams</button>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
              }
              echo '</tbody>'; // End table body
            } else {
              echo '<tbody><tr><td colspan="6" class="text-center">No records found.</td></tr></tbody>';
            }
            ?>
          </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
          <ul class="pagination justify-content-center mt-4">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editUserForm">
          <input type="hidden" id="userId" name="userId" required>
          <div class="mb-3">
            <label for="userName" class="form-label">Name</label>
            <input type="text" class="form-control" id="userName" name="userName" required>
          </div>
          <div class="mb-3">
            <label for="userCollege" class="form-label">College</label>
            <input type="text" class="form-control" id="userCollege" name="userCollege" required>
          </div>
          <div class="mb-3">
            <label for="userUsername" class="form-label">Username</label>
            <input type="text" class="form-control" id="userUsername" name="userUsername" required>
          </div>
          <div class="mb-3">
            <label for="userPassword" class="form-label">Password</label>

            <div class="input-group">
              <input type="text" class="form-control" id="userPassword" name="userPassword" required>
              <button type="button" class="btn btn-outline-secondary" id="copyUsernameButtonEdit">Copy Username</button>
            </div>


          </div>
          <button type="submit" class="btn btn-primary">Update User</button>
        </form>
      </div>
    </div>
  </div>
</div>



<!-- Import Users Modal -->
<div class="modal fade" id="importUsersModal" tabindex="-1" aria-labelledby="importUsersModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importUsersModalLabel">Import Users</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="importUsersForm">
          <div class="mb-3">
            <label for="excelFile" class="form-label">Select Excel File</label>
            <input type="file" class="form-control" id="excelFile" accept=".xlsx, .xls" required>
          </div>
          <button type="submit" class="btn btn-primary">Import</button>
        </form>
        <div id="importStatus" class="mt-3"></div>
        <div class="mt-4">
          <h6>Excel Format:</h6>
          <p>Please use the following column headers in your Excel file:</p>
          <ul>
            <li>Name</li>
            <li>College</li>
            <li>Username</li>
          </ul>
          <p>Note: The password will be set to the same value as the username by default.</p>
          <a href="./egexcel/user_import_template.xlsx" download class="btn btn-outline-secondary">Download Template</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addUserForm">
          <div class="mb-3">
            <label for="newUserName" class="form-label">Name</label>
            <input type="text" class="form-control" id="newUserName" name="userName" required>
          </div>
          <div class="mb-3">
            <label for="newUserCollege" class="form-label">College</label>
            <input type="text" class="form-control" id="newUserCollege" name="userCollege" required>
          </div>
          <div class="mb-3">
            <label for="newUserUsername" class="form-label">Username</label>
            <input type="text" class="form-control" id="newUserUsername" name="userUsername" required>
          </div>
          <div class="mb-3">
            <label for="newUserPassword" class="form-label">Password</label>
            <div class="input-group">
              <input type="text" class="form-control" id="newUserPassword" name="userPassword" required>
              <button type="button" class="btn btn-outline-secondary" id="copyUsernameButton">Copy Username</button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Add User</button>
        </form>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="viewExamModal" tabindex="-1" aria-labelledby="viewExamModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewExamModalLabel">Exam Attempts</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive"> <!-- Added responsive table wrapper -->
          <table class="table table-bordered table-striped" id="examAttemptsTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Quiz ID</th>
                <th>Start Time</th>
                <th>Finish Time</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <!-- Attempt rows will be injected here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>


<script src="../assets/js/xlsx.full.min.js"></script>
<script>
  $(document).ready(function() {
    // Event listener for edit buttons
    $('.edit-btn').on('click', function() {
      const userId = $(this).data('id');
      const userName = $(this).data('name');
      const userCollege = $(this).data('college');
      const userUsername = $(this).data('username');
      const passwd = $(this).data('password');

      $('#userId').val(userId);
      $('#userName').val(userName);
      $('#userCollege').val(userCollege);
      $('#userUsername').val(userUsername);
      $('#userPassword').val(passwd);
    });

    // Handle the update form submission
    $('#editUserForm').on('submit', function(e) {
      e.preventDefault();

      $.ajax({
        url: 'admin_api.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
          if (response.status === 'success') {
            const updatedRow = `
            <tr data-id="${response.data.id}">
              <td class="border-bottom-0"><h6 class="fw-semibold mb-0">${response.data.id}</h6></td>
              <td class="border-bottom-0"><h6 class="fw-semibold mb-1">${response.data.name}</h6></td>
              <td class="border-bottom-0"><p class="fw-semibold mb-0 fs-4">${response.data.college}</p></td>
              <td class="border-bottom-0"><span class="badge bg-primary rounded-3 fw-semibold">${response.data.username}</span></td>
              <td class="border-bottom-0"><h6 class="fw-semibold mb-0 fs-4">${response.data.passwd}</h6></td>
              <td class="border-bottom-0">
                <button class="btn btn-warning btn-sm edit-btn" data-id="${response.data.id}" data-name="${response.data.name}" data-college="${response.data.college}" data-username="${response.data.username}" data-bs-toggle="modal" data-bs-target="#editUserModal">Edit</button>
              </td>
            </tr>`;

            $('tr[data-id="' + response.data.id + '"]').replaceWith(updatedRow);
            $('#editUserModal').modal('hide');
          } else {
            alert(response.message);
          }
        },
        error: function() {
          alert('An error occurred. Please try again.');
        }
      });
    });

    // Handle the add user form submission
    $('#addUserForm').on('submit', function(e) {
      e.preventDefault();

      console.log("Add user form submitted");
      console.log("Form data:", $(this).serialize());

      $.ajax({
        url: 'admin_api.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json', // Expect JSON response
        success: function(response) {
          console.log("Server response:", response);

          if (response.status === 'success') {
            console.log("User added successfully");
            const newRow = `
            <tr data-id="${response.data.id}">
              <td class="border-bottom-0"><h6 class="fw-semibold mb-0">${response.data.id}</h6></td>
              <td class="border-bottom-0"><h6 class="fw-semibold mb-1">${response.data.name}</h6></td>
              <td class="border-bottom-0"><p class="fw-semibold mb-0 fs-4">${response.data.college}</p></td>
              <td class="border-bottom-0"><span class="badge bg-primary rounded-3 fw-semibold">${response.data.username}</span></td>
              <td class="border-bottom-0"><h6 class="fw-semibold mb-0 fs-4">${response.data.username}</h6></td>
              <td class="border-bottom-0">
                <button class="btn btn-warning btn-sm edit-btn" data-id="${response.data.id}" data-name="${response.data.name}" data-college="${response.data.college}" data-username="${response.data.username}" data-bs-toggle="modal" data-bs-target="#editUserModal">Edit</button>
              </td>
            </tr>`;

            $('table tbody').append(newRow);
            $('#addUserModal').modal('hide'); // Close the modal
            $('#addUserForm')[0].reset(); // Reset the form
            alert('User added successfully!'); // Show success message
          } else {
            console.error("Failed to add user:", response.message);
            alert('Failed to add user: ' + response.message);
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.error("AJAX error:", textStatus, errorThrown);
          console.log("Response Text:", jqXHR.responseText);
          alert('An error occurred while adding the user. Please check the console for details.');
        }
      });
    });
    $('.viewexam-btn').on('click', function(e) {
      e.preventDefault();

      $.ajax({
        url: 'admin_api.php',
        type: 'POST',
        data: {
          viewexamid: $(this).data('id')
        },
        success: function(response) {
          if (response.status === 'success') {
            const attempts = response.data;
            let attemptsHtml = '';

            // Loop through each attempt to create table rows
            attempts.forEach(attempt => {
              attemptsHtml += `
            <tr>
              <td>${attempt.id}</td>
              <td>${attempt.quiz_id}</td>
              <td>${attempt.start_time}</td>
              <td>${attempt.finish_time}</td>
              <td>${attempt.status}</td>
             <td>
  <a href="./answersheet.php?quiz_id=${attempt.quiz_id}&user_id=${attempt.user_id}">
    <button class="btn btn-info btn-sm">View Answer Sheet</button>
  </a>
</td>

            </tr>
          `;
            });

            // Populate the table body with the generated HTML
            $('#examAttemptsTable tbody').html(attemptsHtml);

            // Show the modal
            $('#viewExamModal').modal('show');
          } else {
            alert(response.message);
          }
        },
        error: function() {
          alert('An error occurred. Please try again.');
        }
      });
    });


    // Function to copy username to password field
    function copyUsernameToPassword(usernameFieldId, passwordFieldId) {
      const username = $(usernameFieldId).val();
      $(passwordFieldId).val(username);
    }

    // Event listeners for copy username buttons
    $('#copyUsernameButton').on('click', function() {
      copyUsernameToPassword('#newUserUsername', '#newUserPassword');
    });

    $('#copyUsernameButtonEdit').on('click', function() {
      copyUsernameToPassword('#userUsername', '#userPassword');
    });

    // Delete user functionality
    $('.delete-btn').on('click', function() {
      const userId = $(this).data('id');
      const userName = $(this).data('name');

      const confirmed = confirm(`Are you sure you want to delete user "${userName}"?`);
      if (confirmed) {
        $.ajax({
          url: 'admin_api.php',
          type: 'POST',
          data: {
            deluserid: userId
          },
          success: function(response) {
            if (response.status === 'success') {
              $(`tr[data-id="${userId}"]`).remove();
            } else {
              alert('Error deleting user: ' + response.message);
            }
          },
          error: function() {
            alert('An error occurred while deleting the user.');
          }
        });
      }
    });

    // Import users functionality
    $('#importUsersForm').on('submit', function(e) {
      e.preventDefault();
      const file = $('#excelFile')[0].files[0];
      const reader = new FileReader();

      reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {
          type: 'array'
        });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        const jsonData = XLSX.utils.sheet_to_json(worksheet);

        importUsers(jsonData);
      };

      reader.readAsArrayBuffer(file);
    });

    function importUsers(users) {
      let imported = 0;
      let failed = 0;
      const totalUsers = users.length;

      function updateStatus() {
        $('#importStatus').html(`Imported: ${imported}, Failed: ${failed}, Total: ${totalUsers}`);
      }

      function importUser(user) {
        if (imported + failed >= totalUsers) {
          updateStatus();
          return;
        }

        $.ajax({
          url: 'admin_api.php',
          type: 'POST',
          data: {
            action: 'importUser',
            userName: user.name,
            userCollege: user.college,
            userUsername: user.username,
            userPassword: user.username
          },
          success: function(response) {
            if (response.status === 'success') {
              imported++;
            } else {
              failed++;
            }
            updateStatus();
            importUser(users[imported + failed]);
          },
          error: function() {
            failed++;
            updateStatus();
            importUser(users[imported + failed]);
          }
        });
      }

      importUser(users[0]);
    }
  });
</script>
<?php
include('./components/foot.php');
?>