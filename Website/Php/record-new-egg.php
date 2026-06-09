<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record New Egg | Winnest</title>
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
                <a href="youngster-profile.php" class="menu-item"><img src="images/menu-icon/youngster.png"
                        alt="Youngsters"><span>Youngsters</span></a>
                <a href="health-records.html" class="menu-item"><img src="images/menu-icon/health.png"
                        alt="Health Records"><span>Health Records</span></a>
                <a href="race-results.html" class="menu-item active"><img src="images/menu-icon/race.png"
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

        <main class="content">
            <header>
                <h1>Record New Egg</h1>
            </header>

            <?php
                include "./Php/dbHandler.php";

                if($_SERVER["REQUEST_METHOD"] == "POST"){
                    
                    $eggNumber = filter_input(INPUT_POST, 'egg-number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $laidDate = filter_input(INPUT_POST, 'laid-date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $note = filter_input(INPUT_POST, 'egg-note', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $pairId = filter_input(INPUT_POST, 'pair-id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $nestId = filter_input(INPUT_POST, 'nest-id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $breedingRound = filter_input(INPUT_POST, 'breeding-round', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    
                    if(empty($eggNumber) || empty($laidDate) || empty($note) || empty($pairId) || empty($nestId) || empty($breedingRound)){
                        echo '<div class="errors">
                                <p>Please fill out all fields.
                            </div>';
                    }
                }
            ?>

            <form action="record-new-egg.php" method="POST" enctype="multipart/form-data">
                <section class="egg-grid">
                    <article class="card egg-card">
                        <h3>Egg Information</h3>

                        <div class="egg-row">
                            <div class="egg-field">
                                <label for="egg-number">Egg Number</label>
                                <select id="egg-number">
                                    <option selected disabled>Select Egg</option>
                                    <option>1st Egg</option>
                                    <option>2nd Egg</option>
                                </select>
                            </div>

                            <div class="egg-field">
                                <label for="laid-date">Laid Date</label>
                                <input id="laid-date" name="laid-date" type="date">
                            </div>
                        </div>

                        <div class="egg-note">
                            <label for="egg-note">Note (Optional)</label>
                            <input id="egg-note" type="text" placeholder="Add notes about this egg">
                        </div>
                    </article>

                    <article class="card egg-parents-card">
                        <h3>Parents Information</h3>

                        <div class="egg-row">
                            <div class="egg-field">
                                <label for="pair-id">Pair ID</label>
                                <select id="pair-id">
                                    <option selected disabled>Select pair ID</option>
                                    <option>PAIR-001</option>
                                    <option>PAIR-002</option>
                                    <option>PAIR-003</option>
                                </select>
                            </div>

                            <div class="egg-field">
                                <label for="nest-id">Nest ID</label>
                                <select id="nest-id">
                                    <option selected disabled>Select nest ID</option>
                                    <option>NEST-001</option>
                                    <option>NEST-002</option>
                                    <option>NEST-003</option>
                                </select>
                            </div>
                        </div>

                        <div class="egg-round-field">
                            <label for="breeding-round">Breeding Round</label>
                            <select id="breeding-round">
                                <option selected disabled>Select round</option>
                                <option>Round 1</option>
                                <option>Round 2</option>
                                <option>Round 3</option>
                                <option>Round 4</option>
                                <option>Round 5</option>
                                <option>Round 6</option>
                                <option>Round 7</option>
                            </select>
                        </div>
                    </article>
                </section>

                <div class="actions egg-actions">
                    <button type="button" class="cancel">Cancel</button>
                    <button type="submit" class="save">
                        <img src="images/dashboard-icon/add.png" alt="">
                        <span>Save</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>

</html>