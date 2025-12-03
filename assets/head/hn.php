<!DOCTYPE html>
<html lang="en">

<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
?>

<head>
    <title>SAQSHI</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
/* ================================================
   MOBILE RESPONSIVE SIDEBAR + HEADER FIXES
================================================= */

/* Sidebar base */
.pcoded-navbar {
    width: 250px;
    z-index: 9999;
    transition: transform .3s ease-in-out;
}

/* Sidebar hidden on mobile */
@media (max-width: 767px) {
    .pcoded-navbar {
        position: fixed;
        left: 0;
        top: 0;
        transform: translateX(-100%);
        height: 100vh;
        background: #fff;
        box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }
    .pcoded-navbar.mobile-open {
        transform: translateX(0);
    }
    .scroll-div {
        max-height: 90vh;
        overflow-y: auto;
    }
}

/* Notification dropdown fix */
@media (max-width: 480px) {
    .notification {
        width: 260px !important;
    }
}

/* Facility name scaling */
.header-facility-text {
    font-size: 16px;
    color: #fff;
}
@media (max-width: 480px) {
    .header-facility-text {
        font-size: 12px;
        white-space: nowrap;
    }
}
</style>
</head>

<body>

<!-- PRELOADER -->
<div class="loader-bg">
    <div class="loader-track">
        <div class="loader-fill"></div>
    </div>
</div>

<!-- ========================== SIDEBAR =========================== -->
<nav class="pcoded-navbar">
    <div class="navbar-wrapper">
        <div class="navbar-content scroll-div">

            <ul class="nav pcoded-inner-navbar">
                <?php  
                $user_role = $_SESSION['userrole'] ?? 0;
                $facilitytype = $_SESSION['f_type_id'] ?? 0;
                ?>

                <!-- ---------- ROLE 1 & 2 (Facility Users) ---------- -->
                <?php if ($user_role == 1 || $user_role == 2) { ?>

                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <span class="pcoded-micon"><i class="feather icon-home"></i></span>
                        <span class="pcoded-mtext">Dashboard</span>
                    </a>
                </li>

                <?php if (in_array($facilitytype, [1,2,3,5,9,10])) { ?>
                <li class="nav-item">
                    <a href="facdash.php" class="nav-link">
                        <span class="pcoded-micon"><i class="feather icon-grid"></i></span>
                        <span class="pcoded-mtext">Overall Dept.Ass. Dash</span>
                    </a>
                </li>
                <?php } ?>

                <li class="nav-item">
                    <a href="deptask.php" class="nav-link">
                        <span class="pcoded-micon"><i class="feather icon-settings"></i></span>
                        <span class="pcoded-mtext">Assessment Setup</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="facprofile.php" class="nav-link">
                        <span class="pcoded-micon"><i class="bi bi-hospital"></i></span>
                        <span class="pcoded-mtext">Facility Profile</span>
                    </a>
                </li>

                <li class="nav-item pcoded-menu-caption"><label>Assessment Modules</label></li>

                <li class="nav-item pcoded-hasmenu">
                    <a href="#" class="nav-link">
                        <span class="pcoded-micon"><i class="feather icon-layers"></i></span>
                        <span class="pcoded-mtext">Modules</span>
                    </a>
                    <ul class="pcoded-submenu">
                        <li><a href="assessor.php">Assessor Info</a></li>
                        <li><a href="nqst.php">Assessment</a></li>
                        <li><a href="moic.php">Generate Action Plan</a></li>
                        <li><a href="umoic.php">Update Action Plan</a></li>
                    </ul>
                </li>

                <li class="nav-item pcoded-menu-caption"><label>Performance Tracking</label></li>

                <li class="nav-item pcoded-hasmenu">
                    <a href="#" class="nav-link">
                        <span class="pcoded-micon"><i class="feather icon-layout"></i></span>
                        <span class="pcoded-mtext">Indicators</span>
                    </a>
                    <ul class="pcoded-submenu">
                        <li><a href="outcome.php">Outcome Indicators</a></li>

                        <?php if ($facilitytype == 3) { ?>
                            <li><a href="phckpi.php">KPI</a></li>
                        <?php } elseif (in_array($facilitytype, [1,2,10])) { ?>
                            <li><a href="dhkpi.php">NQAS KPI</a></li>
                            <li><a href="mkpi.php">MusQan KPI</a></li>
                            <li><a href="anxc.php">Annexure C (LaQshya)</a></li>
                        <?php } ?>
                    </ul>
                </li>

                <li class="nav-item pcoded-menu-caption"><label>Reports</label></li>
                <li class="nav-item pcoded-hasmenu">
                    <a href="#" class="nav-link">
                        <span class="pcoded-micon"><i class="feather icon-box"></i></span>
                        <span class="pcoded-mtext">Basic</span>
                    </a>
                    <ul class="pcoded-submenu">
                        <li><a href="freports2.php">Reports</a></li>
                    </ul>
                </li>

                <li class="nav-item pcoded-menu-caption"><label>Resources</label></li>

                <li class="nav-item">
                    <a href="pages-faq.php" class="nav-link">
                        <span class="pcoded-mtext">Forms & Docs</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="feedback.php" class="nav-link">
                        <span class="pcoded-mtext">Feedback</span>
                    </a>
                </li>

                <?php } ?>

                <!-- ---------- OTHER ROLES (4,5,8,9) SAME AS YOUR CODE ---------- -->
                <?php include("sidebar_other_roles.php"); ?>

            </ul>

        </div>
    </div>
