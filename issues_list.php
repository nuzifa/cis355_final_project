<?php
ob_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

date_default_timezone_set('America/New_York');

if (!isset($_SESSION['user_id'])) {
  session_destroy();
  header("Location: login.php");
  exit();
}

if (!isset($_SESSION['filter'])) {
  $_SESSION['filter'] = 'open'; 
}

if (isset($_GET['filter'])) {
  $_SESSION['filter'] = $_GET['filter'];
}

$filter = $_SESSION['filter'];

require_once './database/database.php'; 

$pdo = Database::connect();
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_comment'])) {
  $short_comment = trim($_POST['short_comment']);
  $long_comment  = trim($_POST['long_comment']);
  $iss_id        = $_POST['iss_id'];
  $per_id        = $_SESSION['user_id']; 
  $posted_date   = date('Y-m-d H:i:s'); 

  if (!empty($short_comment) && !empty($long_comment)) {
    $sql = "INSERT INTO iss_comments (short_comment, long_comment, iss_id, per_id, posted_date) 
              VALUES (:short_comment, :long_comment, :iss_id, :per_id, :posted_date)";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([
      ':short_comment' => $short_comment,
      ':long_comment'  => $long_comment,
      ':iss_id'        => $iss_id,
      ':per_id'        => $per_id,
      ':posted_date'   => $posted_date
    ])) {
      $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM iss_comments WHERE iss_id = :iss_id");
      $count_stmt->execute([':iss_id' => $iss_id]);
      $total_comments = $count_stmt->fetchColumn();
      $comments_per_page = 5;
      $new_comment_page = ceil($total_comments / $comments_per_page);

      header("Location: issues_list.php?open_modal=readIssue$iss_id&comment_page=$new_comment_page");
      exit();
    } else {
      $error_message = "Failed to add comment. Please try again.";
    }
  } else {
    $error_message = "Both short and long comments are required.";
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_issue'])) {
  $short_description = trim($_POST['short_description']);
  $long_description  = trim($_POST['long_description']);
  $open_date         = !empty($_POST['open_date']) ? $_POST['open_date'] : date('Y-m-d');
  $close_date        = !empty($_POST['close_date']) ? $_POST['close_date'] : '0000-00-00';
  $priority          = $_POST['priority'] ?? null;
  $org               = $_POST['org'] ?? '';
  $project           = $_POST['project'] ?? '';
  $per_id            = $_POST['per_id'] ?? null;
  $created_by        = $_SESSION['user_id'];

  if (empty($short_description) || empty($long_description)) {
    $error_message = "Short description and long description are required.";
  } else {
    $sql = "INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id, created_by) 
              VALUES (:short_description, :long_description, :open_date, :close_date, :priority, :org, :project, :per_id, :created_by)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      'short_description' => $short_description,
      'long_description'  => $long_description,
      'open_date'         => $open_date,
      'close_date'        => $close_date,
      'priority'          => $priority,
      'org'               => $org,
      'project'           => $project,
      'per_id'            => $per_id,
      'created_by'        => $created_by
    ]);

    $new_issue_id = $pdo->lastInsertId();

    $total_issues_sql = "SELECT COUNT(*) 
      FROM iss_issues 
      JOIN iss_persons ON iss_issues.per_id = iss_persons.id";
    $total_issues = $pdo->query($total_issues_sql);
    $total_count = $total_issues->fetchColumn();
    
    $records_per_page = 5;
    $new_page = ceil($total_count / $records_per_page);
    
    header("Location: issues_list.php?" . http_build_query([
      'page' => $new_page,
      'filter' => $_SESSION['filter'] ?? 'open',
      'filter_person' => $_GET['filter_person'] ?? '',
      'sort_column' => $_GET['sort_column'] ?? 'id',
      'sort_direction' => $_GET['sort_direction'] ?? 'asc',
      'priority_sort' => $_GET['priority_sort'] ?? '',
    ]));
    exit();
      }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_issue'])) {
  $id = $_POST['id'];
  $short_description = trim($_POST['short_description']);
  $long_description = trim($_POST['long_description']);
  $open_date = $_POST['open_date'];
  $close_date = !empty($_POST['close_date']) ? $_POST['close_date'] : '0000-00-00';
  $priority = $_POST['priority'];
  $org = $_POST['org'];
  $project = $_POST['project'];
  $per_id = $_POST['per_id'];

  $auth_sql = "SELECT per_id, created_by FROM iss_issues WHERE id = :id";
  $auth_stmt = $pdo->prepare($auth_sql);
  $auth_stmt->execute(['id' => $id]);
  $issue = $auth_stmt->fetch(PDO::FETCH_ASSOC);

  if ($issue && ($_SESSION['admin'] === 'Y' || $_SESSION['user_id'] == $issue['per_id'] || $_SESSION['user_id'] == $issue['created_by'])) {
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
      'close_date' => $close_date,
      'priority' => $priority,
      'org' => $org,
      'project' => $project,
      'per_id' => $per_id,
      'id' => $id
    ]);
  }

  header("Location: issues_list.php?" . http_build_query([
    'page' => $_GET['page'] ?? 1,
    'filter' => $_SESSION['filter'] ?? 'open',
    'filter_person' => $_GET['filter_person'] ?? '',
    'sort_column' => $_GET['sort_column'] ?? 'id',
    'sort_direction' => $_GET['sort_direction'] ?? 'asc',
    'priority_sort' => $_GET['priority_sort'] ?? '',
  ]));
  exit();
}

