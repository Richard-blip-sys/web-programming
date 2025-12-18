<?php
require_once 'auth.php';
require_once 'config.php';

if(!isLoggedIn() || !isFaculty()) {
    header('Location: login.php');
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : 'withdrawals';

$database = new Database();
$db = $database->getConnection();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $type . '_report_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

switch($type) {
    case 'withdrawals':
        // CSV Headers
        fputcsv($output, ['Request ID', 'Student Name', 'Email', 'Subject Code', 'Subject Name', 'Reason', 'Status', 'Request Date', 'Reviewed By', 'Review Date', 'Review Notes']);
        
        // Get data
        $query = "SELECT 
                    wr.request_id,
                    CONCAT(u.first_name, ' ', u.last_name) as student_name,
                    u.email as student_email,
                    s.subject_code,
                    s.subject_name,
                    wr.reason,
                    wr.status,
                    wr.request_date,
                    CONCAT(f.first_name, ' ', f.last_name) as reviewed_by,
                    wr.review_date,
                    wr.review_notes
                  FROM withdrawal_requests wr
                  JOIN enrollments e ON wr.enrollment_id = e.enrollment_id
                  JOIN users u ON e.student_id = u.user_id
                  JOIN subjects s ON e.subject_id = s.subject_id
                  LEFT JOIN users f ON wr.reviewed_by = f.user_id
                  ORDER BY wr.request_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($results as $row) {
            fputcsv($output, [
                $row['request_id'],
                $row['student_name'],
                $row['student_email'],
                $row['subject_code'],
                $row['subject_name'],
                $row['reason'],
                strtoupper($row['status']),
                $row['request_date'],
                $row['reviewed_by'] ?? 'N/A',
                $row['review_date'] ?? 'N/A',
                $row['review_notes'] ?? 'N/A'
            ]);
        }
        break;
        
    case 'enrollments':
        // CSV Headers
        fputcsv($output, ['Enrollment ID', 'Student Name', 'Email', 'Subject Code', 'Subject Name', 'Units', 'Faculty', 'Status', 'Enrollment Date']);
        
        // Get data
        $query = "SELECT 
                    e.enrollment_id,
                    CONCAT(u.first_name, ' ', u.last_name) as student_name,
                    u.email as student_email,
                    s.subject_code,
                    s.subject_name,
                    s.units,
                    CONCAT(f.first_name, ' ', f.last_name) as faculty_name,
                    e.status,
                    e.enrollment_date
                  FROM enrollments e
                  JOIN users u ON e.student_id = u.user_id
                  JOIN subjects s ON e.subject_id = s.subject_id
                  JOIN users f ON e.faculty_id = f.user_id
                  ORDER BY e.enrollment_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($results as $row) {
            fputcsv($output, [
                $row['enrollment_id'],
                $row['student_name'],
                $row['student_email'],
                $row['subject_code'],
                $row['subject_name'],
                $row['units'],
                $row['faculty_name'],
                strtoupper($row['status']),
                $row['enrollment_date']
            ]);
        }
        break;
        
    case 'students':
        // CSV Headers
        fputcsv($output, ['Student ID', 'Username', 'Full Name', 'Email', 'Total Enrollments', 'Active Enrollments', 'Withdrawal Requests', 'Registration Date']);
        
        // Get data
        $query = "SELECT 
                    u.user_id,
                    u.username,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    u.email,
                    COUNT(DISTINCT e.enrollment_id) as total_enrollments,
                    COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.enrollment_id END) as active_enrollments,
                    COUNT(DISTINCT wr.request_id) as withdrawal_count,
                    u.created_at
                  FROM users u
                  LEFT JOIN enrollments e ON u.user_id = e.student_id
                  LEFT JOIN withdrawal_requests wr ON e.enrollment_id = wr.enrollment_id
                  WHERE u.user_type = 'student'
                  GROUP BY u.user_id
                  ORDER BY u.last_name, u.first_name";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($results as $row) {
            fputcsv($output, [
                $row['user_id'],
                $row['username'],
                $row['full_name'],
                $row['email'],
                $row['total_enrollments'],
                $row['active_enrollments'],
                $row['withdrawal_count'],
                $row['created_at']
            ]);
        }
        break;
}

fclose($output);
exit();
?>