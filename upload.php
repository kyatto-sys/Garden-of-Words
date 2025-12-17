<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Validation
    if (empty($title)) {
        $error = "Please enter a title for your manuscript!";
    } elseif (empty($description)) {
        $error = "Please provide a short description!";
    } elseif (!isset($_FILES['manuscript_file']) || $_FILES['manuscript_file']['error'] == UPLOAD_ERR_NO_FILE) {
        $error = "Please upload a PDF file!";
    } else {
        $file = $_FILES['manuscript_file'];
        
        // Validate file type
        $allowed_types = ['application/pdf'];
        $file_type = $file['type'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_type, $allowed_types) || $file_ext !== 'pdf') {
            $error = "Only PDF files are allowed!";
        } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB max
            $error = "File size must be less than 10MB!";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/uploads/manuscripts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
            $unique_name = $user_id . '_' . time() . '_' . uniqid() . '.pdf';
            $file_path = $upload_dir . $unique_name;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Save to database
                $db_filepath = '/garden-of-words/uploads/manuscripts/' . $unique_name;
                $insert_query = "INSERT INTO manuscripts (user_id, title, description, filename, filepath, is_public, created_at) 
                                VALUES ($user_id, '$title', '$description', '" . mysqli_real_escape_string($conn, $file['name']) . "', '$db_filepath', $is_public, NOW())";
                
                if (mysqli_query($conn, $insert_query)) {
                    $success = "Manuscript uploaded successfully! 🎉";
                    // Clear form
                    $_POST = array();
                } else {
                    $error = "Failed to save manuscript to database: " . mysqli_error($conn);
                    // Delete uploaded file if database insert fails
                    unlink($file_path);
                }
            } else {
                $error = "Failed to upload file. Please try again!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Manuscript - Garden of Words 🌿</title>
    <link rel="stylesheet" href="includes/upload.css">
</head>
<body>
    <!-- Floating Leaves -->
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>
    <div class="leaf">🍃</div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">🌿 Garden of Words</div>
        <div class="nav-links">
            <a href="home.php">Discover</a>
            <a href="my-manuscripts.php">My Manuscripts</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="upload-card">
            <h1>📝 Upload Your Manuscript</h1>
            <p class="subtitle">Share your creative work with the community</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <br><br>
                    <a href="home.php" style="color: #1b5e20; text-decoration: underline;">View it on the home page</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                <!-- Title -->
                <div class="form-group">
                    <label for="title">Manuscript Title *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           placeholder="Enter your manuscript title"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                           required>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Short Description *</label>
                    <textarea id="description" 
                              name="description" 
                              placeholder="Write a brief description of your manuscript (what it's about, genre, etc.)"
                              required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <!-- File Upload -->
                <div class="form-group">
                    <label>Upload PDF File *</label>
                    <div class="file-upload-wrapper" id="fileUploadWrapper">
                        <div class="file-upload-icon">📄</div>
                        <p>Click or drag & drop your PDF file here</p>
                        <small>Maximum file size: 10MB</small>
                        <input type="file" 
                               id="manuscript_file" 
                               name="manuscript_file" 
                               accept=".pdf,application/pdf"
                               required>
                        <div class="file-name" id="fileName"></div>
                    </div>
                </div>

                <!-- Public/Private Toggle -->
                <div class="checkbox-group">
                    <input type="checkbox" 
                           id="is_public" 
                           name="is_public" 
                           checked>
                    <label for="is_public">
                        🌍 Make this manuscript public (others can view and read it)
                    </label>
                </div>

                <!-- Buttons -->
                <div class="btn-group">
                    <a href="home.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Upload Manuscript 📤</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // File upload preview
        const fileInput = document.getElementById('manuscript_file');
        const fileWrapper = document.getElementById('fileUploadWrapper');
        const fileName = document.getElementById('fileName');

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileName.textContent = '📄 ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                fileWrapper.classList.add('has-file');
            } else {
                fileName.textContent = '';
                fileWrapper.classList.remove('has-file');
            }
        });

        // Drag and drop
        fileWrapper.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#66bb6a';
            this.style.background = '#e8f5e3';
        });

        fileWrapper.addEventListener('dragleave', function(e) {
            e.preventDefault();
            if (!fileInput.files.length) {
                this.style.borderColor = '#a5d6a7';
                this.style.background = '#f8fdf8';
            }
        });

        fileWrapper.addEventListener('drop', function(e) {
            e.preventDefault();
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>