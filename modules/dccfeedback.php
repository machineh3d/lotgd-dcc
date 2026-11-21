<?php

declare(strict_types=1);

/**
 * Module: Alpha Playtest Feedback & Bug Reporter (dccfeedback.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 *
 * Description:
 *   Gives Alpha/Beta testers a fast in-game form to submit bugs, spelling mistakes,
 *   balance feedback, and feature suggestions directly to the Showrunner Admin Inbox:
 *   - Automatically creates the `dcc_feedback` table on install/first run.
 *   - Captures Crawler Name, Level, Floor, Active Skin, Category, Area, and Message.
 *   - Optional 1/day Bronze Loot Box thank-you reward (configurable, integrates with `systemai`).
 *   - Includes a Showrunner Console Inbox to filter (Open / Resolved / All), resolve,
 *     reopen, or delete tester reports.
 */

function dccfeedback_getmoduleinfo(): array
{
    return [
        'name' => 'Alpha Playtest Feedback & Bug Reporter',
        'version' => '0.6.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Alpha Feedback Box Settings,title',
            'reward_bronze_box' => 'Award 1 Bronze Loot Box per day for submitting feedback (requires systemai)?,bool|1',
            'max_daily_reports' => 'Maximum feedback submissions per player per day,range,1,20,1|5',
        ],
        'prefs' => [
            'Alpha Feedback Telemetry,title',
            'reports_today' => 'Feedback reports submitted today,int|0',
            'reward_claimed_today' => 'Claimed daily feedback Bronze Box today?,bool|0',
        ],
    ];
}

function dccfeedback_ensure_table(): void
{
    $table = db_prefix('dcc_feedback');
    $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `acctid` INT UNSIGNED NOT NULL DEFAULT 0,
        `crawler_name` VARCHAR(120) NOT NULL DEFAULT '',
        `level` INT UNSIGNED NOT NULL DEFAULT 1,
        `floor_num` INT UNSIGNED NOT NULL DEFAULT 1,
        `skin` VARCHAR(80) NOT NULL DEFAULT '',
        `category` VARCHAR(40) NOT NULL DEFAULT 'General',
        `area` VARCHAR(120) NOT NULL DEFAULT '',
        `message` TEXT NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'open',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_status_created` (`status`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    db_query($sql);
}

function dccfeedback_install(): bool
{
    dccfeedback_ensure_table();
    module_addhook('village');
    module_addhook('superuser');
    module_addhook('newday');
    return true;
}

function dccfeedback_uninstall(): bool
{
    return true;
}

function dccfeedback_is_admin(): bool
{
    global $session;
    $su = (int) ($session['user']['superuser'] ?? 0);
    if ($su <= 0) {
        return false;
    }
    $noGrotto = defined('SU_DOESNT_GIVE_GROTTO') ? (int) SU_DOESNT_GIVE_GROTTO : 0;
    return ($noGrotto === 0) || (($su & ~$noGrotto) !== 0);
}

function dccfeedback_count_open(): int
{
    dccfeedback_ensure_table();
    $table = db_prefix('dcc_feedback');
    $res = db_query("SELECT COUNT(*) AS c FROM `{$table}` WHERE `status` = 'open'");
    $row = db_fetch_assoc($res);
    return (int) ($row['c'] ?? 0);
}

function dccfeedback_dohook(string $hookname, array $args): array
{
    switch ($hookname) {
        case 'newday':
            set_module_pref('reports_today', 0, 'dccfeedback');
            set_module_pref('reward_claimed_today', 0, 'dccfeedback');
            break;

        case 'village':
            tlschema($args['schemas']['infonav'] ?? 'village');
            addnav($args['infonav'] ?? 'Broadcast Feed');
            tlschema();
            addnav('`@Alpha Feedback & Bug Report`0', 'runmodule.php?module=dccfeedback');
            break;

        case 'superuser':
            if (dccfeedback_is_admin()) {
                $openCount = dccfeedback_count_open();
                addnav('Dungeon Crawler World');
                if ($openCount > 0) {
                    addnav(
                        ['`$Alpha Feedback Inbox (`^%d Open`$)`0', $openCount],
                        'runmodule.php?module=dccfeedback&op=admin'
                    );
                } else {
                    addnav('`#Alpha Feedback Inbox (0 Open)`0', 'runmodule.php?module=dccfeedback&op=admin');
                }
            }
            break;
    }

    return $args;
}

