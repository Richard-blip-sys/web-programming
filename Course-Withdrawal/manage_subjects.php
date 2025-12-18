<?php
require_once 'auth.php';
require_once 'admin_functions.php';

if(!isLoggedIn() || !isFaculty()) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle Add Subject
if(isset($_POST['add_subject'])) {
    if(addSubject($_POST['subject_code'], $_POST['subject_name'], $_POST['description'], $_POST['units'])) {
        $message = 'Subject added successfully!';
    } else {
        $error = 'Failed to add subject. Subject code may already exist.';
    }
}

// Handle Update Subject
if(isset($_POST['update_subject'])) {
    if(updateSubject($_POST['subject_id'], $_POST['subject_code'], $_POST['subject_name'], $_POST['description'], $_POST['units'])) {
        $message = 'Subject updated successfully!';
    } else {
        $error = 'Failed to update subject.';
    }
}

// Handle Delete Subject
if(isset($_POST['delete_subject'])) {
    if(deleteSubject($_POST['subject_id'])) {
        $message = 'Subject deleted successfully!';
    } else {
        $error = 'Cannot delete subject. There are existing enrollments for this subject.';
    }
}

$subjects = getAllSubjectsWithCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Manage Subjects</h1>
        <form method="POST" action="logout.php" style="display: inline;">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

    <div class="container">
        <a href="faculty_dashboard.php" class="back-btn">← Back to Dashboard</a>

        <?php if($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add Subject Button -->
        <div class="section">
            <div class="header-controls">
                <h2>All Subjects</h2>
                <button class="add-btn" onclick="openAddModal()">+ Add New Subject</button>
            </div>

            <?php if(empty($subjects)): ?>
                <p>No subjects available. Click "Add New Subject" to create one.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Description</th>
                            <th>Units</th>
                            <th>Enrolled Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($subjects as $subject): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['description']); ?></td>
                                <td><?php echo $subject['units']; ?></td>
                                <td><?php echo $subject['enrolled_count']; ?> student(s)</td>
                                <td class="action-buttons">
                                    <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($subject); ?>)'>Edit</button>
                                    <button class="delete-btn" onclick="confirmDelete(<?php echo $subject['subject_id']; ?>, '<?php echo htmlspecialchars($subject['subject_code']); ?>', <?php echo $subject['enrolled_count']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New Subject</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Subject Code *</label>
                    <input type="text" name="subject_code" placeholder="e.g., CS101" required>
                </div>
                <div class="form-group">
                    <label>Subject Name *</label>
                    <input type="text" name="subject_name" placeholder="e.g., Introduction to Computer Science" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description of the subject..."></textarea>
                </div>
                <div class="form-group">
                    <label>Units *</label>
                    <input type="number" name="units" min="1" max="10" value="3" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="add_subject">Add Subject</button>
                    <button type="button" onclick="closeAddModal()" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Subject</h2>
            <form method="POST">
                <input type="hidden" name="subject_id" id="edit_subject_id">
                <div class="form-group">
                    <label>Subject Code *</label>
                    <input type="text" name="subject_code" id="edit_subject_code" required>
                </div>
                <div class="form-group">
                    <label>Subject Name *</label>
                    <input type="text" name="subject_name" id="edit_subject_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Units *</label>
                    <input type="number" name="units" id="edit_units" min="1" max="10" required>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="update_subject">Update Subject</button>
                    <button type="button" onclick="closeEditModal()" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Delete</h2>
            <p id="deleteMessage"></p>
            <form method="POST">
                <input type="hidden" name="subject_id" id="delete_subject_id">
                <div class="modal-buttons">
                    <button type="submit" name="delete_subject" class="delete-btn">Yes, Delete</button>
                    <button type="button" onclick="closeDeleteModal()" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add Modal Functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Edit Modal Functions
        function openEditModal(subject) {
            document.getElementById('edit_subject_id').value = subject.subject_id;
            document.getElementById('edit_subject_code').value = subject.subject_code;
            document.getElementById('edit_subject_name').value = subject.subject_name;
            document.getElementById('edit_description').value = subject.description || '';
            document.getElementById('edit_units').value = subject.units;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Delete Modal Functions
        function confirmDelete(subjectId, subjectCode, enrolledCount) {
            if(enrolledCount > 0) {
                document.getElementById('deleteMessage').innerHTML = 
                    '<strong>Warning:</strong> Cannot delete subject <strong>' + subjectCode + '</strong> because it has ' + 
                    enrolledCount + ' enrolled student(s).<br><br>Please remove all enrollments first.';
                document.querySelector('#deleteModal button[type="submit"]').style.display = 'none';
            } else {
                document.getElementById('deleteMessage').innerHTML = 
                    'Are you sure you want to delete subject <strong>' + subjectCode + '</strong>?<br><br>This action cannot be undone.';
                document.querySelector('#deleteModal button[type="submit"]').style.display = 'inline-block';
                document.getElementById('delete_subject_id').value = subjectId;
            }
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>