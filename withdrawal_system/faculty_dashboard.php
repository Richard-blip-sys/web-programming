<?php
require_once 'auth.php';
require_once 'faculty_functions.php';

if(!isLoggedIn() || !isFaculty()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if(isset($_POST['enroll_student'])) {
    if(enrollStudent($_SESSION['user_id'], $_POST['student_id'], $_POST['subject_id'])) {
        $message = 'Student enrolled successfully!';
    } else {
        $error = 'Enrollment failed.';
    }
}

if(isset($_POST['review_withdrawal'])) {
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $decision = isset($_POST['decision']) ? $_POST['decision'] : '';
    $review_notes = isset($_POST['review_notes']) ? $_POST['review_notes'] : '';
    
    if($request_id && ($decision === 'approved' || $decision === 'rejected')) {
        if(reviewWithdrawal($request_id, $_SESSION['user_id'], $decision, $review_notes)) {
            $message = 'Withdrawal request ' . $decision . ' successfully!';
            header('Location: faculty_dashboard.php?success=1');
            exit();
        } else {
            $error = 'Failed to process request.';
        }
    }
}

if(isset($_GET['success'])) {
    $message = 'Request processed successfully!';
}

$subjects = getSubjects();
$students = getStudents();
$pendingWithdrawals = getPendingWithdrawals();

$database = new Database();
$db = $database->getConnection();

$query = "SELECT COUNT(*) as count FROM withdrawal_requests WHERE status = 'pending' AND DATE(request_date) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$todayPending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$query = "SELECT COUNT(*) as count FROM withdrawal_requests WHERE status = 'approved' AND WEEK(review_date) = WEEK(NOW())";
$stmt = $db->prepare($query);
$stmt->execute();
$weekApprovals = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$totalPending = count($pendingWithdrawals);

// Get notification count
$notificationCount = $totalPending;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>👨‍🏫 Faculty Dashboard</h1>
        <div class="header-buttons">
            <!-- Notification Bell -->
            <div class="notification-bell-wrapper">
                <button class="notification-bell" onclick="toggleNotifications()" id="notificationBell">
                    🔔
                    <span class="notification-badge" id="notificationBadge" style="display: <?php echo $notificationCount > 0 ? 'flex' : 'none'; ?>">
                        <?php echo $notificationCount; ?>
                    </span>
                </button>
                
                <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                    <div class="notification-header">
                        <h3>Withdrawal Requests</h3>
                    </div>
                    
                    <div class="notification-list" id="notificationList">
                        <?php if(empty($pendingWithdrawals)): ?>
                            <div class="notification-empty">No pending requests</div>
                        <?php else: ?>
                            <?php foreach($pendingWithdrawals as $notif): ?>
                                <div class="notification-item unread">
                                    <div class="notification-icon">📝</div>
                                    <div class="notification-content">
                                        <div class="notification-title">New Withdrawal Request</div>
                                        <div class="notification-message">
                                            <?php echo htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']); ?> - 
                                            <?php echo htmlspecialchars($notif['subject_code']); ?>
                                        </div>
                                        <div class="notification-time">
                                            <?php 
                                            $time = strtotime($notif['request_date']);
                                            $diff = time() - $time;
                                            if($diff < 60) echo 'Just now';
                                            elseif($diff < 3600) echo floor($diff/60) . ' min ago';
                                            elseif($diff < 86400) echo floor($diff/3600) . ' hours ago';
                                            else echo floor($diff/86400) . ' days ago';
                                            ?>
                                        </div>
                                    </div>
                                    <span class="notification-unread-dot"></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-footer">
                        <a href="#pending-requests" onclick="closeNotifications()">View All</a>
                    </div>
                </div>
            </div>
            
            <button onclick="window.print()" class="manage-btn">🖨️ Print</button>
            <a href="kpi_reports.php" class="manage-btn">📊 Reports</a>
            <a href="manage_subjects.php" class="manage-btn">📚 Subjects</a>
            <form method="POST" action="logout.php" class="inline-form">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <div class="welcome-content">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! 👋</h2>
                <p class="welcome-subtitle">Here's what's happening today</p>
            </div>
        </div>

        <?php if($message): ?>
            <div class="success auto-dismiss"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error auto-dismiss"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="quick-stats-grid">
            <div class="stat-card stat-orange">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $todayPending; ?></div>
                    <div class="stat-label">New Today</div>
                </div>
            </div>
            <div class="stat-card stat-blue">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $totalPending; ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $weekApprovals; ?></div>
                    <div class="stat-label">Approved This Week</div>
                </div>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2>➕ Enroll Student to Subject</h2>
            </div>
            <form method="POST" class="modern-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Student *</label>
                        <select name="student_id" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?php echo $student['user_id']; ?>">
                                    <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Subject *</label>
                        <select name="subject_id" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="enroll_student" class="btn-primary">Enroll Student</button>
            </form>
        </div>

        <div class="dashboard-section" id="pending-requests">
            <div class="section-header">
                <h2>📝 Pending Withdrawal Requests (<?php echo $totalPending; ?>)</h2>
            </div>
            
            <?php if(empty($pendingWithdrawals)): ?>
                <div class="empty-state">
                    <div class="empty-icon">✨</div>
                    <p>No pending requests</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pendingWithdrawals as $request): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($request['subject_code'] . ' - ' . $request['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($request['reason'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="approve-btn" onclick="openReviewModal(<?php echo $request['request_id']; ?>, 'approved', '<?php echo addslashes($request['first_name'] . ' ' . $request['last_name']); ?>', '<?php echo addslashes($request['subject_code']); ?>')">✓</button>
                                        <button class="reject-btn" onclick="openReviewModal(<?php echo $request['request_id']; ?>, 'rejected', '<?php echo addslashes($request['first_name'] . ' ' . $request['last_name']); ?>', '<?php echo addslashes($request['subject_code']); ?>')">✕</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="reviewModal" class="modal">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h2 id="modalTitle">Review Request</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="request_id" id="modal_request_id">
                <input type="hidden" name="decision" id="modal_decision">
                <div class="info-box">
                    <strong id="studentName"></strong> - <strong id="subjectCode"></strong>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="review_notes" rows="3"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="review_withdrawal" class="btn-primary">Submit</button>
                    <button type="button" onclick="closeReviewModal()" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
        
        function closeNotifications() {
            document.getElementById('notificationDropdown').style.display = 'none';
        }
        
        document.addEventListener('click', function(e) {
            const bell = document.getElementById('notificationBell');
            const dropdown = document.getElementById('notificationDropdown');
            if(!bell.contains(e.target) && !dropdown.contains(e.target)) {
                closeNotifications();
            }
        });

        function openReviewModal(id, decision, name, code) {
            document.getElementById('modal_request_id').value = id;
            document.getElementById('modal_decision').value = decision;
            document.getElementById('studentName').textContent = name;
            document.getElementById('subjectCode').textContent = code;
            document.getElementById('modalTitle').textContent = decision === 'approved' ? '✅ Approve' : '❌ Reject';
            document.getElementById('reviewModal').style.display = 'block';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }

        window.onclick = function(e) {
            if(e.target.classList.contains('modal')) e.target.style.display = 'none';
        }

        document.querySelectorAll('.auto-dismiss').forEach(msg => {
            setTimeout(() => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>