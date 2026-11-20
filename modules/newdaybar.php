<?php

declare(strict_types=1);

/**
 * Module: Syndicate Broadcast & Floor Collapse Timer HUD (newdaybar.php)
 * Reskinned from: Real-Time & In-Game Clock New Day Progress Bar (newdaybar.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Dungeon
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Dungeon_Crawler_Carl_Wiki
 *
 * Features:
 *   - Drop-in replacement for `newdaybar.php` preserving module name `newdaybar`.
 *   - Live ticking Real-Time Earth Clock with an inline Timezone Dropdown Selector.
 *   - Live ticking In-Dungeon Broadcast Cycle Clock (24-hour accelerated game time).
 *   - Dynamic Broadcast Phase Icons (Morning Crawl Feed, Prime-Time Carnage,
 *     Odette's After Hours Recap, and Safe Room Collapse Warning).
 *   - Animated Floor Collapse / Next Broadcast Cycle Progress Bar with live HH:MM:SS countdown.
 *   - Can be rendered in `charstats` (Sidebar HUD), Top Header Banner, or Both.
 */

function newdaybar_getmoduleinfo(): array
{
    return [
        'name' => 'Syndicate Broadcast & Floor Collapse HUD Bar',
        'version' => '2.0.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Syndicate HUD Display Settings,title',
            'display_mode' => 'Where should the Syndicate Clock & Progress HUD appear?,enum,charstats,Sidebar (charstats) Only,header,Top HUD Banner Only,both,Both Sidebar and Top Banner|both',
            'default_timezone' => 'Default Earth Timezone for new Crawlers,text|America/Vancouver',
            'show_tz_dropdown' => 'Allow Crawlers to switch Earth timezone inline?,bool|1',
        ],
        'prefs' => [
            'Crawler HUD Clock Preferences,title',
            'user_timezone' => 'Preferred Earth Timezone (IANA format),text|America/Vancouver',
            'use_24h' => 'Use 24-hour military time format for Earth clock?,bool|0',
        ],
    ];
}

function newdaybar_install(): bool
{
    module_addhook('charstats');
    module_addhook('everyhit-loggedin');
    return true;
}

function newdaybar_uninstall(): bool
{
    return true;
}

/**
 * Curated list of supported Earth IANA timezones for the inline HUD selector.
 *
 * @return array<string, string>
 */
function newdaybar_get_timezones(): array
{
    return [
        'America/Vancouver' => 'PT (Vancouver / Seattle)',
        'America/Edmonton'  => 'MT (Creston / Calgary / Denver)',
        'America/Winnipeg'  => 'CT (Winnipeg / Chicago)',
        'America/Toronto'   => 'ET (Toronto / New York)',
        'America/Halifax'   => 'AT (Halifax)',
        'UTC'               => 'UTC (Syndicate Standard)',
        'Europe/London'     => 'GMT/BST (London)',
        'Europe/Berlin'     => 'CET (Berlin / Paris)',
        'Asia/Tokyo'        => 'JST (Tokyo)',
        'Australia/Sydney'  => 'AEST (Sydney)',
    ];
}

/**
 * Resolves and validates the current Crawler's active IANA timezone.
 */
function newdaybar_resolve_timezone(): string
{
    $tzList = newdaybar_get_timezones();

    // Check if user just switched timezone via inline selector
    $requestedTz = (string) httpget('ndb_tz');
    if ($requestedTz !== '' && isset($tzList[$requestedTz])) {
        set_module_pref('user_timezone', $requestedTz, 'newdaybar');
        return $requestedTz;
    }

    $userTz = (string) get_module_pref('user_timezone', 'newdaybar');
    if ($userTz !== '' && in_array($userTz, timezone_identifiers_list(), true)) {
        return $userTz;
    }

    $defaultTz = (string) get_module_setting('default_timezone', 'newdaybar');
    if ($defaultTz !== '' && in_array($defaultTz, timezone_identifiers_list(), true)) {
        return $defaultTz;
    }

    return 'America/Vancouver';
}

/**
 * Computes exact In-Dungeon time, Broadcast Phase metadata, and seconds until the next New Day cycle.
 *
 * @return array{
 *   earth_time: string,
 *   earth_tz: string,
 *   earth_tz_label: string,
 *   game_time_24: string,
 *   game_hour: int,
 *   phase_icon: string,
 *   phase_name: string,
 *   phase_color: string,
 *   secs_left: int,
 *   cycle_total_secs: int,
 *   progress_pct: float,
 *   countdown_hms: string,
 *   daysperday: int
 * }
 */
