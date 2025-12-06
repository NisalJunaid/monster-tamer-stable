<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monster PvP Admin Console</title>
    <style>
        :root {
            color-scheme: light;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at 10% 20%, #e7f4ff, #f8fbff 40%),
                        linear-gradient(135deg, #f8f9ff, #eef3ff 40%, #f4fbff);
            color: #0f172a;
        }

        .hero {
            max-width: 1100px;
            margin: 0 auto;
            padding: 64px 24px 32px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 32px;
            align-items: center;
        }

        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: #eef2ff;
            color: #4338ca;
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            font-size: 12px;
        }

        h1 {
            margin: 12px 0 16px;
            font-size: 34px;
            line-height: 1.2;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 20px;
        }

        p {
            margin: 0 0 12px;
            color: #1e293b;
            line-height: 1.6;
        }

        ul {
            padding-left: 20px;
            margin: 12px 0 0;
            color: #1f2937;
        }

        .cta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            margin-top: 12px;
            background: #4338ca;
            color: #fff;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 12px 32px rgba(67, 56, 202, 0.25);
        }

        .cta:hover {
            background: #3730a3;
        }

        footer {
            text-align: center;
            margin: 32px 0 40px;
            color: #475569;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <main class="hero">
        <div>
            <span class="pill">Monster PvP Control Center</span>
            <h1>Deploy and tune live encounters for your GPS-driven monster world.</h1>
            <p>
                This console powers the Pokemon-inspired PvP experience. Define encounter zones,
                configure spawn tables, and keep live tickets deterministic for replayable battles.
            </p>
            <a class="cta" href="{{ route('admin.zones.map') }}">
                Open Admin Map
                <span aria-hidden="true">→</span>
            </a>
            <p style="margin-top: 12px; color: #475569; font-size: 14px;">
                Admin access requires authentication and the <strong>admin</strong> role.
            </p>
        </div>

        <div class="card">
            <h2>What you can manage</h2>
            <ul>
                <li><strong>Encounter zones</strong>: draw polygons or circles on the map to gate spawns.</li>
                <li><strong>Spawn tables</strong>: attach species and modifiers per zone.</li>
                <li><strong>PvP determinism</strong>: encounters create authoritative tickets with seeded RNG.</li>
                <li><strong>Replayable logs</strong>: keep battle data consistent for audits and replays.</li>
            </ul>
        </div>

        <div class="card">
            <h2>Getting started</h2>
            <ul>
                <li>Configure your <code>.env</code> file with PostgreSQL (PostGIS) and Redis.</li>
                <li>Run database migrations and seeders for initial data.</li>
                <li>Serve the app via Nginx/Apache to <code>public/index.php</code>.</li>
                <li>Log in as an admin user to reach the encounter tooling.</li>
            </ul>
        </div>
    </main>
    <footer>
        Built with Laravel 10, PostgreSQL + PostGIS, Redis, and Sanctum authentication.
    </footer>
</body>
</html>
