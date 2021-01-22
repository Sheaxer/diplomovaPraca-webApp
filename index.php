<!DOCTYPE html>
<html lang="en">
<head>
	<title>Diplomová práca</title>
</head>
<body>
Hello words
<?php
$path = ltrim($_SERVER['REQUEST_URI'], '/');    // Trim leading slash(es)
$elements = explode('/', $path);                // Split path on slashes
print_r($elements);
?>
</body>
</html>

