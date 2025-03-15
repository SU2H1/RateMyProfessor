<?php
// account-section.php - Dedicated user account management page
require_once 'session_config.php'; //NEW
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once __DIR__ . "/config.php";

// Initialize variables
$username = $_SESSION["username"];
$email = $_SESSION["email"];
$user_id = $_SESSION["id"];

// Password update variables
$password_err = $new_password_err = $confirm_password_err = $success_msg = "";

// Username update variables
$new_username = "";
$username_err = "";
$username_success = "";

// Get user reviews
$reviews_count = 0;
$reviews = [];
try {
    // First, check what columns exist in the ratings table
    $columns_sql = "PRAGMA table_info(ratings)";
    $columns_result = $conn->query($columns_sql);
    $has_comments_field = false;
    $has_comment_field = false;
    
    while ($column = $columns_result->fetchArray(SQLITE3_ASSOC)) {
        if ($column['name'] === 'comments') {
            $has_comments_field = true;
        }
        if ($column['name'] === 'comment') {
            $has_comment_field = true;
        }
    }
    
    // Check what's available in the courses table
    $course_columns_sql = "PRAGMA table_info(courses)";
    $course_columns_result = $conn->query($course_columns_sql);
    $course_name_field = 'name'; // Default field name
    $course_code_field = 'course_code'; // Default field name
    
    while ($column = $course_columns_result->fetchArray(SQLITE3_ASSOC)) {
        if ($column['name'] === 'course_name') {
            $course_name_field = 'course_name';
        }
        if ($column['name'] === 'name') {
            $course_name_field = 'name';
        }
    }
    
    // Build the SQL query dynamically based on available fields
    $comment_field = $has_comments_field ? 'r.comments' : ($has_comment_field ? 'r.comment' : 'NULL');
    
    $review_sql = "SELECT r.*, 
        c.$course_name_field AS course_name, 
        c.$course_code_field AS course_code,
        p.name AS professor_name,
        $comment_field AS review_text
        FROM ratings r 
        JOIN courses c ON r.course_id = c.id 
        LEFT JOIN professors p ON r.professor_id = p.id
        WHERE r.user_id = :user_id 
        ORDER BY r.created_at DESC";


    
    $review_stmt = $conn->prepare($review_sql);
    $review_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $review_stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Map the comment field correctly
        if ($has_comments_field) {
            $row['comments'] = $row['comments'] ?? '';
        } elseif ($has_comment_field) {
            $row['comments'] = $row['comment'] ?? '';
        } else {
            $row['comments'] = '';
        }
        
        // Ensure we have a course name
        if (empty($row['course_name']) && isset($row['name'])) {
            $row['course_name'] = $row['name'];
        }
        
        // Ensure we have a course code
        if (empty($row['course_code'])) {
            $row['course_code'] = 'N/A';
        }
        
        // Ensure content_quality and difficulty exist (default to 3 if missing)
        $row['content_quality'] = $row['content_quality'] ?? ($row['content_rating'] ?? 3);
        $row['difficulty'] = $row['difficulty'] ?? ($row['difficulty_rating'] ?? 3);
        
        // Ensure takes_attendance exists
        $row['takes_attendance'] = $row['attendance_check'] ?? 'Unknown';
        
        $reviews[] = $row;
        $reviews_count++;
    }
} catch (Exception $e) {
    // Log the error but continue with an empty reviews array
    error_log("Error fetching reviews: " . $e->getMessage());
}

// Get user account creation date
$join_date = "";
try {
    $date_sql = "SELECT created_at FROM users WHERE id = :user_id";
    $date_stmt = $conn->prepare($date_sql);
    $date_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $date_stmt->execute();
    
    if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $join_date = date("F j, Y", strtotime($row["created_at"]));
    }
} catch (Exception $e) {
    $join_date = "Unknown";
}

// Process form data when update username form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_username"])) {
    // Validate new username
    if (empty(trim($_POST["new_username"]))) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["new_username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        $new_username = trim($_POST["new_username"]);
        
        // Check if username exists
        $sql = "SELECT id FROM users WHERE username = :username AND id != :user_id";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bindValue(':username', $new_username, SQLITE3_TEXT);
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $username_err = "This username is already taken.";
            } else {
                // Update username
                $update_sql = "UPDATE users SET username = :username WHERE id = :id";
                $update_stmt = $conn->prepare($update_sql);
                if ($update_stmt) {
                    $update_stmt->bindValue(':username', $new_username, SQLITE3_TEXT);
                    $update_stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
                    $result = $update_stmt->execute();
                    
                    if ($result) {
                        $_SESSION["username"] = $new_username;
                        $username = $new_username;
                        $username_success = "Username updated successfully!";
                    } else {
                        $username_err = "Something went wrong. Please try again later.";
                    }
                }
            }
        }
    }
}

