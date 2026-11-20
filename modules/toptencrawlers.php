<?php

declare(strict_types=1);

/**
 * Module: Top 10 Crawler Leaderboard, Bounties & Odette's After Hours (toptencrawlers.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Dungeon_Crawler_Carl_Wiki
 *   - https://tvtropes.org/pmwiki/pmwiki.php/Literature/DungeonCrawlerCarl
 *
 * Description:
 *   Implements the Syndicate's Top 10 Crawler Leaderboard and Bounty system:
 *   - Ranks the top 10 Crawlers across the server by Floors Descended, Level, EXP, and Viewer Ratings.
 *   - Places escalating Borant Head Bounties on every Top 10 Crawler (plus optional player Hit Tickets).
 *   - Pays out the full Head Bounty + Sponsor Tokens as a Survival Bonus whenever a Top 10 Crawler
 *     clears a Floor Guardian and descends the Stairwell (`dragonkill` hook).
 *   - Awards the Head Bounty and a Player Killer (PK) Skull (`pk_skulls`) when a Top 10 Crawler is
 *     slain in PvP (`pvpwin` hook).
 *   - Includes Odette's "Dungeon Crawler After Hours" broadcast studio where Crawlers can do
 *     daily talk-show interviews with Odette and Zev for Sponsor rewards.
 */

function toptencrawlers_getmoduleinfo(): array
{
    return [
        'name' => 'Top 10 Crawler Leaderboard & Odette\'s After Hours',
        'version' => '1.0.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Top 10 Bounty & Broadcast Settings,title',
            'rank1_base_bounty' => 'Base Gold Credit Bounty for Rank #1 Crawler,int|25000',
            'bounty_floor_scale' => 'Additional Gold Credit Bounty per Floor Descended (DK),int|1500',
            'min_charm_for_guest' => 'Minimum Viewer Ratings (Charm) to guest on Odette\'s show if outside Top 10,int|15',
            'allow_hit_tickets' => 'Allow Crawlers to add private Gold Credit Hit Tickets to rivals?,bool|1',
        ],
        'prefs' => [
            'Syndicate Leaderboard & PK Ledger,title',
            'custom_bounty' => 'Extra Hit-Ticket Gold Bounty placed on this Crawler,int|0',
            'pk_skulls' => 'Player Killer (PK) Skulls earned from PvP kills,int|0',
            'bounties_claimed' => 'Lifetime Top 10 Head Bounties claimed in PvP,int|0',
            'survival_payouts' => 'Lifetime Top 10 Stairwell Survival Bonuses earned,int|0',
            'interviewed_today' => 'Appeared on Dungeon Crawler After Hours with Odette today?,bool|0',
        ],
    ];
}

