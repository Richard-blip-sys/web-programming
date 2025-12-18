<?php
require_once 'auth.php';
require_once 'config.php';

if(!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle Add Faculty
if(isset($_POST['add_faculty'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    // Validation
    if(empty($username) || empty($password) || empty($email) || empty($first_name) || empty($last_name)) {
        $error = 'All fields are required.';
    } elseif(strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            // Check if username exists
            $check = "SELECT user_id FROM users WHERE username = :username";
            $stmt = $db->prepare($check);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $error = 'Username already exists.';
            } else {
                // Check if email exists
                $check = "SELECT user_id FROM users WHERE email = :email";
                $stmt = $db->prepare($check);
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    $error = 'Email already exists.';
                } else {
                    // Insert faculty
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $query = "INSERT INTO users (username, password_hash, email, first_name, last_name, user_type, email_verified, created_at) 
                              VALUES (:username, :password_hash, :email, :first_name, :last_name, 'faculty', 1, NOW())";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':password_hash', $password_hash);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':first_name', $first_name);
                    $stmt->bindParam(':last_name', $last_name);
                    
                    if($stmt->execute()) {
                        $message = 'Faculty account created successfully!';
                    } else {
                        $error = 'Failed to create account. Please try again.';
                    }
                }
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            error_log("Faculty creation error: " . $e->getMessage());
        }
    }
}

// Handle Update Faculty
if(isset($_POST['update_faculty'])) {
    $user_id = intval($_POST['user_id']);
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    if(empty($email) || empty($first_name) || empty($last_name)) {
        $error = 'All fields are required.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            // Check if email exists for other users
            $check = "SELECT user_id FROM users WHERE email = :email AND user_id != :user_id";
            $stmt = $db->prepare($check);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $error = 'Email already exists.';
            } else {
                $query = "UPDATE users SET email = :email, first_name = :first_name, last_name = :last_name 
                          WHERE user_id = :user_id AND user_type = 'faculty'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':user_id', $user_id);
                
                if($stmt->execute()) {
                    $message = 'Faculty account updated successfully!';
                } else {
                    $error = 'Failed to update account.';
                }
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Reset Password
if(isset($_POST['reset_password'])) {
    $user_id = intval($_POST['user_id']);
    $new_password = trim($_POST['new_password']);
    
    if(empty($new_password)) {
        $error = 'Password is required.';
    } elseif(strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password_hash = :password_hash 
                      WHERE user_id = :user_id AND user_type = 'faculty'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':user_id', $user_id);
            
            if($stmt->execute()) {
                $message = 'Password reset successfully!';
            } else {
                $error = 'Failed to reset password.';
            }
        } catch(PDOException $e) {
            $error = 'Error resetting password.';
        }
    }
}

// Handle Delete Faculty
if(isset($_POST['delete_faculty'])) {
    $user_id = intval($_POST['user_id']);
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if faculty has enrollments
        $check = "SELECT COUNT(*) as count FROM enrollments WHERE faculty_id = :user_id";
        $stmt = $db->prepare($check);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result['count'] > 0) {
            $error = 'Cannot delete faculty with existing enrollments.';
        } else {
            $query = "DELETE FROM users WHERE user_id = :user_id AND user_type = 'faculty'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            
            if($stmt->execute()) {
                $message = 'Faculty account deleted successfully!';
            } else {
                $error = 'Failed to delete account.';
            }
        }
    } catch(PDOException $e) {
        $error = 'Error deleting account.';
    }
}

// Get all faculty accounts
$database = new Database();
$db = $database->getConnection();

$query = "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.created_at,
                 COUNT(DISTINCT e.enrollment_id) as total_enrollments,
                 COUNT(DISTINCT wr.request_id) as pending_requests
          FROM users u
          LEFT JOIN enrollments e ON u.user_id = e.faculty_id
          LEFT JOIN withdrawal_requests wr ON e.enrollment_id = wr.enrollment_id AND wr.status = 'pending'
          WHERE u.user_type = 'faculty'
          GROUP BY u.user_id
          ORDER BY u.last_name, u.first_name";
