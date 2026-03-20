<?php include "config.php"; ?>
<?php include "header.php"; ?>
<?php include "sidebar.php"; ?>

<h2>Dashboard</h2>

<?php
$total = $conn->query("SELECT COUNT(*) as total FROM items")->fetch_assoc()['total'];
$open = $conn->query("SELECT COUNT(*) as total FROM items WHERE status='Open'")->fetch_assoc()['total'];
$matched = $conn->query("SELECT COUNT(*) as total FROM items WHERE status='matched'");
?>

<p>Total Items: <?php echo $total; ?></p>
<p>Open Items: <?php echo $open; ?></p>

</div>
</body>
</html>
