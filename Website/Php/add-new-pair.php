<?php

    require(__DIR__.'/dbHandler.php');
 
	$lofts = $dbHandler->query("SELECT id, loft_name FROM loft ORDER BY loft_name")->fetchAll(PDO::FETCH_ASSOC);
 
	$colors = [
		'Blue (Blauw)',
		'Ash-Red (Vaal)',
		'Black (Donker)',
		'Recessive Red (Rood)',
		'Light Check (Lichtkras)',
		'Dark Check (Donkerkras)',
	];
 
	function e($v){
		if($v === null){
			$v = '';
		}
		return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
	}
 
	$fields = [
		'fatherRing'      => '',
		'fatherName'      => '',
		'fatherBloodline' => '',
		'fatherColor'     => '',
		'motherRing'      => '',
		'motherName'      => '',
		'motherBloodline' => '',
		'motherColor'     => '',
		'pairedDate'      => '',
		'loftId'          => '',
		'notes'           => '',
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
 
		if($fields['fatherRing'] === ''){
			$errors['fatherRing'] = 'Father ring number is required.';
		}
		elseif(!preg_match('/^[A-Z0-9\-\/\. ]{2,30}$/i', $fields['fatherRing'])){
			$errors['fatherRing'] = 'Ring number may only contain letters, digits, spaces and - / .';
		}
 
		if($fields['fatherColor'] === ''){
			$errors['fatherColor'] = 'Please select a colour for the father.';
		}
 
		if($fields['motherRing'] === ''){
			$errors['motherRing'] = 'Mother ring number is required.';
		}
		elseif(!preg_match('/^[A-Z0-9\-\/\. ]{2,30}$/i', $fields['motherRing'])){
			$errors['motherRing'] = 'Ring number may only contain letters, digits, spaces and - / .';
		}
 
		if($fields['motherColor'] === ''){
			$errors['motherColor'] = 'Please select a colour for the mother.';
		}
 
		if($fields['fatherRing'] !== '' && $fields['motherRing'] !== ''){
			if(strtoupper($fields['fatherRing']) === strtoupper($fields['motherRing'])){
				$errors['motherRing'] = 'Father and mother cannot have the same ring number.';
			}
		}
 
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
 
		if($fields['pairedDate'] !== ''){
			$dt = DateTime::createFromFormat('Y-m-d', $fields['pairedDate']);
			if(!$dt || $dt->format('Y-m-d') !== $fields['pairedDate']){
				$errors['pairedDate'] = 'Invalid date format.';
			}
			elseif($dt > new DateTime()){
				$errors['pairedDate'] = 'Paired date cannot be in the future.';
			}
		}
 
		if(empty($errors)){
			try{
				$dbHandler->beginTransaction();
 
				$upsert = $dbHandler->prepare("
					INSERT INTO pigeon (loft_id, band_number, name, bloodline, color, sex)
						 VALUES (:loft, :band, :name, :bloodline, :color, :sex)
					ON DUPLICATE KEY UPDATE
						 name      = IF(VALUES(name) <> '', VALUES(name), name),
						 bloodline = IF(VALUES(bloodline) <> '', VALUES(bloodline), bloodline),
						 color     = IF(VALUES(color) <> '', VALUES(color), color)
				");
 
				$upsert->execute([
					':loft'      => $fields['loftId'],
					':band'      => strtoupper($fields['fatherRing']),
					':name'      => $fields['fatherName'],
					':bloodline' => $fields['fatherBloodline'],
					':color'     => $fields['fatherColor'],
					':sex'       => 'Cock',
				]);
 
				$sireId = (int) $dbHandler->lastInsertId();
 
				if($sireId === 0){
					$s = $dbHandler->prepare("SELECT id FROM pigeon WHERE band_number = ?");
					$s->execute([strtoupper($fields['fatherRing'])]);
					$sireId = (int) $s->fetchColumn();
				}
 
				$upsert->execute([
					':loft'      => $fields['loftId'],
					':band'      => strtoupper($fields['motherRing']),
					':name'      => $fields['motherName'],
					':bloodline' => $fields['motherBloodline'],
					':color'     => $fields['motherColor'],
					':sex'       => 'Hen',
				]);
 
				$damId = (int) $dbHandler->lastInsertId();
 
				if($damId === 0){
					$s = $dbHandler->prepare("SELECT id FROM pigeon WHERE band_number = ?");
					$s->execute([strtoupper($fields['motherRing'])]);
					$damId = (int) $s->fetchColumn();
				}
 
				if($fields['pairedDate'] !== ''){
					$pairedDate = $fields['pairedDate'];
				}
				else{
					$pairedDate = null;
				}
 
				if($fields['notes'] !== ''){
					$notes = $fields['notes'];
				}
				else{
					$notes = null;
				}
 
				$stmt = $dbHandler->prepare("
					INSERT INTO breeding_pair (loft_id, sire_id, dam_id, pairing_date, notes)
						 VALUES (:loft, :sire, :dam, :date, :notes)
				");
				$stmt->execute([
					':loft'  => $fields['loftId'],
					':sire'  => $sireId,
					':dam'   => $damId,
					':date'  => $pairedDate,
					':notes' => $notes,
				]);
 
				$dbHandler->commit();
				$success = true;
 
				foreach($fields as $key => $value){
					$fields[$key] = '';
				}
 
			}
			catch(PDOException $ex){
				$dbHandler->rollBack();
 
				if($ex->getCode() === '23000'){
					$errors['general'] = 'This pair already exists in the selected loft.';
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

                <!-- Father -->
                <article class="card">
                    <h3>Father</h3>
                    <div class="card-content">
                        <img src="../images/pigeons/pigeon1.png" alt="Father pigeon" class="pigeon-image">
                        <div class="fields">

                            <label for="fatherRing">Ring Number <span class="required">*</span></label>
                            <?php if (isset($errors['fatherRing'])) { ?>
                            <input id="fatherRing" name="fatherRing" type="text"
                                   value="<?php echo e($fields['fatherRing']); ?>"
                                   class="input-error"
                                   placeholder="e.g. NL-2024-1234567">
                            <span class="field-error"><?php echo e($errors['fatherRing']); ?></span>
                            <?php } else { ?>
                            <input id="fatherRing" name="fatherRing" type="text"
                                   value="<?php echo e($fields['fatherRing']); ?>"
                                   placeholder="e.g. NL-2024-1234567">
                            <?php } ?>

                            <label for="fatherName">Name (Optional)</label>
                            <input id="fatherName" name="fatherName" type="text"
                                   value="<?php echo e($fields['fatherName']); ?>"
                                   placeholder="e.g. Blauwe Crack">

                            <label for="fatherBloodline">Bloodline</label>
                            <input id="fatherBloodline" name="fatherBloodline" type="text"
                                   value="<?php echo e($fields['fatherBloodline']); ?>"
                                   placeholder="e.g. Koopman">

                            <label for="fatherColor">Color <span class="required">*</span></label>
                            <?php if (isset($errors['fatherColor'])) { ?>
                            <select id="fatherColor" name="fatherColor" class="input-error">
                            <?php } else { ?>
                            <select id="fatherColor" name="fatherColor">
                            <?php } ?>
                                <option value="">Select color</option>
                                <?php foreach ($colors as $c) { ?>
                                <?php if ($fields['fatherColor'] === $c) { ?>
                                <option value="<?php echo e($c); ?>" selected><?php echo e($c); ?></option>
                                <?php } else { ?>
                                <option value="<?php echo e($c); ?>"><?php echo e($c); ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>
                            <?php if (isset($errors['fatherColor'])) { ?>
                            <span class="field-error"><?php echo e($errors['fatherColor']); ?></span>
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

                            <label for="motherRing">Ring Number <span class="required">*</span></label>
                            <?php if (isset($errors['motherRing'])) { ?>
                            <input id="motherRing" name="motherRing" type="text"
                                   value="<?php echo e($fields['motherRing']); ?>"
                                   class="input-error"
                                   placeholder="e.g. NL-2024-7654321">
                            <span class="field-error"><?php echo e($errors['motherRing']); ?></span>
                            <?php } else { ?>
                            <input id="motherRing" name="motherRing" type="text"
                                   value="<?php echo e($fields['motherRing']); ?>"
                                   placeholder="e.g. NL-2024-7654321">
                            <?php } ?>

                            <label for="motherName">Name (Optional)</label>
                            <input id="motherName" name="motherName" type="text"
                                   value="<?php echo e($fields['motherName']); ?>"
                                   placeholder="e.g. Blauwe Duivin">

                            <label for="motherBloodline">Bloodline</label>
                            <input id="motherBloodline" name="motherBloodline" type="text"
                                   value="<?php echo e($fields['motherBloodline']); ?>"
                                   placeholder="e.g. Koopman">

                            <label for="motherColor">Color <span class="required">*</span></label>
                            <?php if (isset($errors['motherColor'])) { ?>
                            <select id="motherColor" name="motherColor" class="input-error">
                            <?php } else { ?>
                            <select id="motherColor" name="motherColor">
                            <?php } ?>
                                <option value="">Select color</option>
                                <?php foreach ($colors as $c) { ?>
                                <?php if ($fields['motherColor'] === $c) { ?>
                                <option value="<?php echo e($c); ?>" selected><?php echo e($c); ?></option>
                                <?php } else { ?>
                                <option value="<?php echo e($c); ?>"><?php echo e($c); ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select>
                            <?php if (isset($errors['motherColor'])) { ?>
                            <span class="field-error"><?php echo e($errors['motherColor']); ?></span>
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
                        <?php if (isset($errors['pairedDate'])) { ?>
                        <input id="pairedDate" name="pairedDate" type="date"
                               value="<?php echo e($fields['pairedDate']); ?>"
                               class="input-error"
                               max="<?php echo date('Y-m-d'); ?>">
                        <span class="field-error"><?php echo e($errors['pairedDate']); ?></span>
                        <?php } else { ?>
                        <input id="pairedDate" name="pairedDate" type="date"
                               value="<?php echo e($fields['pairedDate']); ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php } ?>
                    </div>

                    <div class="pair-field loft-field">
                        <label for="loftId">Loft <span class="required">*</span></label>
                        <?php if (isset($errors['loftId'])) { ?>
                        <select id="loftId" name="loftId" class="input-error">
                        <?php } else { ?>
                        <select id="loftId" name="loftId">
                        <?php } ?>
                            <option value="">Select Loft</option>
                            <?php foreach ($lofts as $l) { ?>
                            <?php if ($fields['loftId'] == $l['id']) { ?>
                            <option value="<?php echo e($l['id']); ?>" selected><?php echo e($l['loft_name']); ?></option>
                            <?php } else { ?>
                            <option value="<?php echo e($l['id']); ?>"><?php echo e($l['loft_name']); ?></option>
                            <?php } ?>
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