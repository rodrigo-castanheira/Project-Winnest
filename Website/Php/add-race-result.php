<?php

	require(__DIR__.'/dbHandler.php');

	$youngsters = $dbHandler->query("SELECT id, band_number, name FROM pigeon WHERE is_youngster = 1 ORDER BY band_number")->fetchAll(PDO::FETCH_ASSOC);

	function e($v){
		if($v === null){
			$v = '';
		}
		return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
	}

	$fields = [
		'organization'  => '',
		'country'       => '',
		'raceName'      => '',
		'distanceKm'    => '',
		'raceDate'      => '',
		'raceNote'      => '',
		'youngsterId'   => '',
		'placement'     => '',
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

		if($fields['raceName'] === ''){
			$errors['raceName'] = 'Race name is required.';
		}

		if($fields['organization'] !== '' && !preg_match('/^[A-Za-z0-9\s\-\.\']{2,100}$/', $fields['organization'])){
			$errors['organization'] = 'Organization may only contain letters, numbers, spaces and - . \' ';
		}

		if($fields['country'] !== '' && !preg_match('/^[A-Za-z\s\-\.\']{2,100}$/', $fields['country'])){
			$errors['country'] = 'Country may only contain letters, spaces and - . \' ';
		}

		if($fields['distanceKm'] !== ''){
			if(!is_numeric($fields['distanceKm']) || $fields['distanceKm'] <= 0){
				$errors['distanceKm'] = 'Distance must be a positive number.';
			}
		}

		if($fields['raceDate'] !== ''){
			$dt = DateTime::createFromFormat('Y-m-d', $fields['raceDate']);
			if(!$dt || $dt->format('Y-m-d') !== $fields['raceDate']){
				$errors['raceDate'] = 'Invalid date format.';
			}
			elseif($dt > new DateTime()){
				$errors['raceDate'] = 'Race date cannot be in the future.';
			}
		}

		if($fields['youngsterId'] === ''){
			$errors['youngsterId'] = 'Please select a youngster.';
		}
		else{
			$youngsterValid = false;
			foreach($youngsters as $y){
				if($y['id'] == $fields['youngsterId']){
					$youngsterValid = true;
					break;
				}
			}
			if(!$youngsterValid){
				$errors['youngsterId'] = 'Invalid youngster selected.';
			}
		}

		if($fields['placement'] === ''){
			$errors['placement'] = 'Placement is required.';
		}
		elseif(!preg_match('/^[0-9]+$/', $fields['placement'])){
			$errors['placement'] = 'Placement must be a whole number.';
		}

		if(empty($errors)){
			try{
				if($fields['distanceKm'] !== ''){
					$distanceKm = $fields['distanceKm'];
				}
				else{
					$distanceKm = null;
				}

				if($fields['raceDate'] !== ''){
					$raceDate = $fields['raceDate'];
				}
				else{
					$raceDate = null;
				}

				if($fields['raceNote'] !== ''){
					$notes = $fields['raceNote'];
				}
				else{
					$notes = null;
				}

				if($fields['organization'] !== ''){
					$organization = $fields['organization'];
				}
				else{
					$organization = null;
				}

				if($fields['country'] !== ''){
					$country = $fields['country'];
				}
				else{
					$country = null;
				}

				$stmt = $dbHandler->prepare("
					INSERT INTO race_performance (pigeon_id, race_name, organization, country, race_date, distance_km, placement, notes)
						 VALUES (:pigeon, :raceName, :organization, :country, :raceDate, :distance, :placement, :notes)
				");
				$stmt->execute([
					':pigeon'       => (int) $fields['youngsterId'],
					':raceName'     => $fields['raceName'],
					':organization' => $organization,
					':country'      => $country,
					':raceDate'     => $raceDate,
					':distance'     => $distanceKm,
					':placement'    => $fields['placement'],
					':notes'        => $notes,
				]);

				$success = true;

				foreach($fields as $key => $value){
					$fields[$key] = '';
				}

			}
			catch(PDOException $ex){
				$errors['general'] = 'Database error: '.e($ex->getMessage());
			}
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Add Race Result | Winnest</title>
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
					<a href="../pair-management.html" class="menu-item"><img src="../images/menu-icon/pair.png" alt="Pair Management"><span>Pair Management</span></a>
					<a href="../nest-management.html" class="menu-item"><img src="../images/menu-icon/nest.png" alt="Nest Management"><span>Nest Management</span></a>
					<a href="youngster-profile.php" class="menu-item"><img src="../images/menu-icon/youngster.png" alt="Youngsters"><span>Youngsters</span></a>
					<a href="../health-records.html" class="menu-item"><img src="../images/menu-icon/health.png" alt="Health Records"><span>Health Records</span></a>
					<a href="../race-results.php" class="menu-item active"><img src="../images/menu-icon/race.png" alt="Race Results"><span>Race Results</span></a>
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
					<h1>Add Race Result</h1>
				</header>

				<?php if($success){ ?>
				<div class="alert-success">
					Race result saved successfully! <a href="race-results.php">View all results</a> or add another below.
				</div>
				<?php } ?>

				<?php if(!empty($errors['general'])){ ?>
				<div class="alert-danger">
					<?php echo e($errors['general']); ?>
				</div>
				<?php } ?>

				<form method="POST" action="" novalidate>
					<section class="race-grid">
						<article class="card race-card">
							<h3>Race Information</h3>

							<label for="raceName">Race Name</label>
							<?php if(isset($errors['raceName'])){ ?>
							<input id="raceName" name="raceName" type="text"
								   value="<?php echo e($fields['raceName']); ?>"
								   class="input-error">
							<span class="field-error"><?php echo e($errors['raceName']); ?></span>
							<?php } else { ?>
							<input id="raceName" name="raceName" type="text"
								   value="<?php echo e($fields['raceName']); ?>">
							<?php } ?>

							<label for="organization">Organization</label>
							<?php if(isset($errors['organization'])){ ?>
							<input id="organization" name="organization" type="text"
								   value="<?php echo e($fields['organization']); ?>"
								   class="input-error"
								   placeholder="e.g. Pattaya One Loft Race">
							<span class="field-error"><?php echo e($errors['organization']); ?></span>
							<?php } else { ?>
							<input id="organization" name="organization" type="text"
								   value="<?php echo e($fields['organization']); ?>"
								   placeholder="e.g. Pattaya One Loft Race">
							<?php } ?>

							<label for="country">Country</label>
							<?php if(isset($errors['country'])){ ?>
							<input id="country" name="country" type="text"
								   value="<?php echo e($fields['country']); ?>"
								   class="input-error"
								   placeholder="e.g. Netherlands">
							<span class="field-error"><?php echo e($errors['country']); ?></span>
							<?php } else { ?>
							<input id="country" name="country" type="text"
								   value="<?php echo e($fields['country']); ?>"
								   placeholder="e.g. Netherlands">
							<?php } ?>

							<div class="race-row">
								<div class="race-field">
									<label for="distanceKm">Distance (km)</label>
									<?php if(isset($errors['distanceKm'])){ ?>
									<input id="distanceKm" name="distanceKm" type="number" step="0.01" min="0"
										   value="<?php echo e($fields['distanceKm']); ?>"
										   class="input-error">
									<span class="field-error"><?php echo e($errors['distanceKm']); ?></span>
									<?php } else { ?>
									<input id="distanceKm" name="distanceKm" type="number" step="0.01" min="0"
										   value="<?php echo e($fields['distanceKm']); ?>">
									<?php } ?>
								</div>

								<div class="race-field">
									<label for="raceDate">Race Date</label>
									<?php if(isset($errors['raceDate'])){ ?>
									<input id="raceDate" name="raceDate" type="date"
										   value="<?php echo e($fields['raceDate']); ?>"
										   class="input-error"
										   max="<?php echo date('Y-m-d'); ?>">
									<span class="field-error"><?php echo e($errors['raceDate']); ?></span>
									<?php } else { ?>
									<input id="raceDate" name="raceDate" type="date"
										   value="<?php echo e($fields['raceDate']); ?>"
										   max="<?php echo date('Y-m-d'); ?>">
									<?php } ?>
								</div>
							</div>

							<label for="raceNote">Note (Optional)</label>
							<input id="raceNote" name="raceNote" type="text"
								   value="<?php echo e($fields['raceNote']); ?>"
								   placeholder="Add notes about this race">
						</article>

						<article class="card result-card">
							<h3>Record Result</h3>

							<label for="youngsterId">Youngster</label>
							<?php if(isset($errors['youngsterId'])){ ?>
							<select id="youngsterId" name="youngsterId" class="input-error">
							<?php } else { ?>
							<select id="youngsterId" name="youngsterId">
							<?php } ?>
								<option value="">Select youngster</option>
								<?php foreach($youngsters as $y){ ?>
								<?php
									$youngsterLabel = $y['band_number'];
									if(!empty($y['name'])){
										$youngsterLabel .= ' — '.$y['name'];
									}
								?>
								<?php if($fields['youngsterId'] == $y['id']){ ?>
								<option value="<?php echo e($y['id']); ?>" selected><?php echo e($youngsterLabel); ?></option>
								<?php } else { ?>
								<option value="<?php echo e($y['id']); ?>"><?php echo e($youngsterLabel); ?></option>
								<?php } ?>
								<?php } ?>
							</select>
							<?php if(isset($errors['youngsterId'])){ ?>
							<span class="field-error"><?php echo e($errors['youngsterId']); ?></span>
							<?php } ?>

							<label for="placement">Placement</label>
							<?php if(isset($errors['placement'])){ ?>
							<input id="placement" name="placement" type="number" step="1" min="1"
								   value="<?php echo e($fields['placement']); ?>"
								   class="input-error">
							<span class="field-error"><?php echo e($errors['placement']); ?></span>
							<?php } else { ?>
							<input id="placement" name="placement" type="number" step="1" min="1"
								   value="<?php echo e($fields['placement']); ?>">
							<?php } ?>

							<div class="actions race-inner-actions">
								<button type="button" class="cancel"><a href="race-results.php">Cancel</a></button>
								<button type="submit" class="save">
									<img src="../images/dashboard-icon/add.png" alt="">
									<span>Save</span>
								</button>
							</div>
						</article>
					</section>
				</form>
			</main>
		</div>
	</body>
</html>