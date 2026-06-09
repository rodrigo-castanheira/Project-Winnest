<?php
    require __DIR__ . '/Php/dbHandler.php';

    // The youngsters belong to the WINNEST LOFT (seeded as loft_id = 1).
    $loftId = 1;

    // List of all pigeons for the selector dropdown.
    $list = $dbHandler->prepare(
        "SELECT id, band_number FROM pigeon WHERE loft_id = :loft ORDER BY band_number"
    );
    $list->execute([':loft' => $loftId]);
    $pigeons = $list->fetchAll(PDO::FETCH_ASSOC);

    // Which pigeon is selected? Use ?id= from the URL, else the first one.
    $selectedId = isset($_GET['id']) ? (int) $_GET['id'] : ($pigeons[0]['id'] ?? null);

    // Load the selected pigeon's full record.
    $pigeon = null;
    if ($selectedId !== null) {
        $stmt = $dbHandler->prepare(
            "SELECT * FROM pigeon WHERE id = :id AND loft_id = :loft"
        );
        $stmt->execute([':id' => $selectedId, ':loft' => $loftId]);
        $pigeon = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Display helpers.
    $sexLabel  = ['Cock' => 'Male', 'Hen' => 'Female', 'Unknown' => 'Unknown'];
    $sexSymbol = ['Cock' => '♂', 'Hen' => '♀', 'Unknown' => ''];

    // Escape output (the band number, name and notes are user input).
    function e($v) { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'); }
    // Show a dash for empty values.
    function orDash($v) { return ($v === null || $v === '') ? '—' : $v; }
    // Format a Y-m-d date as e.g. "1 January 2025".
    function prettyDate($d) {
        if (!$d) return '—';
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt ? $dt->format('j F Y') : $d;
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Youngster Profile | Winnest</title>
    <link rel="stylesheet" href="winnest-style.css">
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo">
                <img src="./images/winnest-logo.png" alt="Winnest logo">
            </div>
            <nav class="menu">
                <a href="dashboard.html" class="menu-item"><img src="images/menu-icon/dashboard.png"
                        alt="Dashboard"><span>Dashboard</span></a>
                <a href="pair-management.html" class="menu-item"><img src="images/menu-icon/pair.png"
                        alt="Pair Management"><span>Pair Management</span></a>
                <a href="nest-management.html" class="menu-item"><img src="images/menu-icon/nest.png"
                        alt="Nest Management"><span>Nest Management</span></a>
                <a href="youngster-profile.php" class="menu-item active"><img src="images/menu-icon/youngster.png"
                        alt="Youngsters"><span>Youngsters</span></a>
                <a href="health-records.html" class="menu-item"><img src="images/menu-icon/health.png"
                        alt="Health Records"><span>Health Records</span></a>
                <a href="race-results.html" class="menu-item"><img src="images/menu-icon/race.png"
                        alt="Race Results"><span>Race Results</span></a>
                <a href="analytics-dashboard.html" class="menu-item"><img src="images/menu-icon/analytics.png"
                        alt="Analytics"><span>Analytics</span></a>
                <a href="#" class="menu-item"><img src="images/menu-icon/report.png"
                        alt="Reports"><span>Reports</span></a>
                <a href="#" class="menu-item"><img src="images/menu-icon/calendar.png"
                        alt="Calendar"><span>Calendar</span></a>
                <a href="#" class="menu-item"><img src="images/menu-icon/setting.png" alt="Loft Settings"><span>Loft
                        Settings</span></a>
                <a href="#" class="menu-item"><img src="images/menu-icon/users.png" alt="Users & Staff"><span>Users &
                        Staff</span></a>
            </nav>
            <div class="loft-card">
                <img src="images/pigeons/koopman-loft.png" alt="Winnest loft">
                <h3>WINNEST LOFT 🇳🇱</h3>
                <p>Ermerveen 17</p>
                <p>7814 VB Emmen</p>
                <p>The Netherlands</p>
            </div>
        </aside>

        <main class="content youngster-profile-content">
            <section class="youngster-profile-header">
                <h1>Youngster Profile</h1>
                <div>
                    <form method="get" class="youngster-selector">
                        <select name="id">
                            <?php foreach ($pigeons as $p): ?>
                                <option value="<?= e($p['id']) ?>" <?= ($p['id'] == $selectedId ? 'selected' : '') ?>>
                                    <?= e($p['band_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">View</button>
                    </form>
                    <button type="button" class="youngster-edit-button"><img src="images/dashboard-icon/edit.png"
                            alt="">Edit Youngster</button>
                    <button type="button" class="youngster-green-button"><img src="images/dashboard-icon/add.png"
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
                    <div class="youngster-title-row">
                        <h2><?= e($pigeon['band_number']) ?> <?= $sexSymbol[$pigeon['sex']] ?? '' ?></h2>
                        <span><?= e(orDash($pigeon['status'])) ?></span>
                    </div>
                    <div class="youngster-info-row">
                        <img src="<?= $pigeon['photo_url'] ? e($pigeon['photo_url']) : 'images/pigeons/pigeon3.png' ?>"
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
                </article>

                <article class="performance-snapshot-card">
                    <h2>Performance Snapshot</h2>
                    <div class="snapshot-grid">
                        <div>
                            <h4>Races</h4><strong>8</strong>
                        </div>
                        <div>
                            <h4>Top 10 Finises</h4><strong>8</strong>
                        </div>
                        <div>
                            <h4>Win %</h4><strong>100%</strong>
                        </div>
                        <div>
                            <h4>Avg. Position</h4><strong>12.4</strong>
                        </div>
                        <div>
                            <h4>Total Points</h4><strong>880</strong>
                        </div>
                    </div>
                    <div class="snapshot-score">
                        <div>
                            <h4>Performance Score</h4>
                            <strong>88.5 / 100</strong>
                        </div>
                        <span>Excellent</span>
                        <div class="small-score-circle"></div>
                    </div>
                </article>

                <article class="parents-info-card">
                    <div class="parents-title">
                        <h2>Parents Information</h2>
                        <button type="button">View Pair Details</button>
                    </div>
                    <span class="parent-pair-id">P-2026001</span>
                    <div class="parents-profile-row">
                        <div>
                            <h3>Male ♂</h3>
                            <strong>NL24-2102319</strong>
                            <p>Blue</p>
                            <p>C & G Koopman</p>
                            <small>Golden Wings</small>
                        </div>
                        <img src="images/pigeons/pigeon1.png" alt="Male parent">
                        <b>×</b>
                        <img src="images/pigeons/pigeon2.png" alt="Female parent">
                        <div>
                            <h3>Female ♀</h3>
                            <strong>NL22-8072203</strong>
                            <p>Blue</p>
                            <p>C & G Koopman</p>
                            <small>Diamond Wings</small>
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
                    <div class="youngster-timeline-item"><small>1 May 2025</small><strong>Raced - Club Race #2 (50
                            km)</strong></div>
                    <div class="youngster-timeline-item"><small>1 April 2025</small><strong>Raced - Club Race #1 (30
                            km)</strong></div>
                    <div class="youngster-timeline-item"><small>1 February 2025</small><strong>Health treatment</strong>
                    </div>
                    <div class="youngster-timeline-item"><small>1 February 2025</small><strong>Move to youngster loft -
                            Vlieg Hok 1</strong></div>
                    <div class="youngster-timeline-item"><small>15 January 2025</small><strong>Ringed</strong></div>
                    <div class="youngster-timeline-item"><small>1 January 2025</small><strong>Hatched</strong></div>
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
                        <button type="button"><img src="images/dashboard-icon/add.png" alt="">Record Race
                            Result</button>
                        <button type="button"><img src="images/dashboard-icon/edit-green.png" alt="">Update
                            Details</button>
                        <button type="button"><img src="images/dashboard-icon/add.png" alt="">Add Health Record</button>
                        <button type="button"><img src="images/dashboard-icon/export.png" alt="">Export</button>
                    </div>
                </article>
            </section>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>
