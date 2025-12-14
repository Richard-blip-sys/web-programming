<?php
// test_withdrawal.php - CREATE THIS FILE TO TEST
require_once 'config.php';

echo "<h2>🔍 Withdrawal System Debug</h2>";
echo "<hr>";

$database = new Database();
$db = $database->getConnection();

// Test 1: Check if withdrawal_requests table exists
echo "<h3>Test 1: Check Tables</h3>";
try {
    $query = "SHOW TABLES LIKE 'withdrawal_requests'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    if($stmt->rowCount() > 0) {
        echo "✅ withdrawal_requests table EXISTS<br>";
    } else {
        echo "❌ withdrawal_requests table DOES NOT EXIST!<br>";
        echo "<strong>FIX: Run this SQL in phpMyAdmin:</strong><br>";
        echo "<pre>
CREATE TABLE IF NOT EXISTS withdrawal_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    reason TEXT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT,
    review_date TIMESTAMP NULL,
    review_notes TEXT,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id)
);
</pre>";
    }
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 2: Check enrollments table
echo "<h3>Test 2: Check Enrollments</h3>";
try {
    $query = "SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Active enrollments: " . $result['count'] . "<br>";
    
    // Show some enrollments
    $query = "SELECT e.enrollment_id, s.subject_code, 
              CONCAT(u.first_name, ' ', u.last_name) as student_name
              FROM enrollments e
              JOIN subjects s ON e.subject_id = s.subject_id
              JOIN users u ON e.student_id = u.user_id
              WHERE e.status = 'active'
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($enrollments) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>Enrollment ID</th><th>Student</th><th>Subject</th></tr>";
        foreach($enrollments as $e) {
            echo "<tr>";
            echo "<td>" . $e['enrollment_id'] . "</td>";
            echo "<td>" . $e['student_name'] . "</td>";
            echo "<td>" . $e['subject_code'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 3: Check withdrawal_requests
echo "<h3>Test 3: Check Withdrawal Requests</h3>";
try {
    $query = "SELECT COUNT(*) as count FROM withdrawal_requests";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total withdrawal requests: " . $result['count'] . "<br>";
    
    // Show recent requests
    $query = "SELECT wr.request_id, wr.status, wr.request_date,
              CONCAT(u.first_name, ' ', u.last_name) as student_name,
              s.subject_code
              FROM withdrawal_requests wr
              JOIN enrollments e ON wr.enrollment_id = e.enrollment_id
              JOIN users u ON e.student_id = u.user_id
              JOIN subjects s ON e.subject_id = s.subject_id
              ORDER BY wr.request_date DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($requests) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>ID</th><th>Student</th><th>Subject</th><th>Status</th><th>Date</th></tr>";
        foreach($requests as $r) {
            echo "<tr>";
            echo "<td>" . $r['request_id'] . "</td>";
            echo "<td>" . $r['student_name'] . "</td>";
            echo "<td>" . $r['subject_code'] . "</td>";
            echo "<td>" . $r['status'] . "</td>";
            echo "<td>" . $r['request_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No withdrawal requests found<br>";
    }
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 4: Try to insert a test withdrawal
echo "<h3>Test 4: Test Insert</h3>";
try {
    // Get first active enrollment
    $query = "SELECT enrollment_id FROM enrollments WHERE status = 'active' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($enrollment) {
        echo "Testing with enrollment_id: " . $enrollment['enrollment_id'] . "<br>";
        
        $query = "INSERT INTO withdrawal_requests (enrollment_id, reason) 
                  VALUES (:enrollment_id, :reason)";
        $stmt = $db->prepare($query);
        $enrollment_id = $enrollment['enrollment_id'];
        $reason = "TEST WITHDRAWAL - " . date('Y-m-d H:i:s');
        $stmt->bindParam(':enrollment_id', $enrollment_id);
        $stmt->bindParam(':reason', $reason);
        
        if($stmt->execute()) {
            echo "✅ TEST INSERT SUCCESSFUL!<br>";
            echo "Request ID: " . $db->lastInsertId() . "<br>";
        } else {
            echo "❌ TEST INSERT FAILED<br>";
        }
    } else {
        echo "⚠️ No active enrollments to test with<br>";
    }
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>📝 Summary</h3>";
echo "If all tests passed, the system should work!<br>";
echo "If any test failed, follow the FIX instructions above.<br>";
?>