// Process form data when update password form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_password"])) {
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your current password.";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE id = :id";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $hashed_password = $row["password"];
                
                if (!password_verify(trim($_POST["password"]), $hashed_password)) {
                    $password_err = "Current password is incorrect.";
                }
            }
        }
    }
    
    // Validate new password
    if (empty(trim($_POST["new_password"]))) {
        $new_password_err = "Please enter a new password.";     
    } elseif (strlen(trim($_POST["new_password"])) < 8) {
        $new_password_err = "Password must have at least 8 characters.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm the new password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    // Check input errors before updating the database
    if (empty($password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        // Update password
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bindValue(':password', password_hash($new_password, PASSWORD_DEFAULT), SQLITE3_TEXT);
            $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result) {
                $success_msg = "Password updated successfully!";
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}

// Close database connection
$conn->close();

// HTML header with title
$page_title = "My Account";
include_once "header.php";
?>

<div class="account-page-container">
    <div class="account-sidebar">
        <div class="user-profile">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <h3><?php echo htmlspecialchars($username); ?></h3>
            <p class="user-email"><?php echo htmlspecialchars($email); ?></p>
            <p class="join-date">Member since: <?php echo $join_date; ?></p>
        </div>
        
        <ul class="sidebar-nav">
            <li><a href="#profile-settings" data-section="profile-settings">Profile Settings</a></li>
            <li class="active"><a href="#my-reviews" data-section="my-reviews">My Reviews (<?php echo $reviews_count; ?>)</a></li>
            <li class="danger-item"><a href="#delete-account" data-section="delete-account">Delete Account</a></li>
        </ul>
    </div>
    
    <div class="account-content">
        <!-- Profile Settings Section -->
        <div id="profile-settings" class="account-section">
            <h2>Profile Settings</h2>
            
            <!-- Username Update Form -->
            <div class="settings-card">
                <h3>Update Username</h3>
                <?php if (!empty($username_success)) : ?>
                    <div class="success-message"><?php echo $username_success; ?></div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>New Username</label>
                        <input type="text" name="new_username" class="form-control" value="<?php echo htmlspecialchars($new_username); ?>">
                        <span class="help-block"><?php echo $username_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" name="update_username" class="btn-primary" value="Update Username">
                    </div>
                </form>
            </div>
            
            <!-- Password Update Form -->
            <div class="settings-card">
                <h3>Change Password</h3>
                <?php if (!empty($success_msg)) : ?>
                    <div class="success-message"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="password" class="form-control">
                        <span class="help-block"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control">
                        <span class="help-block"><?php echo $new_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control">
                        <span class="help-block"><?php echo $confirm_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" name="update_password" class="btn-primary" value="Change Password">
                    </div>
                </form>
            </div>
        </div>
        
        <!-- My Reviews Section -->
        <div id="my-reviews" class="account-section active">
            <h2>My Reviews</h2>
            
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                <div class="debug-info" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
                    <h3>Debug Information</h3>
                    <p>Reviews Count: <?php echo $reviews_count; ?></p>
                    <p>User ID: <?php echo $user_id; ?></p>
                    <p>SQL Query: <?php echo htmlspecialchars($review_sql ?? 'No query available'); ?></p>
                    <details>
                        <summary>Review Data</summary>
                        <pre><?php print_r($reviews); ?></pre>
                    </details>
                </div>
            <?php endif; ?>
            
            <?php if (count($reviews) > 0): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <h3><?php echo htmlspecialchars($review["course_name"]); ?> (<?php echo htmlspecialchars($review["course_code"]); ?>)</h3>
                                <span class="review-date"><?php echo date("M j, Y", strtotime($review["created_at"])); ?></span>
                            </div>
                            
                            <div class="review-ratings">
                                <div class="rating-item">
                                    <span class="rating-label">Content Quality:</span>
                                    <span class="rating-value"><?php echo $review["content_quality"]; ?>/5</span>
                                </div>
                                <div class="rating-item">
                                    <span class="rating-label">Difficulty:</span>
                                    <span class="rating-value"><?php echo $review["difficulty"]; ?>/5</span>
                                </div>
                                <?php if (isset($review["takes_attendance"])): ?>
                                <div class="rating-item">
                                    <span class="rating-label">Takes Attendance:</span>
                                    <span class="rating-value"><?php echo htmlspecialchars($review["takes_attendance"]); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php 
                            // Check for comments in different possible fields
                            $reviewText = '';
                            if (!empty($review["comments"])) {
                                $reviewText = $review["comments"];
                            } elseif (!empty($review["comment"])) {
                                $reviewText = $review["comment"];
                            } elseif (!empty($review["review_text"])) {
                                $reviewText = $review["review_text"];
                            }
                            
                            if (!empty($reviewText)): 
                            ?>
                                <div class="review-comment">
                                    <p><?php echo htmlspecialchars($reviewText); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="review-actions">
                                <a href="course_page_template.php?course=<?php echo urlencode($review["course_name"]); ?>&professor=<?php echo urlencode(str_replace(' ', '', $review["professor_name"] ?? '')); ?>&lang=en" class="btn-secondary">View Course</a>

                                <button class="delete-review" data-review-id="<?php echo $review["id"]; ?>" title="Delete Review">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-comment-slash"></i>
                    <p>You haven't submitted any reviews yet.</p>
                    <a href="home.php" class="btn-primary">Browse Courses</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Notification settings section removed -->
        
        <!-- Delete Account Section -->
        <div id="delete-account" class="account-section">
            <h2>Delete Account</h2>
            
            <div class="settings-card danger-card">
                <h3>Are you sure you want to delete your account?</h3>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>This action <strong>cannot be undone</strong>. Your account will be permanently deleted, but your reviews will remain anonymized.</p>
                </div>
                
                <div class="form-group">
                    <a href="delete_account.php" class="btn-danger confirm-delete">Yes, Delete My Account</a>
                    <button class="btn-secondary cancel-delete">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Account Page Layout */
.account-page-container {
    display: flex;
    max-width: 1200px;
    margin: 30px auto;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* Sidebar Styles */
.account-sidebar {
    width: 280px;
    background-color: #1e3a8a;
    color: #fff;
    padding: 30px 0;
}

.user-profile {
    padding: 0 20px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.profile-avatar {
    font-size: 60px;
    margin-bottom: 15px;
}

.user-profile h3 {
    margin: 0 0 5px;
    font-size: 20px;
}

.user-email {
    margin: 0 0 10px;
    font-size: 14px;
    opacity: 0.8;
}

.join-date {
    font-size: 12px;
    opacity: 0.6;
}

.sidebar-nav {
    list-style: none;
    padding: 0;
    margin: 20px 0 0 0;
}

.sidebar-nav li {
    padding: 0;
    margin: 0;
}

.sidebar-nav li a {
    display: block;
    padding: 15px 20px;
    color: #fff;
    text-decoration: none;
    transition: background-color 0.2s;
}

.sidebar-nav li a:hover, 
.sidebar-nav li.active a {
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar-nav li.danger-item a {
    color: #ffcccc;
}

.sidebar-nav li.danger-item a:hover {
    background-color: rgba(255, 0, 0, 0.2);
}

/* Main Content Styles */
.account-content {
    flex: 1;
    padding: 30px;
    background-color: #f8f9fa;
    overflow-y: auto;
}

.account-section {
    display: none;
}

.account-section.active {
    display: block;
}

.account-section h2 {
    color: #1e3a8a;
    margin-top: 0;
    margin-bottom: 25px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.settings-card {
    background-color: #fff;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.settings-card h3 {
    color: #1e3a8a;
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 18px;
}

.danger-card {
    border-left: 4px solid #dc3545;
}

.danger-card h3 {
    color: #dc3545;
}

.warning-box {
    background-color: #fff8f8;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
}

.warning-box i {
    color: #dc3545;
    font-size: 20px;
    margin-right: 15px;
}

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.2s;
}

.form-control:focus {
    border-color: #1e3a8a;
    outline: none;
}

.help-block {
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
    display: block;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.form-check {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.form-check input[type="checkbox"] {
    margin-right: 10px;
}

.help-text {
    font-size: 14px;
    color: #6c757d;
    margin-top: 10px;
}

/* Button Styles */
.btn-primary {
    background-color: #1e3a8a;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.2s;
}

.btn-primary:hover {
    background-color: #152a60;
}

.btn-primary[disabled] {
    background-color: #92a3cc;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    transition: background-color 0.2s;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
    text-decoration: none;
    display: inline-block;
}

.btn-danger:hover {
    background-color: #c82333;
}

.confirm-delete {
    padding: 12px 20px;
    font-size: 16px;
}

/* Reviews Styles */
.reviews-list {
    display: grid;
    gap: 20px;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.review-card {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.review-header h3 {
    margin: 0;
    font-size: 16px;
    color: #1e3a8a;
}

.review-date {
    font-size: 12px;
    color: #6c757d;
}

.review-ratings {
    margin-bottom: 15px;
}

.rating-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.rating-label {
    font-weight: 500;
    color: #495057;
}

.review-comment {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 15px;
}

.review-comment p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
}

.review-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.delete-review {
    background: none;
    border: none;
    color: #dc3545;
    font-size: 18px;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.delete-review:hover {
    background-color: #ffeeee;
    transform: scale(1.1);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.empty-state i {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 20px;
}

.empty-state p {
    margin-bottom: 20px;
    color: #495057;
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; transform: translateY(-10px); }
}

/* Responsive Design */
@media (max-width: 900px) {
    .account-page-container {
        flex-direction: column;
        margin: 20px 15px;
    }
    
    .account-sidebar {
        width: 100%;
        padding: 20px 0;
    }
    
    .account-content {
        padding: 20px;
    }
    
    .reviews-list {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Add Font Awesome if not already included
if (!document.querySelector('link[href*="font-awesome"]')) {
    const fontAwesome = document.createElement('link');
    fontAwesome.rel = 'stylesheet';
    fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
    document.head.appendChild(fontAwesome);
}

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    const sections = document.querySelectorAll('.account-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the section to show
            const targetSection = this.getAttribute('data-section');
            
            // Update active class on nav links
            navLinks.forEach(navLink => {
                navLink.parentElement.classList.remove('active');
            });
            this.parentElement.classList.add('active');
            
            // Show the correct section
            sections.forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(targetSection).classList.add('active');
            
            // Update URL hash
            window.location.hash = targetSection;
        });
    });
    
    // Handle page load with hash
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        const link = document.querySelector(`.sidebar-nav a[data-section="${hash}"]`);
        if (link) {
            link.click();
        }
    }
    
    // Cancel delete account
    const cancelDeleteBtn = document.querySelector('.cancel-delete');
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            // Switch to profile section
            document.querySelector('.sidebar-nav a[data-section="profile-settings"]').click();
        });
    }
    
    // Delete review with confirmation
    const deleteReviewBtns = document.querySelectorAll('.delete-review');
    deleteReviewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const reviewId = this.getAttribute('data-review-id');
            
            // Create a custom confirmation dialog
            const confirmDialog = document.createElement('div');
            confirmDialog.className = 'confirm-dialog';
            confirmDialog.innerHTML = `
                <div class="confirm-dialog-content">
                    <div class="confirm-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Delete Review</h3>
                    </div>
                    <p>Are you sure you want to delete this review? This action cannot be undone.</p>
                    <div class="confirm-buttons">
                        <button class="cancel-btn">Cancel</button>
                        <button class="confirm-btn">Delete</button>
                    </div>
                </div>
            `;
            document.body.appendChild(confirmDialog);
            
            // Style the dialog
            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    .confirm-dialog {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0,0,0,0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 1000;
                        animation: fadeIn 0.2s ease-out;
                    }
                    
                    .confirm-dialog-content {
                        background-color: white;
                        border-radius: 8px;
                        padding: 25px;
                        max-width: 400px;
                        width: 90%;
                        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                        animation: scaleIn 0.2s ease-out;
                    }
                    
                    .confirm-header {
                        display: flex;
                        align-items: center;
                        margin-bottom: 15px;
                    }
                    
                    .confirm-header i {
                        color: #dc3545;
                        font-size: 24px;
                        margin-right: 10px;
                    }
                    
                    .confirm-header h3 {
                        color: #333;
                        margin: 0;
                    }
                    
                    .confirm-buttons {
                        display: flex;
                        justify-content: flex-end;
                        margin-top: 20px;
                        gap: 10px;
                    }
                    
                    .cancel-btn, .confirm-btn {
                        padding: 10px 15px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: 500;
                    }
                    
                    .cancel-btn {
                        background-color: #f8f9fa;
                        border: 1px solid #ddd;
                        color: #333;
                    }
                    
                    .confirm-btn {
                        background-color: #dc3545;
                        color: white;
                    }
                    
                    .cancel-btn:hover {
                        background-color: #e9ecef;
                    }
                    
                    .confirm-btn:hover {
                        background-color: #c82333;
                    }
                    
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    
                    @keyframes scaleIn {
                        from { transform: scale(0.9); }
                        to { transform: scale(1); }
                    }
                </style>
            `);
            
            // Add event listeners to dialog buttons
            const cancelBtn = confirmDialog.querySelector('.cancel-btn');
            const confirmBtn = confirmDialog.querySelector('.confirm-btn');
            
            cancelBtn.addEventListener('click', function() {
                document.body.removeChild(confirmDialog);
            });
            
            confirmBtn.addEventListener('click', function() {
                // Send AJAX request to delete the review
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_review.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        // Remove the confirm dialog
                        document.body.removeChild(confirmDialog);
                        
                        // Create a success toast
                        const toast = document.createElement('div');
                        toast.className = 'toast success-toast';
                        toast.innerHTML = `
                            <div class="toast-content">
                                <i class="fas fa-check-circle"></i>
                                <span>Review deleted successfully!</span>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        
                        // Style the toast
                        document.head.insertAdjacentHTML('beforeend', `
                            <style>
                                .toast {
                                    position: fixed;
                                    bottom: 20px;
                                    right: 20px;
                                    padding: 12px 20px;
                                    border-radius: 4px;
                                    background-color: white;
                                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                                    z-index: 1000;
                                    animation: slideIn 0.3s ease-out forwards, fadeOut 0.5s ease-out 2.5s forwards;
                                }
                                
                                .success-toast {
                                    border-left: 4px solid #28a745;
                                }
                                
                                .toast-content {
                                    display: flex;
                                    align-items: center;
                                }
                                
                                .toast-content i {
                                    color: #28a745;
                                    margin-right: 10px;
                                    font-size: 18px;
                                }
                                
                                @keyframes slideIn {
                                    from { transform: translateX(100%); }
                                    to { transform: translateX(0); }
                                }
                                
                                @keyframes fadeOut {
                                    from { opacity: 1; }
                                    to { opacity: 0; transform: translateY(-10px); }
                                }
                            </style>
                        `);
                        
                        // Remove toast after 3 seconds
                        setTimeout(() => {
                            if (document.body.contains(toast)) {
                                document.body.removeChild(toast);
                            }
                        }, 3000);
                        
                        // Remove the review card from the DOM with animation
                        const reviewCard = btn.closest('.review-card');
                        reviewCard.style.animation = 'fadeOut 0.3s ease-out forwards';
                        
                        setTimeout(() => {
                            reviewCard.remove();
                            
                            // Update review count
                            const reviewCountElement = document.querySelector('.sidebar-nav a[data-section="my-reviews"]');
                            let reviewCount = parseInt(reviewCountElement.textContent.match(/\d+/)[0]);
                            reviewCount--;
                            reviewCountElement.textContent = `My Reviews (${reviewCount})`;
                            
                            // Show empty state if no more reviews
                            if (reviewCount === 0) {
                                const reviewsList = document.querySelector('.reviews-list');
                                reviewsList.innerHTML = `
                                    <div class="empty-state">
                                        <i class="fas fa-comment-slash"></i>
                                        <p>You haven't submitted any reviews yet.</p>
                                        <a href="home.php" class="btn-primary">Browse Courses</a>
                                    </div>
                                `;
                            }
                        }, 300);
                    } else {
                        // Show error toast
                        document.body.removeChild(confirmDialog);
                        
                        const toast = document.createElement('div');
                        toast.className = 'toast error-toast';
                        toast.innerHTML = `
                            <div class="toast-content">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>Error deleting review. Please try again.</span>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        
                        // Style the error toast
                        document.head.insertAdjacentHTML('beforeend', `
                            <style>
                                .error-toast {
                                    border-left: 4px solid #dc3545;
                                }
                                
                                .error-toast i {
                                    color: #dc3545;
                                }
                            </style>
                        `);
                        
                        // Remove toast after 3 seconds
                        setTimeout(() => {
                            if (document.body.contains(toast)) {
                                document.body.removeChild(toast);
                            }
                        }, 3000);
                    }
                };
                xhr.send('review_id=' + reviewId);
            });
            
            // Close dialog when clicking outside
            confirmDialog.addEventListener('click', function(e) {
                if (e.target === confirmDialog) {
                    document.body.removeChild(confirmDialog);
                }
            });
        });
    });
});
</script>

<?php
// Include footer
include_once __DIR__ . "/footer.php";
?>