function dccfeedback_run(): void
{
    global $session;

    dccfeedback_ensure_table();
    $table = db_prefix('dcc_feedback');
    $op = (string) httpget('op');
    $isAdmin = dccfeedback_is_admin();

    // ========================================================================
    // 1. SHOWRUNNER ADMIN FEEDBACK INBOX (`op=admin`, `resolve`, `reopen`, `delete`)
    // ========================================================================
    if ($isAdmin && in_array($op, ['admin', 'resolve', 'reopen', 'delete'], true)) {
        page_header('Borant Showrunner Console — Alpha Feedback Inbox');

        $id = (int) httpget('id');
        if ($op === 'resolve' && $id > 0) {
            db_query("UPDATE `{$table}` SET `status` = 'resolved' WHERE `id` = {$id}");
            output('`@Marked report #%d as Resolved.`0`n`n', $id);
        } elseif ($op === 'reopen' && $id > 0) {
            db_query("UPDATE `{$table}` SET `status` = 'open' WHERE `id` = {$id}");
            output('`^Reopened report #%d.`0`n`n', $id);
        } elseif ($op === 'delete' && $id > 0) {
            db_query("DELETE FROM `{$table}` WHERE `id` = {$id}");
            output('`$Deleted report #%d.`0`n`n', $id);
        }

        $filter = (string) httpget('filter');
        if (!in_array($filter, ['open', 'resolved', 'all'], true)) {
            $filter = 'open';
        }

        $where = '';
        if ($filter === 'open') {
            $where = "WHERE `status` = 'open'";
        } elseif ($filter === 'resolved') {
            $where = "WHERE `status` = 'resolved'";
        }

        $res = db_query("SELECT * FROM `{$table}` {$where} ORDER BY `id` DESC LIMIT 100");
        output('`b`#=== ALPHA PLAYTEST FEEDBACK INBOX (%s) ===`b`0`n`n', strtoupper($filter));

        $count = 0;
        rawoutput('<table class="table table-striped table-dark"><thead><tr><th>#</th><th>Crawler &amp; Context</th><th>Category / Area</th><th>Feedback Details</th><th>Actions</th></tr></thead><tbody>');
        while ($row = db_fetch_assoc($res)) {
            $count++;
            $rid = (int) $row['id'];
            $status = (string) $row['status'];
            $statusBadge = ($status === 'open')
                ? '<span style="color:#ff2a85;font-weight:bold;">OPEN</span>'
                : '<span style="color:#3fb950;font-weight:bold;">RESOLVED</span>';

            $toggleOp = ($status === 'open') ? 'resolve' : 'reopen';
            $toggleLabel = ($status === 'open') ? 'Resolve' : 'Reopen';
            $toggleUrl = "runmodule.php?module=dccfeedback&op={$toggleOp}&id={$rid}&filter={$filter}";
            $delUrl = "runmodule.php?module=dccfeedback&op=delete&id={$rid}&filter={$filter}";
            addnav('', $toggleUrl);
            addnav('', $delUrl);

            rawoutput(sprintf(
                '<tr><td><strong>#%d</strong><br>%s</td><td><strong>%s</strong><br><small>Lv %d | Floor %d<br>Skin: %s<br>%s</small></td><td><strong>%s</strong><br><small>%s</small></td><td style="white-space:pre-wrap;min-width:220px;">%s</td><td style="white-space:nowrap;"><a href="%s" style="color:#00e5ff;font-weight:bold;">[%s]</a><br><a href="%s" style="color:#ff5555;">[Delete]</a></td></tr>',
                $rid,
                $statusBadge,
                htmlentities((string) $row['crawler_name'], ENT_QUOTES, 'UTF-8'),
                (int) $row['level'],
                (int) $row['floor_num'],
                htmlentities((string) $row['skin'], ENT_QUOTES, 'UTF-8'),
                htmlentities((string) $row['created_at'], ENT_QUOTES, 'UTF-8'),
                htmlentities((string) $row['category'], ENT_QUOTES, 'UTF-8'),
                htmlentities((string) $row['area'], ENT_QUOTES, 'UTF-8'),
                htmlentities((string) $row['message'], ENT_QUOTES, 'UTF-8'),
                htmlentities($toggleUrl, ENT_QUOTES, 'UTF-8'),
                $toggleLabel,
                htmlentities($delUrl, ENT_QUOTES, 'UTF-8')
            ));
        }
        if ($count === 0) {
            rawoutput('<tr><td colspan="5"><em>No feedback reports matching this filter.</em></td></tr>');
        }
        rawoutput('</tbody></table>');

        addnav('Filter Inbox');
        addnav('Open Reports', 'runmodule.php?module=dccfeedback&op=admin&filter=open');
        addnav('Resolved Reports', 'runmodule.php?module=dccfeedback&op=admin&filter=resolved');
        addnav('All Reports', 'runmodule.php?module=dccfeedback&op=admin&filter=all');

        addnav('Return');
        addnav('Submit New Feedback', 'runmodule.php?module=dccfeedback');
        addnav('Return to Showrunner Console', 'superuser.php');
        addnav('Return to Safe Room Hub', 'village.php');
        page_footer();
        return;
    }

    // ========================================================================
    // 2. PLAYER FEEDBACK SUBMISSION FORM
    // ========================================================================
    page_header('Alpha Playtest Feedback & Bug Reporter');

    $reportsToday = (int) get_module_pref('reports_today', 'dccfeedback');
    $maxDaily = max(1, (int) get_module_setting('max_daily_reports', 'dccfeedback'));

    if ($op === 'submit') {
        $category = trim((string) httppost('category'));
        $area = trim((string) httppost('area'));
        $message = trim((string) httppost('message'));

        $allowedCats = [
            'Bug / PHP Error',
            'Spelling / Typo',
            'Balance / Economy',
            'Feature Suggestion',
            'UI / Mobile Layout',
            'General Thoughts',
        ];
        if (!in_array($category, $allowedCats, true)) {
            $category = 'General Thoughts';
        }

        if ($reportsToday >= $maxDaily) {
            output('`$`b[DAILY LIMIT REACHED]:`b `7You have already submitted `^%d / %d`7 feedback reports today. Thank you for testing!`0`n`n', $reportsToday, $maxDaily);
        } elseif (strlen($message) < 6) {
            output('`$`b[TOO SHORT]:`b `7Please include a few words describing the bug, typo, or suggestion.`0`n`n');
        } else {
            $acctId = (int) ($session['user']['acctid'] ?? 0);
            $rawName = (string) ($session['user']['name'] ?? 'Unknown Crawler');
            $cleanName = (string) preg_replace('/`./', '', $rawName);
            $level = max(1, (int) ($session['user']['level'] ?? 1));
            $floorNum = max(1, (int) ($session['user']['dragonkills'] ?? 0) + 1);
            $skin = (string) ($session['user']['prefs']['template'] ?? getsetting('defaultskin', 'dcc_hud_minimal'));

            $sql = sprintf(
                "INSERT INTO `%s` (`acctid`, `crawler_name`, `level`, `floor_num`, `skin`, `category`, `area`, `message`, `status`) VALUES (%d, '%s', %d, %d, '%s', '%s', '%s', '%s', 'open')",
                $table,
                $acctId,
                \Lotgd\MySQL\Database::escape(substr($cleanName, 0, 120)),
                $level,
                $floorNum,
                \Lotgd\MySQL\Database::escape(substr($skin, 0, 80)),
                \Lotgd\MySQL\Database::escape(substr($category, 0, 40)),
                \Lotgd\MySQL\Database::escape(substr($area, 0, 120)),
                \Lotgd\MySQL\Database::escape(substr($message, 0, 4000))
            );
            db_query($sql);

            $reportsToday++;
            set_module_pref('reports_today', $reportsToday, 'dccfeedback');

            output('`@`b[FEEDBACK LOGGED — THANK YOU, CRAWLER!]:`b `2Your `^%s`2 report has been sent directly to the Showrunner Admin Inbox!`0`n', $category);

            // Optional 1/day Bronze Loot Box thank-you reward
            if (
                (int) get_module_setting('reward_bronze_box', 'dccfeedback') === 1
                && !(bool) get_module_pref('reward_claimed_today', 'dccfeedback')
                && is_module_active('systemai')
            ) {
                set_module_pref('reward_claimed_today', 1, 'dccfeedback');
                if (function_exists('systemai_grant_box')) {
                    systemai_grant_box('bronze', 1);
                } else {
                    $cur = (int) get_module_pref('box_bronze', 'systemai');
                    set_module_pref('box_bronze', $cur + 1, 'systemai');
                }
                output('`^`b[ALPHA TESTER BONUS]:`b `@The System AI dropped `^1 Bronze Loot Box`@ into your Safe Room Vault for helping test the Dungeon today!`0`n');
            }
            output('`n');
        }
    }

    output('`b`#=== DUNGEON CRAWLER WORLD — ALPHA FEEDBACK & BUG REPORTER ===`b`0`n');
    output('`3Spotted a bug, PHP warning, spelling mistake, mobile layout issue, or have an idea for a new achievement or feature? Drop it below and it goes straight to the Showrunner Admin Inbox! `7(Submissions today: `^%d / %d`7)`0`n`n', $reportsToday, $maxDaily);

    $formAction = 'runmodule.php?module=dccfeedback&op=submit';
    addnav('', $formAction);

    rawoutput('<form action="' . $formAction . '" method="POST" style="background:#111823;border:1px solid #263447;border-radius:8px;padding:16px;max-width:720px;">');
    rawoutput('<div style="margin-bottom:12px;"><label style="display:block;color:#00e5ff;font-weight:bold;margin-bottom:4px;">Category</label>');
    rawoutput('<select name="category" style="width:100%;">');
    foreach (['Bug / PHP Error', 'Spelling / Typo', 'Balance / Economy', 'Feature Suggestion', 'UI / Mobile Layout', 'General Thoughts'] as $cat) {
        rawoutput('<option value="' . htmlentities($cat, ENT_QUOTES, 'UTF-8') . '">' . htmlentities($cat, ENT_QUOTES, 'UTF-8') . '</option>');
    }
    rawoutput('</select></div>');

    rawoutput('<div style="margin-bottom:12px;"><label style="display:block;color:#00e5ff;font-weight:bold;margin-bottom:4px;">Page / Area (optional)</label>');
    rawoutput('<input type="text" name="area" placeholder="e.g., Bopca Safe Room, Dungeon Corridors, FAQ, Mobile HUD..." style="width:100%;" maxlength="120"></div>');

    rawoutput('<div style="margin-bottom:12px;"><label style="display:block;color:#00e5ff;font-weight:bold;margin-bottom:4px;">Feedback / Details</label>');
    rawoutput('<textarea name="message" rows="6" placeholder="Describe what happened, what typo you spotted, or your suggestion..." style="width:100%;" required></textarea></div>');

    rawoutput('<button type="submit" style="background:#00e5ff;color:#0a0e14;border:none;font-weight:900;padding:10px 18px;border-radius:6px;">Send Feedback to Showrunners</button>');
    rawoutput('</form>');

    addnav('Feedback Actions');
    if ($isAdmin) {
        addnav(['`$Open Admin Feedback Inbox (%d Open)`0', dccfeedback_count_open()], 'runmodule.php?module=dccfeedback&op=admin');
    }
    addnav('Return to Safe Room Hub', 'village.php');

    page_footer();
}