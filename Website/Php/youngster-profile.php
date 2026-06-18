<?php
    require __DIR__ . '/dbHandler.php';
    session_start();

    $currentUserId = $_SESSION['user_id'] ?? 1;

    // 1. Fetch all lofts for the user
    $loftStmt = $dbHandler->prepare("SELECT id, loft_name FROM loft WHERE user_id = :user_id");
    $loftStmt->execute([':user_id' => $currentUserId]);
    $userLofts = $loftStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Set Active Loft
    // Default to the first of the user's lofts that has a youngster, else their first loft.
    if (isset($_GET['loft_id'])) {
        $loftId = (int) $_GET['loft_id'];
    } else {
        $defStmt = $dbHandler->prepare(
            "SELECT p.loft_id
               FROM pigeon p
               JOIN loft l ON p.loft_id = l.id
              WHERE l.user_id = :user_id AND p.is_youngster = 1
              ORDER BY p.loft_id LIMIT 1"
        );
        $defStmt->execute([':user_id' => $currentUserId]);
        $defaultLoft = $defStmt->fetchColumn();
        $loftId = $defaultLoft !== false ? (int) $defaultLoft : ($userLofts[0]['id'] ?? 0);
    }

    // 3. Fetch Youngsters for the SELECTED loft
    $list = $dbHandler->prepare("SELECT id, band_number FROM pigeon WHERE loft_id = :loft AND is_youngster = 1 ORDER BY band_number");
    $list->execute([':loft' => $loftId]);
    $pigeons = $list->fetchAll(PDO::FETCH_ASSOC);

    // 4. Set Active Pigeon (must belong to the active loft)
    $selectedId = isset($_GET['id']) ? (int) $_GET['id'] : ($pigeons[0]['id'] ?? null);

    // 5. Fetch full record with parents
    $pigeon = null;
    if ($selectedId !== null) {
        $stmt = $dbHandler->prepare("
            SELECT p.*, 
                   sire.band_number AS sire_band, sire.color AS sire_color, sire.bloodline AS sire_bloodline,
                   dam.band_number AS dam_band, dam.color AS dam_color, dam.bloodline AS dam_bloodline
            FROM pigeon p
            LEFT JOIN egg e ON p.hatched_from_egg_id = e.id
            LEFT JOIN breeding_record br ON e.breeding_record_id = br.id
            LEFT JOIN breeding_pair bp ON br.pair_id = bp.id
            LEFT JOIN pigeon sire ON bp.sire_id = sire.id
            LEFT JOIN pigeon dam ON bp.dam_id = dam.id
            WHERE p.id = :id AND p.loft_id = :loft
        ");
        $stmt->execute([':id' => $selectedId, ':loft' => $loftId]);
        $pigeon = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $genderOptions = ['Male', 'Female', 'Unknown'];
    $allowedStatuses = ['OLR', 'Keep as breeder', 'For sale', 'Not healthy', 'Dead'];

    function e($v) { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'); }
    function orDash($v) { return ($v === null || $v === '') ? '—' : $v; }
    function prettyDate($d) {
        if (!$d) return '—';
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt ? $dt->format('j F Y') : $d;
    }

    $editMode = isset($_GET['edit']) && $pigeon !== null;

    // Human-readable messages for the codes update-youngster.php returns via ?error=.
    $editErrorMessages = [
        'ring_required'      => 'Ring number is required.',
        'ring_invalid'       => 'Ring number may only contain letters, digits, spaces and - /.',
        'gender_invalid'     => 'Please select a valid gender.',
        'color_too_long'     => 'Color must be 100 characters or fewer.',
        'bloodline_too_long' => 'Bloodline must be 255 characters or fewer.',
        'status_invalid'     => 'Please select a valid status.',
        'date_invalid'       => 'Hatched date is not a valid date.',
        'date_future'        => 'Hatched date cannot be in the future.',
        'name_too_long'      => 'Name must be 255 characters or fewer.',
        'note_too_long'      => 'Note must be 1000 characters or fewer.',
    ];
    $editErrors = [];
    if ($editMode && isset($_GET['error'])) {
        foreach (explode(',', $_GET['error']) as $code) {
            if (isset($editErrorMessages[$code])) {
                $editErrors[] = $editErrorMessages[$code];
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Youngster Profile | Winnest</title>
    <link rel="stylesheet" href="../winnest-style.css">
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo">
                <img src="../images/winnest-logo.png" alt="Winnest logo">
            </div>
            <nav class="menu">
                <a href="dashboard.php" class="menu-item"><img src="../images/menu-icon/dashboard.png"
                        alt="Dashboard"><span>Dashboard</span></a>
                <a href="../pair-management.html" class="menu-item"><img src="../images/menu-icon/pair.png"
                        alt="Pair Management"><span>Pair Management</span></a>
                <a href="../nest-management.html" class="menu-item"><img src="../images/menu-icon/nest.png"
                        alt="Nest Management"><span>Nest Management</span></a>
                <a href="youngster-profile.php" class="menu-item active"><img src="../images/menu-icon/youngster.png"
                        alt="Youngsters"><span>Youngsters</span></a>
                <a href="../health-records.html" class="menu-item"><img src="../images/menu-icon/health.png"
                        alt="Health Records"><span>Health Records</span></a>
                <a href="../race-results.html" class="menu-item"><img src="../images/menu-icon/race.png"
                        alt="Race Results"><span>Race Results</span></a>
                <a href="../analytics-dashboard.html" class="menu-item"><img src="../images/menu-icon/analytics.png"
                        alt="Analytics"><span>Analytics</span></a>
                <a href="#" class="menu-item"><img src="../images/menu-icon/report.png"
                        alt="Reports"><span>Reports</span></a>
                <a href="#" class="menu-item"><img src="../images/menu-icon/calendar.png"
                        alt="Calendar"><span>Calendar</span></a>
                <a href="#" class="menu-item"><img src="../images/menu-icon/setting.png" alt="Loft Settings"><span>Loft
                        Settings</span></a>
                <a href="#" class="menu-item"><img src="../images/menu-icon/users.png" alt="Users & Staff"><span>Users &
                        Staff</span></a>
            </nav>
            <div class="loft-card">
                <img src="../images/pigeons/koopman-loft.png" alt="Winnest loft">
                <h3>WINNEST LOFT 🇳🇱</h3>
                <p>Ermerveen 17</p>
                <p>7814 VB Emmen</p>
                <p>The Netherlands</p>
            </div>
        </aside>

        <main class="content youngster-profile-content">
            <section class="youngster-profile-header">
                <h1>Youngster Profile</h1>
                
                <div class="selectors-container">
                    <form method="get" class="inline-form">
                        <label>Loft:</label>
                        <select name="loft_id" onchange="this.form.submit()">
                            <?php foreach ($userLofts as $loft): ?>
                                <option value="<?= e($loft['id']) ?>" <?= ($loftId == $loft['id'] ? 'selected' : '') ?>>
                                    <?= e($loft['loft_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <form method="get" class="inline-form">
                        <input type="hidden" name="loft_id" value="<?= e($loftId) ?>">
                        <label>Youngster:</label>
                        <select name="id" onchange="this.form.submit()">
                            <?php foreach ($pigeons as $p): ?>
                                <option value="<?= e($p['id']) ?>" <?= ($p['id'] == $selectedId ? 'selected' : '') ?>>
                                    <?= e($p['band_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div>
                    <?php if (!$editMode && $pigeon !== null): ?>
                        <a class="youngster-edit-button" href="?id=<?= e($selectedId) ?>&loft_id=<?= e($loftId) ?>&edit=1">
                            <img src="../images/dashboard-icon/edit.png" alt="">Edit Youngster
                        </a>
                    <?php endif; ?>
                    <button type="button" class="youngster-green-button"><img src="../images/dashboard-icon/add.png"
                            alt="">Record Race Result</button>
                </div>
            </section>

            <?php if ($pigeon === null): ?>
                <section class="youngster-top-grid">
                    <article class="youngster-main-card">
                        <p>No youngsters registered yet. Use "Register New Youngster" to add one.</p>
                    </article>
                </section>
            <?php else: ?>
            <section class="youngster-top-grid">
                <article class="youngster-main-card">
                    <?php if ($editMode): ?>
                    <form method="post" action="update-youngster.php" class="youngster-edit-form">
                        <input type="hidden" name="id" value="<?= e($pigeon['id']) ?>">
                        <input type="hidden" name="loft_id" value="<?= e($loftId) ?>">
                        <div class="youngster-title-row">
                            <h2>Edit Youngster</h2>
                        </div>
                        <?php if (!empty($editErrors)): ?>
                        <div class="edit-errors">
                            <?php foreach ($editErrors as $msg): ?>
                            <p class="field-error"><?= e($msg) ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <label for="edit-ring">Ring Number</label>
                        <input id="edit-ring" type="text" name="ring_number" value="<?= e($pigeon['band_number']) ?>">
                        <label for="edit-name">Name</label>
                        <input id="edit-name" type="text" name="name" value="<?= e($pigeon['name']) ?>">
                        <label for="edit-gender">Gender</label>
                        <select id="edit-gender" name="gender">
                            <?php foreach ($genderOptions as $option): ?>
                                <option value="<?= e($option) ?>" <?= ($pigeon['sex'] === $option ? 'selected' : '') ?>>
                                    <?= e($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="edit-color">Color</label>
                        <input id="edit-color" type="text" name="color" value="<?= e($pigeon['color']) ?>" placeholder="Enter color">

                        <label for="edit-date">Hatched Date</label>
                        <input id="edit-date" type="date" name="hatched_date" value="<?= e($pigeon['date_of_birth']) ?>">

                        <label for="edit-bloodline">Bloodline</label>
                        <input id="edit-bloodline" type="text" name="bloodline" value="<?= e($pigeon['bloodline']) ?>" placeholder="Enter bloodline">
                        <label for="edit-status">Status</label>
                        <select id="edit-status" name="status">
                            <option value="">Select status</option>
                            <?php foreach ($allowedStatuses as $status): ?>
                                <option value="<?= e($status) ?>" <?= ($pigeon['status'] === $status ? 'selected' : '') ?>>
                                    <?= e($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="edit-note">Note</label>
                        <input id="edit-note" type="text" name="note" value="<?= e($pigeon['notes']) ?>">
                        <div class="youngster-edit-actions">
                            <a class="cancel" href="?id=<?= e($pigeon['id']) ?>">Cancel</a>
                            <button type="submit" class="save">Save Changes</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="youngster-title-row">
                        <h2><?= e($pigeon['band_number']) ?></h2> 
                        <span><?= e(orDash($pigeon['status'])) ?></span>
                    </div>
                    <p><strong>Gender:</strong> <?= e(orDash($pigeon['sex'])) ?></p>
                    <div class="youngster-info-row">
                        <img src="<?= $pigeon['photo_url'] ? e($pigeon['photo_url']) : '../images/pigeons/pigeon3.png' ?>"
                            alt="Youngster pigeon">
                        <div class="youngster-info-list">
                            <p><strong>Name:</strong> <?= e(orDash($pigeon['name'])) ?></p>
                            <p><strong>Gender:</strong> <?= e($sexLabel[$pigeon['sex']] ?? $pigeon['sex']) ?></p>
                            <p><strong>Color:</strong> <?= e(orDash($pigeon['color'])) ?></p>
                            <p><strong>Hatched Date:</strong> <?= e(prettyDate($pigeon['date_of_birth'])) ?></p>
                            <p><strong>Bloodline:</strong> <?= e(orDash($pigeon['bloodline'])) ?></p>
                            <p><strong>Status:</strong> <?= e(orDash($pigeon['status'])) ?></p>
                            <p><strong>Current Location:</strong> —</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </article>

                <article class="performance-snapshot-card">
                    <h2>Performance Snapshot</h2>
                    <div class="snapshot-grid">
                        <div>
                            <h4>Races</h4><strong>0</strong>
                        </div>
                        <div>
                            <h4>Top 10 Finises</h4><strong>0</strong>
                        </div>
                        <div>
                            <h4>Win %</h4><strong>0%</strong>
                        </div>
                        <div>
                            <h4>Avg. Position</h4><strong>0</strong>
                        </div>
                        <div>
                            <h4>Total Points</h4><strong>0</strong>
                        </div>
                    </div>
                    <div class="snapshot-score">
                        <div>
                            <h4>Performance Score</h4>
                            <strong>0 / 100</strong>
                        </div>
                        <span>Uknown</span>
                        <div class="small-score-circle"></div>
                    </div>
                </article>

                <article class="parents-info-card">
                    <div class="parents-title">
                        <h2>Parents Information</h2>
                    </div>
                    
                    <div class="parents-profile-row">
                        <div>
                            <h3>Male ♂</h3>
                            <strong><?= e($pigeon['sire_band'] ?? 'Unknown') ?></strong>
                            <p><?= e($pigeon['sire_color'] ?? '—') ?></p>
                            <p><?= e($pigeon['sire_bloodline'] ?? '—') ?></p>
                        </div>
                        
                        <b>×</b>
                        
                        <div>
                            <h3>Female ♀</h3>
                            <strong><?= e($pigeon['dam_band'] ?? 'Unknown') ?></strong>
                            <p><?= e($pigeon['dam_color'] ?? '—') ?></p>
                            <p><?= e($pigeon['dam_bloodline'] ?? '—') ?></p>
                        </div>
                    </div>
                </article>
            </section>

            <section class="youngster-bottom-grid">
                <article class="youngster-panel race-records-card">
                    <div class="youngster-panel-title">
                        <h2>Race Records</h2>
                        <button type="button">View All Races</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Race</th>
                                <th>Distance</th>
                                <th>Position</th>
                                <th>Points</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </article>

                <article class="youngster-panel timeline-card">
                    <h2>Timeline</h2>
                    <div class="youngster-timeline-item"><small>Date</small><strong>Raced - Name & Distance</strong></div>
                    <div class="youngster-timeline-item"><small>Date</small><strong>Raced - Name & Distance</strong></div>
                    <div class="youngster-timeline-item"><small>Date</small><strong>Health treatment</strong>
                    </div>
                    <div class="youngster-timeline-item"><small>Date</small><strong>Move to youngster loft</strong></div>
                    <div class="youngster-timeline-item"><small>Date</small><strong>Ringed</strong></div>
                </article>

                <article class="youngster-panel health-records-card">
                    <div class="youngster-panel-title">
                        <h2>Health Records</h2>
                        <button type="button">View All</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>By</th>
                                <th>Next Due</th>
                            </tr>
                        </thead>
                    </table>
                </article>

                <article class="youngster-panel quick-actions-profile">
                    <h2>Quick Actions</h2>
                    <div>
                        <button type="button"><img src="../images/dashboard-icon/add.png" alt="">Record Race
                            Result</button>
                        <button type="button"><img src="../images/dashboard-icon/edit-green.png" alt="">Update
                            Details</button>
                        <button type="button"><img src="../images/dashboard-icon/add.png" alt="">Add Health Record</button>
                        <button type="button"><img src="../images/dashboard-icon/export.png" alt="">Export</button>
                    </div>
                </article>
            </section>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>
