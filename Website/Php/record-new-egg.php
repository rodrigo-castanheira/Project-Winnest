<?php
require(__DIR__ . '/dbHandler.php');

// ── Helper: safely escape output ─────────────────────────────────────────────
function e($v) {
    if ($v === null) {
        $v = '';
    }
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

// ── Pull pairs for the dropdown ───────────────────────────────────────────────
// Show pair ID alongside the sire and dam band numbers so the user knows which pair is which
$pairs = $dbHandler->query("
    SELECT bp.id,
           CONCAT('PAIR-', LPAD(bp.id, 3, '0')) AS pair_label,
           sire.band_number AS sire_band,
           dam.band_number  AS dam_band
      FROM breeding_pair bp
      LEFT JOIN pigeon sire ON bp.sire_id = sire.id
      LEFT JOIN pigeon dam  ON bp.dam_id  = dam.id
     WHERE bp.is_active = 1
     ORDER BY bp.id
")->fetchAll(PDO::FETCH_ASSOC);

// ── Pull available nests for the dropdown ─────────────────────────────────────
$nests = $dbHandler->query("
    SELECT id, nest_number
      FROM nest
     WHERE status = 'available'
     ORDER BY nest_number
")->fetchAll(PDO::FETCH_ASSOC);

// ── Breeding rounds ───────────────────────────────────────────────────────────
$rounds = ['Round 1', 'Round 2', 'Round 3', 'Round 4', 'Round 5', 'Round 6', 'Round 7'];

// ── Initialise field values & errors ─────────────────────────────────────────
$fields = [
    'egg_number'     => '',
    'laid_date'      => '',
    'egg_note'       => '',
    'pair_id'        => '',
    'nest_id'        => '',
    'breeding_round' => '',
];
$errors  = [];
$success = false;

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect & sanitise every field
    foreach ($fields as $key => $_) {
        if (isset($_POST[$key])) {
            $fields[$key] = trim($_POST[$key]);
        } else {
            $fields[$key] = '';
        }
    }

    // ── Validation ────────────────────────────────────────────────────────────

    // Egg number
    if ($fields['egg_number'] === '') {
        $errors['egg_number'] = 'Please select an egg number.';
    }

    // Laid date
    if ($fields['laid_date'] === '') {
        $errors['laid_date'] = 'Laid date is required.';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $fields['laid_date']);
        if (!$dt || $dt->format('Y-m-d') !== $fields['laid_date']) {
            $errors['laid_date'] = 'Invalid date format.';
        } elseif ($dt > new DateTime()) {
            $errors['laid_date'] = 'Laid date cannot be in the future.';
        }
    }

    // Pair ID
    if ($fields['pair_id'] === '') {
        $errors['pair_id'] = 'Please select a pair.';
    } else {
        $pairValid = false;
        foreach ($pairs as $p) {
            if ($p['id'] == $fields['pair_id']) {
                $pairValid = true;
                break;
            }
        }
        if (!$pairValid) {
            $errors['pair_id'] = 'Invalid pair selected.';
        }
    }

    // Nest ID
    if ($fields['nest_id'] === '') {
        $errors['nest_id'] = 'Please select a nest.';
    } else {
        $nestValid = false;
        foreach ($nests as $n) {
            if ($n['id'] == $fields['nest_id']) {
                $nestValid = true;
                break;
            }
        }
        if (!$nestValid) {
            $errors['nest_id'] = 'Invalid nest selected.';
        }
    }

    // Breeding round
    if ($fields['breeding_round'] === '') {
        $errors['breeding_round'] = 'Please select a breeding round.';
    } else {
        if (!in_array($fields['breeding_round'], $rounds)) {
            $errors['breeding_round'] = 'Invalid breeding round selected.';
        }
    }

    // ── Save to DB if no validation errors ────────────────────────────────────
    if (empty($errors)) {
        try {
            $dbHandler->beginTransaction();

            // Check if a breeding_record already exists for this pair + nest + round.
            // If so, reuse it; otherwise create a new one.
            $stmtCheck = $dbHandler->prepare("
                SELECT id FROM breeding_record
                 WHERE pair_id        = :pair
                   AND nest_id        = :nest
                   AND breeding_round = :round
                 LIMIT 1
            ");
            $stmtCheck->execute([
                ':pair'  => $fields['pair_id'],
                ':nest'  => $fields['nest_id'],
                ':round' => $fields['breeding_round'],
            ]);
            $existingRecord = $stmtCheck->fetchColumn();

            if ($existingRecord) {
                $breedingRecordId = (int) $existingRecord;
            } else {
                $stmtRecord = $dbHandler->prepare("
                    INSERT INTO breeding_record (pair_id, nest_id, breeding_round, start_date)
                         VALUES (:pair, :nest, :round, :start)
                ");
                $stmtRecord->execute([
                    ':pair'  => $fields['pair_id'],
                    ':nest'  => $fields['nest_id'],
                    ':round' => $fields['breeding_round'],
                    ':start' => $fields['laid_date'],
                ]);
                $breedingRecordId = (int) $dbHandler->lastInsertId();
            }

            // Work out expected hatch date (pigeon eggs hatch after 17-19 days; we use 18)
            $layDt = DateTime::createFromFormat('Y-m-d', $fields['laid_date']);
            $layDt->modify('+18 days');
            $expectedHatchDate = $layDt->format('Y-m-d');

            // Determine nullable note value
            if ($fields['egg_note'] !== '') {
                $eggNote = $fields['egg_note'];
            } else {
                $eggNote = null;
            }

            // Insert the egg
            $stmtEgg = $dbHandler->prepare("
                INSERT INTO egg (breeding_record_id, egg_number, lay_date, expected_hatch_date, notes)
                     VALUES (:record, :egg_number, :lay_date, :expected_hatch, :notes)
            ");
            $stmtEgg->execute([
                ':record'        => $breedingRecordId,
                ':egg_number'    => $fields['egg_number'],
                ':lay_date'      => $fields['laid_date'],
                ':expected_hatch'=> $expectedHatchDate,
                ':notes'         => $eggNote,
            ]);

            $dbHandler->commit();
            $success = true;

            // Reset form fields after a successful save
            foreach ($fields as $key => $_) {
                $fields[$key] = '';
            }

        } catch (PDOException $ex) {
            $dbHandler->rollBack();
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
    <title>Record New Egg | Winnest</title>
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
                <a href="pair-management.html" class="menu-item"><img src="../images/menu-icon/pair.png" alt="Pair Management"><span>Pair Management</span></a>
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
                <h1>Record New Egg</h1>
            </header>

            <?php if ($success) { ?>
            <div class="alert-success">
                Egg recorded successfully! <a href="dashboard.php">Back to dashboard</a> or record another below.
            </div>
            <?php } ?>

            <?php if (!empty($errors['general'])) { ?>
            <div class="alert-danger">
                <?php echo e($errors['general']); ?>
            </div>
            <?php } ?>

            <form action="record-new-egg.php" method="POST" novalidate>
                <section class="egg-grid">

                    <article class="card egg-card">
                        <h3>Egg Information</h3>

                        <div class="egg-row">
                            <div class="egg-field">
                                <label for="egg-number">Egg Number <span class="required">*</span></label>
                                <?php if (isset($errors['egg_number'])) { ?>
                                <select id="egg-number" name="egg_number" class="input-error">
                                <?php } else { ?>
                                <select id="egg-number" name="egg_number">
                                <?php } ?>
                                    <option value="" disabled>Select Egg</option>
                                    <?php if ($fields['egg_number'] === '1st Egg') { ?>
                                    <option value="1st Egg" selected>1st Egg</option>
                                    <?php } else { ?>
                                    <option value="1st Egg">1st Egg</option>
                                    <?php } ?>
                                    <?php if ($fields['egg_number'] === '2nd Egg') { ?>
                                    <option value="2nd Egg" selected>2nd Egg</option>
                                    <?php } else { ?>
                                    <option value="2nd Egg">2nd Egg</option>
                                    <?php } ?>
                                </select>
                                <?php if (isset($errors['egg_number'])) { ?>
                                <span class="field-error"><?php echo e($errors['egg_number']); ?></span>
                                <?php } ?>
                            </div>

                            <div class="egg-field">
                                <label for="laid-date">Laid Date <span class="required">*</span></label>
                                <?php if (isset($errors['laid_date'])) { ?>
                                <input id="laid-date" name="laid_date" type="date"
                                       value="<?php echo e($fields['laid_date']); ?>"
                                       class="input-error"
                                       max="<?php echo date('Y-m-d'); ?>">
                                <span class="field-error"><?php echo e($errors['laid_date']); ?></span>
                                <?php } else { ?>
                                <input id="laid-date" name="laid_date" type="date"
                                       value="<?php echo e($fields['laid_date']); ?>"
                                       max="<?php echo date('Y-m-d'); ?>">
                                <?php } ?>
                            </div>
                        </div>

                        <div class="egg-note">
                            <label for="egg-note">Note (Optional)</label>
                            <input id="egg-note" name="egg_note" type="text"
                                   value="<?php echo e($fields['egg_note']); ?>"
                                   placeholder="Add notes about this egg">
                        </div>
                    </article>

                    <article class="card egg-parents-card">
                        <h3>Parents Information</h3>

                        <div class="egg-row">
                            <div class="egg-field">
                                <label for="pair-id">Pair <span class="required">*</span></label>
                                <?php if (isset($errors['pair_id'])) { ?>
                                <select id="pair-id" name="pair_id" class="input-error">
                                <?php } else { ?>
                                <select id="pair-id" name="pair_id">
                                <?php } ?>
                                    <option value="">Select Pair</option>
                                    <?php if (empty($pairs)) { ?>
                                    <option value="" disabled>No active pairs available</option>
                                    <?php } else { ?>
                                    <?php foreach ($pairs as $p) { ?>
                                    <?php if ($fields['pair_id'] == $p['id']) { ?>
                                    <option value="<?php echo e($p['id']); ?>" selected>
                                        PAIR-<?php echo str_pad($p['id'], 3, '0', STR_PAD_LEFT); ?>
                                        (<?php echo e($p['sire_band']); ?> × <?php echo e($p['dam_band']); ?>)
                                    </option>
                                    <?php } else { ?>
                                    <option value="<?php echo e($p['id']); ?>">
                                        PAIR-<?php echo str_pad($p['id'], 3, '0', STR_PAD_LEFT); ?>
                                        (<?php echo e($p['sire_band']); ?> × <?php echo e($p['dam_band']); ?>)
                                    </option>
                                    <?php } ?>
                                    <?php } ?>
                                    <?php } ?>
                                </select>
                                <?php if (isset($errors['pair_id'])) { ?>
                                <span class="field-error"><?php echo e($errors['pair_id']); ?></span>
                                <?php } ?>
                            </div>

                            <div class="egg-field">
                                <label for="nest-id">Nest <span class="required">*</span></label>
                                <?php if (isset($errors['nest_id'])) { ?>
                                <select id="nest-id" name="nest_id" class="input-error">
                                <?php } else { ?>
                                <select id="nest-id" name="nest_id">
                                <?php } ?>
                                    <option value="">Select Nest</option>
                                    <?php if (empty($nests)) { ?>
                                    <option value="" disabled>No available nests</option>
                                    <?php } else { ?>
                                    <?php foreach ($nests as $n) { ?>
                                    <?php if ($fields['nest_id'] == $n['id']) { ?>
                                    <option value="<?php echo e($n['id']); ?>" selected><?php echo e($n['nest_number']); ?></option>
                                    <?php } else { ?>
                                    <option value="<?php echo e($n['id']); ?>"><?php echo e($n['nest_number']); ?></option>
                                    <?php } ?>
                                    <?php } ?>
                                    <?php } ?>
                                </select>
                                <?php if (isset($errors['nest_id'])) { ?>
                                <span class="field-error"><?php echo e($errors['nest_id']); ?></span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="egg-round-field">
                            <label for="breeding-round">Breeding Round <span class="required">*</span></label>
                            <?php if (isset($errors['breeding_round'])) { ?>
                            <select id="breeding-round" name="breeding_round" class="input-error">
                            <?php } else { ?>
                            <select id="breeding-round" name="breeding_round">
                            <?php } ?>
                                <option value="">Select Round</option>
                                <?php foreach ($rounds as $r) { ?>
                                <?php if ($fields['breeding_round'] === $r) { ?>
                                <option value="<?php echo e($r); ?>" selected><?php echo e($r); ?></option>
                                <?php } else { ?>
                                <option value="<?php echo e($r); ?>"><?php echo e($r); ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>
                            <?php if (isset($errors['breeding_round'])) { ?>
                            <span class="field-error"><?php echo e($errors['breeding_round']); ?></span>
                            <?php } ?>
                        </div>
                    </article>

                </section>

                <div class="actions egg-actions">
                    <button type="button" class="cancel"><a href="dashboard.php">Cancel</a></button>
                    <button type="submit" class="save">
                        <img src="../images/dashboard-icon/add.png" alt="">
                        <span>Save</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>