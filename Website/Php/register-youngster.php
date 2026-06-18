<?php

    require __DIR__ . '/dbHandler.php';

    // Only handle form submissions
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../register-new-youngster.php');
        exit;
    }

    // The youngster belongs to the WINNEST LOFT (seeded as loft_id = 1).
    // Hardcoded server-side so it can never be set from the form.
    $loftId = 1;

    // Allowed whitelists matching your updated database ENUM values
    $genderMap         = ['Male' => 'Male', 'Female' => 'Female', 'Unknown' => 'Unknown'];
    $allowedColors     = ['Blue', 'Ash-Red', 'Black', 'Light Check', 'Dark Check'];
    $allowedBloodlines = ['Koopman', 'Janssen', 'Heremans', 'Van den Bulck'];
    $allowedStatuses   = ['OLR', 'Keep as breeder', 'For sale', 'Not healthy', 'Dead', 'Active'];

    // Read a field, trimming it and treating "Select ..." placeholders as empty.
    $field = function ($key) {
        $value = trim($_POST[$key] ?? '');
        return (str_starts_with($value, 'Select ') || $value === '') ? null : $value;
    };

    $bandNumber = $field('ring_number');
    $name       = $field('name');
    $gender     = $field('gender');
    $color      = $field('color');
    $bloodline  = $field('bloodline');
    $dob        = $field('hatched_date');
    $status     = $field('status');
    $notes      = $field('note');
    $eggId      = $field('hatched_from_egg_id');

    // --- Server-side validation (untrusted input) ---
    $errors = [];

    // Ring number: required, 1-100 chars, letters/digits/dash/slash/space only.
    if ($bandNumber === null) {
        $errors[] = 'ring_required';
    } elseif (!preg_match('/^[A-Za-z0-9\-\/ ]{1,100}$/', $bandNumber)) {
        $errors[] = 'ring_invalid';
    } else {
        // Optimization: Check for duplicate ring numbers within this loft
        $dupCheck = $dbHandler->prepare("SELECT COUNT(*) FROM pigeon WHERE band_number = :band AND loft_id = :loft");
        $dupCheck->execute([':band' => $bandNumber, ':loft' => $loftId]);
        if ((int)$dupCheck->fetchColumn() > 0) {
            $errors[] = 'ring_duplicate';
        }
    }

    // Gender: defaults to Unknown when not chosen
   $sex = $_POST['gender'] ?? 'Unknown'; 

    if (!in_array($sex, ['Male', 'Female', 'Unknown'])) {
        $sex = 'Unknown';
    }

    // Optional dropdowns: if present, must be a known value.
    if ($color !== null && !in_array($color, $allowedColors, true)) {
        $errors[] = 'color_invalid';
    }
    if ($bloodline !== null && !in_array($bloodline, $allowedBloodlines, true)) {
        $errors[] = 'bloodline_invalid';
    }
    
    // Status fallback: handle fallback logic if nothing is selected
    if ($status === null) {
        $status = 'Active';
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $errors[] = 'status_invalid';
    }

    // Hatched date: must be a real Y-m-d date and not in the future.
    if ($dob !== null) {
        $parsed = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$parsed || $parsed->format('Y-m-d') !== $dob) {
            $errors[] = 'date_invalid';
        } elseif ($parsed > new DateTime('today')) {
            $errors[] = 'date_future';
        }
    }

    // Name: optional, max 255 chars, stripped of any HTML tags.
    if ($name !== null) {
        $name = strip_tags($name);
        if (mb_strlen($name) > 255) {
            $errors[] = 'name_too_long';
        }
    }

    // Note: optional free text, capped and stripped of any HTML tags.
    if ($notes !== null) {
        $notes = strip_tags($notes);
        if (mb_strlen($notes) > 1000) {
            $errors[] = 'note_too_long';
        }
    }

    // Hatched-from egg: optional validation.
    $eggId = $_POST['hatched_from_egg_id'] ?? null;

    if ($eggId) {
        // This single query fetches the exact lineage context behind the scenes!
        $lineageLookup = $dbHandler->prepare("
            SELECT bp.loft_id, br.pair_id, br.nest_id 
              FROM egg e
              JOIN breeding_record br ON e.breeding_record_id = br.id
              JOIN breeding_pair bp ON br.pair_id = bp.id
             WHERE e.id = :egg_id
        ");
        $lineageLookup->execute([':egg_id' => $eggId]);
        $lineage = $lineageLookup->fetch(PDO::FETCH_ASSOC);

        if ($lineage) {
            $loftId = $lineage['loft_id'];
            // If your pigeon table contains explicit columns for pair or nest:
            // $pairId = $lineage['pair_id'];
            // $nestId = $lineage['nest_id'];
        }
    }

    // Reject the whole submission if anything failed validation.
    if (!empty($errors)) {
        header('Location: ../register-new-youngster.php?error=' . implode(',', $errors));
        exit;
    }

    // INSERT statement explicitly including 'is_youngster' 
    $sql = "INSERT INTO pigeon
                (loft_id, band_number, name, sex, bloodline, color, status, is_youngster, date_of_birth, notes, hatched_from_egg_id)
            VALUES
                (:loft_id, :band_number, :name, :sex, :bloodline, :color, :status, 1, :dob, :notes, :egg_id)";

    $stmt = $dbHandler->prepare($sql);
    $stmt->execute([
        ':loft_id'     => $loftId,
        ':band_number' => $bandNumber,
        ':name'        => $name,
        ':sex'         => $sex,
        ':bloodline'   => $bloodline,
        ':color'       => $color,
        ':status'      => $status,
        ':dob'         => $dob,
        ':notes'       => $notes,
        ':egg_id'      => $eggId,
    ]);

    header('Location: ../register-new-youngster.php?saved=1');
    exit;
?>