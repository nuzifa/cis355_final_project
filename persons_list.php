<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}


if ($_SESSION['admin'] !== 'Y') {
    header("Location: issues_list.php"); 
    exit();
}

require './database/database.php';
$pdo = Database::connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_person'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $mobile = $_POST['mobile'];
    $admin = $_POST['admin'];
    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/^\d{3}-\d{3}-\d{4}$/', $mobile)) {
        $errors[] = "Mobile number must be in the format 000-000-0000.";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM iss_persons WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Email is already registered.";
    }

    if (empty($errors)) {
        $hashedPassword = md5('learn' . $password);

        $sql = "INSERT INTO iss_persons (fname, lname, email, pwd_hash, pwd_salt, mobile, admin) 
                VALUES (:fname, :lname, :email, :pwd_hash, :pwd_salt, :mobile, :admin)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':fname' => $fname,
            ':lname' => $lname,
            ':email' => $email,
            ':pwd_hash' => $hashedPassword,
            ':pwd_salt' => $password,
            ':mobile' => $mobile,
            ':admin' => $admin
        ]);
        header("Location: persons_list.php"); 
        exit();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_person'])) {
    $id = $_POST['id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $admin = $_POST['admin'];
    $mobile = $_POST['mobile'];

    if (!empty($fname) && !empty($lname) && !empty($email) && !empty($mobile)) {
        $sql = "UPDATE iss_persons 
                SET fname = :fname, lname = :lname, email = :email, admin = :admin, mobile = :mobile 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':fname' => $fname,
            ':lname' => $lname,
            ':email' => $email,
            ':admin' => $admin,
            ':mobile' => $mobile, 
            ':id' => $id
        ]);
        header("Location: persons_list.php"); 
        exit();
    } else {
        $error_message = "All fields are required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_person'])) {
    $id = $_POST['id'];

    if (!empty($id)) {
        $sql = "DELETE FROM iss_persons WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        header("Location: persons_list.php"); 
        exit();
    } else {
        $error_message = "Invalid person ID.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_person'])) {
    $id = $_POST['id'];

    if (!empty($id)) {
        $sql = "UPDATE iss_persons SET verified = '1' WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        header("Location: persons_list.php"); 
        exit();
    } else {
        $error_message = "Invalid person ID.";
    }
}

$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'id'; 
$sort_direction = isset($_GET['sort_direction']) && in_array($_GET['sort_direction'], ['asc', 'desc']) ? $_GET['sort_direction'] : 'asc';

$records_per_page = 5; 
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
$offset = ($current_page - 1) * $records_per_page; 

$total_persons_sql = "SELECT COUNT(*) FROM iss_persons";
$total_persons = $pdo->query($total_persons_sql)->fetchColumn();
$total_pages = ceil($total_persons / $records_per_page); 

$sql = "SELECT * FROM iss_persons ORDER BY $sort_column $sort_direction LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persons List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .text-danger {
            display: block;
        }
    </style>
</head>

