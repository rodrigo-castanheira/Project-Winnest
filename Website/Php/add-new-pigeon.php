<?php
require __DIR__ . '/dbHandler.php';

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function cleanField($key) {
    $value = trim($_POST[$key] ?? '');

    if ($value === '' || stripos($value, 'Select ') === 0) {
        return null;
    }

    return $value;
}

function selectedOption($currentValue, $optionValue) {
    return $currentValue === $optionValue ? 'selected' : '';
}

$loftId = 1;

// FIXED: Updated to match your new database ENUM values ('Male' and 'Female')
$genderMap = [
    'Male' => 'Male',
    'Female' => 'Female',
];

$bloodlines = ['Koopman', 'Janssen', 'Heremans'];

$fields = [
    'ring_number' => '',
    'gender' => '',
    'color' => '',
    'bloodline' => '',
    'note' => '',
    'father' => '',
    'mother' => '',
];

$errors = [];
$success = isset($_GET['saved']) && $_GET['saved'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $_) {
        $fields[$key] = cleanField($key) ?? '';
    }

    $bandNumber = strtoupper($fields['ring_number']);
    $sex = null;
    $photoUrl = null;

    if ($bandNumber === '') {
        $errors['ring_number'] = 'Ring number is required.';
    } elseif (!preg_match('/^[A-Z0-9\-\/\. ]{2,100}$/i', $bandNumber)) {
        $errors['ring_number'] = 'Ring number may only contain letters, digits, spaces and - / .';
    }

    if ($fields['gender'] === '') {
        $errors['gender'] = 'Please select a gender.';
    } elseif (!isset($genderMap[$fields['gender']])) {
        $errors['gender'] = 'Invalid gender selected.';
    } else {
        $sex = $genderMap[$fields['gender']];
    }

    if (empty(trim($fields['color']))) {
        $errors['color'] = 'Color is required.';
    }

    if ($fields['bloodline'] !== '' && !in_array($fields['bloodline'], $bloodlines, true)) {
        $errors['bloodline'] = 'Invalid bloodline selected.';
    }

    if ($fields['note'] !== '') {
        $fields['note'] = strip_tags($fields['note']);
        if (strlen($fields['note']) > 1000) {
            $errors['note'] = 'Note is too long. Maximum 1000 characters.';
        }
    }

    if ($fields['father'] !== '') {
        $fields['father'] = strip_tags($fields['father']);
        if (strlen($fields['father']) > 100) {
            $errors['father'] = 'Father field is too long. Maximum 100 characters.';
        }
    }

    if ($fields['mother'] !== '') {
        $fields['mother'] = strip_tags($fields['mother']);
        if (strlen($fields['mother']) > 100) {
            $errors['mother'] = 'Mother field is too long. Maximum 100 characters.';
        }
    }

    if (empty($errors)) {
        $loftCheck = $dbHandler->prepare('SELECT COUNT(*) FROM loft WHERE id = :loft_id');
        $loftCheck->execute([':loft_id' => $loftId]);

        if ((int) $loftCheck->fetchColumn() === 0) {
            $errors['general'] = 'Loft with ID 1 does not exist. Import Database/winnest_demo_seed.sql first or create a loft in the database.';
        }
    }

    if (empty($errors)) {
        $duplicateCheck = $dbHandler->prepare(
            'SELECT COUNT(*) FROM pigeon WHERE loft_id = :loft_id AND band_number = :band_number'
        );
        $duplicateCheck->execute([
            ':loft_id' => $loftId,
            ':band_number' => $bandNumber,
        ]);

        if ((int) $duplicateCheck->fetchColumn() > 0) {
            $errors['ring_number'] = 'A pigeon with this ring number already exists in this loft.';
        }
    }

    if (empty($errors) && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errors['photo'] = 'Photo upload failed. Please try again.';
        } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $errors['photo'] = 'Photo is too large. Maximum size is 5 MB.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['photo']['tmp_name']);
            finfo_close($finfo);

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ];

            if (!isset($extensions[$mimeType])) {
                $errors['photo'] = 'Only JPEG and PNG images are allowed.';
            } else {
                $uploadDir = __DIR__ . '/uploads/pigeons';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $safeBandNumber = preg_replace('/[^A-Z0-9]+/i', '-', $bandNumber);
                $fileName = strtolower($safeBandNumber) . '-' . uniqid('', true) . '.' . $extensions[$mimeType];
                $targetPath = $uploadDir . '/' . $fileName;

                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $errors['photo'] = 'Could not save the uploaded photo.';
                } else {
                    $photoUrl = 'uploads/pigeons/' . $fileName;
                }
            }
        }
    }

    if (empty($errors)) {
        $noteParts = [];

        if ($fields['father'] !== '') {
            $noteParts[] = 'Father: ' . $fields['father'];
        }

        if ($fields['mother'] !== '') {
            $noteParts[] = 'Mother: ' . $fields['mother'];
        }

        if ($fields['note'] !== '') {
            $noteParts[] = 'Note: ' . $fields['note'];
        }

        $notesForDatabase = !empty($noteParts) ? implode(PHP_EOL, $noteParts) : null;

        try {
            // FIXED: Hardcoded '0' directly into the VALUES string for is_youngster
            $stmt = $dbHandler->prepare(
                'INSERT INTO pigeon
                    (loft_id, band_number, sex, bloodline, color, status, is_youngster, photo_url, notes)
                 VALUES
                    (:loft_id, :band_number, :sex, :bloodline, :color, :status, 0, :photo_url, :notes)'
            );

            $stmt->execute([
                ':loft_id' => $loftId,
                ':band_number' => $bandNumber,
                ':sex' => $sex,
                ':bloodline' => $fields['bloodline'] !== '' ? $fields['bloodline'] : null,
                ':color' => $fields['color'] !== '' ? $fields['color'] : null,
                ':status' => 'Active',
                ':photo_url' => $photoUrl,
                ':notes' => $notesForDatabase,
            ]);

            header('Location: add-new-breeding-pigeon.php?saved=1');
            exit;
        } catch (PDOException $ex) {
            $errors['general'] = 'Database error: ' . e($ex->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Breeding Pigeon | Winnest</title>
    <link rel="stylesheet" href="../winnest-style.css">
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo">
                <img src="../images/winnest-logo.png" alt="Winnest logo">
            </div>
            <nav class="menu">
                <a href="dashboard.php" class="menu-item"><img src="../images/menu-icon/dashboard.png" alt="Dashboard"><span>Dashboard</span></a>
                <a href="../pair-management.html" class="menu-item active"><img src="../images/menu-icon/pair.png" alt="Pair Management"><span>Pair Management</span></a>
                <a href="../nest-management.html" class="menu-item"><img src="../images/menu-icon/nest.png" alt="Nest Management"><span>Nest Management</span></a>
                <a href="youngster-profile.php" class="menu-item"><img src="../images/menu-icon/youngster.png" alt="Youngsters"><span>Youngsters</span></a>
                <a href="../health-records.html" class="menu-item"><img src="../images/menu-icon/health.png" alt="Health Records"><span>Health Records</span></a>
                <a href="../race-results.html" class="menu-item"><img src="../images/menu-icon/race.png" alt="Race Results"><span>Race Results</span></a>
                <a href="../analytics-dashboard.html" class="menu-item"><img src="../images/menu-icon/analytics.png" alt="Analytics"><span>Analytics</span></a>
                <a href="#" class="menu-item"><img src="../images/menu-icon/report.png" alt="Reports"><span>Reports</span></a>
                <a href="#" class="menu-item"><img src="../images/menu-icon/calendar.png" alt="Calendar"><span>Calendar</span></a>
                <a href="#" class="menu-item"><img src="../images/menu-icon/setting.png" alt="Loft Settings"><span>Loft Settings</span></a>
                <a href="#" class="menu-item"><img src="../images/menu-icon/users.png" alt="Users & Staff"><span>Users & Staff</span></a>
            </nav>
            <div class="loft-card">
                <img src="../images/pigeons/koopman-loft.png" alt="Winnest loft">
                <h3>WINNEST LOFT 🇳🇱</h3>
                <p>Ermerveen 17</p>
                <p>7814 VB Emmen</p>
                <p>The Netherlands</p>
            </div>
        </aside>

        <main class="content add-breeding-pigeon-content">
            <header class="add-breeding-pigeon-header">
                <h1>Add New Pigeon</h1>
            </header>

            <?php if ($success) { ?>
            <div class="alert-success breeding-page-message">
                Breeding pigeon saved successfully.
            </div>
            <?php } ?>

            <?php if (!empty($errors['general'])) { ?>
            <div class="alert-danger breeding-page-message">
                <?php echo e($errors['general']); ?>
            </div>
            <?php } ?>

            <form class="add-breeding-pigeon-form" method="POST" action="" enctype="multipart/form-data" novalidate>
                <section class="breeding-pigeon-grid">
                    <article class="breeding-pigeon-card pigeon-info-card">
                        <h3>Pigeon Information</h3>

                        <div class="breeding-field full-field">
                            <label for="ring-number">Ring Number <span class="required">*</span></label>
                            <input id="ring-number" name="ring_number" type="text" value="<?php echo e($fields['ring_number']); ?>" class="<?php echo isset($errors['ring_number']) ? 'input-error' : ''; ?>" placeholder="e.g. NL-2024-1234567">
                            <?php if (isset($errors['ring_number'])) { ?>
                            <span class="field-error"><?php echo e($errors['ring_number']); ?></span>
                            <?php } ?>
                        </div>

                        <div class="breeding-two-columns">
                            <div class="breeding-field">
                                <label for="gender">Gender <span class="required">*</span></label>
                                <select id="gender" name="gender" class="<?php echo isset($errors['gender']) ? 'input-error' : ''; ?>">
                                    <option value="">Select gender</option>
                                    <option value="Male" <?php echo selectedOption($fields['gender'], 'Male'); ?>>Male</option>
                                    <option value="Female" <?php echo selectedOption($fields['gender'], 'Female'); ?>>Female</option>
                                </select>
                                <?php if (isset($errors['gender'])) { ?>
                                <span class="field-error"><?php echo e($errors['gender']); ?></span>
                                <?php } ?>
                            </div>

                           <div class="breeding-field">
                                <label for="color">Color</label>
                                <input 
                                    type="text" 
                                    id="color" 
                                    name="color" 
                                    class="<?php echo isset($errors['color']) ? 'input-error' : ''; ?>" 
                                    value="<?php echo e($fields['color'] ?? ''); ?>" 
                                    placeholder="Enter pigeon color">
                                
                                <?php if (isset($errors['color'])) { ?>
                                    <span class="field-error"><?php echo e($errors['color']); ?></span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="breeding-field bloodline-field">
                            <label for="bloodline">Blood Line</label>
                            <select id="bloodline" name="bloodline" class="<?php echo isset($errors['bloodline']) ? 'input-error' : ''; ?>">
                                <option value="">Select bloodline</option>
                                <?php foreach ($bloodlines as $bloodline) { ?>
                                <option value="<?php echo e($bloodline); ?>" <?php echo selectedOption($fields['bloodline'], $bloodline); ?>><?php echo e($bloodline); ?></option>
                                <?php } ?>
                            </select>
                            <?php if (isset($errors['bloodline'])) { ?>
                            <span class="field-error"><?php echo e($errors['bloodline']); ?></span>
                            <?php } ?>
                        </div>

                        <div class="breeding-field note-field-breeding">
                            <label for="note">Note (Optional)</label>
                            <input id="note" name="note" type="text" value="<?php echo e($fields['note']); ?>" class="<?php echo isset($errors['note']) ? 'input-error' : ''; ?>" placeholder="Add notes about this breeding pigeon">
                            <?php if (isset($errors['note'])) { ?>
                            <span class="field-error"><?php echo e($errors['note']); ?></span>
                            <?php } ?>
                        </div>
                    </article>

                    <div class="breeding-right-column">
                        <article class="breeding-pigeon-card parents-information-card">
                            <h3>Parents Information (Optional)</h3>

                            <div class="parent-information-grid">
                                <div class="breeding-field">
                                    <label for="father">Father</label>
                                    <input id="father" name="father" type="text" value="<?php echo e($fields['father']); ?>" class="<?php echo isset($errors['father']) ? 'input-error' : ''; ?>" placeholder="Father ring/name">
                                    <?php if (isset($errors['father'])) { ?>
                                    <span class="field-error"><?php echo e($errors['father']); ?></span>
                                    <?php } ?>
                                </div>

                                <div class="breeding-field">
                                    <label for="mother">Mother</label>
                                    <input id="mother" name="mother" type="text" value="<?php echo e($fields['mother']); ?>" class="<?php echo isset($errors['mother']) ? 'input-error' : ''; ?>" placeholder="Mother ring/name">
                                    <?php if (isset($errors['mother'])) { ?>
                                    <span class="field-error"><?php echo e($errors['mother']); ?></span>
                                    <?php } ?>
                                </div>
                            </div>
                        </article>

                        <article class="breeding-pigeon-card breeding-photo-card">
                            <h3>Photo (Optional)</h3>

                            <div class="breeding-upload-row">
                                <input id="photo" name="photo" class="photo-file-input <?php echo isset($errors['photo']) ? 'input-error' : ''; ?>" type="file" accept="image/jpeg,image/png">
                                <button type="button" onclick="document.getElementById('photo').click();">Upload</button>
                            </div>
                            <?php if (isset($errors['photo'])) { ?>
                            <span class="field-error"><?php echo e($errors['photo']); ?></span>
                            <?php } ?>

                            <p>* JPEG, PNG up to 5 MB</p>
                        </article>
                    </div>
                </section>

                <div class="breeding-pigeon-actions">
                    <button type="button" class="breeding-cancel" onclick="window.location.href='../pair-management.html';">Cancel</button>
                    <button type="submit" class="breeding-save">
                        <img src="../images/dashboard-icon/add.png" alt="">
                        <span>Save</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>

</html>