$stmt = $db->prepare($query);
$stmt->execute();
$faculty_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get system stats
$query = "SELECT 
            (SELECT COUNT(*) FROM users WHERE user_type = 'faculty') as total_faculty,
            (SELECT COUNT(*) FROM users WHERE user_type = 'student') as total_students,
            (SELECT COUNT(*) FROM subjects) as total_subjects,
            (SELECT COUNT(*) FROM enrollments WHERE status = 'active') as active_enrollments";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="header" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
        <h1>Admin Dashboard</h1>
        <div class="header-buttons">
            <a href="change_admin_password.php" class="manage-btn" style="color: #1e293b;">Change Password</a>
            <form method="POST" action="logout.php" class="inline-form">
                <button type="submit" class="logout-btn" style="color: #1e293b;">Logout</button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
            <div class="welcome-content">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                <p class="welcome-subtitle">Manage faculty accounts and monitor system activity</p>
            </div>
        </div>

        <?php if($message): ?>
            <div class="success auto-dismiss"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error auto-dismiss"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="quick-stats-grid">
            <div class="stat-card stat-blue">
                <div class="stat-icon">Faculty</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_faculty']; ?></div>
                    <div class="stat-label">Faculty Members</div>
                </div>
            </div>
            
            <div class="stat-card stat-green">
                <div class="stat-icon">Students</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                    <div class="stat-label">Students</div>
                </div>
            </div>
            
            <div class="stat-card stat-orange">
                <div class="stat-icon">Subjects</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_subjects']; ?></div>
                    <div class="stat-label">Subjects</div>
                </div>
            </div>
            
            <div class="stat-card" style="border-left-color: #8b5cf6;">
                <div class="stat-icon">Active</div>
                <div class="stat-content">
                    <div class="stat-value" style="color: #8b5cf6;"><?php echo $stats['active_enrollments']; ?></div>
                    <div class="stat-label">Active Enrollments</div>
                </div>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2>Add New Faculty</h2>
                <p class="section-subtitle">Create a new faculty account</p>
            </div>
            
            <form method="POST" class="modern-form" id="addFacultyForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                    <small class="form-help">Minimum 6 characters</small>
                </div>
                <button type="submit" name="add_faculty" class="btn-primary">Create Faculty Account</button>
            </form>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2>Faculty Accounts (<?php echo count($faculty_list); ?>)</h2>
                <p class="section-subtitle">Manage all faculty accounts</p>
            </div>
            
            <?php if(empty($faculty_list)): ?>
                <div class="empty-state">
                    <div class="empty-icon">Faculty</div>
                    <p>No faculty accounts yet</p>
                    <small>Create your first faculty account above</small>
                </div>
            <?php else: ?>
                <div class="search-box">
                    <input type="text" id="searchFaculty" placeholder="Search by name, username or email..." class="search-input">
                </div>

                <div class="table-container">
                    <table id="facultyTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Enrollments</th>
                                <th>Pending Requests</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($faculty_list as $faculty): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($faculty['username']); ?></td>
                                    <td><?php echo htmlspecialchars($faculty['email']); ?></td>
                                    <td><?php echo $faculty['total_enrollments']; ?></td>
                                    <td>
                                        <?php if($faculty['pending_requests'] > 0): ?>
                                            <span class="status-badge status-pending" title="<?php echo $faculty['pending_requests']; ?> Pending Withdrawal Requests">
                                                <i class="fa-solid fa-bell"></i> <?php echo $faculty['pending_requests']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($faculty['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($faculty); ?>)'>Edit</button>
                                        <button class="btn-secondary" style="padding: 8px 15px; font-size: 14px;" onclick="openResetModal(<?php echo $faculty['user_id']; ?>, '<?php echo htmlspecialchars($faculty['username']); ?>')">Reset Password</button>
                                        <button class="delete-btn" onclick="openDeleteModal(<?php echo $faculty['user_id']; ?>, '<?php echo htmlspecialchars($faculty['username']); ?>', <?php echo $faculty['total_enrollments']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h2>Edit Faculty Account</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Username (cannot be changed)</label>
                    <input type="text" id="edit_username" disabled style="background: #f0f0f0;">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" id="edit_first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" id="edit_last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" name="update_faculty" class="btn-primary">Update Account</button>
                    <button type="button" onclick="closeEditModal()" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="resetModal" class="modal">
        <div class="modal-content modern-modal small-modal">
            <div class="modal-header">
                <h2>Reset Password</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="reset_user_id">
                
                <p class="confirm-text">Reset password for: <strong id="reset_username"></strong></p>
                
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" required minlength="6">
                    <small class="form-help">Minimum 6 characters</small>
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" name="reset_password" class="btn-primary">Reset Password</button>
                    <button type="button" onclick="closeResetModal()" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content modern-modal small-modal">
            <div class="modal-header">
                <h2>Delete Faculty Account</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="delete_user_id">
                
                <p class="confirm-text" id="deleteMessage"></p>
                
                <div class="modal-buttons">
                    <button type="submit" name="delete_faculty" class="delete-btn" id="confirmDeleteBtn">Yes, Delete</button>
                    <button type="button" onclick="closeDeleteModal()" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-dismiss messages
        document.querySelectorAll('.auto-dismiss').forEach(msg => {
            setTimeout(() => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            }, 5000);
        });

        // Search functionality
        document.getElementById('searchFaculty')?.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#facultyTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });

        // Edit Modal
        function openEditModal(faculty) {
            document.getElementById('edit_user_id').value = faculty.user_id;
            document.getElementById('edit_username').value = faculty.username;
            document.getElementById('edit_first_name').value = faculty.first_name;
            document.getElementById('edit_last_name').value = faculty.last_name;
            document.getElementById('edit_email').value = faculty.email;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Reset Modal
        function openResetModal(userId, username) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_username').textContent = username;
            document.getElementById('resetModal').style.display = 'block';
        }

        function closeResetModal() {
            document.getElementById('resetModal').style.display = 'none';
        }

        // Delete Modal
        function openDeleteModal(userId, username, enrollments) {
            document.getElementById('delete_user_id').value = userId;
            
            if(enrollments > 0) {
                document.getElementById('deleteMessage').innerHTML = 
                    '<strong>Warning:</strong> Cannot delete faculty <strong>' + username + '</strong> because they have ' + 
                    enrollments + ' enrollment(s).<br><br>Please reassign or remove all enrollments first.';
                document.getElementById('confirmDeleteBtn').style.display = 'none';
            } else {
                document.getElementById('deleteMessage').innerHTML = 
                    'Are you sure you want to delete faculty account <strong>' + username + '</strong>?<br><br>This action cannot be undone.';
                document.getElementById('confirmDeleteBtn').style.display = 'inline-block';
            }
            
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if(event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Form validation
        document.getElementById('addFacultyForm')?.addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]').value;
            if(password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
            }
        });
    </script>
</body>
</html>