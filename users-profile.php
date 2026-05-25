<?php
include("assets/head/h.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uid = (int)($_SESSION['userid'] ?? 0);

if ($uid <= 0) {
    die("Invalid Session");
}

$message = "";
$messageType = "";

if (isset($_POST['postsubmit'])) {

    $oldpass = trim($_POST['password'] ?? '');
    $newpass = trim($_POST['newpassword'] ?? '');
    $repass  = trim($_POST['renewpassword'] ?? '');

    if (
        empty($oldpass) ||
        empty($newpass) ||
        empty($repass)
    ) {

        $message = "All fields are required.";
        $messageType = "danger";

    } elseif ($newpass !== $repass) {

        $message = "New password and Re-enter password do not match.";
        $messageType = "danger";

    } elseif (strlen($newpass) < 6) {

        $message = "Password must be at least 6 characters.";
        $messageType = "danger";

    } else {

        $stmt = $con->prepare(
            "SELECT u_password
             FROM s_user
             WHERE u_id = ?
             LIMIT 1"
        );

        if (!$stmt) {
            die($con->error);
        }

        $stmt->bind_param("i", $uid);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            $message = "User not found.";
            $messageType = "danger";

        } else {

            $row = $result->fetch_assoc();

            $dbPassword = $row['u_password'];

            $isValid = false;

            // Old plain password support
            if ($oldpass === $dbPassword) {

                $isValid = true;

            }
            // New hashed password support
            elseif (password_verify($oldpass, $dbPassword)) {

                $isValid = true;
            }

            if (!$isValid) {

                $message = "Current password does not match.";
                $messageType = "danger";

            } else {

                $hashedPassword = password_hash(
                    $newpass,
                    PASSWORD_DEFAULT
                );

                $updateStmt = $con->prepare(
                    "UPDATE s_user
                     SET u_password = ?
                     WHERE u_id = ?"
                );

                if (!$updateStmt) {
                    die($con->error);
                }

                $updateStmt->bind_param(
                    "si",
                    $hashedPassword,
                    $uid
                );

                if ($updateStmt->execute()) {

                    $message = "Password updated successfully.";
                    $messageType = "success";

                } else {

                    $message = "Password update failed.";
                    $messageType = "danger";
                }

                $updateStmt->close();
            }
        }

        $stmt->close();
    }
}
?>

<div class="pcoded-main-container">

    <div class="pcoded-content">

        <div class="pagetitle mb-2">

            <h5 class="fw-bold text-primary">
                Password Update / Change
            </h5>

        </div>

        <div class="row">

            <div class="col-xl-8">

                <div class="card">

                    <div class="card-body pt-4">

                        <?php if (!empty($message)) { ?>

                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show"
                                role="alert">

                                <?php echo htmlspecialchars($message); ?>

                                <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="alert">
                                </button>

                            </div>

                        <?php } ?>

                        <form method="POST" novalidate autocomplete="off">
<?= csrf(); ?>
                            <!-- Current Password -->

                            <div class="row mb-3">

                                <label class="col-md-4 col-lg-3 col-form-label">
                                    Current Password
                                </label>

                                <div class="col-md-8 col-lg-9">

                                    <div class="input-group">

                                        <input
                                            name="password"
                                            type="password"
                                            class="form-control"
                                            id="currentPassword"
                                            autocomplete="new-password"
                                            required>

                                        <span class="input-group-text"
                                            onclick="togglePassword('currentPassword',this)"
                                            style="cursor:pointer;">

                                            <i class="bi bi-eye"></i>

                                        </span>

                                    </div>

                                </div>

                            </div>

                            <!-- New Password -->

                            <div class="row mb-3">

                                <label class="col-md-4 col-lg-3 col-form-label">
                                    New Password
                                </label>

                                <div class="col-md-8 col-lg-9">

                                    <div class="input-group">

                                        <input
                                            name="newpassword"
                                            type="password"
                                            class="form-control"
                                            id="newPassword"
                                            autocomplete="new-password"
                                            minlength="6"
                                            required>

                                        <span class="input-group-text"
                                            onclick="togglePassword('newPassword',this)"
                                            style="cursor:pointer;">

                                            <i class="bi bi-eye"></i>

                                        </span>

                                    </div>

                                </div>

                            </div>

                            <!-- Re-enter Password -->

                            <div class="row mb-3">

                                <label class="col-md-4 col-lg-3 col-form-label">
                                    Re-enter New Password
                                </label>

                                <div class="col-md-8 col-lg-9">

                                    <div class="input-group">

                                        <input
                                            name="renewpassword"
                                            type="password"
                                            class="form-control"
                                            id="renewPassword"
                                            autocomplete="new-password"
                                            minlength="6"
                                            required>

                                        <span class="input-group-text"
                                            onclick="togglePassword('renewPassword',this)"
                                            style="cursor:pointer;">

                                            <i class="bi bi-eye"></i>

                                        </span>

                                    </div>

                                </div>

                            </div>

                            <div class="text-center">

                                <button
                                    type="submit"
                                    name="postsubmit"
                                    class="btn btn-primary">

                                    Change Password

                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

function togglePassword(fieldId, iconElement)
{
    let field = document.getElementById(fieldId);

    let icon = iconElement.querySelector("i");

    if(field.type === "password")
    {
        field.type = "text";

        icon.classList.remove("bi-eye");

        icon.classList.add("bi-eye-slash");
    }
    else
    {
        field.type = "password";

        icon.classList.remove("bi-eye-slash");

        icon.classList.add("bi-eye");
    }
}

</script>

<?php include("assets/head/f.php"); ?>