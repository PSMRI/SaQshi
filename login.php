<?php
include("assets/conn/db.php");
ob_start();
session_start();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $myusername = trim($_POST['myusername']);
    $mypassword = trim($_POST['mypassword']);
    $_SESSION['lang'] = $_POST['lang'];

    $stmt = $con->prepare("SELECT * FROM s_user WHERE u_name = ? AND is_active = 1");
    $stmt->bind_param("s", $myusername);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->num_rows;

    if ($count == 1) {
        $row = mysqli_fetch_assoc($result);
        $userid = $row['u_id'];
        $userrole = $row['role_id_fk'];
        $district_id = $row['dist_id'];
        $stored_password = $row['u_password'];
        $_SESSION['userrole'] = $row['role_id_fk'];
        if (password_verify($mypassword, $stored_password) || $mypassword === $stored_password) {
            session_regenerate_id(true);
            $_SESSION['urole'] = $userrole;
            $_SESSION['userid'] = $userid;
            $_SESSION['u_name'] = $myusername;
            $_SESSION['dist'] = $district_id;
            $_SESSION['login_time'] = date('d M Y h:i A');

            if ($userrole == 1) {
                $stmt2 = $con->prepare("SELECT 
    a.fac_id_fk, 
    b.NIN_no,
    a.assessment_id, 
    a.dist_id, 
    b.Health_facilty_type, 
    b.fac_name,    
    c.fac
FROM 
    s_user AS a
JOIN 
    facilities AS b ON a.fac_id_fk = b.fac_id
JOIN 
    facilities_type AS c ON b.Health_facilty_type = c.fac_type_id
WHERE 
    a.u_id = ? 
    AND a.is_active = 1;");
                $stmt2->bind_param("i", $userid);
                $stmt2->execute();
                $res = $stmt2->get_result();
                $row = $res->fetch_assoc();
                $_SESSION['u_facilityid'] = $row['fac_id_fk'];
                $_SESSION['f_type_id'] = $row['Health_facilty_type'];
                $_SESSION['assperiod'] = $row['assessment_id'];
                $_SESSION['facname'] = $row['fac_name'];
                $_SESSION['factypename'] = $row['fac'];
                 $_SESSION['factynin'] = $row['NIN_no'];
                header("location:index.php");
                exit;
            } elseif ($userrole == 2) {
                $stmt2 = $con->prepare("SELECT a.fac_id_fk, a.dept_id, a.assessment_id, b.Health_facilty_type, b.fac_name FROM s_user AS a JOIN facilities AS b ON a.fac_id_fk = b.fac_id WHERE a.u_id = ? AND a.is_active = 1");
                $stmt2->bind_param("i", $userid);
                $stmt2->execute();
                $res = $stmt2->get_result();
                $row = $res->fetch_assoc();
                $_SESSION['u_facilityid'] = $row['fac_id_fk'];
                $_SESSION['dept_id1'] = $row['dept_id'];
                $_SESSION['assperiod'] = $row['assessment_id'];
                $_SESSION['f_type_id'] = $row['Health_facilty_type'];
                $_SESSION['facname'] = $row['fac_name'];
                header("location:index.php");
                exit;
            } elseif ($userrole == 3) {
                header("location:index.php");
                exit;
            } elseif ($userrole == 4) {
                 $stmt2 = $con->prepare("SELECT a.Dist_id, b.Dist_name FROM facilities AS a JOIN dist_master AS b ON a.dist_id = b.Dist_id WHERE a.Dist_id = (SELECT dist_id FROM s_user WHERE u_id = ?)");
                $stmt2->bind_param("i", $userid);
                $stmt2->execute();
                $res = $stmt2->get_result();
                $row = $res->fetch_assoc();
                $_SESSION['div_id'] = $row['Dist_id'];
                $_SESSION['div_name'] = $row['Dist_name'];
                header("location:distdash.php");
                exit;
            } elseif ($userrole == 5) {
                $stmt2 = $con->prepare("SELECT a.division_id, b.division_name FROM facilities AS a JOIN division AS b ON a.division_id = b.iddivision WHERE a.division_id = (SELECT division_id FROM s_user WHERE u_id = ?)");
                $stmt2->bind_param("i", $userid);
                $stmt2->execute();
                $res = $stmt2->get_result();
                $row = $res->fetch_assoc();
                $_SESSION['div_id'] = $row['division_id'];
                $_SESSION['div_name'] = $row['division_name'];
                header("location:regdash.php");
                exit;
            } elseif ($userrole == 6) {
                header("location:index.php");
                exit;
            } elseif ($userrole == 8) {
                $_SESSION['block_id'] = $row['block_id'];
                $stmt2 = $con->prepare("SELECT block_name FROM block_master WHERE block_id = ?");
                $stmt2->bind_param("i",  $_SESSION['block_id']);
                $stmt2->execute();
                $res = $stmt2->get_result();
                $row = $res->fetch_assoc();
                 $_SESSION['block_name'] = $row['block_name'];
                header("location:bdash.php");
                exit;
            } elseif ($userrole == 9) {
                $_SESSION['u_facilityid']=0;
                header("location:sdash.php");
                exit;
            }
        } else {
            $error = "Invalid password. Please try again.";
        }
    } else {
        echo '<div class="alert alert-danger bg-danger text-light border-0 alert-dismissible fade show" role="alert">
                Email or password wrong! Please try again!.
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>SaQshi</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 48px;
            font-weight: 600;
            letter-spacing: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }

        .logo .sa,
        .logo .shi {
            color: #00c6ff;
        }

        .logo .q-img {
            background: radial-gradient(circle, rgb(255, 0, 0) 0%, rgb(235, 105, 6) 70%);
            padding: 12px;
            border-radius: 50%;
            box-shadow: 0 0 30px rgba(234, 231, 5, 0.94);
            animation: glow 3s infinite ease-in-out;
        }

        .logo .q-img img {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            mix-blend-mode: screen;
        }

        @keyframes glow {

            0%,
            100% {
                box-shadow: 0 0 15px rgba(244, 240, 4, 0.97);
            }

            50% {
                box-shadow: 0 0 50px rgb(219, 93, 9);
            }
        }
    </style>
</head>

<body>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="auth-wrapper">
        <div class="auth-content text-center">

            <div class="card borderless">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <form method="POST">
                            <div class="card-body">
                                <div class="logo">
                                    <h3>
                                        <span class="sa">Sa</span>
                                        <span class="q-img"><img src="assets/img/n.png" alt="Q"></span>
                                        <span class="shi">shi</span>
                                    </h3>
                                </div>
                                <hr>
                                <div class="form-group mb-3">
                                    <input type="text" name="myusername" placeholder="Username" required autocomplete="off" class="form-control">
                                </div>
                                <div class="form-group mb-4">
                                    <input type="password" name="mypassword" placeholder="Password" required autocomplete="off" class="form-control">
                                </div>
                                <div class="form-group mb-4">
                                    <select name="lang" required class="form-control">
                                        <option value="5">English</option>
                                        <?php
                                        //$query = "SELECT lang_id, lang_name FROM lang_mast WHERE lang_id=5";
                                       // $result = $con->query($query);
                                       // while ($row = mysqli_fetch_assoc($result)) {
                                       //     echo '<option value="' . $row['lang_id'] . '">' . $row['lang_name'] . '</option>';
                                      //  }
                                        ?>
                                    </select>
                                </div>
                                <div class="custom-control custom-checkbox text-left mb-4 mt-2">
                                    <input type="checkbox" class="custom-control-input" id="customCheck1">
                                    <label class="custom-control-label" for="customCheck1">Save credentials.</label>
                                </div>
                                <button class="btn btn-block btn-primary mb-4" type="submit">Login</button>
                                <hr>

                                <p class="mb-0 text-muted"><?= date('Y') ?> Piramal Swasthya. All Rights Reserved.</p>
                              
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
</body>
<script>
    function validateForm() {
        const user = document.querySelector('input[name="myusername"]').value.trim();
        const pass = document.querySelector('input[name="mypassword"]').value.trim();
        if (!user || !pass) {
            alert("Please enter both username and password.");
            return false;
        }
        return true;
    }
</script>

</html>