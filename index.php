<!DOCTYPE html>
<html lang="en">
<head>
	<title>Diplomová práca</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="functions.js"></script>
</head>
<body>
<form id="loginForm" method="post">
    <label>User name</label> <input type="text" name="username" id="usernameInput"> <br>
    <label>Password</label> <input type="password" name="password" id="passwordInput"> <br>
    <input type="submit" value="Log In">
</form>

<form action="images.php" method="post" enctype="multipart/form-data" id="imageUploadForm">
    Fotografia nomenklatora: <input type="file" name="nomenklatorImage" id="nomenklatorImage">
    <br>
    <input type="submit" value="Upload Image" name="submit">
</form>
<button id="logOff">Log out</button>
</body>
</html>

