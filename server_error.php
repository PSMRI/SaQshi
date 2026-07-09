<?php
http_response_code(503);
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Service Unavailable</title>

<link href="assets/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.error-box{

    width:600px;
    max-width:95%;

    margin:80px auto;

    background:#fff;

    border-radius:10px;

    box-shadow:0 5px 15px rgba(0,0,0,.15);

    padding:40px;

    text-align:center;

}

</style>

</head>

<body>

<div class="error-box">

<h1 class="text-danger">

<i class="bi bi-exclamation-triangle-fill"></i>

Service Temporarily Unavailable

</h1>

<hr>

<p class="lead">

Unable to process your login request at the moment.

</p>

<p>

The service is temporarily unavailable due to a technical issue.

Please try again after some time.

</p>

<a href="start.php" class="btn btn-primary">

Return to Login

</a>

</div>

</body>
</html>