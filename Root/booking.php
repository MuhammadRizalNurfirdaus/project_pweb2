<?php
include 'template/header.php';
include 'controllers/BookingController.php';
?>

<?php
$controller = new BookingController();
$data = $_POST;
$controller->create($data);
?>

<?php include 'template/footer.php'; ?>
