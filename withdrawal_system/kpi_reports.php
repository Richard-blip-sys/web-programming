<?php
require_once 'auth.php';
require_once 'config.php';

if(!isLoggedIn() || !isFaculty()) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get KPI Data
function getKPIData($db) {
    $kpis = [];
    
    // Total Students
    $query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'student'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $kpis['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Subjects
    $query = "SELECT COUNT(*) as total FROM subjects";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $kpis['total_subjects'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active Enrollments
    $query = "SELECT COUNT(*) as total FROM enrollments WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $kpis['active_enrollments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Withdrawals
    $query = "SELECT COUNT(*) as total FROM withdrawal_requests";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $kpis['total_withdrawals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending Withdrawals
    $query = "SELECT COUNT(*) as total FROM withdrawal_requests WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $kpis['pending_withdrawals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Approved Withdrawals
    $query = "SELECT COUNT(*) as total FROM withdrawal_requests WHERE status = 'approved'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $kpis['approved_withdrawals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Rejected Withdrawals
    $query = "SELECT COUNT(*) as total FROM withdrawal_requests WHERE status = 'rejected'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $kpis['rejected_withdrawals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Approval Rate
    $total = $kpis['approved_withdrawals'] + $kpis['rejected_withdrawals'];
    $kpis['approval_rate'] = $total > 0 ? round(($kpis['approved_withdrawals'] / $total) * 100, 1) : 0;
    
    return $kpis;
}

// Get Most Withdrawn Subjects
function getMostWithdrawnSubjects($db) {
    $query = "SELECT s.subject_code, s.subject_name, COUNT(wr.request_id) as withdrawal_count
              FROM subjects s
              LEFT JOIN enrollments e ON s.subject_id = e.subject_id
              LEFT JOIN withdrawal_requests wr ON e.enrollment_id = wr.enrollment_id
              WHERE wr.status = 'approved'
              GROUP BY s.subject_id
              HAVING withdrawal_count > 0
              ORDER BY withdrawal_count DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get Recent Withdrawal Activity
function getRecentActivity($db) {
    $query = "SELECT wr.request_date, wr.status, wr.reason,
                     u.first_name, u.last_name,
                     s.subject_code, s.subject_name
              FROM withdrawal_requests wr
              JOIN enrollments e ON wr.enrollment_id = e.enrollment_id
              JOIN users u ON e.student_id = u.user_id
              JOIN subjects s ON e.subject_id = s.subject_id
              ORDER BY wr.request_date DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get Monthly Withdrawal Trend
function getMonthlyTrend($db) {
    $query = "SELECT 
                DATE_FORMAT(request_date, '%Y-%m') as month,
                COUNT(*) as count
              FROM withdrawal_requests
              WHERE request_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              GROUP BY month
              ORDER BY month";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$kpis = getKPIData($db);
$mostWithdrawn = getMostWithdrawnSubjects($db);
$recentActivity = getRecentActivity($db);
$monthlyTrend = getMonthlyTrend($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI & Reports Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header -->
    <div class="header no-print">
        <h1>📊 KPI & Reports Dashboard</h1>
        <div class="header-buttons">
            <button onclick="window.print()" class="print-btn">🖨️ Print</button>
            <a href="faculty_dashboard.php" class="back-btn">← Back</a>
            <form method="POST" action="logout.php" class="inline-form">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>
    
    <!-- Print Header (only visible when printing) -->
    <div class="print-only print-header">
        <h1>Course Withdrawal System - KPI & Reports</h1>
        <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
        <p>Generated by: <?php echo $_SESSION['name']; ?></p>
        <hr>
    </div>

    <div class="container">
        <!-- Report Info Bar -->
        <div class="report-info-bar no-print">
            <div class="info-item">
                <span class="info-label">Report Date:</span>
                <span class="info-value"><?php echo date('F d, Y'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Time:</span>
                <span class="info-value"><?php echo date('h:i A'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Generated By:</span>
                <span class="info-value"><?php echo $_SESSION['name']; ?></span>
            </div>
        </div>

        <!-- Key Performance Indicators Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>📈 Key Performance Indicators</h2>
                <p class="section-subtitle">Overview of system performance metrics</p>
            </div>
            
            <div class="kpi-grid">
                <!-- System Metrics -->
                <div class="kpi-category">
                    <h3 class="category-title">System Overview</h3>
                    <div class="kpi-cards">
                        <div class="kpi-card kpi-blue">
                            <div class="kpi-icon">👥</div>
                            <div class="kpi-value"><?php echo $kpis['total_students']; ?></div>
                            <div class="kpi-label">Total Students</div>
                        </div>
                        
                        <div class="kpi-card kpi-purple">
                            <div class="kpi-icon">📚</div>
                            <div class="kpi-value"><?php echo $kpis['total_subjects']; ?></div>
                            <div class="kpi-label">Total Subjects</div>
                        </div>
                        
                        <div class="kpi-card kpi-green">
                            <div class="kpi-icon">✅</div>
                            <div class="kpi-value"><?php echo $kpis['active_enrollments']; ?></div>
                            <div class="kpi-label">Active Enrollments</div>
                        </div>
                    </div>
                </div>

                <!-- Withdrawal Metrics -->
                <div class="kpi-category">
                    <h3 class="category-title">Withdrawal Statistics</h3>
                    <div class="kpi-cards">
                        <div class="kpi-card kpi-orange">
                            <div class="kpi-icon">⏳</div>
                            <div class="kpi-value"><?php echo $kpis['pending_withdrawals']; ?></div>
                            <div class="kpi-label">Pending Requests</div>
                        </div>
                        
                        <div class="kpi-card kpi-success">
                            <div class="kpi-icon">✔️</div>
                            <div class="kpi-value"><?php echo $kpis['approved_withdrawals']; ?></div>
                            <div class="kpi-label">Approved</div>
                        </div>
                        
                        <div class="kpi-card kpi-danger">
                            <div class="kpi-icon">❌</div>
                            <div class="kpi-value"><?php echo $kpis['rejected_withdrawals']; ?></div>
                            <div class="kpi-label">Rejected</div>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="kpi-category full-width">
                    <h3 class="category-title">Performance Analysis</h3>
                    <div class="kpi-cards">
                        <div class="kpi-card kpi-info">
                            <div class="kpi-icon">📊</div>
                            <div class="kpi-value"><?php echo $kpis['total_withdrawals']; ?></div>
                            <div class="kpi-label">Total Withdrawals</div>
                        </div>
                        
                        <div class="kpi-card kpi-teal">
                            <div class="kpi-icon">📈</div>
                            <div class="kpi-value"><?php echo $kpis['approval_rate']; ?>%</div>
                            <div class="kpi-label">Approval Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trend Chart Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>📉 Withdrawal Trends</h2>
                <p class="section-subtitle">Last 6 months withdrawal request patterns</p>
            </div>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Two Column Layout for Tables -->
        <div class="two-column-layout">
            <!-- Most Withdrawn Subjects -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>🔝 Most Withdrawn Subjects</h2>
                    <p class="section-subtitle">Top 5 subjects by withdrawal count</p>
                </div>
                <?php if(empty($mostWithdrawn)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <p>No withdrawal data available yet</p>
                    </div>
                <?php else: ?>
                    <div class="rank-table">
                        <?php $rank = 1; foreach($mostWithdrawn as $subject): ?>
                            <div class="rank-item">
                                <div class="rank-badge rank-<?php echo $rank; ?>">#<?php echo $rank; ?></div>
                                <div class="rank-content">
                                    <div class="rank-title"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                                    <div class="rank-subtitle"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                </div>
                                <div class="rank-count"><?php echo $subject['withdrawal_count']; ?> withdrawals</div>
                            </div>
                        <?php $rank++; endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Export Reports Card -->
            <div class="dashboard-section no-print">
                <div class="section-header">
                    <h2>📥 Export Reports</h2>
                    <p class="section-subtitle">Download data as CSV files</p>
                </div>
                <div class="export-card-grid">
                    <a href="export_report.php?type=withdrawals" class="export-card">
                        <div class="export-icon">📄</div>
                        <div class="export-title">Withdrawals Report</div>
                        <div class="export-desc">Complete withdrawal history</div>
                    </a>
                    
                    <a href="export_report.php?type=enrollments" class="export-card">
                        <div class="export-icon">📋</div>
                        <div class="export-title">Enrollments Report</div>
                        <div class="export-desc">All enrollment records</div>
                    </a>
                    
                    <a href="export_report.php?type=students" class="export-card">
                        <div class="export-icon">👥</div>
                        <div class="export-title">Students Report</div>
                        <div class="export-desc">Student statistics</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2>🕒 Recent Activity</h2>
                <p class="section-subtitle">Latest withdrawal requests</p>
            </div>
            <?php if(empty($recentActivity)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <p>No recent activity</p>
                </div>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-date"><?php echo date('M d, Y', strtotime($activity['request_date'])); ?></div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <strong><?php echo $activity['first_name'] . ' ' . $activity['last_name']; ?></strong>
                                    requested withdrawal from
                                    <strong><?php echo $activity['subject_code']; ?></strong>
                                </div>
                                <div class="activity-reason"><?php echo htmlspecialchars(substr($activity['reason'], 0, 80)) . (strlen($activity['reason']) > 80 ? '...' : ''); ?></div>
                            </div>
                            <div class="activity-status">
                                <span class="status-badge status-<?php echo $activity['status']; ?>">
                                    <?php echo strtoupper($activity['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Print Footer -->
        <div class="print-only print-footer">
            <hr>
            <p>Course Withdrawal System | <?php echo $_SERVER['HTTP_HOST']; ?> | Page printed on <?php echo date('F d, Y h:i A'); ?></p>
        </div>
    </div>

    <script>
        // Withdrawal Trend Chart
        const ctx = document.getElementById('trendChart').getContext('2d');
        const trendData = <?php echo json_encode($monthlyTrend); ?>;
        
        const labels = trendData.map(item => {
            const [year, month] = item.month.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        const data = trendData.map(item => item.count);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Withdrawal Requests',
                    data: data,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#667eea',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>