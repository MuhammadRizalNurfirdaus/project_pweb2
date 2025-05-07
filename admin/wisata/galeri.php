<?php include '../template/header_admin.php';
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Galeri Wisata Cilengkrang</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <img src="../public/img/air_panas.jpg" class="card-img-top" alt="Air Panas">
                <div class="card-body">
                    <h5 class="card-title">Pemandian Air Panas</h5>
                    <p class="card-text">Nikmati relaksasi alami dari sumber air panas pegunungan.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <img src="../public/img/gazebo.jpg" class="card-img-top" alt="Gazebo">
                <div class="card-body">
                    <h5 class="card-title">Gazebo Pinggir Sungai</h5>
                    <p class="card-text">Tempat bersantai yang tenang di tengah alam.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <video controls class="w-100 rounded">
                    <source src="../public/img/curug.mp4" type="video/mp4">
                </video>
                <div class="card-body">
                    <h5 class="card-title">Curug Cilengkrang</h5>
                    <p class="card-text">Air terjun alami yang cocok untuk rekreasi keluarga.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>