function newdaybar_get_telemetry(): array
{
    $tz = newdaybar_resolve_timezone();
    $tzList = newdaybar_get_timezones();
    $tzLabel = $tzList[$tz] ?? $tz;
    $use24h = ((int) get_module_pref('use_24h', 'newdaybar') === 1);

    try {
        $dt = new DateTimeImmutable('now', new DateTimeZone($tz));
    } catch (Throwable) {
        $dt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
    $earthTime = $dt->format($use24h ? 'H:i:s' : 'g:i:s A');

    // Calculate LotGD game cycle parameters
    $daysPerDay = max(1, (int) getsetting('daysperday', 4));
    $cycleTotalSecs = (int) max(60, round(86400 / $daysPerDay));

    // Use LotGD's `gametime()` if available, otherwise derive from standard offset
    $gameTimestamp = function_exists('gametime') ? (int) gametime() : (time() * $daysPerDay);
    $gameSecOfDay = (($gameTimestamp % 86400) + 86400) % 86400;
    $gameHour = (int) floor($gameSecOfDay / 3600);
    $gameMin = (int) floor(($gameSecOfDay % 3600) / 60);
    $gameSec = (int) ($gameSecOfDay % 60);
    $gameTime24 = sprintf('%02d:%02d:%02d', $gameHour, $gameMin, $gameSec);

    // Real-world seconds remaining until next New Day (24:00:00 in-game)
    $gameSecsLeft = 86400 - $gameSecOfDay;
    $realSecsLeft = (int) max(0, round($gameSecsLeft / $daysPerDay));
    $progressPct = round(min(100.0, max(0.0, (($cycleTotalSecs - $realSecsLeft) / $cycleTotalSecs) * 100.0)), 1);

    $remH = (int) floor($realSecsLeft / 3600);
    $remM = (int) floor(($realSecsLeft % 3600) / 60);
    $remS = (int) ($realSecsLeft % 60);
    $countdownHms = sprintf('%02d:%02d:%02d', $remH, $remM, $remS);

    // Determine Dungeon Crawler World Broadcast Phase & Icon
    if ($gameHour >= 6 && $gameHour < 12) {
        $phaseIcon = '&#128225;'; // Satellite Dish
        $phaseName = 'Morning Crawl Feed';
        $phaseColor = '#3fb950';
    } elseif ($gameHour >= 12 && $gameHour < 18) {
        $phaseIcon = '&#9876;&#65039;'; // Crossed Swords
        $phaseName = 'Prime-Time Carnage';
        $phaseColor = '#ffd700';
    } elseif ($gameHour >= 18 && $gameHour < 22) {
        $phaseIcon = '&#128250;'; // Television
        $phaseName = 'Odette\'s After Hours';
        $phaseColor = '#00f0ff';
    } else {
        $phaseIcon = '&#9888;&#65039;'; // Warning Sign
        $phaseName = 'Collapse Curfew Window';
        $phaseColor = '#ff2a85';
    }

    return [
        'earth_time' => $earthTime,
        'earth_tz' => $tz,
        'earth_tz_label' => $tzLabel,
        'game_time_24' => $gameTime24,
        'game_hour' => $gameHour,
        'phase_icon' => $phaseIcon,
        'phase_name' => $phaseName,
        'phase_color' => $phaseColor,
        'secs_left' => $realSecsLeft,
        'cycle_total_secs' => $cycleTotalSecs,
        'progress_pct' => $progressPct,
        'countdown_hms' => $countdownHms,
        'daysperday' => $daysPerDay,
    ];
}

/**
 * Injects the shared JavaScript live-ticker once per page load so all clocks and progress bars update every second.
 */
function newdaybar_inject_live_script(array $t): void
{
    static $injected = false;
    if ($injected) {
        return;
    }
    $injected = true;

    $use24h = ((int) get_module_pref('use_24h', 'newdaybar') === 1) ? 'true' : 'false';
    $tzJson = json_encode($t['earth_tz']);
    $secsLeft = (int) $t['secs_left'];
    $cycleTotal = (int) $t['cycle_total_secs'];
    $daysPerDay = (int) $t['daysperday'];
    $gameSecNow = (($t['game_hour'] * 3600) + ((int) substr($t['game_time_24'], 3, 2) * 60) + (int) substr($t['game_time_24'], 6, 2));

    rawoutput("
    <script>
    (function() {
        var secsLeft = {$secsLeft};
        var cycleTotal = {$cycleTotal};
        var gameSec = {$gameSecNow};
        var daysPerDay = {$daysPerDay};
        var userTz = {$tzJson};
        var use24h = {$use24h};

        function pad2(n) { return n < 10 ? '0' + n : '' + n; }

        function updateDccHudClocks() {
            // 1. Update Earth Real-Time Clock
            try {
                var now = new Date();
                var fmt = new Intl.DateTimeFormat('en-US', {
                    timeZone: userTz,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: !use24h
                });
                var earthStr = fmt.format(now);
                var earthNodes = document.querySelectorAll('.dcc-ndb-earth-clock');
                for (var i = 0; i < earthNodes.length; i++) {
                    earthNodes[i].textContent = earthStr;
                }
            } catch (e) {}

            // 2. Update In-Dungeon Accelerated Clock
            gameSec = (gameSec + daysPerDay) % 86400;
            var gh = Math.floor(gameSec / 3600);
            var gm = Math.floor((gameSec % 3600) / 60);
            var gs = Math.floor(gameSec % 60);
            var gameStr = pad2(gh) + ':' + pad2(gm) + ':' + pad2(gs);
            var gameNodes = document.querySelectorAll('.dcc-ndb-game-clock');
            for (var j = 0; j < gameNodes.length; j++) {
                gameNodes[j].textContent = gameStr;
            }

            // 3. Update Broadcast Collapse Countdown & Progress Bar
            if (secsLeft > 0) {
                secsLeft--;
            }
            var rh = Math.floor(secsLeft / 3600);
            var rm = Math.floor((secsLeft % 3600) / 60);
            var rs = secsLeft % 60;
            var cdStr = (secsLeft > 0) ? (pad2(rh) + ':' + pad2(rm) + ':' + pad2(rs)) : 'CYCLE READY!';
            var pct = Math.min(100, Math.max(0, ((cycleTotal - secsLeft) / cycleTotal) * 100)).toFixed(1);

            var cdNodes = document.querySelectorAll('.dcc-ndb-countdown');
            for (var k = 0; k < cdNodes.length; k++) {
                cdNodes[k].textContent = cdStr;
            }
            var barNodes = document.querySelectorAll('.dcc-ndb-bar-fill');
            for (var m = 0; m < barNodes.length; m++) {
                barNodes[m].style.width = pct + '%';
            }
        }

        setInterval(updateDccHudClocks, 1000);
    })();
    </script>
    ");
}

/**
 * Builds the HTML for the inline timezone dropdown selector.
 */
function newdaybar_render_tz_dropdown(string $activeTz, bool $compact = false): string
{
    if ((int) get_module_setting('show_tz_dropdown', 'newdaybar') !== 1) {
        return '';
    }

    $tzList = newdaybar_get_timezones();
    $reqUri = (string) ($_SERVER['REQUEST_URI'] ?? 'village.php');
    $baseUri = strtok($reqUri, '?') ?: 'village.php';

    // Preserve existing query params except `ndb_tz`
    $queryParams = $_GET ?? [];
    unset($queryParams['ndb_tz']);

    // Whitelist safe navigation route so LotGD's badnav checker allows the switch
    $html = '<select onchange="if(this.value) window.location.href=this.value;" style="background:#0d1117;color:#00f0ff;border:1px solid #30363d;border-radius:4px;font-family:Consolas,monospace;font-size:' . ($compact ? '0.75em' : '0.82em') . ';padding:1px 4px;max-width:' . ($compact ? '135px' : '195px') . ';">';

    foreach ($tzList as $tzKey => $label) {
        $params = array_merge($queryParams, ['ndb_tz' => $tzKey]);
        $targetUrl = basename($baseUri) . '?' . http_build_query($params);
        addnav('', $targetUrl);
        $selected = ($tzKey === $activeTz) ? ' selected="selected"' : '';
        $shortLabel = $compact ? explode(' ', $label)[0] . ' (' . explode('/', $tzKey)[1 - (int) (strpos($tzKey, '/') === false)] . ')' : $label;
        $html .= '<option value="' . htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' . htmlspecialchars($shortLabel, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $html .= '</select>';

    return $html;
}

function newdaybar_dohook(string $hookname, array $args): array
{
    $mode = (string) get_module_setting('display_mode', 'newdaybar');
    $t = newdaybar_get_telemetry();

    switch ($hookname) {
        case 'everyhit-loggedin':
            newdaybar_inject_live_script($t);

            if ($mode === 'header' || $mode === 'both') {
                $barColor = ($t['progress_pct'] >= 85.0)
                    ? 'linear-gradient(90deg, #ff2a85, #ff0040)'
                    : 'linear-gradient(90deg, #00f0ff, #3fb950)';
                $tzDropdown = newdaybar_render_tz_dropdown($t['earth_tz'], false);

                rawoutput('
                <div style="background:linear-gradient(90deg,#090d16 0%,#121826 50%,#090d16 100%);border-bottom:2px solid #00f0ff;padding:6px 12px;font-family:Consolas,monospace;font-size:0.86em;color:#e6edf3;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span><strong style="color:#00f0ff;">&#127757; Earth Clock:</strong> <span class="dcc-ndb-earth-clock" style="color:#f0f6fc;font-weight:bold;">' . htmlspecialchars($t['earth_time'], ENT_QUOTES, 'UTF-8') . '</span> ' . $tzDropdown . '</span>
                        <span><strong style="color:#ffd700;">&#9201; Dungeon Time:</strong> <span class="dcc-ndb-game-clock" style="color:#ffd700;font-weight:bold;">' . htmlspecialchars($t['game_time_24'], ENT_QUOTES, 'UTF-8') . '</span></span>
                        <span style="background:rgba(255,255,255,0.06);border:1px solid ' . $t['phase_color'] . ';border-radius:4px;padding:1px 7px;color:' . $t['phase_color'] . ';font-weight:bold;">' . $t['phase_icon'] . ' ' . htmlspecialchars($t['phase_name'], ENT_QUOTES, 'UTF-8') . '</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;min-width:230px;">
                        <span style="color:#ff2a85;font-weight:bold;white-space:nowrap;">Next Cycle: <span class="dcc-ndb-countdown" style="color:#ffffff;">' . htmlspecialchars($t['countdown_hms'], ENT_QUOTES, 'UTF-8') . '</span></span>
                        <div style="flex:1;min-width:80px;background:#21262d;height:8px;border-radius:4px;overflow:hidden;border:1px solid #30363d;">
                            <div class="dcc-ndb-bar-fill" style="background:' . $barColor . ';width:' . $t['progress_pct'] . '%;height:100%;transition:width 0.8s linear;"></div>
                        </div>
                    </div>
                </div>
                ');
            }
            break;

        case 'charstats':
            newdaybar_inject_live_script($t);

            if ($mode === 'charstats' || $mode === 'both') {
                $barColor = ($t['progress_pct'] >= 85.0) ? '#ff2a85' : '#00f0ff';
                $tzDropdown = newdaybar_render_tz_dropdown($t['earth_tz'], true);

                addcharstat('Broadcast Telemetry');
                addcharstat(
                    'Earth Time',
                    '<span class="dcc-ndb-earth-clock" style="color:#00f0ff;font-weight:bold;">' . htmlspecialchars($t['earth_time'], ENT_QUOTES, 'UTF-8') . '</span>'
                );
                if ($tzDropdown !== '') {
                    addcharstat('Timezone', $tzDropdown);
                }
                addcharstat(
                    'Dungeon Clock',
                    '<span class="dcc-ndb-game-clock" style="color:#ffd700;font-weight:bold;">' . htmlspecialchars($t['game_time_24'], ENT_QUOTES, 'UTF-8') . '</span>'
                );
                addcharstat(
                    'Feed Phase',
                    '<span style="color:' . $t['phase_color'] . ';font-weight:bold;">' . $t['phase_icon'] . ' ' . htmlspecialchars($t['phase_name'], ENT_QUOTES, 'UTF-8') . '</span>'
                );

                $progressWidget = '<div style="min-width:110px;">'
                    . '<div style="font-size:0.88em;color:#f0f6fc;font-weight:bold;margin-bottom:2px;"><span class="dcc-ndb-countdown">' . htmlspecialchars($t['countdown_hms'], ENT_QUOTES, 'UTF-8') . '</span> (' . $t['progress_pct'] . '%)</div>'
                    . '<div style="background:#21262d;height:7px;border-radius:4px;overflow:hidden;border:1px solid #30363d;">'
                    . '<div class="dcc-ndb-bar-fill" style="background:' . $barColor . ';width:' . $t['progress_pct'] . '%;height:100%;"></div>'
                    . '</div></div>';

                addcharstat('Next Cycle', $progressWidget);
            }
            break;
    }

    return $args;
}
