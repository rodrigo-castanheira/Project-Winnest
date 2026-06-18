<?php

    require(__DIR__.'/dbHandler.php');
 
    // 1. Fetch Lofts
    $lofts = $dbHandler->query("SELECT id, loft_name FROM loft ORDER BY loft_name")->fetchAll(PDO::FETCH_ASSOC);
 
    // 2. Fetch all registered Cocks (Fathers)
    $fathers = $dbHandler->query("SELECT id, band_number, name FROM pigeon WHERE sex = 'Male' ORDER BY band_number")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch all registered Hens (Mothers)
    $mothers = $dbHandler->query("SELECT id, band_number, name FROM pigeon WHERE sex = 'Female' ORDER BY band_number")->fetchAll(PDO::FETCH_ASSOC);

    function e($v){
        if($v === null){
            $v = '';
        }
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
 
    // Using IDs instead of raw strings for the parent selections
    $fields = [
        'fatherId'   => '',
        'motherId'   => '',
        'pairedDate' => '',
        'loftId'     => '',
        'notes'      => '',
    ];
    $errors = [];
    $success = false;
 
    if($_SERVER["REQUEST_METHOD"] == "POST"){
 
        foreach($fields as $key => $value){
            if(isset($_POST[$key])){
                $fields[$key] = trim($_POST[$key]);
            }
            else{
                $fields[$key] = '';
            }
        }
 
        // --- Father Validation ---
        if($fields['fatherId'] === ''){
            $errors['fatherId'] = 'Please select a father.';
        }
 
        // --- Mother Validation ---
        if($fields['motherId'] === ''){
            $errors['motherId'] = 'Please select a mother.';
        }
 
        // --- Loft Validation ---
        if($fields['loftId'] === ''){
            $errors['loftId'] = 'Please select a loft.';
        }
        else{
            $loftValid = false;
            foreach($lofts as $l){
                if($l['id'] == $fields['loftId']){
                    $loftValid = true;
                    break;
                }
            }
            if(!$loftValid){
                $errors['loftId'] = 'Invalid loft selected.';
            }
        }
 
        // --- Date Validation ---
        if($fields['pairedDate'] !== ''){
            $dt = DateTime::createFromFormat('Y-m-d', $fields['pairedDate']);
            if(!$dt || $dt->format('Y-m-d') !== $fields['pairedDate']){
                $errors['pairedDate'] = 'Invalid date format.';
            }
            elseif($dt > new DateTime()){
                $errors['pairedDate'] = 'Paired date cannot be in the future.';
            }
        }
 
        // --- Insert Pair into Database ---
        if(empty($errors)){
            try{
                $dbHandler->beginTransaction();
 
                $pairedDate = ($fields['pairedDate'] !== '') ? $fields['pairedDate'] : null;
                $notes = ($fields['notes'] !== '') ? $fields['notes'] : null;
 
                $stmt = $dbHandler->prepare("
                    INSERT INTO breeding_pair (loft_id, sire_id, dam_id, pairing_date, notes)
                         VALUES (:loft, :sire, :dam, :date, :notes)
                ");
                $stmt->execute([
                    ':loft'  => $fields['loftId'],
                    ':sire'  => $fields['fatherId'], // Directly using the selected ID
                    ':dam'   => $fields['motherId'], // Directly using the selected ID
                    ':date'  => $pairedDate,
                    ':notes' => $notes,
                ]);
 
                $dbHandler->commit();
                $success = true;
 
                // Clear form inputs on success
                foreach($fields as $key => $value){
                    $fields[$key] = '';
                }
            }
            catch(PDOException $ex){
                $dbHandler->rollBack();
 
                if($ex->getCode() === '23000'){
                    $errors['general'] = 'This breeding pair already exists in the selected loft.';
                }
                else{
                    $errors['general'] = 'Database error: '.e($ex->getMessage());
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
            <a href="../pair-management.html" class="menu-item active"><img src="../images/menu-icon/pair.png" alt="Pair Management"><span>Pair Management</span></a>
            <a href="../nest-management.html" class="menu-item"><img src="../images/menu-icon/nest.png" alt="Nest Management"><span>Nest Management</span></a>
            <a href="youngster-profile.php" class="menu-item"><img src="../images/menu-icon/youngster.png" alt="Youngsters"><span>Youngsters</span></a>
            <a href="../health-records.html" class="menu-item"><img src="../images/menu-icon/health.png" alt="Health Records"><span>Health Records</span></a>
            <a href="../race-results.html" class="menu-item"><img src="../images/menu-icon/race.png" alt="Race Results"><span>Race Results</span></a>
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

                <article class="card">
                    <h3>Father</h3>
                    <div class="card-content">
                        <img src="../images/pigeons/pigeon1.png" alt="Father pigeon" class="pigeon-image">
                        <div class="fields">
                            <label for="fatherId">Select Male <span class="required">*</span></label>
                            <select id="fatherId" name="fatherId" class="<?php echo isset($errors['fatherId']) ? 'input-error' : ''; ?>">
                                <option value="">-- Choose a Male --</option>
                                <?php foreach ($fathers as $f) { 
                                    $displayName = e($f['band_number']);
                                    if (!empty($f['name'])) {
                                        $displayName .= ' (' . e($f['name']) . ')';
                                    }
                                ?>
                                    <option value="<?php echo e($f['id']); ?>" <?php echo ($fields['fatherId'] == $f['id']) ? 'selected' : ''; ?>>
                                        <?php echo $displayName; ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <?php if (isset($errors['fatherId'])) { ?>
                            <span class="field-error"><?php echo e($errors['fatherId']); ?></span>
                            <?php } ?>
                        </div>
                    </div>
                </article>

                <article class="card">
                    <h3>Mother</h3>
                    <div class="card-content">
                        <img src="../images/pigeons/pigeon2.png" alt="Mother pigeon" class="pigeon-image">
                        <div class="fields">
                            <label for="motherId">Select Female <span class="required">*</span></label>
                            <select id="motherId" name="motherId" class="<?php echo isset($errors['motherId']) ? 'input-error' : ''; ?>">
                                <option value="">-- Choose a Female --</option>
                                <?php foreach ($mothers as $m) { 
                                    $displayName = e($m['band_number']);
                                    if (!empty($m['name'])) {
                                        $displayName .= ' (' . e($m['name']) . ')';
                                    }
                                ?>
                                    <option value="<?php echo e($m['id']); ?>" <?php echo ($fields['motherId'] == $m['id']) ? 'selected' : ''; ?>>
                                        <?php echo $displayName; ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <?php if (isset($errors['motherId'])) { ?>
                            <span class="field-error"><?php echo e($errors['motherId']); ?></span>
                            <?php } ?>
                        </div>
                    </div>
                </article>

            </section>

            <section class="pair-info">
                <h3>Pair Information</h3>
                <div class="pair-row">

                    <div class="pair-field date-field">
                        <label for="pairedDate">Paired Date</label>
                        <input id="pairedDate" name="pairedDate" type="date"
                               value="<?php echo e($fields['pairedDate']); ?>"
                               class="<?php echo isset($errors['pairedDate']) ? 'input-error' : ''; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['pairedDate'])) { ?>
                        <span class="field-error"><?php echo e($errors['pairedDate']); ?></span>
                        <?php } ?>
                    </div>

                    <div class="pair-field loft-field">
                        <label for="loftId">Loft <span class="required">*</span></label>
                        <select id="loftId" name="loftId" class="<?php echo isset($errors['loftId']) ? 'input-error' : ''; ?>">
                            <option value="">Select Loft</option>
                            <?php foreach ($lofts as $l) { ?>
                            <option value="<?php echo e($l['id']); ?>" <?php echo ($fields['loftId'] == $l['id']) ? 'selected' : ''; ?>><?php echo e($l['loft_name']); ?></option>
                            <?php } ?>
                        </select>
                        <?php if (isset($errors['loftId'])) { ?>
                        <span class="field-error"><?php echo e($errors['loftId']); ?></span>
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