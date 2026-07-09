<?php
session_start();

$wait = isset($_GET['wait']) ? (int)$_GET['wait'] : 15;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Locked</title>

    <link href="assets/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:#f5f7fa;
        }
        .lock-box{
            max-width:600px;
            margin:100px auto;
            background:#fff;
            border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,.15);
            padding:40px;
            text-align:center;
        }
    </style>

</head>

<body>

<div class="lock-box">

<h1 class="text-danger">
🔒 Account Temporarily Locked
</h1>

<hr>

<p class="lead">

Too many unsuccessful login attempts were detected.

</p>

<p>

For your security, login has been temporarily disabled.

</p>

<h3>

Please try again after
<b><?= $wait ?></b>
minutes.

</h3>

<a href="login.php" class="btn btn-primary mt-4">

Back to Login

</a>

</div>

</body>
</html>