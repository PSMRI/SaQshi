<?php
// Sample dynamic data (replace from DB)
$cert_no     = "SAQSHI/2025/CHC/000145";
$facility    = "Community Health Centre – Arajiline";
$location    = "Arajiline Block, Varanasi, Uttar Pradesh";
$framework   = "NQAS";
$facility_ty = "CHC";
$from_date   = "01 Jan 2025";
$to_date     = "15 Mar 2025";
$issue_date  = date("d M Y");
$verify_url  = "https://saqshi.in/certificate/verify.php?cert=".$cert_no;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body{
    font-family: "DejaVu Sans", serif;
}
.certificate{
    width: 100%;
    height: 100%;
    border: 8px solid #c9a227;
    padding: 30px;
    background: #fffdf5;
}
.inner-border{
    border: 2px solid #003366;
    padding: 25px;
}
.title{
    text-align: center;
    font-size: 32px;
    font-weight: bold;
    color: #003366;
}
.subtitle{
    text-align: center;
    font-size: 18px;
    margin-top: 5px;
}
.content{
    margin-top: 30px;
    font-size: 16px;
    line-height: 1.7;
    text-align: center;
}
.facility{
    font-size: 22px;
    font-weight: bold;
    margin: 15px 0;
}
.details{
    margin-top: 25px;
    width: 100%;
    font-size: 14px;
}
.details td{
    padding: 6px;
}
.footer{
    margin-top: 40px;
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}
.qr{
    margin-top: 15px;
    text-align: center;
    font-size: 11px;
}
.watermark{
    position: fixed;
    top: 40%;
    left: 25%;
    font-size: 80px;
    color: rgba(0,0,0,0.05);
    transform: rotate(-30deg);
}
</style>
</head>

<body>
<div class="watermark">SaQshi</div>

<div class="certificate">
<div class="inner-border">

<div class="title">CERTIFICATE OF ASSESSMENT COMPLETION</div>
<div class="subtitle">मूल्यांकन पूर्णता प्रमाण पत्र</div>

<div class="content">
This is to certify that<br>

<div class="facility"><?= $facility ?></div>

located at<br>
<strong><?= $location ?></strong><br><br>

has successfully completed the quality assessment under the
<strong><?= $framework ?></strong> framework through the
<strong>SaQshi Quality Assessment Platform</strong>.
<br><br>

यह प्रमाणित किया जाता है कि उपरोक्त स्वास्थ्य संस्था ने
SaQshi गुणवत्ता मूल्यांकन मंच के अंतर्गत निर्धारित मानकों के अनुसार
सफलतापूर्वक मूल्यांकन पूर्ण किया है।
</div>

<table class="details" border="1" cellspacing="0">
<tr><td><strong>Facility Type</strong></td><td><?= $facility_ty ?></td></tr>
<tr><td><strong>Assessment Period</strong></td><td><?= $from_date ?> to <?= $to_date ?></td></tr>
<tr><td><strong>Certificate No</strong></td><td><?= $cert_no ?></td></tr>
<tr><td><strong>Date of Issue</strong></td><td><?= $issue_date ?></td></tr>
</table>

<div class="footer">
<div>
<strong>Authorized Signatory</strong><br>
District Health Authority
</div>
<div>
<strong>Program Coordinator</strong><br>
SaQshi Platform
</div>
</div>

<div class="qr">
QR Code for Verification
</div>

</div>
</div>
</body>
</html>
