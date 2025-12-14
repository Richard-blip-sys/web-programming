<?php
require_once 'auth.php';
require_once 'faculty_functions.php';

if(!isLoggedIn() || !isFaculty()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle enrollment
if(isset($_POST['enroll_student'])) {
    if(enrollStudent($_SESSION['user_id'], $_POST['student_id'], $_POST['subject_id'])) {
        $message = 'Student enrolled successfully!';
    } else {
        $error = 'Enrollment failed. Student may already be enrolled in this subject.';
    }
}

// Handle withdrawal review
if(isset($_POST['review_withdrawal'])) {
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $faculty_id = $_SESSION['user_id'];
    $decision = isset($_POST['decision']) ? $_POST['decision'] : '';
    $review_notes = isset($_POST['review_notes']) ? $_POST['review_notes'] : '';
    
    if($request_id && ($decision === 'approved' || $decision === 'rejected')) {
        if(reviewWithdrawal($request_id, $faculty_id, $decision, $review_notes)) {
            $message = 'Withdrawal request ' . $decision . ' successfully!';
        } else {
            $error = 'Failed to process withdrawal request.';
        }
    } else {
        $error = 'Invalid withdrawal data.';
    }
}

$subjects = getSubjects();
$students = getStudents();
$pendingWithdrawals = getPendingWithdrawals();

// Get quick stats
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
            <button onclick="window.print()" class="manage-btn">🖨️ Print</button>
            <a href="kpi_reports.php" class="manage-btn">📊 KPI & Reports</a>
            <a href="manage_subjects.php" class="manage-btn">📚 Manage Subjects</a>
            <form method="POST" action="logout.php" class="inline-form">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h2>Welcome back, <?php echo $_SESSION['name']; ?>! 👋</h2>
                <p class="welcome-subtitle">Here's what's happening today</p>
            </div>
        </div>

        <!-- Auto-dismiss Messages -->
        <?php if($message): ?>
            <div class="success auto-dismiss"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error auto-dismiss"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Quick Stats Cards -->
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

        <!-- Enroll Student Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>➕ Enroll Student to Subject</h2>
                <p class="section-subtitle">Add new student enrollment</p>
            </div>
            <form method="POST" class="modern-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Student *</label>
                        <select name="student_id" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?php echo $student['user_id']; ?>">
                                    <?php echo $student['last_name'] . ', ' . $student['first_name']; ?>
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
                                    <?php echo $subject['subject_code'] . ' - ' . $subject['subject_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="enroll_student" class="btn-primary">Enroll Student</button>
            </form>
        </div>

        <!-- Pending Withdrawal Requests -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>📝 Pending Withdrawal Requests</h2>
                <p class="section-subtitle">Review and approve/reject student requests</p>
            </div>
            
            <?php if(empty($pendingWithdrawals)): ?>
                <div class="empty-state">
                    <div class="empty-icon">✨</div>
                    <p>No pending withdrawal requests</p>
                    <small>All caught up!</small>
                </div>
            <?php else: ?>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍 Search by student name or subject code..." class="search-input">
                </div>

                <div class="table-container">
                    <table id="withdrawalTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Reason</th>
                                <th>Request Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pendingWithdrawals as $request): ?>
                                <tr>
                                    <td><strong><?php echo $request['first_name'] . ' ' . $request['last_name']; ?></strong></td>
                                    <td><?php echo $request['subject_code'] . ' - ' . $request['subject_name']; ?></td>
                                    <td class="reason-cell"><?php echo htmlspecialchars($request['reason']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($request['request_date'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="approve-btn" onclick="openReviewModal(<?php echo $request['request_id']; ?>, 'approved', '<?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>', '<?php echo htmlspecialchars($request['subject_code']); ?>')">
                                            ✓ Approve
                                        </button>
                                        <button class="reject-btn" onclick="openReviewModal(<?php echo $request['request_id']; ?>, 'rejected', '<?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>', '<?php echo htmlspecialchars($request['subject_code']); ?>')">
                                            ✕ Reject
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h2 id="modalTitle">Review Withdrawal Request</h2>
            </div>
            <form method="POST" id="reviewForm">
                <input type="hidden" name="request_id" id="modal_request_id">
                <input type="hidden" name="decision" id="modal_decision">
                
                <div class="info-box">
                    <strong id="studentName"></strong> is requesting withdrawal from <strong id="subjectCode"></strong>
                </div>
                
                <div class="form-group">
                    <label>Faculty Notes (Optional)</label>
                    <textarea name="review_notes" rows="4" placeholder="Add any notes or comments for the student..."></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" name="review_withdrawal" class="btn-primary" id="submitBtn">Submit Decision</button>
                    <button type="button" onclick="closeReviewModal()" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-dismiss messages
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.auto-dismiss');
            messages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    message.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        message.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });

        // Search functionality
        document.getElementById('searchInput')?.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('withdrawalTable');
            const rows = table.getElementsByTagName('tr');
            
            for(let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            }
        });

        // Review Modal Functions
        function openReviewModal(requestId, decision, studentName, subjectCode) {
            document.getElementById('modal_request_id').value = requestId;
            document.getElementById('modal_decision').value = decision;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('subjectCode').textContent = subjectCode;
            
            const title = decision === 'approved' ? '✅ Approve Withdrawal Request' : '❌ Reject Withdrawal Request';
            document.getElementById('modalTitle').textContent = title;
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.textContent = decision === 'approved' ? '✅ Approve Request' : '❌ Reject Request';
            
            document.getElementById('reviewModal').style.display = 'block';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
            document.getElementById('reviewForm').reset();
        }

        // Form submission
        document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            if(confirm('Are you sure you want to submit this decision?')) {
                this.submit();
            }
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>