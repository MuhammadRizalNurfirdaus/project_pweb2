<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cilengkrang Web Wisata</title>
    <link rel="stylesheet" href="/public/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/index.php">
                <img src="./public/img/logo.png" alt="Logo Cilengkrang" style="height: 40px;" class="me-2">
                <span>Cilengkrang</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav"> <!-- Perbaiki typo: navbar-collaps -> navbar-collapse -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/user/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/galeri.php">Galeri</a></li>
                    <li class="nav-item"><a class="nav-link" href="/booking.php">Pemesanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="/contact.php">Kontak</a></li>
                    <li class="nav-item"><a class="nav-link" href="/project_pweb2">Wisata</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
