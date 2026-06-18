<?php

    require __DIR__ . '/dbHandler.php';

    // Fetch ALL eggs, most recently laid first.
    $eggStmt = $dbHandler->prepare(
        "SELECT id, egg_number, lay_date
           FROM egg
          ORDER BY lay_date DESC, id"
    );
    $eggStmt->execute();
    $eggs = $eggStmt->fetchAll(PDO::FETCH_ASSOC);

    function e($v) { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Youngster | Winnest</title>
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
        <main class="content">
            <header>
                <h1>Register New Youngster</h1>
            </header>
            <form action="register-youngster.php" method="POST">
                <section class="form-grid">
                    <article class="card youngster-card">
                        <h3>Youngster Information</h3>
                        <label for="ring-number">Ring Number</label>
                        <input id="ring-number" name="ring_number" type="text" required>
                        <label for="name">Name (Optional)</label>
                        <input id="name" name="name" type="text" placeholder="Pigeon name">
                        <div class="two-columns">
                            <div class="field">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option>Select gender</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                    <option>Unknown</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="color">Color</label>
                                <select id="color" name="color">
                                    <option>Select color</option>
                                    <option>Blue</option>
                                    <option>Ash-Red</option>
                                    <option>Black</option>
                                    <option>Light Check</option>
                                    <option>Dark Check</option>
                                </select>
                            </div>
                        </div>
                        <div class="two-columns">
                            <div class="field">
                                <label for="hatched-date">Hatched Date</label>
                                <input id="hatched-date" name="hatched_date" type="date">
                            </div>
                            <div class="field">
                                <label for="bloodline">Blood Line</label>
                                <select id="bloodline" name="bloodline">
                                    <option>Select bloodline</option>
                                    <option>Koopman</option>
                                    <option>Janssen</option>
                                    <option>Heremans</option>
                                    <option>Van den Bulck</option>
                                </select>
                            </div>
                        </div>
                        <div class="status-field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option>Select status</option>
                                <option>OLR</option>
                                <option>Keep as breeder</option>
                                <option>For sale</option>
                                <option>Not healthy</option>
                                <option>Dead</option>
                            </select>
                        </div>
                        <label for="note">Note (Optional)</label>
                        <input id="note" name="note" type="text" placeholder="Add notes about this youngster">
                    </article>
                    <div class="right-column">
                        <article class="card parents-card">
                            <h3>Parents Information</h3>
                            <div class="round-field">
                                <label for="hatched-from-egg">Hatched From Egg <span style="color: red;">*</span></label>
                                <select id="hatched-from-egg" name="hatched_from_egg_id" required>
                                    <option value="">Select egg</option>
                                    <?php foreach ($eggs as $egg): ?>
                                        <option value="<?= e($egg['id']) ?>">
                                            <?= e($egg['egg_number'] !== null ? $egg['egg_number'] : 'Egg #' . $egg['id']) ?>
                                            <?php if (!empty($egg['lay_date'])): ?>
                                                — Laid <?= e(date('M j, Y', strtotime($egg['lay_date']))) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </article>
                        <article class="card photo-card">
                            <h3>Photo (Optional)</h3>
                            <div class="upload-row">
                                <input type="text" placeholder="Browse...">
                                <button type="button" class="upload">Upload</button>
                            </div>
                            <p>* JPEG, PNG up to 5 MB</p>
                        </article>
                    </div>
                </section>
                <div class="actions register-actions">
                    <button type="button" class="cancel">Cancel</button>
                    <button type="submit" class="save register-save">
                        <img src="../images/dashboard-icon/add.png" alt="">
                        <span>Register</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>

</html>