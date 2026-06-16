<?php
require(__DIR__ . '/dbHandler.php');

// ── Pull lofts for the dropdown ──────────────────────────────────────────────
$lofts = $dbHandler->query("SELECT id, loft_name FROM loft ORDER BY loft_name")->fetchAll(PDO::FETCH_ASSOC);

// ── Colour options (shared by father & mother) ───────────────────────────────
$colors = [
    'Blue (Blauw)',
    'Ash-Red (Vaal)',
    'Black (Donker)',
    'Recessive Red (Rood)',
    'Light Check (Lichtkras)',
    'Dark Check (Donkerkras)',
];

// ── Helper: safely escape output ─────────────────────────────────────────────
function e($v) {
    if ($v === null) {
        $v = '';
    }
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

// ── Initialise field values & errors ─────────────────────────────────────────
$fields = [
    'father_ring'      => '',
    'father_name'      => '',
    'father_bloodline' => '',
    'father_color'     => '',
    'mother_ring'      => '',
    'mother_name'      => '',
    'mother_bloodline' => '',
    'mother_color'     => '',
    'paired_date'      => '',
    'loft_id'          => '',
    'notes'            => '',
];
$errors  = [];
$success = false;

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitise & collect every field
    foreach ($fields as $key => $_) {
        if (isset($_POST[$key])) {
            $fields[$key] = trim($_POST[$key]);
        } else {
            $fields[$key] = '';
        }
    }

    // ── Validation ────────────────────────────────────────────────────────────

    // Father ring number
    if ($fields['father_ring'] === '') {
        $errors['father_ring'] = 'Father ring number is required.';
    } elseif (!preg_match('/^[A-Z0-9\-\/\. ]{2,30}$/i', $fields['father_ring'])) {
        $errors['father_ring'] = 'Ring number may only contain letters, digits, spaces and - / .';
    }

    // Father colour
    if ($fields['father_color'] === '') {
        $errors['father_color'] = 'Please select a colour for the father.';
    }

    // Mother ring number
    if ($fields['mother_ring'] === '') {
        $errors['mother_ring'] = 'Mother ring number is required.';
    } elseif (!preg_match('/^[A-Z0-9\-\/\. ]{2,30}$/i', $fields['mother_ring'])) {
        $errors['mother_ring'] = 'Ring number may only contain letters, digits, spaces and - / .';
    }

    // Mother colour
    if ($fields['mother_color'] === '') {
        $errors['mother_color'] = 'Please select a colour for the mother.';
    }

    // Father and mother must be different pigeons
    if ($fields['father_ring'] !== '' && $fields['mother_ring'] !== '') {
        if (strtoupper($fields['father_ring']) === strtoupper($fields['mother_ring'])) {
            $errors['mother_ring'] = 'Father and mother cannot have the same ring number.';
        }
    }

    // Loft is required and must match a real DB row
    if ($fields['loft_id'] === '') {
        $errors['loft_id'] = 'Please select a loft.';
    } else {
        $loftValid = false;
        foreach ($lofts as $l) {
            if ($l['id'] == $fields['loft_id']) {
                $loftValid = true;
                break;
            }
        }
        if (!$loftValid) {
            $errors['loft_id'] = 'Invalid loft selected.';
        }
    }

    // Paired date is optional but must be valid and not in the future if supplied
    if ($fields['paired_date'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $fields['paired_date']);
        if (!$dt || $dt->format('Y-m-d') !== $fields['paired_date']) {
            $errors['paired_date'] = 'Invalid date format.';
        } elseif ($dt > new DateTime()) {
            $errors['paired_date'] = 'Paired date cannot be in the future.';
        }
    }

    // ── Save to DB if no validation errors ───────────────────────────────────
    if (empty($errors)) {
        try {
            $dbHandler->beginTransaction();

            // Upsert father (Cock) — insert or update if band_number already exists
            $upsert = $dbHandler->prepare("
                INSERT INTO pigeon (loft_id, band_number, name, bloodline, color, sex)
                     VALUES (:loft, :band, :name, :bloodline, :color, :sex)
                ON DUPLICATE KEY UPDATE
                     name      = IF(VALUES(name) <> '', VALUES(name), name),
                     bloodline = IF(VALUES(bloodline) <> '', VALUES(bloodline), bloodline),
                     color     = IF(VALUES(color) <> '', VALUES(color), color)
            ");

            $upsert->execute([
                ':loft'      => $fields['loft_id'],
                ':band'      => strtoupper($fields['father_ring']),
                ':name'      => $fields['father_name'],
                ':bloodline' => $fields['father_bloodline'],
                ':color'     => $fields['father_color'],
                ':sex'       => 'Cock',
            ]);

            $sireId = (int) $dbHandler->lastInsertId();

            // If lastInsertId is 0 the row already existed — look it up
            if ($sireId === 0) {
                $s = $dbHandler->prepare("SELECT id FROM pigeon WHERE band_number = ?");
                $s->execute([strtoupper($fields['father_ring'])]);
                $sireId = (int) $s->fetchColumn();
            }

            // Upsert mother (Hen)
            $upsert->execute([
                ':loft'      => $fields['loft_id'],
                ':band'      => strtoupper($fields['mother_ring']),
                ':name'      => $fields['mother_name'],
                ':bloodline' => $fields['mother_bloodline'],
                ':color'     => $fields['mother_color'],
                ':sex'       => 'Hen',
            ]);

            $damId = (int) $dbHandler->lastInsertId();

            if ($damId === 0) {
                $s = $dbHandler->prepare("SELECT id FROM pigeon WHERE band_number = ?");
                $s->execute([strtoupper($fields['mother_ring'])]);
                $damId = (int) $s->fetchColumn();
            }

            // Determine nullable values before the insert
            if ($fields['paired_date'] !== '') {
                $pairedDate = $fields['paired_date'];
            } else {
                $pairedDate = null;
            }

            if ($fields['notes'] !== '') {
                $notes = $fields['notes'];
            } else {
                $notes = null;
            }

            // Insert the breeding pair
            $stmt = $dbHandler->prepare("
                INSERT INTO breeding_pair (loft_id, sire_id, dam_id, pairing_date, notes)
                     VALUES (:loft, :sire, :dam, :date, :notes)
            ");
            $stmt->execute([
                ':loft'  => $fields['loft_id'],
                ':sire'  => $sireId,
                ':dam'   => $damId,
                ':date'  => $pairedDate,
                ':notes' => $notes,
            ]);

            $dbHandler->commit();
            $success = true;

            // Reset form fields after a successful save
            foreach ($fields as $key => $_) {
                $fields[$key] = '';
            }

        } catch (PDOException $ex) {
            $dbHandler->rollBack();

            if ($ex->getCode() === '23000') {
                $errors['general'] = 'This pair already exists in the selected loft.';
            } else {
                $errors['general'] = 'Database error: ' . e($ex->getMessage());
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
    <title>Add New Pair | Winnest</title>
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
            <a href="pair-management.html" class="menu-item active"><img src="../images/menu-icon/pair.png" alt="Pair Management"><span>Pair Management</span></a>
            <a href="nest-management.html" class="menu-item"><img src="../images/menu-icon/nest.png" alt="Nest Management"><span>Nest Management</span></a>
            <a href="youngster-profile.php" class="menu-item"><img src="../images/menu-icon/youngster.png" alt="Youngsters"><span>Youngsters</span></a>
            <a href="health-records.html" class="menu-item"><img src="../images/menu-icon/health.png" alt="Health Records"><span>Health Records</span></a>
            <a href="race-results.html" class="menu-item"><img src="../images/menu-icon/race.png" alt="Race Results"><span>Race Results</span></a>
            <a href="analytics-dashboard.html" class="menu-item"><img src="../images/menu-icon/analytics.png" alt="Analytics"><span>Analytics</span></a>
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

    <main class="content">
        <header>
            <h2>Add New Pair</h2>
        </header>

        <?php if ($success) { ?>
        <div class="alert-success">
            Pair saved successfully! <a href="pair-management.html">View all pairs</a> or add another below.
        </div>
        <?php } ?>

        <?php if (!empty($errors['general'])) { ?>
        <div class="alert-danger">
            <?php echo e($errors['general']); ?>
        </div>
        <?php } ?>

        <form method="POST" action="" novalidate>

            <section class="parents">

                <!-- Father -->
                <article class="card">
                    <h3>Father</h3>
                    <div class="card-content">
                        <img src="../images/pigeons/pigeon1.png" alt="Father pigeon" class="pigeon-image">
                        <div class="fields">

                            <label for="father-ring">Ring Number <span class="required">*</span></label>
                            <?php if (isset($errors['father_ring'])) { ?>
                            <input id="father-ring" name="father_ring" type="text"
                                   value="<?php echo e($fields['father_ring']); ?>"
                                   class="input-error"
                                   placeholder="e.g. NL-2024-1234567">
                            <span class="field-error"><?php echo e($errors['father_ring']); ?></span>
                            <?php } else { ?>
                            <input id="father-ring" name="father_ring" type="text"
                                   value="<?php echo e($fields['father_ring']); ?>"
                                   placeholder="e.g. NL-2024-1234567">
                            <?php } ?>

                            <label for="father-name">Name (Optional)</label>
                            <input id="father-name" name="father_name" type="text"
                                   value="<?php echo e($fields['father_name']); ?>"
                                   placeholder="e.g. Blauwe Crack">

                            <label for="father-bloodline">Bloodline</label>
                            <input id="father-bloodline" name="father_bloodline" type="text"
                                   value="<?php echo e($fields['father_bloodline']); ?>"
                                   placeholder="e.g. Koopman">

                            <label for="father-color">Color <span class="required">*</span></label>
                            <?php if (isset($errors['father_color'])) { ?>
                            <select id="father-color" name="father_color" class="input-error">
                            <?php } else { ?>
                            <select id="father-color" name="father_color">
                            <?php } ?>
                                <option value="">Select color</option>
                                <?php foreach ($colors as $c) { ?>
                                <?php if ($fields['father_color'] === $c) { ?>
                                <option value="<?php echo e($c); ?>" selected><?php echo e($c); ?></option>
                                <?php } else { ?>
                                <option value="<?php echo e($c); ?>"><?php echo e($c); ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>
                            <?php if (isset($errors['father_color'])) { ?>
                            <span class="field-error"><?php echo e($errors['father_color']); ?></span>
                            <?php } ?>

                        </div>
                    </div>
                </article>

                <!-- Mother -->
                <article class="card">
                    <h3>Mother</h3>
                    <div class="card-content">
                        <img src="../images/pigeons/pigeon2.png" alt="Mother pigeon" class="pigeon-image">
                        <div class="fields">

                            <label for="mother-ring">Ring Number <span class="required">*</span></label>
                            <?php if (isset($errors['mother_ring'])) { ?>
                            <input id="mother-ring" name="mother_ring" type="text"
                                   value="<?php echo e($fields['mother_ring']); ?>"
                                   class="input-error"
                                   placeholder="e.g. NL-2024-7654321">
                            <span class="field-error"><?php echo e($errors['mother_ring']); ?></span>
                            <?php } else { ?>
                            <input id="mother-ring" name="mother_ring" type="text"
                                   value="<?php echo e($fields['mother_ring']); ?>"
                                   placeholder="e.g. NL-2024-7654321">
                            <?php } ?>

                            <label for="mother-name">Name (Optional)</label>
                            <input id="mother-name" name="mother_name" type="text"
                                   value="<?php echo e($fields['mother_name']); ?>"
                                   placeholder="e.g. Blauwe Duivin">

                            <label for="mother-bloodline">Bloodline</label>
                            <input id="mother-bloodline" name="mother_bloodline" type="text"
                                   value="<?php echo e($fields['mother_bloodline']); ?>"
                                   placeholder="e.g. Koopman">

                            <label for="mother-color">Color <span class="required">*</span></label>
                            <?php if (isset($errors['mother_color'])) { ?>
                            <select id="mother-color" name="mother_color" class="input-error">
                            <?php } else { ?>
                            <select id="mother-color" name="mother_color">
                            <?php } ?>
                                <option value="">Select color</option>
                                <?php foreach ($colors as $c) { ?>
                                <?php if ($fields['mother_color'] === $c) { ?>
                                <option value="<?php echo e($c); ?>" selected><?php echo e($c); ?></option>
                                <?php } else { ?>
                                <option value="<?php echo e($c); ?>"><?php echo e($c); ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>
                            <?php if (isset($errors['mother_color'])) { ?>
                            <span class="field-error"><?php echo e($errors['mother_color']); ?></span>
                            <?php } ?>

                        </div>
                    </div>
                </article>

            </section>

            <section class="pair-info">
                <h3>Pair Information</h3>
                <div class="pair-row">

                    <div class="pair-field date-field">
                        <label for="paired-date">Paired Date</label>
                        <?php if (isset($errors['paired_date'])) { ?>
                        <input id="paired-date" name="paired_date" type="date"
                               value="<?php echo e($fields['paired_date']); ?>"
                               class="input-error"
                               max="<?php echo date('Y-m-d'); ?>">
                        <span class="field-error"><?php echo e($errors['paired_date']); ?></span>
                        <?php } else { ?>
                        <input id="paired-date" name="paired_date" type="date"
                               value="<?php echo e($fields['paired_date']); ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php } ?>
                    </div>

                    <div class="pair-field loft-field">
                        <label for="loft">Loft <span class="required">*</span></label>
                        <?php if (isset($errors['loft_id'])) { ?>
                        <select id="loft" name="loft_id" class="input-error">
                        <?php } else { ?>
                        <select id="loft" name="loft_id">
                        <?php } ?>
                            <option value="">Select Loft</option>
                            <?php foreach ($lofts as $l) { ?>
                            <?php if ($fields['loft_id'] == $l['id']) { ?>
                            <option value="<?php echo e($l['id']); ?>" selected><?php echo e($l['loft_name']); ?></option>
                            <?php } else { ?>
                            <option value="<?php echo e($l['id']); ?>"><?php echo e($l['loft_name']); ?></option>
                            <?php } ?>
                            <?php } ?>
                        </select>
                        <?php if (isset($errors['loft_id'])) { ?>
                        <span class="field-error"><?php echo e($errors['loft_id']); ?></span>
                        <?php } ?>
                    </div>

                    <div class="pair-field notes-field">
                        <label for="notes">Notes (Optional)</label>
                        <input id="notes" name="notes" type="text"
                               value="<?php echo e($fields['notes']); ?>"
                               placeholder="Any additional notes…">
                    </div>

                </div>
            </section>

            <div class="actions">
                <button type="button" class="cancel"><a href="../pair-management.html">Cancel</a></button>
                <button type="submit" class="save">
                    <img src="../images/dashboard-icon/add.png" alt="">
                    <span>Save Pair</span>
                </button>
            </div>

        </form>
    </main>
</div>
</body>
</html>