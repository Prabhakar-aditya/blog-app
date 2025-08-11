<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

// Pagination settings
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? $_GET['search'] : '';

if (!empty($search)) {
    $countSql = "SELECT COUNT(*) AS total FROM posts WHERE title LIKE ? OR content LIKE ?";
    $countStmt = $conn->prepare($countSql);
    $like = "%" . $search . "%";
    $countStmt->bind_param("ss", $like, $like);
    $countStmt->execute();
    $totalPosts = $countStmt->get_result()->fetch_assoc()['total'];

    $sql = "SELECT * FROM posts WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $like, $like, $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $countSql = "SELECT COUNT(*) AS total FROM posts";
    $totalPosts = $conn->query($countSql)->fetch_assoc()['total'];

    $sql = "SELECT * FROM posts ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
}

$totalPages = ceil($totalPosts / $limit);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Blog Home</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>All Blog Posts</h1>
            <div>
                <a href="add_post.php" class="btn btn-success me-2">+ Add New Post</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <form method="GET" action="" class="input-group mb-4">
            <input type="text" name="search" class="form-control" placeholder="Search posts..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
                        <small class="text-muted">Posted on: <?php echo $row['created_at']; ?></small>
                        <div class="mt-3">
                            <a href="edit_post.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete_post.php?id=<?php echo $row['id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Prev</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

        <?php else: ?>
            <div class="alert alert-warning">No posts found.</div>
        <?php endif; ?>
    </div>
</body>
</html>
