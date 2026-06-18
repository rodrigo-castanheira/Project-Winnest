<?php
    require __DIR__ . '/dbHandler.php';

    // The dashboard summarises the WINNEST LOFT (seeded as loft_id = 1).
    $loftId = 1;

    // Eggs recorded for this loft (egg -> breeding_record -> breeding_pair).
    $stmt = $dbHandler->prepare(
        "SELECT COUNT(*) FROM egg e
           JOIN breeding_record br ON e.breeding_record_id = br.id
           JOIN breeding_pair bp ON br.pair_id = bp.id
          WHERE bp.loft_id = :loft"
    );
    $stmt->execute([':loft' => $loftId]);
    $eggsRecorded = (int) $stmt->fetchColumn();

    // Youngsters that hatched from a recorded egg.
    $stmt = $dbHandler->prepare(
        "SELECT COUNT(*) FROM pigeon
          WHERE loft_id = :loft AND hatched_from_egg_id IS NOT NULL"
    );
    $stmt->execute([':loft' => $loftId]);
    $hatchedCount = (int) $stmt->fetchColumn();

    // Registered youngsters (every pigeon row is a youngster), plus how
    // many of them were born this year.
    $stmt = $dbHandler->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(YEAR(date_of_birth) = YEAR(CURDATE())), 0) AS born_this_year
           FROM pigeon
          WHERE loft_id = :loft"
    );
    $stmt->execute([':loft' => $loftId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $youngsterTotal        = (int) $row['total'];
    $youngsterBornThisYear = (int) $row['born_this_year'];

    // Nests in this loft, plus how many are still available.
    $stmt = $dbHandler->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(status = 'available'), 0) AS available
           FROM nest
          WHERE loft_id = :loft"
    );
    $stmt->execute([':loft' => $loftId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nestTotal     = (int) $row['total'];
    $nestAvailable = (int) $row['available'];

    // Breeding pairs in this loft, plus how many are marked active.
    $stmt = $dbHandler->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(is_active = 1), 0) AS active
           FROM breeding_pair
          WHERE loft_id = :loft"
    );
    $stmt->execute([':loft' => $loftId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $pairTotal  = (int) $row['total'];
    $pairActive = (int) $row['active'];

    // Health records for this loft's pigeons.
    $stmt = $dbHandler->prepare(
        "SELECT COUNT(*) FROM health_record hr
           JOIN pigeon p ON hr.pigeon_id = p.id
          WHERE p.loft_id = :loft"
    );
    $stmt->execute([':loft' => $loftId]);
    $healthRecords = (int) $stmt->fetchColumn();

    // Recent activity: the latest registered youngsters and recorded eggs,
    // newest dates first (the tables store no creation timestamps).
    // Positional params because the loft id is bound twice.
    $stmt = $dbHandler->prepare(
        "(SELECT 'youngster' AS kind, p.id, p.band_number AS label, p.name AS extra, p.date_of_birth AS event_date
            FROM pigeon p
           WHERE p.loft_id = ?)
         UNION ALL
         (SELECT 'egg' AS kind, e.id, e.egg_number AS label, NULL AS extra, e.lay_date AS event_date
            FROM egg e
            JOIN breeding_record br ON e.breeding_record_id = br.id
            JOIN breeding_pair bp ON br.pair_id = bp.id
           WHERE bp.loft_id = ?)
         ORDER BY (event_date IS NULL), event_date DESC, id DESC
         LIMIT 5"
    );
    $stmt->execute([$loftId, $loftId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Escape output (band numbers, names and egg labels are user input).
    function e($v) { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'); }
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
    <title>Dashboard | Winnest</title>
    <link rel="stylesheet" href="../winnest-style.css">
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo">
                <img src="../images/winnest-logo.png" alt="Winnest logo">
            </div>
            <nav class="menu">
                <a href="dashboard.php" class="menu-item active"><img src="../images/menu-icon/dashboard.png"
                        alt="Dashboard"><span>Dashboard</span></a>
                <a href="../pair-management.html" class="menu-item"><img src="../images/menu-icon/pair.png"
                        alt="Pair Management"><span>Pair Management</span></a>
                <a href="../nest-management.html" class="menu-item"><img src="../images/menu-icon/nest.png"
                        alt="Nest Management"><span>Nest Management</span></a>
                <a href="youngster-profile.php" class="menu-item"><img src="../images/menu-icon/youngster.png"
                        alt="Youngsters"><span>Youngsters</span></a>
                <a href="../health-records.html" class="menu-item"><img src="../images/menu-icon/health.png"
                        alt="Health Records"><span>Health Records</span></a>
                <a href="../race-results.php" class="menu-item"><img src="../images/menu-icon/race.png"
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

        <main class="content dashboard-content">
            <section class="dashboard-header">
                <div>
                    <h1>Dashboard</h1>
                    <p>Welcome back, <strong>Gerard Koopman!</strong> Here’s what’s happening in your loft today.</p>
                </div>
                <div class="top-controls">
                    <select>
                        <option>Season 2025</option>
                    </select>
                    <div class="user-card">
                        <span>G</span>
                        <div>
                            <strong>Gerard Koopman</strong>
                            <p>Owner</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="dashboard-top">
                <article class="overview-panel">
                    <div class="section-title">
                        <h2>Loft Overview</h2>
                        <button type="button">View All Overview</button>
                    </div>

                    <div class="overview-cards">
                        <div class="overview-card">
                            <h4>Eggs Recorded</h4>
                            <div class="overview-value">
                                <img src="../images/dashboard-icon/egg-circle.png" alt="Egg">
                                <strong><?= $eggsRecorded ?></strong>
                            </div>
                        </div>

                        <div class="overview-card">
                            <h4>Hatched</h4>
                            <div class="overview-value">
                                <img src="../images/dashboard-icon/hatch-circle.png" alt="Hatch">
                                <strong><?= $hatchedCount ?></strong>
                            </div>
                        </div>

                        <div class="overview-card">
                            <h4>Youngsters</h4>
                            <div class="overview-value">
                                <img src="../images/dashboard-icon/youngster-circle.png" alt="Youngster">
                                <strong><?= $youngsterTotal ?></strong>
                            </div>
                        </div>

                        <div class="overview-card">
                            <h4>Nests</h4>
                            <div class="overview-value">
                                <img src="../images/dashboard-icon/nest-circle.png" alt="Nest">
                                <strong><?= $nestTotal ?></strong>
                            </div>
                        </div>

                        <div class="overview-card">
                            <h4>Health Records</h4>
                            <div class="overview-value">
                                <img src="../images/dashboard-icon/survival-circle.png" alt="Health">
                                <strong><?= $healthRecords ?></strong>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="quick-grid">
                        <a href="./add-new-pair.php"><img src="../images/dashboard-icon/add.png" alt=""><span>Add New
                                Pair</span></a>
                        <a href="./record-new-egg.php"><img src="../images/dashboard-icon/add.png" alt=""><span>Record
                                Egg</span></a>
                        <a href="./register-new-youngster.php"><img src="../images/dashboard-icon/add.png"
                                alt=""><span>Register Youngster</span></a>
                        <a href="./add-race-result.php"><img src="../images/dashboard-icon/add.png" alt=""><span>Add Race
                                Result</span></a>
                        <a href="#"><img src="../images/dashboard-icon/add.png" alt=""><span>Health Record</span></a>
                        <a href="./add-race-result.html"><img src="../images/dashboard-icon/add.png" alt=""><span>Add Race
                                Result</span></a>
                    </div>
                </article>
            </section>

            <section class="dashboard-main-grid">
                <article class="dashboard-box recent-activity">
                    <div class="section-title">
                        <h2>Recent Activity</h2>
                        <button type="button">View All Activity</button>
                    </div>

                    <?php if (empty($activities)): ?>
                    <p>No activity yet — register a youngster or record an egg to see it here.</p>
                    <?php else: ?>
                    <?php foreach ($activities as $a): $isEgg = ($a['kind'] === 'egg'); ?>
                    <div class="activity-item">
                        <img src="../images/dashboard-icon/<?= $isEgg ? 'egg' : 'youngster' ?>-circle.png" alt="">
                        <div>
                            <h4><?= $isEgg ? 'Egg recorded' : 'Youngster registered' ?></h4>
                            <p><?= $isEgg ? e($a['label'] ?: 'Egg') : 'Ring ' . e($a['label']) . ($a['extra'] ? ' — ' . e($a['extra']) : '') ?></p>
                        </div>
                        <small><?= $a['event_date'] ? ($isEgg ? 'Laid ' : 'Born ') . e(prettyDate($a['event_date'])) : '—' ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </article>

                <article class="dashboard-box alerts-box">
                    <div class="section-title">
                        <h2>Alerts & Reminders</h2>
                        <button type="button">View All</button>
                    </div>

                    <div class="alert red">
                        <img src="../images/dashboard-icon/warning.png" alt="">
                        <p><strong>Low survival rate alert</strong><br>Pair 1 has a survival rate below 70%</p>
                        <button class="alert-button">View Pair</button>
                    </div>

                    <div class="alert orange">
                        <img src="../images/dashboard-icon/notification.png" alt="">
                        <p><strong>Missing ring numbers</strong><br>5 youngsters</p>
                        <button>View Youngster</button>
                    </div>

                    <div class="alert green">
                        <img src="../images/dashboard-icon/vaccination.png" alt="">
                        <p><strong>Vaccination due</strong><br>12 youngsters</p>
                        <button>View Health</button>
                    </div>

                    <div class="alert blue">
                        <img src="../images/dashboard-icon/race-event.png" alt="">
                        <p><strong>Upcoming race</strong><br>PIPR Training 5 is on 5 June 2026</p>
                        <button>View Calendar</button>
                    </div>
                </article>

                <aside class="dashboard-box schedule-box">
                    <div class="section-title">
                        <h2>Upcoming Schedule</h2>
                        <button type="button">View Calendar</button>
                    </div>

                    <h3>May 2026</h3>

                    <div class="calendar-grid">
                        <strong>MON</strong><strong>TUE</strong><strong>WED</strong><strong>THU</strong><strong>FRI</strong><strong>SAT</strong><strong>SUN</strong>
                        <span></span><span></span><span></span><span></span><span>1</span><span>2</span><span>3</span>
                        <span>4</span><span>5</span><span>6</span><span>7</span><span>8</span><span>9</span><span>10</span>
                        <span>11</span><span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span>
                        <span>18</span><span>19</span><span>20</span><span>21</span><span>22</span><span>23</span><span>24</span>
                        <span>25</span><span
                            class="selected-day">26</span><span>27</span><span>28</span><span>29</span><span>30</span><span>31</span>
                    </div>

                    <div class="schedule-item"><strong>29<br>MAY</strong>
                        <p>DNA Testing<br><span>Friday, 12:00</span></p>
                    </div>
                    <div class="schedule-item"><strong>31<br>MAY</strong>
                        <p>Lot 4 - PIPR pigeon collection<br><span>Sunday, 11:30</span></p>
                    </div>
                    <div class="schedule-item"><strong>6<br>JUN</strong>
                        <p>PIPR Training Race #2 - 30 km<br><span>Saturday, 7:00</span></p>
                    </div>
                    <div class="schedule-item"><strong>13<br>JUN</strong>
                        <p>Victoria Fall Hotspot 1 - 120 km<br><span>Saturday, 8:00</span></p>
                    </div>
                </aside>
            </section>

            <section class="loft-summary">
                <h2>Loft Summary</h2>
                <div class="summary-grid">
                    <div>
                        <p>Pairs</p>
                        <strong><?= $pairTotal ?></strong>
                        <span>Active <?= $pairActive ?></span>
                        <img src="../images/dashboard-icon/pairing-circle.png" alt="">
                    </div>
                    <div>
                        <p>Nests</p>
                        <strong><?= $nestTotal ?></strong>
                        <span>Available <?= $nestAvailable ?></span>
                        <img src="../images/dashboard-icon/nest-circle.png" alt="">
                    </div>
                    <div>
                        <p>Youngsters</p>
                        <strong><?= $youngsterTotal ?></strong>
                        <span>Born in <?= date('Y') ?>: <?= $youngsterBornThisYear ?></span>
                        <img src="../images/dashboard-icon/youngster-circle.png" alt="">
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>

</html>