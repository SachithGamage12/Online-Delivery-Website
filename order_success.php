<?php
session_start();
if (!isset($_GET['id'])) header('Location: index.php');
$order_id = (int)$_GET['id'];
?>
<!DOCTYPE html><html><head><title>Order Success</title></head><body style="font-family:Poppins;text-align:center;padding:3rem;">
<h1>Order #<?= $order_id ?> Placed!</h1>
<p>Pay on delivery. We'll call you soon.</p>
<a href="index.php" style="color:#2d7a4e;">Back to Home</a>
</body></html>