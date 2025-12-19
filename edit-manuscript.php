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

// Check if manuscript ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my-manuscripts.php");
    exit();
}

$manuscript_id = (int)$_GET['id'];

// Get manuscript details and verify ownership
$query = "SELECT * FROM manuscripts WHERE id = $manuscript_id AND user_id = $user_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: my-manuscripts.php");
    exit();
}

$manuscript = mysqli_fetch_assoc($result);

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
    } else {
        $cover_path = $manuscript['cover_image'];
        
        // Handle new cover image upload
        if (!empty($_FILES['cover_image']['name'])) {
            $cover_ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            $allowed_cover_ext = ['jpg', 'jpeg', 'png'];
            
            if (in_array($cover_ext, $allowed_cover_ext)) {
                // Delete old cover if exists
                if (!empty($manuscript['cover_image'])) {
                    $old_cover = $_SERVER['DOCUMENT_ROOT'] . $manuscript['cover_image'];
                    if (file_exists($old_cover)) {
                        unlink($old_cover);
                    }
                }
                
                // Upload new cover
                $cover_dir = $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/uploads/covers/';
                if (!file_exists($cover_dir)) mkdir($cover_dir, 0777, true);
                
                $cover_name = $user_id . '_' . time() . '_' . uniqid() . '.' . $cover_ext;
                $cover_path = '/garden-of-words/uploads/covers/' . $cover_name;
                
                move_uploaded_file($_FILES['cover_image']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $cover_path);
            } else {
                $error = "Cover image must be JPG or PNG!";
            }
        }
        
        // Handle cover removal
        if (isset($_POST['remove_cover']) && !empty($manuscript['cover_image'])) {
            $old_cover = $_SERVER['DOCUMENT_ROOT'] . $manuscript['cover_image'];
            if (file_exists($old_cover)) {
                unlink($old_cover);
            }
            $cover_path = null;
        }
        
        if (empty($error)) {
            // Update manuscript
            $update_query = "UPDATE manuscripts 
                            SET title = '$title', 
                                description = '$description', 
                                cover_image = " . ($cover_path ? "'$cover_path'" : "NULL") . ", 
                                is_public = $is_public,
                                updated_at = NOW()
                            WHERE id = $manuscript_id AND user_id = $user_id";
            
            if (mysqli_query($conn, $update_query)) {
                $success = "Manuscript updated successfully! 🎉";
                // Refresh manuscript data
                $result = mysqli_query($conn, $query);
                $manuscript = mysqli_fetch_assoc($result);
            } else {
                $error = "Failed to update manuscript: " . mysqli_error($conn);
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
    <title>Edit Manuscript - Garden of Words 🌿</title>
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
        <div class="logo"><img src="assets/garden.png" alt="Garden"> Garden of Words</div>
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
            <h1><img src="assets/edit.png" alt="Edit" class="icon"> Edit Manuscript</h1>
            <p class="subtitle">Update your manuscript details</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?><br><br>
                    <a href="my-manuscripts.php" style="color: #1b5e20; text-decoration: underline;">Back to My Manuscripts</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" id="editForm">
                <!-- Title -->
                <div class="form-group">
                    <label for="title">Manuscript Title *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           placeholder="Enter your manuscript title"
                           value="<?php echo htmlspecialchars($manuscript['title']); ?>"
                           required>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Short Description *</label>
                    <textarea id="description" 
                              name="description" 
                              placeholder="Write a brief description of your manuscript"
                              required><?php echo htmlspecialchars($manuscript['description']); ?></textarea>
                </div>

                <!-- Current Cover Display -->
                <?php if (!empty($manuscript['cover_image'])): ?>
                    <div class="form-group">
                        <label>Current Cover Image</label>
                        <div class="current-cover-wrapper">
                            <img src="<?php echo htmlspecialchars($manuscript['cover_image']); ?>" 
                                 alt="Current cover" 
                                 class="current-cover-image">
                            <div class="cover-actions">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="remove_cover" id="remove_cover">
                                    <span>Remove cover image</span>
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- New Cover Upload -->
                <div class="form-group">
                    <label><?php echo !empty($manuscript['cover_image']) ? 'Replace Cover Image (optional)' : 'Add Cover Image (optional)'; ?></label>
                    <div class="file-upload-wrapper">
                        <img src="assets/cover.png" alt="Cover" class="file-upload-icon">
                        <p><?php echo !empty($manuscript['cover_image']) ? 'Upload a new cover to replace the current one' : 'Upload a cover image for your manuscript'; ?></p>
                        <small>JPEG or PNG · Recommended ratio 2:3</small>
                        <input type="file" name="cover_image" accept="image/jpeg,image/png" id="cover_input">
                        <div class="file-name" id="coverFileName"></div>
                    </div>
                </div>

                <!-- PDF Info (Not Editable) -->
                <div class="form-group">
                    <label>PDF File</label>
                    <div class="pdf-info">
                        <img src="assets/pdf.png" alt="PDF" class="pdf-icon-text">
                        <div class="pdf-details">
                            <div class="pdf-name"><?php echo htmlspecialchars($manuscript['filename']); ?></div>
                            <div class="pdf-note">Note: PDF file cannot be changed. Upload a new manuscript if you need to update the PDF.</div>
                        </div>
                    </div>
                </div>

                <!-- Public/Private Toggle -->
                <div class="checkbox-group">
                    <input type="checkbox" 
                           id="is_public" 
                           name="is_public" 
                           <?php echo $manuscript['is_public'] ? 'checked' : ''; ?>>
                    <label for="is_public">
                        <img src="assets/public.png" alt="Public" class="icon"> Make this manuscript public (others can view and read it)
                    </label>
                </div>

                <!-- Buttons -->
                <div class="btn-group">
                    <a href="my-manuscripts.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Manuscript</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Cover image upload preview
        const coverInput = document.getElementById('cover_input');
        const coverFileName = document.getElementById('coverFileName');
        const removeCheckbox = document.getElementById('remove_cover');

        if (coverInput) {
            coverInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    coverFileName.textContent = 'Cover: ' + file.name;
                    
                    // Uncheck remove cover if uploading new one
                    if (removeCheckbox) {
                        removeCheckbox.checked = false;
                    }
                } else {
                    coverFileName.textContent = '';
                }
            });
        }

        // Disable file input if remove is checked
        if (removeCheckbox) {
            removeCheckbox.addEventListener('change', function() {
                if (this.checked && coverInput) {
                    coverInput.value = '';
                    coverFileName.textContent = '';
                }
            });
        }
    </script>

    <style>
        /* Additional styles for edit page */
        .current-cover-wrapper {
            background: #f8fdf8;
            padding: 20px;
            border-radius: 15px;
            border: 2px solid #e8f5e3;
        }

        .current-cover-image {
            max-width: 300px;
            width: 100%;
            height: auto;
            border-radius: 12px;
            display: block;
            margin: 0 auto 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .cover-actions {
            text-align: center;
        }

        .checkbox-inline {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 10px 20px;
            background: white;
            border-radius: 10px;
            border: 2px solid #ffcdd2;
            transition: all 0.3s;
        }

        .checkbox-inline:hover {
            background: #ffebee;
            border-color: #ef5350;
        }

        .checkbox-inline input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-inline span {
            color: #c62828;
            font-weight: 600;
        }

        .pdf-info {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #e8f5e3;
            padding: 20px;
            border-radius: 15px;
            border: 2px solid #c8e6c9;
        }

        .pdf-icon-text {
            font-size: 3em;
        }

        img.pdf-icon-text {
            width: 48px;
            height: 48px;
        }

        .pdf-details {
            flex-grow: 1;
        }

        .pdf-name {
            font-weight: 700;
            color: #2e7d32;
            font-size: 1.1em;
            margin-bottom: 8px;
        }

        .pdf-note {
            color: #66bb6a;
            font-size: 0.9em;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .current-cover-image {
                max-width: 100%;
            }

            .pdf-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</body>
</html>