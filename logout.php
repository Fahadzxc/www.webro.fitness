<?php
session_start();
session_destroy();
header("Location: index.php"); // Balik login page
exit();
?>
