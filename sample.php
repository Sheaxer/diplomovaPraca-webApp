<!DOCTYPE html>
<html lang="en">
<head>
	<title>Diplomová práca</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="functions.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>
</head>
<body>
<form id="loginForm" method="post">
    <div class="form-group">
        <label for="usernameInput">User name</label> <input type="text" name="username" id="usernameInput"> <br>
        <label for="passwordInput">Password</label> <input type="password" name="password" id="passwordInput"> <br>
        <input type="submit" value="Log In">
    </div>
</form>


<button id="logOff">Log out</button>

<form id="nomenklatorUploadForm" method="post" datatype="application/json">
    <div class="form-group">
    <label for="signature">Signature of nomenklator</label><input type="text" id="signatureInput" name="signature">
    </div>
    <div class="form-group">
        <label for="addNomenklatorFolderSelect">  Choose a nomenklator folder: </label>
        <select name="folder" id="addNomenklatorFolderSelect" class="form-control">
        </select>
    </div>

    <div class="form-group">
        <label for="addNomenklatorKeyUserSelect"> Choose Nomenclator Key Users</label>
        <select name="keyUsers" id="addNomenklatorKeyUserSelect" multiple class="form-control"> </select>
    </div>
    <label for="addNomenklatorCompleteStructure">Complete Structure</label>
    <textarea name='completeStructure' id="addNomenklatorCompleteStructure"></textarea> <br>

    <label>Language</label>
    <input type="text" name="language" id="addNomenklatorLanguage">
    <br>
    <label> Images</label> <br>
    <button id="addNomenklatorAddUrl">New image</button>
    <div class="form-group" id="addNomenklatorImages">
    </div>



    <input type="submit" value="Add new nomenklator">
</form>
<!--
<label>Uploaded images / image URL</label>

<label class="switch">
    <input type="checkbox" id="addNomenklatorSlider">
    <span class="slider round"></span>
</label>
-->
</body>
</html>

