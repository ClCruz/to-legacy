<?php
if (isset($_GET['redirect']) and $_GET['redirect'] != '') {
	header("Location: " . urldecode($_GET['redirect']));
} else {
	header("Location: index.php");
}
?>