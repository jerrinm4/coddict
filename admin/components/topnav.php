<header class="app-header">
    <nav class="navbar navbar-expand-lg navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item d-block d-xl-none">
                <a class="nav-link sidebartoggler nav-icon-hover" id="headerCollapse" href="javascript:void(0)">
                    <i class="ti ti-menu-2"></i>
                </a>
            </li>

        </ul>
        <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
    <ul class="navbar-nav flex-row align-items-center">
        <li class="nav-item me-3">
            <button class="btn btn-primary">Hello, <?php echo $_SESSION['coddict_admin']; ?></button>
        </li>
        <li class="nav-item">
            <a href="./logout.php" class="btn btn-outline-primary">Logout</a>
        </li>
    </ul>
</div>

    </nav>
</header>