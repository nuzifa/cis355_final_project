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

// Corrected path to database.php
require './database/database.php';

$pdo = Database::connect();
$error_message = "";

// Handle "Add New Issue" form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_issue'])) {
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $open_date = !empty($_POST['open_date']) ? $_POST['open_date'] : null; // Optional
    $close_date = !empty($_POST['close_date']) ? $_POST['close_date'] : '0000-00-00'; // Default to "0000-00-00" if empty
    $priority = !empty($_POST['priority']) ? $_POST['priority'] : null; // Optional
    $org = !empty($_POST['org']) ? $_POST['org'] : null; // Optional
    $project = !empty($_POST['project']) ? $_POST['project'] : null; // Optional
    $per_id = !empty($_POST['per_id']) ? $_POST['per_id'] : null; // Optional

    // Validate required fields
    if (empty($short_description) || empty($long_description)) {
        $error_message = "Short description and long description are required.";
    } else {
        // Insert the new issue into the database
        $sql = "INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id) 
                VALUES (:short_description, :long_description, :open_date, :close_date, :priority, :org, :project, :per_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'short_description' => $short_description,
            'long_description' => $long_description,
            'open_date' => $open_date,
            'close_date' => $close_date, // Use "0000-00-00" if empty
            'priority' => $priority,
            'org' => $org,
            'project' => $project,
            'per_id' => $per_id
        ]);

        // Reload the page to display the updated list of issues
        header("Location: issues_list.php");
        exit();
    }
}

// Handle "Update Issue" form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_issue'])) {
    $id = $_POST['id'];
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $open_date = $_POST['open_date'];
    $close_date = !empty($_POST['close_date']) ? $_POST['close_date'] : '0000-00-00'; // Default to "0000-00-00" if empty
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];
    $per_id = $_POST['per_id'];

    // Update the issue in the database
    $sql = "UPDATE iss_issues 
            SET short_description = :short_description, 
                long_description = :long_description, 
                open_date = :open_date, 
                close_date = :close_date, 
                priority = :priority, 
                org = :org, 
                project = :project, 
                per_id = :per_id 
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'short_description' => $short_description,
        'long_description' => $long_description,
        'open_date' => $open_date,
        'close_date' => $close_date, // Use "0000-00-00" if empty
        'priority' => $priority,
        'org' => $org,
        'project' => $project,
        'per_id' => $per_id,
        'id' => $id
    ]);

    // Reload the page to display the updated list of issues
    header("Location: issues_list.php");
    exit();
}

// Handle "Delete Issue" form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_issue'])) {
    $id = $_POST['id'];

    // Delete the issue from the database
    $sql = "DELETE FROM iss_issues WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

    // Reload the page to display the updated list of issues
    header("Location: issues_list.php");
    exit();
}

// Fetch persons for dropdown list
$persons_sql = "SELECT id, fname, lname FROM iss_persons ORDER BY lname ASC";
$persons_stmt = $pdo->query($persons_sql);
$persons = $persons_stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine the filter column and value
$filter_column = isset($_GET['filter_column']) ? $_GET['filter_column'] : null;
$filter_value = isset($_GET['filter_value']) ? $_GET['filter_value'] : null;

// Determine the sort column and direction
$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'id'; // Default to "id"
$sort_direction = isset($_GET['sort_direction']) && in_array($_GET['sort_direction'], ['asc', 'desc']) ? $_GET['sort_direction'] : 'asc'; // Default to "asc"
$priority_sort = isset($_GET['priority_sort']) ? $_GET['priority_sort'] : null; // Custom priority sorting

// Pagination settings
$records_per_page = 5; // Number of issues per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page from URL, default to 1
$offset = ($current_page - 1) * $records_per_page; // Calculate the offset

// Fetch total number of issues
$total_issues_sql = "SELECT COUNT(*) FROM iss_issues";
$total_issues = $pdo->query($total_issues_sql)->fetchColumn();
$total_pages = ceil($total_issues / $records_per_page); // Calculate total pages

