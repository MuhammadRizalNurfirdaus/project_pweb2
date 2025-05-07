<?php
include 'template/header.php';
include 'controllers/ContactController.php';
?>

<?php
$controller = new ContactController();
$data = $_POST;
$controller->create($data);
?>

<?php include 'template/footer.php'; ?>
