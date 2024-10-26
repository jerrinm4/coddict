<?php
session_start();

// Check if admin is already logged in
if (isset($_SESSION['user'])) {
  header('Location: ./');
  exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Check if it's an AJAX request
  if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
  } else {
    $data = $_POST;
  }

  if (isset($data['username']) && isset($data['password'])) {
    include('./db_con.php');
    $username = mysqli_real_escape_string($conn, $data['username']);
    $password = mysqli_real_escape_string($conn, $data['password']);
    $query = "SELECT * FROM user WHERE u_name='$username'";
    $result = $conn->query($query);
    
    if ($result === false) {
      $error_message = "Database error";
    } elseif ($result->num_rows == 1) {
      $row = $result->fetch_assoc();
      if ($password === $row['passwd']) {
        // If password matches, start session
        $_SESSION['coddict_uid'] = $row['id'];
        $_SESSION['user'] = $username;
        $_SESSION['u_name'] = $row['name'];
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
          echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
          header('Location: ./');
        }
        exit();
      } else {
        $error_message = "Invalid username or password";
      }
    } else {
      $error_message = "Invalid username or password";
    }
    $conn->close();
  } else {
    $error_message = "Username and password are required";
  }

  if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit();
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Coddict Login</title>
  <link rel="shortcut icon" type="image/png" href="./assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="./assets/css/styles.min.css" />
  <style>
    body {
      background-image: url('./assets/images/bg.jpeg');
      background-size: cover;
      background-position: center;
      height: 100vh;
      margin: 0;
    }
    .glass {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(5px);
      -webkit-backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    #particles-js {
      position: absolute;
      width: 100%;
      height: 100%;
    }

    .login-container {
      position: relative;
      right: 0;
      margin-right: 5%;
      width: 500px;
    }

    .card {
      background-color: transparent;
      padding: 20px;
      border-radius: 16px;
    }

    .card-body {
      padding: 20px;
    }

    .form-label {
      color: #fff;
      font-weight: bold;
    }

    .form-control {
      background-color: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 10px;
      border-radius: 5px;
      color: #fff;
    }

    .form-control:focus {
      background-color: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.5);
      box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
      color: #fff;
    }

    .btn-primary {
      background-color: rgba(51, 51, 51, 0.7);
      color: #fff;
      padding: 10px 20px;
      border-radius: 5px;
      border: none;
      transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
      background-color: rgba(68, 68, 68, 0.8);
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
      background: rgba(255, 255, 255, 0.2);
      margin: 15% auto;
      padding: 20px;
      border: 1px solid rgba(255, 255, 255, 0.3);
      width: 300px;
      border-radius: 16px;
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(5px);
      -webkit-backdrop-filter: blur(5px);
      color: #fff;
      text-align: center;
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
    }

    .close:hover,
    .close:focus {
      color: #fff;
      text-decoration: none;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <div id="particles-js"></div>

  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <div class="position-relative min-vh-100 d-flex align-items-center justify-content-end">
      <div class="login-container glass">
        <div class="card mb-0 glass">
          <div class="card-body">
            <h1 style="color: #fff; text-align: center;">Coddict Candidate</h1>
            <p class="text-center" style="color: #eee;">Just For Code Addicts</p>
            <form id="loginForm">
              <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" class="form-control" id="username" required>
              </div>
              <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="password" required>
              </div>
              <button class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2" type="submit">Sign In</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="errorModal" class="modal">
    <div class="modal-content glass">
      <span class="close">&times;</span>
      <p id="errorMessage"></p>
    </div>
  </div>

  <script src="./assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="./assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="./assets/js/particles.min.js"></script>
  <script>
    particlesJS("particles-js", {
      particles: {
        number: { value: 310, density: { enable: true, value_area: 800 } },
        color: { value: "#fff" },
        shape: { type: "circle", stroke: { width: 0, color: "#000000" }, polygon: { nb_sides: 5 } },
        opacity: { value: .7, random: true, anim: { enable: false, speed: .6, opacity_min: 0.1, sync: false } },
        size: { value: 3, random: true, anim: { enable: false } },
        line_linked: { enable: false },
        move: {
          enable: true, speed: .6, direction: "bottom", random: false, straight: false, out_mode: "out", bounce: false,
          attract: { enable: false, rotateX: 600, rotateY: 1200 }
        }
      },
      retina_detect: false
    });

    // Login form submission
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const username = document.getElementById('username').value;
      const password = document.getElementById('password').value;

      // API call for login
      fetch(window.location.href, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ username, password }),
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          window.location.href = './';  // Redirect to dashboard on success
        } else {
          showErrorModal(data.message || 'Invalid username or password');
        }
      })
      .catch(error => {
        showErrorModal('An error occurred. Please try again.');
      });
    });

    // Error modal functionality
    const modal = document.getElementById("errorModal");
    const span = document.getElementsByClassName("close")[0];
    const errorMessage = document.getElementById("errorMessage");

    function showErrorModal(message) {
      errorMessage.textContent = message;
      modal.style.display = "block";
    }

    span.onclick = function() {
      modal.style.display = "none";
    }

    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }

    <?php if ($error_message): ?>
    showErrorModal(<?php echo json_encode($error_message); ?>);
    <?php endif; ?>
    
  </script>
</body>

</html>