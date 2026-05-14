<!DOCTYPE html>
<html lang="en">
<?php include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
?>

<head>
	<title>SAQSHI</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="description" content="" />
	<meta name="keywords" content="">
	<meta name="author" content="Phoenixcoded" />
	<!-- Favicon icon -->
	<link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
	<script src="assets/js/plugins/min.js"></script>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
	<link rel="stylesheet" href="assets/datatables/leaflet.css" />
	<script src="assets/datatables/leaflet.js"></script>
	<link rel="stylesheet" href="assets/css/style.css">
	<style>
		.falling-emoji {
			position: fixed;
			top: -50px;
			font-size: 4rem;
			z-index: 9999;
			animation: fall 2s ease-out forwards;
			pointer-events: none;
			left: 50%;
			transform: translateX(-50%);
		}

		@keyframes fall {
			0% {
				transform: translateX(-50%) translateY(-50px) rotate(0deg);
				opacity: 1;
			}

			80% {
				transform: translateX(-50%) translateY(80vh) rotate(180deg);
				opacity: 1;
			}

			100% {
				transform: translateX(-50%) translateY(100vh) rotate(360deg);
				opacity: 0;
			}
		}
	</style>
</head>

<body class="">
	<!-- [ Pre-loader ] start -->
	<div class="loader-bg">
		<div class="loader-track">
			<div class="loader-fill"></div>
		</div>
	</div>
	<!-- [ Pre-loader ] End -->
	<!-- [ navigation menu ] start -->
	<nav class="pcoded-navbar  ">
		<div class="navbar-wrapper  ">
			<div class="navbar-content scroll-div ">
				<ul class="nav pcoded-inner-navbar ">
					<?php
					$user_role = $_SESSION['userrole'] ?? 0;
					if ($user_role == 1 || $user_role == 2) {

					?>
						<li class="nav-item">
							<a href="index.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-home"></i></span><span class="pcoded-mtext">Dashboard</span></a>
						</li>
						<?php
						$facilitytype =  $_SESSION['f_type_id'] ?? 0;
						if (in_array($facilitytype, [1, 2, 3, 5, 9, 10])) {
						?>
							<li class="nav-item">
								<a href="facdash.php" class="nav-link">
									<span class="pcoded-micon"><i class="feather icon-grid"></i></span>
									<span class="pcoded-mtext">Overall Dept.Ass. Dash</span>
								</a>
							</li>


						<?php
						}

						?>
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
						<li class="nav-item pcoded-menu-caption">
							<label>Assessment Modules</label>
						</li>
						<li class="nav-item pcoded-hasmenu">
							<a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-layers"></i></span><span class="pcoded-mtext">Modules</span></a>
							<ul class="pcoded-submenu">


								<li><a href="assessor.php"><span class="pcoded-micon"><i class="bi bi-person-vcard"></i></span><span class="pcoded-mtext">Assessor info</span></a></li>
								<li><a href="assessment.php"> <span class="pcoded-micon"><i class="bi bi-card-checklist"></i></span><span class="pcoded-mtext">Assessment</span></a></li>
								<li><a href="moic.php"><span class="pcoded-micon"><i class="bi-journal-plus"></i></span><span class="pcoded-mtext">Generate Action Plan</span></a></li>
								<li><a href="umoic.php"><span class="pcoded-micon"><i class="bi-journal-check"></i></span><span class="pcoded-mtext">Update Action Plan</span></a></li>


							</ul>
						</li>
						<li class="nav-item pcoded-menu-caption">
							<label>Performance Tracking</label>
						</li>
						<li class="nav-item pcoded-hasmenu">
							<a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-layout"></i></span><span class="pcoded-mtext">Indicators</span></a>
							<ul class="pcoded-submenu">
								<li><a href="outcome.php">OutCome Indicators</a></li>
								<?php
								$facilitytype =  $_SESSION['f_type_id'] ?? 0;
								if ($facilitytype == 3) {
								?>
									<li><a href="phckpi.php">KPI</a></li>
								<?php
								} elseif (in_array($facilitytype, [2, 10])) {
								?>
									<li><a href="dhkpi.php">NQAS KPI</a></li>
									<li><a href="mkpi.php">MusQan KPI</a></li>
									<li><a href="anxc.php">Anexure C for LaQshya</a></li>

								<?php
								} elseif (in_array($facilitytype, [1])) {
								?>
									<li><a href="chckpi.php">NQAS KPI</a></li>
									<li><a href="mkpi.php">MusQan KPI</a></li>
									<li><a href="anxc.php">Anexure C for LaQshya</a></li>

								<?php
								} ?>
							</ul>
						</li>

						<li class="nav-item pcoded-menu-caption">
							<label>Reports</label>
						</li>
						<li class="nav-item pcoded-hasmenu">
							<a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-box"></i></span><span class="pcoded-mtext">Basic</span></a>
							<ul class="pcoded-submenu">
								<li><a href="freports2.php">Reports</a></li>
							</ul>
						</li>

						<li class="nav-item pcoded-menu-caption">
							<label>Resources</label>
						</li>
						<li class="nav-item">
							<a href="pages-faq.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-file-text"></i></span><span class="pcoded-mtext">Forms & Docs</span></a>
						</li>
						<li class="nav-item">
							<a href="chattest.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-align-justify"></i></span><span class="pcoded-mtext">Ask me</span></a>
						</li>
						<li class="nav-item">
							<a href="feedback.php" class="nav-link position-relative">
								<span class="pcoded-micon">
									<i class="bi bi-star-half"></i>

								</span>
								<span class="pcoded-mtext">Feedback</span>

								<!-- 🔴 Notification badge -->
								<span id="feedbackBadge"
									class="badge bg-danger position-absolute top-0 start-100 translate-middle"
									style="display:none;">
									0
								</span>
							</a>
						</li>



					<?php } elseif ($user_role == 4) { ?>
						<li class="nav-item">
							<a href="distdash.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-home"></i></span><span class="pcoded-mtext">Dashboard</span></a>
						</li>
						<li class="nav-item pcoded-menu-caption">
							<label>Resources</label>
						</li>
						<li class="nav-item">
							<a href="pages-faq.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-file-text"></i></span><span class="pcoded-mtext">Forms & Docs</span></a>
						</li>
						<li class="nav-item">
							<a href="chattest.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-align-justify"></i></span><span class="pcoded-mtext">Ask me</span></a>
						</li>
						<li class="nav-item">
							<a href="feedback.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-align-justify"></i></span><span class="pcoded-mtext">Feedback</span></a>
						</li>
				</ul>
			<?php } elseif ($user_role == 5) { ?>
				<li class="nav-item">
					<a href="regdash.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-home"></i></span><span class="pcoded-mtext">Dashboard</span></a>
				</li>
				<li class="nav-item pcoded-menu-caption">
					<label>Resources</label>
				</li>
				<li class="nav-item">
					<a href="pages-faq.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-file-text"></i></span><span class="pcoded-mtext">Forms & Docs</span></a>
				</li>
				<li class="nav-item">
					<a href="chattest.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-align-justify"></i></span><span class="pcoded-mtext">Ask me</span></a>
				</li>
				<li class="nav-item">
					<a href="feedback.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-align-justify"></i></span><span class="pcoded-mtext">Feedback</span></a>
				</li>
				</ul>
			<?php } elseif ($user_role == 9) { ?>
				<li class="nav-item pcoded-hasmenu">
					<a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-layout"></i></span><span class="pcoded-mtext">Dashboard</span></a>
					<ul class="pcoded-submenu">


						<li><a href="sdash.php"><span class="pcoded-micon"><i class="feather icon-layout"></i></span><span class="pcoded-mtext">NQAS</span></a></li>
						<li><a href="musdash.php"> <span class="pcoded-micon"><i class="feather icon-layout"></i></span><span class="pcoded-mtext">MusQan</span></a></li>
						<li><a href="laxydash.php"><span class="pcoded-micon"><i class="feather icon-layout"></i></span><span class="pcoded-mtext">LaQshya</span></a></li>
<li><a href="kayadash.php"><span class="pcoded-micon"><i class="feather icon-layout"></i></span><span class="pcoded-mtext">Kayakalp</span></a></li>


					</ul>

				</li>

				<li class="nav-item pcoded-hasmenu">
					<a href="#!" class="nav-link">
						<span class="pcoded-micon"><i class="feather icon-layout"></i></span>
						<span class="pcoded-mtext">Gap Analysis</span>
					</a>
					<ul class="pcoded-submenu">
						<li>
							<a href="gap.php">
								<span class="pcoded-micon"><i class="bi bi-person-vcard"></i></span>
								<span class="pcoded-mtext">Indicator</span>
							</a>
							<a href="deptgap.php">
								<span class="pcoded-micon"><i class="bi bi-person-vcard"></i></span>
								<span class="pcoded-mtext">Department</span>
							</a>
						</li>
					</ul>
				</li>
				<li class="nav-item pcoded-menu-caption">
					<label>Administration</label>
				</li>
				<li class="nav-item pcoded-hasmenu">
					<a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-layout"></i></span><span class="pcoded-mtext">Setup</span></a>
					<ul class="pcoded-submenu">


						<li><a href="healthblock.php"><span class="pcoded-micon"><i class="bi bi-person-vcard"></i></span><span class="pcoded-mtext">Add health block</span></a></li>
						<li><a href="fac.php"> <span class="pcoded-micon"><i class="bi bi-card-checklist"></i></span><span class="pcoded-mtext">Facility Setup</span></a></li>
						<li><a href="useradd.php"><span class="pcoded-micon"><i class="bi-journal-plus"></i></span><span class="pcoded-mtext">User</span></a></li>
						<li><a href="cert.php"><span class="pcoded-micon"><i class="bi-journal-check"></i></span><span class="pcoded-mtext">Certification</span></a></li>
						<li><a href="outsource.php"><span class="pcoded-micon"><i class="bi-journal-check"></i></span><span class="pcoded-mtext">Outcome Source</span></a></li>
						<!--li><a href="data.php"><span class="pcoded-micon"><i class="bi-journal-check"></i></span><span class="pcoded-mtext">Data Maintenance</span></a></li-->
						<li><a href="login_analytics.php"><span class="pcoded-micon"><i class="bi-journal-check"></i></span><span class="pcoded-mtext">Login Analytics</span></a></li>
						<!--li><a href="system_monitoring.php"><span class="pcoded-micon"><i class="bi-journal-check"></i></span><span class="pcoded-mtext">System monitoring</span></a></li-->
					</ul>

				</li>
				<li class="nav-item pcoded-hasmenu">
					<a href="#!" class="nav-link "><span class="pcoded-micon"><i class="feather icon-layout"></i></span><span class="pcoded-mtext">Message</span></a>
					<ul class="pcoded-submenu">
						<li><a href="email_inbox.php"><span class="pcoded-micon"><i class="bi bi-person-vcard"></i></span><span class="pcoded-mtext">Send message</span></a></li>
					</ul>

				</li>
				<li class="nav-item pcoded-menu-caption">
					<label>Resources</label>
				</li>
				<li class="nav-item">
					<a href="pages-faq.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-file-text"></i></span><span class="pcoded-mtext">Forms & Docs</span></a>
				</li>
				<li class="nav-item">
					<a href="chattest.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-align-justify"></i></span><span class="pcoded-mtext">Ask me</span></a>
				</li>
				<li class="nav-item">
					<a href="feedback.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-align-justify"></i></span><span class="pcoded-mtext">Feedback</span></a>
				</li>
				</ul>
			<?php } elseif ($user_role == 8) { ?>
				<li class="nav-item">
					<a href="bdash.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-home"></i></span><span class="pcoded-mtext">Dashboard</span></a>
				</li>
				<li class="nav-item pcoded-menu-caption">
					<label>Resources</label>
				</li>
				<li class="nav-item">
					<a href="pages-faq.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-file-text"></i></span><span class="pcoded-mtext">Forms & Docs</span></a>
				</li>
				<li class="nav-item">
					<a href="chattest.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-align-justify"></i></span><span class="pcoded-mtext">Ask me</span></a>
				</li>
				<li class="nav-item">
					<a href="feedback.php" class="nav-link "><span class="pcoded-micon"><i class="feather icon-align-justify"></i></span><span class="pcoded-mtext">Feedback</span></a>
				</li>
				</ul>
			<?php } ?>





			</div>
		</div>
	</nav>
	<!-- [ navigation menu ] end -->
	<!-- [ Header ] start -->
	<header class="navbar pcoded-header navbar-expand-lg navbar-light header-dark">


		<div class="m-header">
			<a class="mobile-menu" id="mobile-collapse" href="#!"><span></span></a>
			<a href="#!" class="b-brand">
				<!-- ========   change your logo hear   ============ 
						<img src="assets/img/logo.png" alt="" class="logo">
						<img src="assets/img/logo3.png" alt="" class="logo-thumb">-->
			</a>
			<a href="#!" class="mob-toggler">
				<i class="feather icon-more-vertical"></i>
			</a>
		</div>
		<div class="collapse navbar-collapse">
			<?php
			$f = $_SESSION['facname'] ?? 0;

			?>
			<?php
			$f = $_SESSION['facname'] ?? 0;
			$div =  $_SESSION['div_name'] ?? 0;
			$bdiv = $_SESSION['block_name'] ?? 0;
			if ($user_role == 5 || $user_role == 4) {
				echo $div;
			} elseif ($user_role == 1 || $user_role == 2) {

				echo 'Health Facility: ';
				echo $f;
			} elseif ($user_role == 8) {

				echo 'Block: ';
				echo $bdiv;
			} else {
				echo 'Admin';
			} ?></span>
			<ul class="navbar-nav ml-auto">
				<li>
					<?php