function toptencrawlers_install(): bool
{
    module_addhook('village');
    module_addhook('hof');
    module_addhook('newday');
    module_addhook('dragonkill');
    module_addhook('pvpwin');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function toptencrawlers_uninstall(): bool
{
    return true;
}

/**
 * Queries the top 10 Crawlers from the `accounts` table and computes each Crawler's
 * current Rank (1-10) and total active Gold Credit Head Bounty.
 *
 * @return array<int, array{
 *   rank: int,
 *   acctid: int,
 *   name: string,
 *   level: int,
 *   dks: int,
 *   floor: int,
 *   charm: int,
 *   alive: bool,
 *   base_bounty: int,
 *   extra_bounty: int,
 *   total_bounty: int,
 *   pk_skulls: int
 * }>
 */
function toptencrawlers_get_leaderboard(): array
{
    $accountsTable = db_prefix('accounts');
    $sql = "SELECT acctid, name, level, dragonkills, experience, charm, alive
            FROM {$accountsTable}
            WHERE locked = 0
            ORDER BY dragonkills DESC, level DESC, experience DESC, charm DESC, acctid ASC
            LIMIT 10";
    $res = db_query($sql);

    $rank1Base = max(1000, (int) get_module_setting('rank1_base_bounty', 'toptencrawlers'));
    $floorScale = max(100, (int) get_module_setting('bounty_floor_scale', 'toptencrawlers'));

    // Multipliers for Ranks 1 through 10
    $rankMultipliers = [
        1 => 1.00,
        2 => 0.75,
        3 => 0.60,
        4 => 0.48,
        5 => 0.40,
        6 => 0.32,
        7 => 0.26,
        8 => 0.20,
        9 => 0.16,
        10 => 0.12,
    ];

    $board = [];
    $rank = 1;
    while ($row = db_fetch_assoc($res)) {
        $acctId = (int) ($row['acctid'] ?? 0);
        $dks = max(0, (int) ($row['dragonkills'] ?? 0));
        $floor = $dks + 1;
        $mult = $rankMultipliers[$rank] ?? 0.10;

        $baseBounty = (int) round(($rank1Base * $mult) + ($dks * $floorScale * $mult));
        $extraBounty = max(0, (int) get_module_pref('custom_bounty', 'toptencrawlers', $acctId));
        $pkSkulls = max(0, (int) get_module_pref('pk_skulls', 'toptencrawlers', $acctId));

        $board[$acctId] = [
            'rank' => $rank,
            'acctid' => $acctId,
            'name' => (string) ($row['name'] ?? 'Unknown Crawler'),
            'level' => (int) ($row['level'] ?? 1),
            'dks' => $dks,
            'floor' => $floor,
            'charm' => (int) ($row['charm'] ?? 0),
            'alive' => ((int) ($row['alive'] ?? 1) === 1),
            'base_bounty' => $baseBounty,
            'extra_bounty' => $extraBounty,
            'total_bounty' => $baseBounty + $extraBounty,
            'pk_skulls' => $pkSkulls,
        ];
        $rank++;
    }

    return $board;
}

function toptencrawlers_dohook(string $hookname, array $args): array
{
    global $session;

    $myId = (int) ($session['user']['acctid'] ?? 0);

    switch ($hookname) {
        case 'village':
            tlschema($args['schemas']['marketnav'] ?? 'village');
            addnav($args['marketnav'] ?? 'Safe Room Facilities');
            tlschema();
            addnav('`5Top 10 Crawlers & Odette\'s Show`0', 'runmodule.php?module=toptencrawlers');
            break;

        case 'hof':
            addnav('Syndicate Broadcast');
            addnav('`5Top 10 Crawler Bounty Board`0', 'runmodule.php?module=toptencrawlers');
            break;

        case 'newday':
            set_module_pref('interviewed_today', 0, 'toptencrawlers');

            $board = toptencrawlers_get_leaderboard();
            if (isset($board[$myId])) {
                $entry = $board[$myId];
                output(
                    "`n`5[SYNDICATE TOP 10 HOTLIST ALERT]: `3You are currently ranked `^#%d in the World Dungeon`3 with a `^%s Gold Credit Bounty`3 on your head! Survive to the next Stairwell to claim your own bounty—or watch your back in PvP!`0`n",
                    $entry['rank'],
                    number_format($entry['total_bounty'])
                );
            }
            break;

        case 'dragonkill':
            $board = toptencrawlers_get_leaderboard();
            if (isset($board[$myId])) {
                $entry = $board[$myId];
                $bountyPayout = $entry['total_bounty'];
                $tokenBonus = max(2, 12 - $entry['rank']);

                $session['user']['gold'] += $bountyPayout;
                $session['user']['gems'] += $tokenBonus;

                // Reset custom hit-ticket bounty since they survived the floor
                set_module_pref('custom_bounty', 0, 'toptencrawlers');
                $survivals = (int) get_module_pref('survival_payouts', 'toptencrawlers') + 1;
                set_module_pref('survival_payouts', $survivals, 'toptencrawlers');

                output("`n`c`b`5=== TOP 10 CRAWLER STAIRWELL SURVIVAL BONUS! ===`0`b`c`n");
                output(
                    "`2Because you descended the Stairwell while ranked `^#%d on the Syndicate Top 10 List`2, Borant is forced to pay out your entire head bounty of `^%s Gold Credits`2 and `5%d Sponsor Tokens`2 directly to you!`0`n",
                    $entry['rank'],
                    number_format($bountyPayout),
                    $tokenBonus
                );

                // Award bonus Loot Box if `systemai` module is active
                if (function_exists('is_module_active') && is_module_active('systemai')) {
                    if ($entry['rank'] <= 3) {
                        $curGold = (int) get_module_pref('box_gold', 'systemai');
                        set_module_pref('box_gold', $curGold + 1, 'systemai');
                        output("`5[SYSTEM AI]: `^\"Top 3 Survival! Have a bonus `6Gold Hotlist Box`^ in your Safe Room Vault!\"`0`n");
                    } else {
                        $curSilver = (int) get_module_pref('box_silver', 'systemai');
                        set_module_pref('box_silver', $curSilver + 1, 'systemai');
                        output("`5[SYSTEM AI]: `3\"Top 10 Survival! Have a bonus `7Silver Hotlist Box`3 in your Safe Room Vault!\"`0`n");
                    }
                }

                addnews(
                    '`5TOP 10 SURVIVAL PAYOUT:`0 `@%s`2 (Ranked `#%d`2) survived the Floor and collected their own `^%s Gold Credit`2 head bounty at the Stairwell!',
                    (string) ($session['user']['name'] ?? 'A Crawler'), $entry['rank'], number_format($bountyPayout)
                );
            }
            break;

        case 'pvpwin':
            $badguyId = (int) ($args['badguy']['acctid'] ?? 0);
            $skulls = (int) get_module_pref('pk_skulls', 'toptencrawlers') + 1;
            set_module_pref('pk_skulls', $skulls, 'toptencrawlers');

            $board = toptencrawlers_get_leaderboard();
            if ($badguyId > 0 && isset($board[$badguyId])) {
                $target = $board[$badguyId];
                $bounty = $target['total_bounty'];

                $session['user']['gold'] += $bounty;
                $session['user']['gems'] += 3;
                set_module_pref('custom_bounty', 0, 'toptencrawlers', $badguyId);

                $claimed = (int) get_module_pref('bounties_claimed', 'toptencrawlers') + 1;
                set_module_pref('bounties_claimed', $claimed, 'toptencrawlers');

                output("`n`c`b`\$=== SYNDICATE TOP 10 HEAD BOUNTY CLAIMED! ===`0`b`c`n");
                output(
                    "`4You have assassinated `^#%d Ranked Crawler %s`4! A flashing `\$Player Killer Skull (Total: %d)`4 appears beside your name as Borant wires `^%s Gold Credits`4 and `53 Sponsor Tokens`4 to your account!`0`n",
                    $target['rank'],
                    $target['name'],
                    $skulls,
                    number_format($bounty)
                );

                addnews(
                    '`$PLAYER KILLER ALERT:`0 `@%s`4 assassinated Top 10 Crawler `^%s`4 (Rank `#%d`4) and claimed a `^%s Gold Credit`4 head bounty!',
                    (string) ($session['user']['name'] ?? 'A Hunter'), $target['name'], $target['rank'], number_format($bounty)
                );
            } else {
                output("`n`\$[PLAYER KILLER TELEMETRY]: `4Your PK Skull count has increased to `^%d`4!`0`n", $skulls);
            }
            break;

        case 'charstats':
            $board = toptencrawlers_get_leaderboard();
            $skulls = (int) get_module_pref('pk_skulls', 'toptencrawlers');
            addcharstat('Syndicate Ranking');
            if (isset($board[$myId])) {
                addcharstat('Top 10 Rank', sprintf('`^#%d`0 (`$%sG`0)', $board[$myId]['rank'], number_format($board[$myId]['total_bounty'])));
            } else {
                addcharstat('Top 10 Rank', '`7Unranked`0');
            }
            if ($skulls > 0) {
                addcharstat('PK Skulls', sprintf('`$&#128128; x%d`0', $skulls));
            }
            break;

        case 'bioinfo':
            $targetId = (int) ($args['acctid'] ?? 0);
            if ($targetId > 0) {
                $board = toptencrawlers_get_leaderboard();
                $tSkulls = (int) get_module_pref('pk_skulls', 'toptencrawlers', $targetId);
                $tSurvivals = (int) get_module_pref('survival_payouts', 'toptencrawlers', $targetId);
                if (isset($board[$targetId]) || $tSkulls > 0 || $tSurvivals > 0) {
                    output(
                        "`n`5Syndicate Hotlist Status:`0 %s | `\$PK Skulls: %d`0 | `@Top 10 Survivals: %d`0`n",
                        isset($board[$targetId])
                            ? sprintf('`^Ranked #%d (Bounty: %s Gold)`0', $board[$targetId]['rank'], number_format($board[$targetId]['total_bounty']))
                            : '`7Unranked`0',
                        $tSkulls,
                        $tSurvivals
                    );
                }
            }
            break;
    }

    return $args;
}

function toptencrawlers_run(): void
{
    global $session;

    page_header('Top 10 Crawler Leaderboard & Odette\'s After Hours');

    $op = (string) httpget('op');
    $myId = (int) ($session['user']['acctid'] ?? 0);
    $board = toptencrawlers_get_leaderboard();
    $isTopTen = isset($board[$myId]);
    $minCharm = (int) get_module_setting('min_charm_for_guest', 'toptencrawlers');
    $canGuest = $isTopTen || ((int) ($session['user']['charm'] ?? 0) >= $minCharm);

    // ========================================================================
    // ODETTE'S "DUNGEON CRAWLER AFTER HOURS" INTERVIEW HANDLER
    // ========================================================================
    if ($op === 'interview') {
        $choice = (string) httpget('topic');
        if (!$canGuest) {
            output("`\$PR Agent **Zev** shakes her head. \"Sorry! Only **Top 10 Crawlers** or high-profile celebrities with at least `^%d Viewer Ratings (Charm)`\$ get invited onto Odette's couch!\"`0`n`n", $minCharm);
        } elseif ((int) get_module_pref('interviewed_today', 'toptencrawlers') === 1) {
            output("`\$**Zev** checks her tablet. \"You already filmed your *Dungeon Crawler After Hours* segment for this broadcast cycle!\"`0`n`n");
        } elseif ($choice === '') {
            output("`3You are teleported out of the Dungeon into aZero-Zone broadcast trailer! Former Crawler **Odette** welcomes you onto the curved stage in her gleaming metallic crab-suit while **Zev** signals from the control booth.`0`n`n");
            output("`2\"Welcome to *Dungeon Crawler After Hours*, darling! Quintillions of citizens are watching. How do you want to play tonight's interview?\"`0`n`n");

            addnav('Choose Interview Angle');
            addnav('Plug Sponsor Products (Gold)', 'runmodule.php?module=toptencrawlers&op=interview&topic=sponsor');
            addnav('Trash-Talk the Skull Empire (Buff)', 'runmodule.php?module=toptencrawlers&op=interview&topic=roast');
            addnav('Share Mordecai\'s Survival Tips (Tokens)', 'runmodule.php?module=toptencrawlers&op=interview&topic=tips');
            addnav('Return to Leaderboard', 'runmodule.php?module=toptencrawlers');
            page_footer();
            return;
        } else {
            set_module_pref('interviewed_today', 1, 'toptencrawlers');
            $lvl = max(1, (int) ($session['user']['level'] ?? 1));

            if ($choice === 'sponsor') {
                $gold = e_rand(400, 750) * $lvl * ($isTopTen ? 2 : 1);
                $session['user']['gold'] += $gold;
                $session['user']['charm'] += 2;
                output("`@[SPONSOR SEGMENT SMASH HIT!]: `2You shamelessly plug Rev-Up Moonshine and Enchanted BigBoi Boxers on camera! Zev beams as `^%s Gold Credits`2 and `@+2 Viewer Ratings`2 hit your account!`0`n`n", number_format($gold));
            } elseif ($choice === 'roast') {
                $session['user']['charm'] += 3;
                apply_buff('odette_after_hours_hype', [
                    'name' => '`5After Hours Galactic Hype Aura`0',
                    'rounds' => 35,
                    'wearoff' => 'The post-interview adrenaline finally fades.',
                    'atkmod' => 1.35,
                    'defmod' => 1.25,
                    'roundmsg' => '`5Trillions of viewers cheer in your HUD feed as you strike {badguy}!`0',
                    'schema' => 'module-toptencrawlers',
                ]);
                output("`@[GALACTIC VIRAL MOMENT!]: `2You mock Prince Maestro and the Skull Empire to their faces on live television! You gain `@+3 Viewer Ratings`2 and the `5After Hours Galactic Hype Aura`2 (`+35 percent ATK / +25 percent DEF for 35 rounds`)!`0`n`n");
                addnews(
                    '`5AFTER HOURS VIRAL CLIP:`0 `@%s`2 roasted the Skull Empire live on **Odette\'s After Hours** to thunderous galactic applause!',
                    (string) ($session['user']['name'] ?? 'A Crawler')
                );
            } else {
                $tokens = $isTopTen ? 5 : 3;
                $session['user']['gems'] += $tokens;
                $session['user']['turns'] += 2;
                output("`@[INSIDER SURVIVAL SEGMENT]: `2Odette discreetly slips you a floor hint between commercial breaks! You gain `5%d Sponsor Tokens`2 and `@+2 Floor Stamina`2!`0`n`n", $tokens);
            }
        }
    }

    // ========================================================================
    // ADD HIT-TICKET BOUNTY HANDLER
    // ========================================================================
    elseif ($op === 'add_bounty' && (int) get_module_setting('allow_hit_tickets', 'toptencrawlers') === 1) {
        $targetId = (int) httppost('target_id');
        $amount = (int) httppost('amount');

        if ($targetId <= 0 || !isset($board[$targetId])) {
            output("`\$Select a valid Top 10 Crawler to place a Hit Ticket on.`0`n`n");
        } elseif ($amount < 500) {
            output("`\$Minimum Hit Ticket bounty addition is `^500 Gold Credits`\$.`0`n`n");
        } elseif ((int) ($session['user']['gold'] ?? 0) < $amount) {
            output("`\$You don't have `^%s Gold Credits`\$ on hand to fund that Hit Ticket!`0`n`n", number_format($amount));
        } else {
            $session['user']['gold'] -= $amount;
            $curExtra = (int) get_module_pref('custom_bounty', 'toptencrawlers', $targetId);
            set_module_pref('custom_bounty', $curExtra + $amount, 'toptencrawlers', $targetId);
            $board = toptencrawlers_get_leaderboard();

            output(
                "`4[DESPERADO HIT TICKET ISSUED]: `2You anonymously added `^%s Gold Credits`2 to **%s**'s head bounty (New Total: `^%s Gold Credits`2)!`0`n`n",
                number_format($amount),
                $board[$targetId]['name'],
                number_format($board[$targetId]['total_bounty'])
            );
        }
    }

    // ========================================================================
    // RENDER TOP 10 CRAWLER LEADERBOARD & BOUNTY TABLE
    // ========================================================================
    rawoutput('
    <div style="background:linear-gradient(135deg,#0d1117 0%,#1f0d29 55%,#0d1117 100%);border:2px solid #ff2a85;border-radius:8px;padding:14px 18px;margin-bottom:14px;color:#f0f6fc;font-family:Consolas,monospace;box-shadow:0 0 18px rgba(255,42,133,0.3);">
        <div style="font-size:1.28em;font-weight:900;color:#ff2a85;letter-spacing:1.4px;">&#127942; SYNDICATE TOP 10 CRAWLER HOTLIST &amp; BOUNTY BOARD &#127942;</div>
        <div style="font-size:0.9em;color:#00f0ff;margin-top:4px;">Broadcast live across 3 billion star systems • Hosted by Odette &amp; PR Agent Zev</div>
        <div style="font-size:0.84em;color:#c9d1d9;margin-top:6px;">
            • <strong>Stairwell Survival Rule:</strong> Any Top 10 Crawler who defeats their Floor Guardian collects their own entire Head Bounty!<br>
            • <strong>Player Killer Rule:</strong> Any Crawler who eliminates a Top 10 Crawler in PvP steals their Head Bounty and earns a &#128128; PK Skull!
        </div>
    </div>
    ');

    rawoutput('<table border="0" cellpadding="7" cellspacing="1" width="100%" style="background:#0d1117;border:1px solid #30363d;margin-bottom:16px;font-family:Consolas,monospace;">');
    rawoutput('<tr style="background:#161b22;color:#00f0ff;font-weight:bold;"><td>Rank</td><td>Crawler</td><td>Floor / Level</td><td>Viewer Ratings</td><td>PK Skulls</td><td>Active Head Bounty</td></tr>');

    foreach ($board as $acctId => $entry) {
        $isMe = ($acctId === $myId);
        $rowBg = $isMe ? '#1c2536' : '#0d1117';
        $rankBadge = ($entry['rank'] <= 3)
            ? '<span style="color:#ffd700;font-weight:900;">#' . $entry['rank'] . ' &#9733;</span>'
            : '<span style="color:#8b949e;font-weight:bold;">#' . $entry['rank'] . '</span>';

        $cleanName = (string) preg_replace('/`./', '', $entry['name']);
        $statusDot = $entry['alive']
            ? '<span style="color:#3fb950;">&#9679; Active</span>'
            : '<span style="color:#f85149;">&#9679; Reconstructing</span>';
        $skullStr = ($entry['pk_skulls'] > 0)
            ? '<span style="color:#f85149;font-weight:bold;">&#128128; x' . $entry['pk_skulls'] . '</span>'
            : '<span style="color:#6e7681;">—</span>';

        rawoutput('<tr style="background:' . $rowBg . ';border-bottom:1px solid #21262d;">');
        rawoutput('<td>' . $rankBadge . '</td>');
        rawoutput('<td style="color:#f0f6fc;font-weight:bold;">' . htmlspecialchars($cleanName, ENT_QUOTES, 'UTF-8') . ' <small>(' . $statusDot . ')</small></td>');
        rawoutput('<td style="color:#00f0ff;">Floor ' . $entry['floor'] . ' <small style="color:#8b949e;">(Lv ' . $entry['level'] . ')</small></td>');
        rawoutput('<td style="color:#ff2a85;">' . number_format($entry['charm']) . '</td>');
        rawoutput('<td>' . $skullStr . '</td>');
        rawoutput('<td style="color:#ffd700;font-weight:bold;">' . number_format($entry['total_bounty']) . ' Gold' . ($entry['extra_bounty'] > 0 ? ' <small style="color:#f85149;">(+' . number_format($entry['extra_bounty']) . ' Hit Ticket)</small>' : '') . '</td>');
        rawoutput('</tr>');
    }
    rawoutput('</table>');

    // Optional Hit-Ticket Form
    if ((int) get_module_setting('allow_hit_tickets', 'toptencrawlers') === 1 && $board !== []) {
        rawoutput('<form action="runmodule.php?module=toptencrawlers&op=add_bounty" method="POST" style="background:#161b22;border:1px solid #30363d;border-radius:6px;padding:10px 14px;color:#c9d1d9;font-family:Consolas,monospace;margin-bottom:14px;">');
        addnav('', 'runmodule.php?module=toptencrawlers&op=add_bounty');
        rawoutput('<strong style="color:#ff2a85;">&#127915; Issue Anonymous Desperado Hit Ticket:</strong> ');
        rawoutput('<select name="target_id" style="background:#0d1117;color:#f0f6fc;border:1px solid #30363d;padding:3px 6px;margin:0 6px;">');
        foreach ($board as $acctId => $entry) {
            $cleanName = (string) preg_replace('/`./', '', $entry['name']);
            rawoutput('<option value="' . $acctId . '">#' . $entry['rank'] . ' ' . htmlspecialchars($cleanName, ENT_QUOTES, 'UTF-8') . '</option>');
        }
        rawoutput('</select>');
        rawoutput(' Add Gold: <input type="number" name="amount" min="500" step="500" value="1000" style="width:90px;padding:3px;"> ');
        rawoutput('<input type="submit" class="button" value="Post Hit Ticket">');
        rawoutput('</form>');
    }

    addnav('Dungeon Crawler After Hours');
    if ((int) get_module_pref('interviewed_today', 'toptencrawlers') === 0) {
        addnav('`5Guest on Odette\'s Show`0', 'runmodule.php?module=toptencrawlers&op=interview');
    }
    addnav('Refresh Leaderboard', 'runmodule.php?module=toptencrawlers');

    addnav('Return');
    addnav('Return to Safe Room', 'village.php');
    addnav('Hall of Fame', 'hof.php');

    page_footer();
}