</nav>

<!-- ========================== HEADER =========================== -->
<header class="navbar pcoded-header navbar-expand-lg navbar-light header-dark">

    <div class="m-header">
        <a class="mobile-menu" id="mobile-collapse" href="#"><span></span></a>
        <a class="mob-toggler"><i class="feather icon-more-vertical"></i></a>
    </div>

    <div class="collapse navbar-collapse">

        <span class="header-facility-text">
            <?php
            $f = $_SESSION['facname'] ?? 0;
            $div = $_SESSION['div_name'] ?? 0;
            $block = $_SESSION['block_name'] ?? 0;

            if ($user_role == 5 || $user_role == 4) echo $div;
            elseif ($user_role == 1 || $user_role == 2) echo "Health Facility: ".$f;
            elseif ($user_role == 8) echo "Block: ".$block;
            else echo "State Admin";
            ?>
        </span>

        <ul class="navbar-nav ml-auto">

            <!-- NOTIFICATION -->
            <li>
                <?php
                $facility_id = $_SESSION['u_facilityid'] ?? 0;

                $notif_count = mysqli_fetch_assoc(mysqli_query(
                    $con,
                    "SELECT COUNT(*) AS c FROM facility_chat_messages 
                     WHERE receiver_facility_id=$facility_id AND is_read=0"
                ))['c'];

                $notif_result = mysqli_query(
                    $con,
                    "SELECT message_text, message_date 
                     FROM facility_chat_messages 
                     WHERE receiver_facility_id=$facility_id AND is_read=0 
                     ORDER BY message_date DESC"
                );
                ?>

                <div class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown">
                        <i class="icon feather icon-bell"></i>
                        <span class="badge badge-pill badge-danger"><?php echo $notif_count; ?></span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-right notification">
                        <div class="noti-head"><h6>Notifications</h6></div>
                        <ul class="noti-body">

                            <?php 
                            if (mysqli_num_rows($notif_result) > 0) {
                                while ($row = mysqli_fetch_assoc($notif_result)) {
                                    echo '<li class="notification">
                                        <strong>Admin</strong> 
                                        <span>'.date("d M Y H:i", strtotime($row['message_date'])).'</span>
                                        <p>'.htmlspecialchars($row['message_text']).'</p>
                                    </li>';
                                }
                            } else {
                                echo '<li class="notification text-center text-muted">No new notifications</li>';
                            }
                            ?>

                        </ul>
                        <div class="noti-footer"><a href="email_inbox.php">Show all</a></div>
                    </div>
                </div>
            </li>

            <!-- USER MENU -->
            <li>
                <div class="dropdown drp-user">
                    <a class="dropdown-toggle" data-toggle="dropdown">
                        <i class="feather icon-user"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right profile-notification">

                        <div class="pro-head">
                            <img src="assets/images/user/avatar-1.jpg" class="img-radius" alt="">
                            <span>
                                <?php 
                                if ($user_role == 5 || $user_role == 4) echo $div;
                                elseif ($user_role == 1 || $user_role == 2) echo $f;
                                elseif ($user_role == 8) echo $block;
                                else echo "State Admin"; 
                                ?>
                            </span>
                            <a href="logout.php"><i class="feather icon-log-out"></i></a>
                        </div>

                        <ul class="pro-body">
                            <li><a href="users-profile.php"><i class="feather icon-user"></i> Change Password</a></li>
                            <li><a href="email_inbox.php"><i class="feather icon-mail"></i> My Messages</a></li>
                            <li><a href="logout.php"><i class="feather icon-lock"></i> Logout</a></li>
                        </ul>

                    </div>
                </div>
            </li>

        </ul>
    </div>
</header>

<!-- ========================== JS FOR SIDEBAR ========================== -->
<script>
document.getElementById("mobile-collapse").addEventListener("click", function () {
    document.querySelector(".pcoded-navbar").classList.toggle("mobile-open");
});
</script>


