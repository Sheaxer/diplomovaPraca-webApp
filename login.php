<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
</head>
<body>
<form>

</form>
<?php
session_start();

$database = new Database();
$db = $database->getConnection();
if(isset($_SESSION["user_id"]) && $_SESSION["user_id"] != null && !empty($_SESSION["user_id"])){

    $user_id = $_SESSION["user_id"];
    header("location: welcome.php");
    exit;
}
require_once "objects/LoginInfo.php";
require_once "config/database_init.php";

?>
</body>
</html>

