<?php
    require __DIR__ . '/dbHandler.php';

    // Only handle form submissions
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../register-new-youngster.html');
        exit;
    }

    // The youngster belongs to the WINNEST LOFT (seeded as loft_id = 1).
    // Hardcoded server-side so it can never be set from the form.
    $loftId = 1;

    // Allowed values. Every submitted value is checked against these
    // whitelists, so tampered input from dev tools is rejected here.
    $genderMap         = ['Male' => 'Cock', 'Female' => 'Hen', 'Unknown' => 'Unknown'];
    $allowedColors     = ['Blue', 'Ash-Red', 'Black', 'Light Check', 'Dark Check'];
    $allowedBloodlines = ['Koopman', 'Janssen', 'Heremans', 'Van den Bulck'];
    $allowedStatuses   = ['OLR', 'Keep as breeder', 'For sale', 'Not healthy', 'Dead'];

    // Read a field, trimming it and treating "Select ..." placeholders as empty.
    $field = function ($key) {
        $value = trim($_POST[$key] ?? '');
        return (str_starts_with($value, 'Select ') || $value === '') ? null : $value;
    };

    $bandNumber = $field('ring_number');
    $gender     = $field('gender');
    $color      = $field('color');
    $bloodline  = $field('bloodline');
    $dob        = $field('hatched_date');
    $status     = $field('status');
    $notes      = $field('note');

    // --- Server-side validation (untrusted input) ---
    $errors = [];

    // Ring number: required, 1-100 chars, letters/digits/dash/slash/space only.
    if ($bandNumber === null) {
        $errors[] = 'ring_required';
    } elseif (!preg_match('/^[A-Za-z0-9\-\/ ]{1,100}$/', $bandNumber)) {
        $errors[] = 'ring_invalid';
    }

    // Gender: defaults to Unknown when not chosen; any other value is tampering.
    if ($gender === null) {
        $sex = 'Unknown';
    } elseif (isset($genderMap[$gender])) {
        $sex = $genderMap[$gender];
    } else {
        $errors[] = 'gender_invalid';
        $sex = 'Unknown';
    }

    // Optional dropdowns: if present, must be a known value.
    if ($color !== null && !in_array($color, $allowedColors, true)) {
        $errors[] = 'color_invalid';
    }
    if ($bloodline !== null && !in_array($bloodline, $allowedBloodlines, true)) {
        $errors[] = 'bloodline_invalid';
    }
    if ($status !== null && !in_array($status, $allowedStatuses, true)) {
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

    // Note: optional free text, capped and stripped of any HTML tags.
    if ($notes !== null) {
        $notes = strip_tags($notes);
        if (mb_strlen($notes) > 1000) {
            $errors[] = 'note_too_long';
        }
    }

    // Reject the whole submission if anything failed validation.
    if (!empty($errors)) {
        header('Location: ../register-new-youngster.html?error=' . implode(',', $errors));
        exit;
    }

    $sql = "INSERT INTO pigeon
                (loft_id, band_number, sex, bloodline, color, status, date_of_birth, notes)
            VALUES
                (:loft_id, :band_number, :sex, :bloodline, :color, :status, :dob, :notes)";

    $stmt = $dbHandler->prepare($sql);
    $stmt->execute([
        ':loft_id'     => $loftId,
        ':band_number' => $bandNumber,
        ':sex'         => $sex,
        ':bloodline'   => $bloodline,
        ':color'       => $color,
        ':status'      => $status,
        ':dob'         => $dob,
        ':notes'       => $notes,
    ]);

    header('Location: ../register-new-youngster.html?saved=1');
    exit;
?>