$facility_id = (int)($_SESSION['u_facilityid'] ?? 0);

/* ============================================================
   FETCH LATEST 5 UNREAD ADMIN NOTIFICATIONS
   (DIRECT + BROADCAST)
============================================================ */

$notif_query = "
    (
        -- Direct admin → facility
        SELECT message_text, message_date
        FROM facility_chat_messages
        WHERE sender_facility_id = 0
          AND receiver_facility_id = {$facility_id}
          AND is_read = 0
    )
    UNION ALL
    (
        -- Broadcast admin → all (not yet read by this facility)
        SELECT m.message_text, m.message_date
        FROM facility_chat_messages m
        WHERE m.sender_facility_id = 0
          AND m.receiver_facility_id = 0
          AND NOT EXISTS (
              SELECT 1
              FROM facility_broadcast_read r
              WHERE r.message_id = m.message_id
                AND r.facility_id = {$facility_id}
          )
    )
    ORDER BY message_date DESC
    LIMIT 5
";

$notif_result = mysqli_query($con, $notif_query);

/* ============================================================
   BADGE COUNT (TOTAL UNREAD)
============================================================ */

$count_query = "
    SELECT
    (
        -- Direct admin messages
        SELECT COUNT(*)
        FROM facility_chat_messages
        WHERE sender_facility_id = 0
          AND receiver_facility_id = {$facility_id}
          AND is_read = 0
    ) +
    (
        -- Broadcast messages not read by this facility
        SELECT COUNT(*)
        FROM facility_chat_messages m
        WHERE m.sender_facility_id = 0
          AND m.receiver_facility_id = 0
          AND NOT EXISTS (
              SELECT 1
              FROM facility_broadcast_read r
              WHERE r.message_id = m.message_id
                AND r.facility_id = {$facility_id}
          )
    ) AS cnt