<body>
    <div class="container mt-3">
        <h2 class="text-center">Persons List</h2>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <h3>All Persons</h3>
            <div class="d-flex align-items-center">
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addPersonModal">Add Person</button>
                <a href="logout.php" class="btn btn-warning">Logout</a>
            </div>
        </div>

        <table class="table table-striped table-sm mt-2">
            <thead class="table-dark">
                <tr>
                    <th>
                        <a href="?sort_column=id&sort_direction=<?= $sort_column === 'id' && $sort_direction === 'asc' ? 'desc' : 'asc'; ?>" class="text-white">ID</a>
                    </th>
                    <th>
                        <a href="?sort_column=fname&sort_direction=<?= $sort_column === 'fname' && $sort_direction === 'asc' ? 'desc' : 'asc'; ?>" class="text-white">First Name</a>
                    </th>
                    <th>
                        <a href="?sort_column=lname&sort_direction=<?= $sort_column === 'lname' && $sort_direction === 'asc' ? 'desc' : 'asc'; ?>" class="text-white">Last Name</a>
                    </th>
                    <th>
                        <a href="?sort_column=email&sort_direction=<?= $sort_column === 'email' && $sort_direction === 'asc' ? 'desc' : 'asc'; ?>" class="text-white">Email</a>
                    </th>
                    <th>
                        <a href="?sort_column=admin&sort_direction=<?= $sort_column === 'admin' && $sort_direction === 'asc' ? 'desc' : 'asc'; ?>" class="text-white">Admin</a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($persons as $person): ?>
                    <tr>
                        <td><?= htmlspecialchars($person['id']); ?></td>
                        <td><?= htmlspecialchars($person['fname']); ?></td>
                        <td><?= htmlspecialchars($person['lname']); ?></td>
                        <td><?= htmlspecialchars($person['email']); ?></td>
                        <td><?= htmlspecialchars($person['admin'] === 'Y' ? 'Yes' : 'No'); ?></td>
                        <td>
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readPerson<?= $person['id']; ?>">R</button>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updatePerson<?= $person['id']; ?>">U</button>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deletePerson<?= $person['id']; ?>">D</button>

                            <?php if ($person['verified'] !== '1'): ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $person['id']; ?>">
                                    <button type="submit" name="verify_person" class="btn btn-success btn-sm">Verify</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <div class="modal fade" id="readPerson<?= $person['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Person Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>ID:</strong> <?= htmlspecialchars($person['id']); ?></p>
                                    <p><strong>First Name:</strong> <?= htmlspecialchars($person['fname']); ?></p>
                                    <p><strong>Last Name:</strong> <?= htmlspecialchars($person['lname']); ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($person['email']); ?></p>
                                    <p><strong>Admin:</strong> <?= htmlspecialchars($person['admin'] === 'Y' ? 'Yes' : 'No'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="updatePerson<?= $person['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Person</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="id" value="<?= $person['id']; ?>">
                                        <input type="hidden" name="update_person" value="1">

                                        <label for="fname">First Name</label>
                                        <input type="text" name="fname" class="form-control mb-2" value="<?= htmlspecialchars($person['fname']); ?>" required>

                                        <label for="lname">Last Name</label>
                                        <input type="text" name="lname" class="form-control mb-2" value="<?= htmlspecialchars($person['lname']); ?>" required>

                                        <label for="email">Email</label>
                                        <input type="email" name="email" class="form-control mb-2" value="<?= htmlspecialchars($person['email']); ?>" required>

                                        <label for="admin">Admin</label>
                                        <select name="admin" class="form-control mb-2" required>
                                            <option value="Y" <?= $person['admin'] === 'Y' ? 'selected' : ''; ?>>Yes</option>
                                            <option value="N" <?= $person['admin'] === 'N' ? 'selected' : ''; ?>>No</option>
                                        </select>

                                        <label for="mobile">Mobile</label>
                                        <input type="text" name="mobile" class="form-control mb-2" value="<?= htmlspecialchars($person['mobile']); ?>" required
                                            pattern="\d{3}-\d{3}-\d{4}"
                                            title="Phone number must be in the format 000-000-0000"
                                            oninput="this.value = this.value.replace(/[^0-9\-]/g, '').replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3').slice(0, 12);">
                                        <div id="mobileError" class="text-danger"></div>

                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="deletePerson<?= $person['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Confirm Deletion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete this person?</p>
                                    <p><strong>ID:</strong> <?= htmlspecialchars($person['id']); ?></p>
                                    <p><strong>Name:</strong> <?= htmlspecialchars($person['fname'] . ' ' . $person['lname']); ?></p>
                                    <form method="POST" action="">
                                        <input type="hidden" name="id" value="<?= $person['id']; ?>">
                                        <input type="hidden" name="delete_person" value="1">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="d-flex justify-content-center mt-4">
            <nav>
                <ul class="pagination">
                    <li class="page-item <?= $current_page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?sort_column=<?= $sort_column; ?>&sort_direction=<?= $sort_direction; ?>&page=<?= $current_page - 1; ?>">Previous</a>
                    </li>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="?sort_column=<?= $sort_column; ?>&sort_direction=<?= $sort_direction; ?>&page=<?= $i; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?sort_column=<?= $sort_column; ?>&sort_direction=<?= $sort_direction; ?>&page=<?= $current_page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="d-flex justify-content-center mt-2">
            <a href="issues_list.php" class="btn btn-info">Back to Issues List</a>
        </div>
    </div>

    <div class="modal fade" id="addPersonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Person</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="add_person" value="1">

                        <label for="fname">First Name</label>
                        <input type="text" name="fname" id="fname" class="form-control mb-2" required onblur="validateFirstName()">
                        <div id="fnameError" class="text-danger"></div>

                        <label for="lname">Last Name</label>
                        <input type="text" name="lname" id="lname" class="form-control mb-2" required onblur="validateLastName()">
                        <div id="lnameError" class="text-danger"></div>

                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control mb-2" required onblur="validateEmail()">
                        <div id="emailError" class="text-danger"></div>

                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control mb-2" required onblur="validatePassword()">
                        <div id="passwordError" class="text-danger"></div>

                        <label for="mobile">Mobile</label>
                        <input type="text" name="mobile" id="mobile" class="form-control mb-2" required
                            pattern="\d{3}-\d{3}-\d{4}"
                            title="Phone number must be in the format 000-000-0000"
                            oninput="this.value = this.value.replace(/[^0-9\-]/g, '').replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3').slice(0, 12);"
                            onblur="validateMobile()">
                        <div id="mobileError" class="text-danger"></div>

                        <label for="admin">Admin</label>
                        <select name="admin" id="admin" class="form-control mb-2" required>
                            <option value="Y">Yes</option>
                            <option value="N">No</option>
                        </select>

                        <button type="submit" class="btn btn-primary">Add Person</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateFirstName() {
            const fname = document.getElementById('fname').value;
            const fnameError = document.getElementById('fnameError');

            if (fname.trim() === '') {
                fnameError.textContent = "First name is required.";
            } else {
                fnameError.textContent = "";
            }
        }

        function validateLastName() {
            const lname = document.getElementById('lname').value;
            const lnameError = document.getElementById('lnameError');

            if (lname.trim() === '') {
                lnameError.textContent = "Last name is required.";
            } else {
                lnameError.textContent = "";
            }
        }

        function validateEmail() {
            const email = document.getElementById('email').value;
            const emailError = document.getElementById('emailError');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailRegex.test(email)) {
                emailError.textContent = "Invalid email format.";
            } else {
                emailError.textContent = "";
            }
        }

        function validatePassword() {
            const password = document.getElementById('password').value;
            const passwordError = document.getElementById('passwordError');

            if (password.length < 8) {
                passwordError.textContent = "Password must be at least 8 characters long.";
            } else {
                passwordError.textContent = "";
            }
        }

        function validateMobile() {
            const mobile = document.getElementById('mobile').value;
            const mobileError = document.getElementById('mobileError');
            const mobileRegex = /^\d{3}-\d{3}-\d{4}$/;

            if (!mobileRegex.test(mobile)) {
                mobileError.textContent = "Mobile number must be in the format 000-000-0000.";
            } else {
                mobileError.textContent = "";
            }
        }
    </script>
</body>

</html>

<?php Database::disconnect(); ?>