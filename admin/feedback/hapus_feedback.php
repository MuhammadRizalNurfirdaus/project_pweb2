<?php
include "../../config/Koneksi.php";

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM feedback WHERE id='$id'");

header("Location: kelola_feedback.php");
