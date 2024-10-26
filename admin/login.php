<?php
session_start();

// Check if admin is already logged in
if (isset($_SESSION['coddict_admin'])) {
  header('Location: ./');
  exit();
}

if (isset($_POST['username'])) {
  include('../db_con.php');
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $password = mysqli_real_escape_string($conn, $_POST['password']);
  $query = "SELECT * FROM admin WHERE u_name='$username'";
  $result = $conn->query($query);
  if ($result === false) {
    die("Error executing query: " . $conn->error);
  }
  if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    if ($password === $row['passwd']) {
      // If password matches, start session
      $_SESSION['coddict_admin'] = $username;
      header('Location: ./');
      exit();
    } else {
      // If password doesn't match
      echo "<script>alert('Invalid username or password!');</script>";
    }
  } else {
    // If no user found
    echo "<script>alert('Invalid username or password!');</script>";
  }
  $conn->close();
}

// Close the database connection

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Coddict Login</title>
  <link rel="shortcut icon" type="image/png" href="../assets/images/logos/favicon.png" />
  <link rel="stylesheet" href="../assets/css/styles.min.css" />
  <style>
    body {
      background-image: url('../assets/images/bg.jpeg');
      background-size: cover;
      background-position: center;
      height: 100vh;
      margin: 0;
    }

    #particles-js {
      position: absolute;
      width: 100%;
      height: 100%;
      /* z-index: -1; */
    }

    .login-container {
      position: relative;
      right: 0;
      margin-right: 5%;
      width: 500px;
      /* Set a width for your login form */
    }

    .card {
      background-color: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .card-body {
      padding: 20px;
    }

    .form-label {
      color: #333;
      font-weight: bold;
    }

    .form-control {
      background-color: #f7f7f7;
      border: 1px solid #ddd;
      padding: 10px;
      border-radius: 5px;
    }

    .form-control:focus {
      background-color: #fff;
      border-color: #aaa;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .btn-primary {
      background-color: #333;
      color: #fff;
      padding: 10px 20px;
      border-radius: 5px;
      border: none;
    }

    .btn-primary:hover {
      background-color: #444;
    }
  </style>
</head>

<body>
  <div id="particles-js"></div> <!-- Corrected container element -->

  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <div class="position-relative min-vh-100 d-flex align-items-center justify-content-end">
      <div class="login-container">
        <div class="card mb-0">
          <div class="card-body">
            <h1 style="color: #333; text-align: center;">Coddict Admin</h1> <!-- Centered h1 -->
            <p class="text-center" style="color: #666;">Just For Code Addicts</p>
            <form method="POST">
              <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Username</label>
                <input type="text" name="username" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" required>
              </div>
              <div class="mb-4">
                <label for="exampleInputPassword1" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="exampleInputPassword1" required>
              </div>
              <button class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2" type="submit">Sign In</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/particles.min.js"></script>
  <script>
    particlesJS("particles-js", {
  particles: {
    number: {
      value: 310,
      density: {
        enable: true,
        value_area: 800,
      },
    },
    color: {
      value: "#fff",
    },
    shape: {
      type: "circle",
      stroke: {
        width: 0,
        color: "#000000",
      },
      polygon: {
        nb_sides: 5,
      },
    },
    opacity: {
      value: .7,
      random: true,
      anim: {
        enable: false,
        speed: .6,
        opacity_min: 0.1,
        sync: false,
      },
    },
    size: {
      value: 3,
      random: true,
      anim: {
        enable: false,
      },
    },
    line_linked: {
      enable: false,
    },
    move: {
      enable: true,
      speed: .6,
      direction: "bottom",
      random: false,
      straight: false,
      out_mode: "out",
      bounce: false,
      attract: {
        enable: false,
        rotateX: 600,
        rotateY: 1200,
      },
    },
  },
  retina_detect: false,
});

  </script>
</body>

</html>