<?php
require_once 'auth.php';
require_once 'config.php';

// Must be logged in as student
if(!isLoggedIn() || !isStudent()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if(isset($_POST['submit_withdrawal'])) {
    $enrollment_id = isset($_POST['enrollment_id']) ? intval($_POST['enrollment_id']) : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    if($enrollment_id > 0 && !empty($reason)) {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            // Just insert it - no fancy stuff
            $query = "INSERT INTO withdrawal_requests (enrollment_id, reason, request_date, status) 
                      VALUES (:enrollment_id, :reason, NOW(), 'pending')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':enrollment_id', $enrollment_id, PDO::PARAM_INT);
            $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
            
            if($stmt->execute()) {
                $message = 'Withdrawal request submitted successfully! Your faculty will review it soon.';
            } else {
                $error = 'Failed to submit request. Please try again.';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Invalid data provided.';
    }
}

// Get student enrollments
$database = new Database();
$db = $database->getConnection();

$query = "SELECT e.enrollment_id, s.subject_code, s.subject_name, 
                 s.units, e.enrollment_date,
                 u.first_name as faculty_first, u.last_name as faculty_last
          FROM enrollments e
          JOIN subjects s ON e.subject_id = s.subject_id
          JOIN users u ON e.faculty_id = u.user_id
          WHERE e.student_id = :student_id AND e.status = 'active'
          ORDER BY s.subject_code";
$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $_SESSION['user_id']);
$stmt->execute();
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get withdrawal history
$query = "SELECT wr.request_date, wr.status, wr.reason, wr.review_notes,
                 s.subject_code, s.subject_name
          FROM withdrawal_requests wr
          JOIN enrollments e ON wr.enrollment_id = e.enrollment_id
          JOIN subjects s ON e.subject_id = s.subject_id
          WHERE e.student_id = :student_id
          ORDER BY wr.request_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $_SESSION['user_id']);
