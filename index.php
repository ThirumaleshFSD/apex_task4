<?php
/**
 * Apex Planet Project
 * Main Entry Point
 * 
 * @author  ApexPlanet
 * @version 1.0.0
 */

// ─── Environment Check ────────────────────────────────────────────────────────
$phpVersion   = phpversion();
$mysqlEnabled = extension_loaded('mysqli') ? 'Enabled ✔' : 'Not Loaded ✘';
$apacheInfo   = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Apex Planet Project – PHP & MySQL Development Environment">
    <title>Apex Planet Project</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:   #0d9e8a;
            --secondary: #1a7a6e;
            --accent:    #56e0c8;
            --bg:        #0a0f1e;
            --surface:   #111827;
            --card:      #1c2535;
            --text:      #e2e8f0;
            --muted:     #94a3b8;
            --success:   #22c55e;
            --warning:   #f59e0b;
            --danger:    #ef4444;
            --radius:    14px;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            background-image:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(13,158,138,0.18), transparent),
                radial-gradient(ellipse 60% 40% at 80% 80%, rgba(86,224,200,0.08), transparent);
        }

        /* ── Header ── */
        header {
            text-align: center;
            margin-bottom: 50px;
            animation: fadeDown .7s ease both;
        }
        header .badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 4px 16px;
            border-radius: 50px;
            margin-bottom: 18px;
        }
        header h1 {
            font-size: clamp(2rem, 5vw, 3.4rem);
            font-weight: 800;
            background: linear-gradient(135deg, #fff 30%, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.15;
        }
        header p {
            color: var(--muted);
            font-size: 1.05rem;
            margin-top: 10px;
        }

        /* ── Grid ── */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 22px;
            width: 100%;
            max-width: 1000px;
            animation: fadeUp .7s .2s ease both;
        }

        /* ── Card ── */
        .card {
            background: var(--card);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: var(--radius);
            padding: 28px;
            transition: transform .25s, box-shadow .25s, border-color .25s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,.4);
            border-color: rgba(13,158,138,.4);
        }
        .card-icon {
            font-size: 2rem;
            margin-bottom: 14px;
        }
        .card h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .card p, .card .value {
            color: var(--muted);
            font-size: .9rem;
        }
        .card .value {
            font-weight: 600;
            font-size: 1rem;
            color: var(--accent);
            margin-top: 4px;
        }

        /* ── Status pill ── */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .82rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 50px;
            margin-top: 8px;
        }
        .pill.ok  { background: rgba(34,197,94,.15);  color: var(--success); }
        .pill.err { background: rgba(239,68,68,.15);  color: var(--danger);  }
        .pill.warn{ background: rgba(245,158,11,.15); color: var(--warning); }

        /* ── Environment table ── */
        .env-card {
            grid-column: 1 / -1;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        th { text-align: left; color: var(--primary); font-weight: 600; padding: 10px 14px; border-bottom: 1px solid rgba(255,255,255,.08); }
        td { padding: 10px 14px; color: var(--muted); border-bottom: 1px solid rgba(255,255,255,.04); }
        tr:last-child td { border-bottom: none; }
        td:first-child { color: var(--text); font-weight: 500; }

        /* ── Footer ── */
        footer {
            margin-top: 50px;
            color: var(--muted);
            font-size: .82rem;
            text-align: center;
            animation: fadeUp .7s .4s ease both;
        }
        footer span { color: var(--accent); }

        /* ── Animations ── */
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: .5; }
        }
        .blink { animation: pulse 1.8s ease infinite; }
    </style>
</head>
<body>

<header>
    <div class="badge">⚡ Task 1 – Environment Setup</div>
    <h1>Apex Planet Project</h1>
    <p>PHP &amp; MySQL Development Environment — Local Server Running</p>
</header>

<div class="grid">

    <!-- PHP Version -->
    <div class="card">
        <div class="card-icon">🐘</div>
        <h3>PHP Version</h3>
        <div class="value"><?= htmlspecialchars($phpVersion) ?></div>
        <span class="pill ok">✔ Active</span>
    </div>

    <!-- MySQL / MySQLi -->
    <div class="card">
        <div class="card-icon">🗄️</div>
        <h3>MySQLi Extension</h3>
        <div class="value"><?= $mysqlEnabled ?></div>
        <?php if (extension_loaded('mysqli')): ?>
            <span class="pill ok">✔ Ready</span>
        <?php else: ?>
            <span class="pill err">✘ Enable in php.ini</span>
        <?php endif; ?>
    </div>

    <!-- Apache -->
    <div class="card">
        <div class="card-icon">🌐</div>
        <h3>Web Server</h3>
        <div class="value"><?= htmlspecialchars($apacheInfo) ?></div>
        <span class="pill ok blink">● Running</span>
    </div>

    <!-- Project Status -->
    <div class="card">
        <div class="card-icon">🚀</div>
        <h3>Project Status</h3>
        <div class="value">Initialized</div>
        <span class="pill warn">Task 1 In Progress</span>
    </div>

    <!-- Environment Detail Table -->
    <div class="card env-card">
        <div class="card-icon">⚙️</div>
        <h3 style="margin-bottom:18px;">Full Environment Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>PHP Version</td>        <td><?= phpversion() ?></td></tr>
                <tr><td>Server Software</td>    <td><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?></td></tr>
                <tr><td>Document Root</td>      <td><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') ?></td></tr>
                <tr><td>Server OS</td>          <td><?= PHP_OS ?></td></tr>
                <tr><td>MySQLi</td>             <td><?= extension_loaded('mysqli') ? '✔ Enabled' : '✘ Disabled' ?></td></tr>
                <tr><td>PDO MySQL</td>          <td><?= extension_loaded('pdo_mysql') ? '✔ Enabled' : '✘ Disabled' ?></td></tr>
                <tr><td>Session Support</td>    <td><?= extension_loaded('session') ? '✔ Enabled' : '✘ Disabled' ?></td></tr>
                <tr><td>Max Upload Size</td>    <td><?= ini_get('upload_max_filesize') ?></td></tr>
                <tr><td>Max Execution Time</td> <td><?= ini_get('max_execution_time') ?>s</td></tr>
                <tr><td>Memory Limit</td>       <td><?= ini_get('memory_limit') ?></td></tr>
                <tr><td>Date &amp; Time</td>    <td><?= date('Y-m-d H:i:s') ?></td></tr>
            </tbody>
        </table>
    </div>

</div>

<footer>
    &copy; <?= date('Y') ?> <span>Apex Planet Project</span> — Task 1 Complete | Built with PHP &amp; XAMPP
</footer>

</body>
</html>