/** 
 * --- HANDLE DELETE ISSUE ---
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_issue'])) {
  $id = $_POST['id'];

  $auth_sql = "SELECT per_id, created_by FROM iss_issues WHERE id = :id";
  $auth_stmt = $pdo->prepare($auth_sql);
  $auth_stmt->execute(['id' => $id]);
  $issue = $auth_stmt->fetch(PDO::FETCH_ASSOC);

  if ($_SESSION['admin'] === 'Y' || $_SESSION['user_id'] === $issue['per_id'] || $_SESSION['user_id'] === $issue['created_by']) {
    $sql = "DELETE FROM iss_issues WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
  }

  $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
  $filter_person = $_POST['filter_person'] ?? '';

  $whereConditions = [];
  $params = [];

  if ($_SESSION['filter'] === 'open') {
    $whereConditions[] = "(close_date IS NULL OR close_date = '0000-00-00')";
  }
  if (!empty($filter_person)) {
    $whereConditions[] = "per_id = :filter_person";
    $params['filter_person'] = $filter_person;
  }

  $whereClause = '';
  if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(' AND ', $whereConditions);
  }

  $count_sql = "SELECT COUNT(*) FROM iss_issues $whereClause";
  $count_stmt = $pdo->prepare($count_sql);
  foreach ($params as $key => $value) {
    $count_stmt->bindValue(":$key", $value);
  }
  $count_stmt->execute();
  $total_issues = (int)$count_stmt->fetchColumn();

  $records_per_page = 5;
  $total_pages = max(ceil($total_issues / $records_per_page), 1);

  if ($page > $total_pages && $total_pages > 0) {
    header("Location: issues_list.php?" . http_build_query([
      'page' => $total_pages,
      'filter' => $_SESSION['filter'] ?? 'open',
      'filter_person' => $_GET['filter_person'] ?? '',
      'sort_column' => $_GET['sort_column'] ?? 'id',
      'sort_direction' => $_GET['sort_direction'] ?? 'asc',
      'priority_sort' => $_GET['priority_sort'] ?? ''
    ]));
    exit();
  }

  header("Location: issues_list.php?" . http_build_query([
    'page' => $page,
    'filter' => $_SESSION['filter'] ?? 'open',
    'filter_person' => $_GET['filter_person'] ?? '',
    'sort_column' => $_GET['sort_column'] ?? 'id',
    'sort_direction' => $_GET['sort_direction'] ?? 'asc',
    'priority_sort' => $_GET['priority_sort'] ?? ''
  ]));
  exit();
}

/** 
 * --- HANDLE UPDATE COMMENT ---
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comment'])) {
  $comment_id = $_POST['comment_id'];
  $issue_id = $_POST['issue_id'];
  $short_comment = trim($_POST['short_comment']);
  $long_comment = trim($_POST['long_comment']);

  if (!empty($short_comment) && !empty($long_comment)) {
    $sql = "UPDATE iss_comments SET short_comment = :short_comment, long_comment = :long_comment WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':short_comment' => $short_comment,
      ':long_comment'  => $long_comment,
      ':id'            => $comment_id
    ]);

    $index_sql = "SELECT COUNT(*) AS position
                    FROM iss_comments 
                    WHERE iss_id = :iss_id 
                    AND (posted_date > (SELECT posted_date FROM iss_comments WHERE id = :id)
                        OR (posted_date = (SELECT posted_date FROM iss_comments WHERE id = :id) AND id > :id))";
    $index_stmt = $pdo->prepare($index_sql);
    $index_stmt->execute([
      ':iss_id' => $issue_id,
      ':id'     => $comment_id
    ]);
    $position = $index_stmt->fetchColumn(); 

    $comments_per_page = 5;
    $target_page = floor($position / $comments_per_page) + 1;

    header("Location: issues_list.php?open_modal=readIssue$issue_id&comment_page=$target_page");
    exit();
  } else {
    header("Location: issues_list.php?" . http_build_query([
      'page' => $_GET['page'] ?? 1,
      'filter' => $_SESSION['filter'] ?? 'open',
      'filter_person' => $_GET['filter_person'] ?? '',
      'sort_column' => $_GET['sort_column'] ?? 'id',
      'sort_direction' => $_GET['sort_direction'] ?? 'asc',
      'priority_sort' => $_GET['priority_sort'] ?? '',
      'open_modal' => "readIssue$id" 
    ]));
    exit();
  }
}


/** 
 * --- HANDLE DELETE COMMENT ---
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
  $comment_id = $_POST['comment_id'];

  $fetch_sql = "SELECT iss_id, posted_date FROM iss_comments WHERE id = :id";
  $fetch_stmt = $pdo->prepare($fetch_sql);
  $fetch_stmt->execute([':id' => $comment_id]);
  $comment = $fetch_stmt->fetch(PDO::FETCH_ASSOC);

  if ($comment) {
    $issue_id = $comment['iss_id'];
    $posted_date = $comment['posted_date'];

    $index_sql = "SELECT COUNT(*) FROM iss_comments 
                    WHERE iss_id = :iss_id 
                    AND (posted_date > :posted_date OR (posted_date = :posted_date AND id > :id))";
    $index_stmt = $pdo->prepare($index_sql);
    $index_stmt->execute([
      ':iss_id' => $issue_id,
      ':posted_date' => $posted_date,
      ':id' => $comment_id
    ]);
    $position = $index_stmt->fetchColumn();

    $comments_per_page = 5;
    $target_page = floor($position / $comments_per_page) + 1;

    $delete_stmt = $pdo->prepare("DELETE FROM iss_comments WHERE id = :id");
    $delete_stmt->execute([':id' => $comment_id]);

    header("Location: issues_list.php?open_modal=readIssue$issue_id&comment_page=$target_page");
    exit();
  }

  header("Location: issues_list.php");
  exit();
}




$persons_sql = "SELECT id, fname, lname FROM iss_persons ORDER BY lname ASC";
$persons_stmt = $pdo->query($persons_sql);
$persons = $persons_stmt->fetchAll(PDO::FETCH_ASSOC);


$sort_column    = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'id';
$sort_direction = isset($_GET['sort_direction']) ? strtolower($_GET['sort_direction']) : 'asc';
$priority_sort  = isset($_GET['priority_sort']) ? $_GET['priority_sort'] : '';


$valid_columns = ['id', 'short_description', 'open_date', 'close_date', 'priority', 'person_name'];
if (!in_array($sort_column, $valid_columns)) {
  $sort_column = 'id';
}
if (!in_array($sort_direction, ['asc', 'desc'])) {
  $sort_direction = 'desc';
}


if ($sort_column === 'person_name') {
  $orderClause = "ORDER BY person_name $sort_direction";
} elseif ($sort_column === 'priority') {
  $order = ($sort_direction === 'asc') ?
    "FIELD(priority, 'Low', 'Medium', 'High')" :
    "FIELD(priority, 'High', 'Medium', 'Low')";
  $orderClause = "ORDER BY $order";
} else {
  $orderClause = "ORDER BY iss_issues.$sort_column $sort_direction";
}


$records_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

$whereConditions = [];
$params = [];

if ($filter === 'open') {
  $whereConditions[] = "(iss_issues.close_date IS NULL OR iss_issues.close_date = '0000-00-00')";
}

if (isset($_GET['filter_person']) && !empty($_GET['filter_person'])) {
  $whereConditions[] = "iss_issues.per_id = :filter_person";
  $params['filter_person'] = $_GET['filter_person'];
}

$whereClause = "";
if ($whereConditions) {
  $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

$total_issues_sql = "SELECT COUNT(*) 
  FROM iss_issues 
  JOIN iss_persons ON iss_issues.per_id = iss_persons.id 
  $whereClause";
$total_issues = $pdo->prepare($total_issues_sql);
foreach ($params as $key => &$val) {
  $total_issues->bindValue(":$key", $val);
}
$total_issues->execute();
$total_count = $total_issues->fetchColumn();

$sql = "SELECT iss_issues.*, iss_issues.created_by, CONCAT(iss_persons.fname, ' ', iss_persons.lname) AS person_name 
        FROM iss_issues 
        JOIN iss_persons ON iss_issues.per_id = iss_persons.id 
        $whereClause
        $orderClause
        LIMIT :limit OFFSET :offset";
if (empty($orderClause)) {
  $orderClause = "ORDER BY iss_issues.id DESC";
}

$stmt = $pdo->prepare($sql);
foreach ($params as $key => &$val) {
  $stmt->bindValue(":$key", $val);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pages = max(1, ceil($total_count / $records_per_page));

if (empty($issues) && $total_pages > 0 && $current_page > $total_pages) {
  $redirect_params = $_GET;
  $redirect_params['page'] = $total_pages;
  header("Location: issues_list.php?" . http_build_query($redirect_params));
  exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ISS2: Issues List</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <styl>
    </style>
</head>

<body>
  <div class="container mt-3">
    <h2 class="text-center">Issues List</h2>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <h3>
        <?= ($_SESSION['filter'] === 'open') ? 'Open Issues' : 'All Issues'; ?>
      </h3>
      <div>
        <a href="issues_list.php?filter=all" class="btn btn-primary">All Issues</a>
        <a href="issues_list.php?filter=open" class="btn btn-secondary">Open Issues</a>
      </div>
      <div>
        <form method="GET" class="d-inline">
          <label for="filter_person">Filter by Person:</label>
          <select name="filter_person" id="filter_person" class="form-control d-inline w-auto">
            <option value="">-- Select Person --</option>
            <?php foreach ($persons as $person): ?>
              <option value="<?= $person['id']; ?>" <?= (isset($_GET['filter_person']) && $_GET['filter_person'] == $person['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($person['fname'] . ' ' . $person['lname']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="sort_column" value="<?= htmlspecialchars($sort_column); ?>">
          <input type="hidden" name="sort_direction" value="<?= htmlspecialchars($sort_direction); ?>">
          <input type="hidden" name="priority_sort" value="<?= htmlspecialchars($priority_sort); ?>">
          <button type="submit" class="btn btn-primary">Filter</button>
        </form>
      </div>
      <div class="d-flex align-items-center">
        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addIssueModal">+</button>
        <a href="logout.php" class="btn btn-warning">Logout</a>
      </div>
    </div>

    <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger mt-3">
        <?= htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

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
                    <?= htmlspecialchars($person['lname'] . ', ' . $person['fname']) . " ({$person['id']})"; ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <button type="submit" name="create_issue" class="btn btn-success">Add Issue</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <table class="table table-striped table-sm mt-2">
      <thead class="table-dark">
        <tr>
          <th>
            <a href="?filter=<?= urlencode($filter); ?>&sort_column=id&sort_direction=<?= ($sort_column === 'id' && $sort_direction === 'asc') ? 'desc' : 'asc'; ?>" class="text-white">ID</a>
          </th>
          <th>
            <a href="?filter=<?= urlencode($filter); ?>&sort_column=short_description&sort_direction=<?= ($sort_column === 'short_description' && $sort_direction === 'asc') ? 'desc' : 'asc'; ?>" class="text-white">Short Description</a>
          </th>
          <th>
            <a href="?filter=<?= urlencode($filter); ?>&sort_column=open_date&sort_direction=<?= ($sort_column === 'open_date' && $sort_direction === 'asc') ? 'desc' : 'asc'; ?>" class="text-white">Open Date</a>
          </th>
          <th>
            <a href="?filter=<?= urlencode($filter); ?>&sort_column=close_date&sort_direction=<?= ($sort_column === 'close_date' && $sort_direction === 'asc') ? 'desc' : 'asc'; ?>" class="text-white">Close Date</a>
          </th>
          <th>
            <a href="?filter=<?= urlencode($filter); ?>&sort_column=priority&sort_direction=<?= ($sort_column === 'priority' && $sort_direction === 'asc') ? 'desc' : 'asc'; ?>" class="text-white">Priority</a>
          </th>
          <th>
            <a href="?filter=<?= urlencode($filter); ?>&sort_column=person_name&sort_direction=<?= ($sort_column === 'person_name' && $sort_direction === 'asc') ? 'desc' : 'asc'; ?>" class="text-white">Person Responsible</a>
          </th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($issues as $issue): ?>
          <tr>
            <td><?= htmlspecialchars($issue['id']); ?></td>
            <td><?= htmlspecialchars($issue['short_description']); ?></td>
            <td><?= htmlspecialchars($issue['open_date']); ?></td>
            <td><?= htmlspecialchars($issue['close_date']); ?></td>
            <td><?= htmlspecialchars($issue['priority']); ?></td>
            <td><?= htmlspecialchars($issue['person_name']); ?></td>
            <td>
              <?php if ($_SESSION['admin'] === 'Y' || $_SESSION['user_id'] === $issue['per_id'] || $_SESSION['user_id'] === $issue['created_by']): ?>
                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readIssue<?= $issue['id']; ?>">R</button>
                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateIssue<?= $issue['id']; ?>">U</button>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteIssue<?= $issue['id']; ?>">D</button>
              <?php else: ?>
                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readIssue<?= $issue['id']; ?>">R</button>
                <button class="btn btn-secondary btn-sm disabled-button" disabled>U</button>
                <button class="btn btn-secondary btn-sm disabled-button" disabled>D</button>
              <?php endif; ?>

            </td>

          </tr>

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
                  <p><strong>Person:</strong> <?= htmlspecialchars($issue['person_name']); ?></p>

                  <?php
                  $comments_per_page = 5; 
                  $current_comment_page = isset($_GET['comment_page']) ? (int)$_GET['comment_page'] : 1;
                  $comment_offset = ($current_comment_page - 1) * $comments_per_page;

                  $total_comments_sql = "SELECT COUNT(*) FROM iss_comments WHERE iss_id = :iss_id";
                  $total_comments_stmt = $pdo->prepare($total_comments_sql);
                  $total_comments_stmt->execute(['iss_id' => $issue['id']]);
                  $total_comments_count = $total_comments_stmt->fetchColumn();
                  $total_comment_pages = ceil($total_comments_count / $comments_per_page);

                  $comments_sql = "SELECT iss_comments.*, iss_persons.fname, iss_persons.lname 
                  FROM iss_comments 
                  JOIN iss_persons ON iss_comments.per_id = iss_persons.id 
                  WHERE iss_id = :iss_id 
                  ORDER BY posted_date DESC, id DESC 
                  LIMIT :limit OFFSET :offset";

                  $comments_stmt = $pdo->prepare($comments_sql);
                  $comments_stmt->bindValue(':iss_id', $issue['id'], PDO::PARAM_INT);
                  $comments_stmt->bindValue(':limit', $comments_per_page, PDO::PARAM_INT);
                  $comments_stmt->bindValue(':offset', $comment_offset, PDO::PARAM_INT);
                  $comments_stmt->execute();
                  $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
                  ?>
                  <?php if ($comments): ?>
                    <?php foreach ($comments as $comment): ?>
                      <div style="font-family: monospace; border-bottom: 1px solid #ccc; padding: 5px 0;">
                        <span style="display:inline-block; width: 180px;">
                          <?= htmlspecialchars($comment['lname'] . ", " . $comment['fname']); ?>
                        </span>
                        <span style="display:inline-block; width: 300px;">
                          <?= htmlspecialchars($comment['short_comment']); ?>
                        </span>
                        <span style="display:inline-block; width: 140px;">
                          <?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($comment['posted_date']))); ?>
                        </span>
                        <span style="display:inline-block; width: 150px;">
                          <?php if ($_SESSION['admin'] === 'Y' || $_SESSION['user_id'] === $comment['per_id']): ?>
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readCommentModal<?= $comment['id']; ?>">R</button>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateCommentModal<?= $comment['id']; ?>">U</button>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteCommentModal<?= $comment['id']; ?>">D</button>
                          <?php else: ?>
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readCommentModal<?= $comment['id']; ?>">R</button>
                            <button class="btn btn-secondary btn-sm disabled-button" disabled>U</button>
                            <button class="btn btn-secondary btn-sm disabled-button" disabled>D</button>
                          <?php endif; ?>
                        </span>
                      </div>



                    <?php endforeach; ?>
                  <?php else: ?>
                    <p>No comments added yet.</p>
                  <?php endif; ?>

                  <div class="d-flex justify-content-center mt-3">
                    <nav>
                      <ul class="pagination">
                        <li class="page-item <?= $current_page <= 1 ? 'disabled' : ''; ?>">
                          <a class="page-link" href="?<?= http_build_query([
                                                        'filter' => $filter,
                                                        'filter_person' => $_GET['filter_person'] ?? '',
                                                        'sort_column' => $sort_column,
                                                        'sort_direction' => $sort_direction,
                                                        'priority_sort' => $priority_sort,
                                                        'page' => max(1, $current_page - 1)
                                                      ]) ?>">Previous</a>
                        </li>

                        <?php
                        for ($i = 1; $i <= $total_pages; $i++) {
                          echo '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">';
                          echo '<a class="page-link" href="?' . http_build_query([
                            'filter' => $filter,
                            'filter_person' => $_GET['filter_person'] ?? '',
                            'sort_column' => $sort_column,
                            'sort_direction' => $sort_direction,
                            'priority_sort' => $priority_sort,
                            'page' => $i
                          ]) . '">' . $i . '</a>';
                          echo '</li>';
                        }
                        ?>

                        <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : ''; ?>">
                          <a class="page-link" href="?<?= http_build_query([
                                                        'filter' => $filter,
                                                        'filter_person' => $_GET['filter_person'] ?? '',
                                                        'sort_column' => $sort_column,
                                                        'sort_direction' => $sort_direction,
                                                        'priority_sort' => $priority_sort,
                                                        'page' => min($total_pages, $current_page + 1)
                                                      ]) ?>">Next</a>
                        </li>
                      </ul>
                    </nav>
                  </div>

                  <form method="POST" action="issues_list.php" class="mt-3">
                    <label for="short_comment">Short Comment</label>
                    <input type="text" name="short_comment" class="form-control mb-2" required>

                    <label for="long_comment">Long Comment</label>
                    <textarea name="long_comment" class="form-control mb-2" required></textarea>

                    <input type="hidden" name="iss_id" value="<?= $issue['id']; ?>">
                    <button type="submit" name="create_comment" class="btn btn-primary">Add Comment</button>
                  </form>

                </div>
              </div>
            </div>
          </div>
          

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
                  <p><strong>Person:</strong> <?= htmlspecialchars($issue['person_name']); ?></p>

                  <form method="POST" action="issues_list.php">
                    <input type="hidden" name="id" value="<?= $issue['id']; ?>">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                    <input type="hidden" name="filter_person" value="<?= isset($_GET['filter_person']) ? htmlspecialchars($_GET['filter_person']) : ''; ?>">
                    <input type="hidden" name="sort_column" value="<?= htmlspecialchars($sort_column); ?>">
                    <input type="hidden" name="sort_direction" value="<?= htmlspecialchars($sort_direction); ?>">
                    <input type="hidden" name="priority_sort" value="<?= htmlspecialchars($priority_sort); ?>">
                    <button type="submit" name="delete_issue" class="btn btn-danger">Delete</button>
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
            <a class="page-link" href="?<?= http_build_query([
                                          'filter' => $filter,
                                          'filter_person' => $_GET['filter_person'] ?? '',
                                          'sort_column' => $sort_column,
                                          'sort_direction' => $sort_direction,
                                          'priority_sort' => $priority_sort,
                                          'page' => max(1, $current_page - 1)
                                        ]) ?>">Previous</a>
          </li>

          <?php
          $start_page = 1;
          $end_page = $total_pages;

          for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?= ($i == $current_page) ? 'active' : ''; ?>">
              <a class="page-link" href="?<?= http_build_query([
                                            'filter' => $filter,
                                            'filter_person' => $_GET['filter_person'] ?? '',
                                            'sort_column' => $sort_column,
                                            'sort_direction' => $sort_direction,
                                            'priority_sort' => $priority_sort,
                                            'page' => $i
                                          ]) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?<?= http_build_query([
                                          'filter' => $filter,
                                          'filter_person' => $_GET['filter_person'] ?? '',
                                          'sort_column' => $sort_column,
                                          'sort_direction' => $sort_direction,
                                          'priority_sort' => $priority_sort,
                                          'page' => min($total_pages, $current_page + 1)
                                        ]) ?>">Next</a>
          </li>
        </ul>
      </nav>
    </div>

    <div class="d-flex justify-content-center mt-2">
      <a href="persons_list.php" class="btn btn-info <?= $_SESSION['admin'] !== 'Y' ? 'disabled' : ''; ?>">Edit Persons</a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('hidden.bs.modal', function() {
      setTimeout(() => {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
          for (let i = 1; i < backdrops.length; i++) {
            backdrops[i].remove();
          }
        } else if (backdrops.length === 1) {
          const anyOpenModal = document.querySelector('.modal.show');
          if (!anyOpenModal) {
            backdrops[0].remove();
            document.body.classList.remove('modal-open');
            document.body.style.overflow = ''; 
          }
        }
      }, 200); 
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const openModalId = urlParams.get('open_modal');
      if (openModalId) {
        const modal = document.getElementById(openModalId);
        if (modal) {
          const bootstrapModal = new bootstrap.Modal(modal);
          bootstrapModal.show();

          urlParams.delete('open_modal');
          const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
          window.history.replaceState({}, document.title, newUrl);
        }
      }
    });
  </script>

</body>

</html>
<?php

foreach ($issues as $issue) {
  $comments_sql = "SELECT iss_comments.*, iss_persons.fname, iss_persons.lname 
                     FROM iss_comments 
                     JOIN iss_persons ON iss_comments.per_id = iss_persons.id 
                     WHERE iss_id = :iss_id 
ORDER BY posted_date DESC, id DESC";
  $comments_stmt = $pdo->prepare($comments_sql);
  $comments_stmt->execute(['iss_id' => $issue['id']]);
  $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($comments as $comment): ?>
    <div class="modal fade" id="readCommentModal<?= $comment['id']; ?>" tabindex="-1" aria-labelledby="readCommentModalLabel<?= $comment['id']; ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="readCommentModalLabel<?= $comment['id']; ?>">Comment Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p><strong>Commenter:</strong> <?= htmlspecialchars($comment['lname'] . ", " . $comment['fname']); ?></p>
            <p><strong>Short Comment:</strong> <?= htmlspecialchars($comment['short_comment']); ?></p>
            <p><strong>Long Comment:</strong> <?= htmlspecialchars($comment['long_comment']); ?></p>
            <p><strong>Posted Date:</strong> <?= htmlspecialchars($comment['posted_date']); ?></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="updateCommentModal<?= $comment['id']; ?>" tabindex="-1" aria-labelledby="updateCommentModalLabel<?= $comment['id']; ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateCommentModalLabel<?= $comment['id']; ?>">Update Comment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="">
              <input type="hidden" name="comment_id" value="<?= $comment['id']; ?>">
              <input type="hidden" name="issue_id" value="<?= $comment['iss_id']; ?>"> 
              <label for="short_comment">Short Comment</label>
              <input type="text" name="short_comment" class="form-control mb-2" value="<?= htmlspecialchars($comment['short_comment']); ?>" required>

              <label for="long_comment">Long Comment</label>
              <textarea name="long_comment" class="form-control mb-2" required><?= htmlspecialchars($comment['long_comment']); ?></textarea>

              <button type="submit" name="update_comment" class="btn btn-primary">Save Changes</button>
            </form>
          </div>
        </div>
      </div>
    </div>


    <div class="modal fade" id="deleteCommentModal<?= $comment['id']; ?>" tabindex="-1" aria-labelledby="deleteCommentModalLabel<?= $comment['id']; ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="deleteCommentModalLabel<?= $comment['id']; ?>">Delete Comment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this comment?</p>
            <p><strong>Short Comment:</strong> <?= htmlspecialchars($comment['short_comment']); ?></p>
            <p><strong>Long Comment:</strong> <?= htmlspecialchars($comment['long_comment']); ?></p>
          </div>
          <div class="modal-footer">
            <form method="POST" action="">
              <input type="hidden" name="comment_id" value="<?= $comment['id']; ?>">
              <button type="submit" name="delete_comment" class="btn btn-danger">Delete</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </form>
          </div>
        </div>
      </div>
    </div>
<?php endforeach;
}
?>

<?php
Database::disconnect();
ob_end_flush(); 
?> 