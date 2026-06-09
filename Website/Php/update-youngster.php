<?php
    require __DIR__ . '/dbHandler.php';

    // Only handle form submissions.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../youngster-profile.php');
        exit;
    }

    $loftId = 1;

    // The pigeon being edited (from the hidden field). Must be a positive int.
    $id = (isset($_POST['id']) && ctype_digit((string) $_POST['id'])) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        header('Location: ../youngster-profile.php');
        exit;
    }

    // Same whitelists the register handler enforces.
    $genderMap         = ['Male' => 'Cock', 'Female' => 'Hen', 'Unknown' => 'Unknown'];
    $allowedColors     = ['Blue', 'Ash-Red', 'Black', 'Light Check', 'Dark Check'];
    $allowedBloodlines = ['Koopman', 'Janssen', 'Heremans', 'Van den Bulck'];
    $allowedStatuses   = ['OLR', 'Keep as breeder', 'For sale', 'Not healthy', 'Dead'];

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

    $errors = [];

    if ($bandNumber === null) {
        $errors[] = 'ring_required';
    } elseif (!preg_match('/^[A-Za-z0-9\-\/ ]{1,100}$/', $bandNumber)) {
        $errors[] = 'ring_invalid';
    }

    if ($gender === null) {
        $sex = 'Unknown';
    } elseif (isset($genderMap[$gender])) {
        $sex = $genderMap[$gender];
    } else {
        $errors[] = 'gender_invalid';
        $sex = 'Unknown';
    }

    if ($color !== null && !in_array($color, $allowedColors, true)) {
        $errors[] = 'color_invalid';
    }
    if ($bloodline !== null && !in_array($bloodline, $allowedBloodlines, true)) {
        $errors[] = 'bloodline_invalid';
    }
    if ($status !== null && !in_array($status, $allowedStatuses, true)) {
        $errors[] = 'status_invalid';
    }

    if ($dob !== null) {
        $parsed = DateTime::createFromFormat('Y-m-d', $dob);
        if (!$parsed || $parsed->format('Y-m-d') !== $dob) {
            $errors[] = 'date_invalid';
        } elseif ($parsed > new DateTime('today')) {
            $errors[] = 'date_future';
        }
    }

    if ($name !== null) {
        $name = strip_tags($name);
        if (mb_strlen($name) > 255) {
            $errors[] = 'name_too_long';
        }
    }
    if ($notes !== null) {
        $notes = strip_tags($notes);
        if (mb_strlen($notes) > 1000) {
            $errors[] = 'note_too_long';
        }
    }

    // On any error, return to the edit form with the reasons.
    if (!empty($errors)) {
        header('Location: ../youngster-profile.php?id=' . $id . '&edit=1&error=' . implode(',', $errors));
        exit;
    }

    $sql = "UPDATE pigeon SET
                band_number   = :band_number,
                name          = :name,
                sex           = :sex,
                bloodline     = :bloodline,
                color         = :color,
                status        = :status,
                date_of_birth = :dob,
                notes         = :notes
            WHERE id = :id AND loft_id = :loft";

    $stmt = $dbHandler->prepare($sql);
    $stmt->execute([
        ':band_number' => $bandNumber,
        ':name'        => $name,
        ':sex'         => $sex,
        ':bloodline'   => $bloodline,
        ':color'       => $color,
        ':status'      => $status,
        ':dob'         => $dob,
        ':notes'       => $notes,
        ':id'          => $id,
        ':loft'        => $loftId,
    ]);

    header('Location: ../youngster-profile.php?id=' . $id . '&saved=1');
    exit;
?>
