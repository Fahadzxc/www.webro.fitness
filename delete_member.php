<?php
include 'db.php';

$id = $_GET['id'];
$conn->query("DELETE FROM members WHERE id = $id");

header("Location: members.php");
exit();
?>