$stmt->execute();
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEnrollments = count($enrollments);
$totalUnits = array_sum(array_column($enrollments, 'units'));
$pendingRequests = count(array_filter($history, fn($h) => $h['status'] === 'pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header student">
        <h1>👨‍🎓 Student Dashboard</h1>
        <form method="POST" action="logout.php" class="inline-form">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section student-welcome">
            <div class="welcome-content">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! 👋</h2>
                <p class="welcome-subtitle">Manage your enrollments and withdrawal requests</p>
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
            <div class="stat-card stat-blue">
                <div class="stat-icon">📚</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $totalEnrollments; ?></div>
                    <div class="stat-label">Active Subjects</div>
                </div>
            </div>
            
            <div class="stat-card stat-green">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $totalUnits; ?></div>
                    <div class="stat-label">Total Units</div>
                </div>
            </div>
            
            <div class="stat-card stat-orange">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $pendingRequests; ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
            </div>
        </div>

        <!-- Current Enrollments -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>📖 My Current Enrollments</h2>
                <p class="section-subtitle">Your active subjects this semester</p>
            </div>
            
            <?php if(empty($enrollments)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📚</div>
                    <p>You are not currently enrolled in any subjects</p>
                    <small>Please contact your faculty to enroll in courses</small>
                </div>
            <?php else: ?>
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" id="searchEnrollments" placeholder="🔍 Search by subject code or name..." class="search-input">
                </div>

                <div class="table-container">
                    <table id="enrollmentsTable">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="sortTable(0)">Subject Code <span class="sort-icon">⇅</span></th>
                                <th class="sortable" onclick="sortTable(1)">Subject Name <span class="sort-icon">⇅</span></th>
                                <th class="sortable" onclick="sortTable(2)">Units <span class="sort-icon">⇅</span></th>
                                <th>Faculty</th>
                                <th class="sortable" onclick="sortTable(4)">Enrolled Date <span class="sort-icon">⇅</span></th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($enrollments as $enrollment): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($enrollment['subject_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($enrollment['subject_name']); ?></td>
                                    <td><?php echo $enrollment['units']; ?></td>
                                    <td><?php echo htmlspecialchars($enrollment['faculty_first'] . ' ' . $enrollment['faculty_last']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                    <td>
                                        <button class="withdraw-btn" onclick="openWithdrawModal(<?php echo $enrollment['enrollment_id']; ?>, '<?php echo htmlspecialchars($enrollment['subject_code'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($enrollment['subject_name'], ENT_QUOTES); ?>')">
                                            📝 Request Withdrawal
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Withdrawal History -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>📋 Withdrawal Request History</h2>
                <p class="section-subtitle">Track your withdrawal requests and their status</p>
            </div>
            
            <?php if(empty($history)): ?>
                <div class="empty-state">
                    <div class="empty-icon">✨</div>
                    <p>No withdrawal requests yet</p>
                    <small>Your withdrawal history will appear here</small>
                </div>
            <?php else: ?>
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" id="searchHistory" placeholder="🔍 Search by subject or status..." class="search-input">
                </div>

                <div class="history-list">
                    <?php foreach($history as $request): ?>
                        <div class="history-item">
                            <div class="history-date"><?php echo date('M d, Y', strtotime($request['request_date'])); ?></div>
                            <div class="history-content">
                                <div class="history-title">
                                    <strong><?php echo htmlspecialchars($request['subject_code']); ?></strong> - <?php echo htmlspecialchars($request['subject_name']); ?>
                                </div>
                                <div class="history-reason">
                                    <strong>Reason:</strong> <?php echo htmlspecialchars($request['reason']); ?>
                                </div>
                                <?php if($request['review_notes']): ?>
                                    <div class="history-notes">
                                        <strong>Faculty Notes:</strong> <?php echo htmlspecialchars($request['review_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="history-status">
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php echo strtoupper($request['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Withdrawal Modal -->
    <div id="withdrawModal" class="modal">
        <div class="modal-content modern-modal">
            <div class="modal-header">
                <h2>📝 Request Course Withdrawal</h2>
            </div>
            <div class="info-box">
                <strong>Subject:</strong> <span id="modal_subject"></span>
            </div>
            <form method="POST" action="" id="withdrawForm">
                <input type="hidden" name="enrollment_id" id="modal_enrollment_id">
                <div class="form-group">
                    <label>Reason for Withdrawal *</label>
                    <textarea name="reason" rows="5" placeholder="Please provide a detailed reason for your withdrawal request..." required></textarea>
                    <small class="form-help">Your faculty will review this request and provide feedback.</small>
                </div>
                <div class="info-box">
                    📧 Your faculty will be notified of this request.
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="submit_withdrawal" class="btn-primary">Submit Request</button>
                    <button type="button" onclick="closeWithdrawModal()" class="btn-secondary">Cancel</button>
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

        // Search functionality for enrollments
        document.getElementById('searchEnrollments')?.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('enrollmentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for(let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent.toLowerCase();
                rows[i].style.display = text.includes(searchValue) ? '' : 'none';
            }
        });

        // Search functionality for history
        document.getElementById('searchHistory')?.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const items = document.querySelectorAll('.history-item');
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });

        // Sort table
        let sortDirection = {};
        function sortTable(columnIndex) {
            const table = document.getElementById('enrollmentsTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            
            if(!sortDirection[columnIndex]) sortDirection[columnIndex] = false;
            const dir = sortDirection[columnIndex] = !sortDirection[columnIndex];
            
            rows.sort((a, b) => {
                const aText = a.getElementsByTagName('td')[columnIndex].textContent.trim();
                const bText = b.getElementsByTagName('td')[columnIndex].textContent.trim();
                return dir ? (aText > bText ? 1 : -1) : (aText < bText ? 1 : -1);
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }

        // Withdrawal modal functions
        function openWithdrawModal(enrollmentId, subjectCode, subjectName) {
            document.getElementById('modal_enrollment_id').value = enrollmentId;
            document.getElementById('modal_subject').textContent = subjectCode + ' - ' + subjectName;
            document.getElementById('withdrawModal').style.display = 'block';
        }

        function closeWithdrawModal() {
            document.getElementById('withdrawModal').style.display = 'none';
            document.getElementById('withdrawForm').reset();
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Session timeout (30 minutes)
        let sessionTimeout;
        let warningTimeout;

        function resetSessionTimer() {
            clearTimeout(sessionTimeout);
            clearTimeout(warningTimeout);
            
            warningTimeout = setTimeout(() => {
                alert('⚠️ Your session will expire in 2 minutes due to inactivity. Please save your work.');
            }, 28 * 60 * 1000);
            
            sessionTimeout = setTimeout(() => {
                alert('🔒 Your session has expired due to inactivity. You will be logged out.');
                window.location.href = 'logout.php';
            }, 30 * 60 * 1000);
        }

        ['mousedown', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetSessionTimer, true);
        });

        resetSessionTimer();
    </script>
</body>
</html>