// Fetch issues for the current page with sorting
if ($sort_column === 'priority' && $priority_sort === 'high_to_low') {
    $sql = "SELECT * FROM iss_issues 
            ORDER BY 
                CASE 
                    WHEN priority = 'High' THEN 1
                    WHEN priority = 'Medium' THEN 2
                    WHEN priority = 'Low' THEN 3
                    ELSE 4
                END, 
                id ASC 
            LIMIT :limit OFFSET :offset";
} elseif ($sort_column === 'priority' && $priority_sort === 'low_to_high') {
    $sql = "SELECT * FROM iss_issues 
            ORDER BY 
                CASE 
                    WHEN priority = 'Low' THEN 1
                    WHEN priority = 'Medium' THEN 2
                    WHEN priority = 'High' THEN 3
                    ELSE 4
                END, 
                id ASC 
            LIMIT :limit OFFSET :offset";
} else {
    $sql = "SELECT * FROM iss_issues ORDER BY $sort_column $sort_direction LIMIT :limit OFFSET :offset";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISS2: Issues List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-3">
        <h2 class="text-center">Issues List</h2>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <h3>All Issues</h3>
            <div>
                <a href="issues_list.php?filter=all" class="btn btn-primary">All Issues</a>
                <a href="issues_list.php?filter=open" class="btn btn-secondary">Open Issues</a>
            </div>
            <a href="logout.php" class="btn btn-warning">Logout</a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addIssueModal">+</button>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger mt-3">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <table class="table table-striped table-sm mt-2">
            <thead class="table-dark">
                <tr>
                    <th>
                        <a href="?sort_column=id&sort_direction=<?= isset($_GET['sort_direction']) && $_GET['sort_direction'] === 'asc' ? 'desc' : 'asc'; ?>" class="text-white">ID</a>
                    </th>
                    <th>
                        <a href="?sort_column=short_description&sort_direction=<?= isset($_GET['sort_direction']) && $_GET['sort_direction'] === 'asc' ? 'desc' : 'asc'; ?>" class="text-white">Short Description</a>
                    </th>
                    <th>
                        <a href="?sort_column=open_date&sort_direction=<?= isset($_GET['sort_direction']) && $_GET['sort_direction'] === 'asc' ? 'desc' : 'asc'; ?>" class="text-white">Open Date</a>
                    </th>
                    <th>
                        <a href="?sort_column=close_date&sort_direction=<?= isset($_GET['sort_direction']) && $_GET['sort_direction'] === 'asc' ? 'desc' : 'asc'; ?>" class="text-white">Close Date</a>
                    </th>
                    <th>
                        <a href="?sort_column=priority&priority_sort=<?= isset($_GET['priority_sort']) && $_GET['priority_sort'] === 'low_to_high' ? 'high_to_low' : 'low_to_high'; ?>" class="text-white">Priority</a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($issues as $issue) : ?>
                    <tr>
                        <td><?= htmlspecialchars($issue['id']); ?></td>
                        <td><?= htmlspecialchars($issue['short_description']); ?></td>
                        <td><?= htmlspecialchars($issue['open_date']); ?></td>
                        <td><?= htmlspecialchars($issue['close_date']); ?></td>
                        <td><?= htmlspecialchars($issue['priority']); ?></td>
                        <td>
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readIssue<?= $issue['id']; ?>">R</button>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateIssue<?= $issue['id']; ?>">U</button>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteIssue<?= $issue['id']; ?>">D</button>
                        </td>
                    </tr>

                    <!-- Add Issue Modal -->
                    <div class="modal fade" id="addIssueModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title">Add New Issue</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <label for="short_description">Short Description</label>
                                        <input type="text" name="short_description" class="form-control mb-2" required>

                                        <label for="long_description">Long Description</label>
                                        <textarea name="long_description" class="form-control mb-2" required></textarea>

                                        <label for="open_date">Open Date</label>
                                        <input type="date" name="open_date" class="form-control mb-2" value="<?= date('Y-m-d'); ?>" required>

                                        <label for="close_date">Close Date</label>
                                        <input type="date" name="close_date" class="form-control mb-2">

                                        <label for="priority">Priority</label>
                                        <select name="priority" class="form-control mb-2" required>
                                            <option value="">-- Select Priority --</option>
                                            <option value="High">High</option>
                                            <option value="Medium">Medium</option>
                                            <option value="Low">Low</option>
                                        </select>

                                        <label for="org">Org</label>
                                        <input type="text" name="org" class="form-control mb-2">

                                        <label for="project">Project</label>
                                        <input type="text" name="project" class="form-control mb-2">

                                        <label for="per_id">Person Responsible</label>
                                        <select name="per_id" class="form-control mb-3" required>
                                            <option value="">-- Select Person --</option>
                                            <?php foreach ($persons as $person): ?>
                                                <option value="<?= $person['id']; ?>">
                                                    <?= htmlspecialchars($person['lname'] . ', ' . $person['fname']) . ' (' . $person['id'] .  ') '; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit" name="create_issue" class="btn btn-success">Add Issue</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Read Modal -->
                    <div class="modal fade" id="readIssue<?= $issue['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Issue Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>ID:</strong> <?= htmlspecialchars($issue['id']); ?></p>
                                    <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']); ?></p>
                                    <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']); ?></p>
                                    <p><strong>Open Date:</strong> <?= htmlspecialchars($issue['open_date']); ?></p>
                                    <p><strong>Close Date:</strong> <?= htmlspecialchars($issue['close_date']); ?></p>
                                    <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']); ?></p>
                                    <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']); ?></p>
                                    <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']); ?></p>
                                    <p><strong>Person:</strong> <?= htmlspecialchars($issue['per_id']); ?></p>
                                    
                                    
                                    <?php
                                        $com_iss_id = $issue['id'];
                                        // Fetch comments this particular issue: gpcorser
                                        $comments_sql = "SELECT * FROM iss_comments, iss_persons 
                                            WHERE iss_id = $com_iss_id
                                            AND `iss_persons`.id = per_id
                                            ORDER BY posted_date DESC";
                                        $comments_stmt = $pdo->query($comments_sql);
                                        $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                    ?>
<?php foreach ($comments as $comment) : ?>
    <div style="font-family: monospace;">
        <span style="display:inline-block; width: 180px;">
            <?= htmlspecialchars($comment['lname'] . ", " . $comment['fname']) ?>
        </span>
        <span style="display:inline-block; width: 300px;">
            <?= htmlspecialchars($comment['short_comment']) ?>
        </span>
        <span style="display:inline-block; width: 140px;">
            <?= htmlspecialchars($comment['posted_date']) ?>
        </span>
        <span style="display:inline-block; width: 150px;">
            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readIssue<?= $comment['id']; ?>">R</button>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateIssue<?= $comment['id']; ?>">U</button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteIssue<?= $comment['id']; ?>">D</button>
        </span>
    </div>
<?php endforeach; ?>


                                    
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Modal -->
                    <div class="modal fade" id="updateIssue<?= $issue['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Issue</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= $issue['id']; ?>">
                                        <label for="short_description">Short Description</label>
                                        <input type="text" name="short_description" class="form-control mb-2" value="<?= htmlspecialchars($issue['short_description']); ?>" required>

                                        <label for="long_description">Long Description</label>
                                        <textarea name="long_description" class="form-control mb-2" required><?= htmlspecialchars($issue['long_description']); ?></textarea>

                                        <label for="open_date">Open Date</label>
                                        <input type="date" name="open_date" class="form-control mb-2" value="<?= $issue['open_date']; ?>" readonly>

                                        <label for="close_date">Close Date</label>
                                        <input type="date" name="close_date" class="form-control mb-2" value="<?= $issue['close_date']; ?>">

                                        <label for="priority">Priority</label>
                                        <select name="priority" class="form-control mb-2" required>
                                            <option value="High" <?= $issue['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                                            <option value="Medium" <?= $issue['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="Low" <?= $issue['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                                        </select>

                                        <label for="org">Org</label>
                                        <input type="text" name="org" class="form-control mb-2" value="<?= $issue['org']; ?>">

                                        <label for="project">Project</label>
                                        <input type="text" name="project" class="form-control mb-2" value="<?= $issue['project']; ?>">

                                        <label for="per_id">Person Responsible</label>
                                        <input type="number" name="per_id" class="form-control mb-2" value="<?= $issue['per_id']; ?>">

                                        <button type="submit" name="update_issue" class="btn btn-primary">Save Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteIssue<?= $issue['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Confirm Deletion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete this issue?</p>
                                    <p><strong>ID:</strong> <?= htmlspecialchars($issue['id']); ?></p>
                                    <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']); ?></p>
                                    <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']); ?></p>
                                    <p><strong>Open Date:</strong> <?= htmlspecialchars($issue['open_date']); ?></p>
                                    <p><strong>Close Date:</strong> <?= htmlspecialchars($issue['close_date']); ?></p>
                                    <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']); ?></p>
                                    <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']); ?></p>
                                    <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']); ?></p>
                                    <p><strong>Person:</strong> <?= htmlspecialchars($issue['per_id']); ?></p>

                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= $issue['id']; ?>">
                                        <button type="submit" name="delete_issue" class="btn btn-danger">Delete</button>
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
                        <a class="page-link" href="?sort_column=<?= $sort_column; ?>&sort_direction=<?= $sort_direction; ?>&priority_sort=<?= $priority_sort; ?>&page=<?= $current_page - 1; ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="?sort_column=<?= $sort_column; ?>&sort_direction=<?= $sort_direction; ?>&priority_sort=<?= $priority_sort; ?>&page=<?= $i; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?sort_column=<?= $sort_column; ?>&sort_direction=<?= $sort_direction; ?>&priority_sort=<?= $priority_sort; ?>&page=<?= $current_page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php Database::disconnect(); ?>