";

$count_result = mysqli_query($con, $count_query);
$count_row    = mysqli_fetch_assoc($count_result);
$notif_count  = (int)$count_row['cnt'];
?>


					<div class="dropdown">
						<a class="dropdown-toggle" href="#" data-toggle="dropdown">
							<i class="icon feather icon-bell"></i>
							<span class="badge badge-pill badge-danger"><?php echo $notif_count; ?></span>
						</a>
						<div class="dropdown-menu dropdown-menu-right notification">
							<div class="noti-head">
								<h6 class="d-inline-block m-b-0">Notifications</h6>

							</div>
							<ul class="noti-body">
								<li class="n-title">
									<p class="m-b-0">LATEST</p>
								</li>

								<?php
								if ($notif_result && mysqli_num_rows($notif_result) > 0) {
									while ($row = mysqli_fetch_assoc($notif_result)) {
										// Simple format: show message and time
										echo '<li class="notification">';
										echo '  <div class="media">';
										echo '      <img class="img-radius" src="assets/images/user/avatar-1.jpg" alt="Generic placeholder image">';
										echo '      <div class="media-body">';
										echo '          <p><strong>Admin</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>' . date("d M Y H:i", strtotime($row['message_date'])) . '</span></p>';
										echo '          <p>' . htmlspecialchars($row['message_text']) . '</p>';
										echo '      </div>';
										echo '  </div>';
										echo '</li>';
									}
								} else {
									echo '<li class="notification text-center text-muted">No new notifications</li>';
								}
								?>

							</ul>
							<div class="noti-footer">
								<a href="email_inbox.php">show all</a>
							</div>
						</div>
					</div>
				</li>

				<li>
					<div class="dropdown drp-user">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
							<i class="feather icon-user"></i>
						</a>
						<div class="dropdown-menu dropdown-menu-right profile-notification">
							<div class="pro-head">
								<img src="assets/images/user/avatar-1.jpg" class="img-radius" alt="User-Profile-Image">
								<span> <?php
										$f = $_SESSION['facname'] ?? 0;
										$div =  $_SESSION['div_name'] ?? 0;
										$bdiv = $_SESSION['block_name'] ?? 0;
										if ($user_role == 5 || $user_role == 4) {
											echo $div;
										} elseif ($user_role == 1 || $user_role == 2) {

											echo 'Health Facility: ';
											echo $f;
										} elseif ($user_role == 8) {
											echo $bdiv;
										} else {
											echo 'State Admin';
										} ?></span>
								<a href="logout.php" class="dud-logout" title="Logout">
									<i class="feather icon-log-out"></i>
								</a>
							</div>
							<ul class="pro-body">
								<li><a href="users-profile.php" class="dropdown-item"><i class="feather icon-user"></i> Change Password</a></li>
								<li><a href="email_inbox.php" class="dropdown-item"><i class="feather icon-mail"></i> My Messages</a></li>
								<li><a href="logout.php" class="dropdown-item"><i class="feather icon-lock"></i>Log out</a></li>
							</ul>
						</div>
					</div>
				</li>
			</ul>
		</div>
	</header>

	<script>
		(function() {

			const badge = document.getElementById('feedbackBadge');
			if (!badge) return; // no badge on this page

			function checkFeedbackNotification() {
				fetch('assets/get/unread_feedback_count.php') // ✅ FIXED PATH
					.then(res => res.json())
					.then(data => {
						if (data.count > 0) {
							badge.innerText = data.count;
							badge.style.display = 'inline-block';
						} else {
							badge.style.display = 'none';
						}
					})
					.catch(err => console.error('Feedback notification error', err));
			}

			// check immediately
			checkFeedbackNotification();

			// check every 10 seconds
			setInterval(checkFeedbackNotification, 10000);

		})();
	</script>



	<!-- [ Header ] end -->