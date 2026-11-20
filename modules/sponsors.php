<?php

declare(strict_types=1);

/**
 * Module: Syndicate Sponsors, Benefactor Boxes & Viewer Fan Boxes (sponsors.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Sponsors
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Loot_Boxes
 *
 * Description:
 *   Implements the Syndicate Patronage & Sponsor Auction system:
 *   - Crawlers can hold up to 3 Syndicate Sponsor slots (Slot 1 unlocks immediately for testing
 *     or Floor 1+, Slot 2 on Floor 2+, Slot 3 on Floor 4+ — configurable in settings).
 *   - Choose from canonical Syndicate Patrons (Open Intellect Pacifist Action Network, Valtay
 *     Corporation, Prism Industries, The Plenty, Long Haul Biological Waste Management Solutions)
 *     each delivering daily Benefactor Stipends and coded AI-filtered notes.
 *   - Periodic Benefactor Loot Boxes (`systemai` integration) plus Viewer-Voted Fan Boxes
 *     funded by your Viewer Ratings (Charm) and tunnel highlights!
 */

function sponsors_getmoduleinfo(): array
{
    return [
        'name' => 'Syndicate Sponsors, Benefactor Boxes & Fan Boxes',
        'version' => '0.3.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Syndicate Sponsorship Settings,title',
            'slot1_min_floor' => 'Floor required to unlock Sponsor Slot #1 (default 1),range,1,10,1|1',
            'slot2_min_floor' => 'Floor required to unlock Sponsor Slot #2 (default 2),range,1,10,1|2',
            'slot3_min_floor' => 'Floor required to unlock Sponsor Slot #3 (default 4),range,1,10,1|4',
            'fan_box_charm_req' => 'Minimum Viewer Ratings (Charm) to trigger a daily Viewer Fan Box vote,int|10',
            'borant_commission_pct' => 'Borant Corp commission percent skimmed from cash stipends (0-40),range,0,40,5|15',
        ],
        'prefs' => [
            'Crawler Sponsorship Ledger,title',
            'patron_slot_1' => 'Sponsor ID in Patron Slot #1,text|',
            'patron_slot_2' => 'Sponsor ID in Patron Slot #2,text|',
            'patron_slot_3' => 'Sponsor ID in Patron Slot #3,text|',
            'stipend_claimed_today' => 'Claimed daily Benefactor Stipend & Note today?,bool|0',
            'fan_box_claimed_today' => 'Opened Viewer-Voted Fan Box today?,bool|0',
            'benefactor_boxes_received' => 'Lifetime Benefactor & Fan Boxes received,int|0',
            'sponsor_favor' => 'Syndicate PR Favor points toward next Benefactor Box,int|0',
        ],
    ];
}

function sponsors_install(): bool
{
    module_addhook('village');
    module_addhook('newday');
    module_addhook('dragonkill');
    module_addhook('battle-victory');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function sponsors_uninstall(): bool
{
    return true;
}

/**
 * Returns the Crawler's current Floor (dragonkills + 1).
 */
function sponsors_get_floor(?int $userId = null): int
{
    global $session;

    if ($userId === null) {
        $dks = (int) ($session['user']['dragonkills'] ?? 0);
    } else {
        $sql = 'SELECT dragonkills FROM ' . db_prefix('accounts') . ' WHERE acctid = ' . (int) $userId;
        $res = db_query($sql);
        $row = db_fetch_assoc($res);
        $dks = (int) ($row['dragonkills'] ?? 0);
    }

    return max(1, $dks + 1);
}

/**
 * Returns the catalog of canonical Syndicate Sponsors & Benefactors.
 *
 * @return array<string, array{
 *   name: string,
 *   short: string,
 *   color: string,
 *   gold_per_lvl: int,
 *   gems_bonus: int,
 *   perk_desc: string,
 *   coded_note: string
 * }>
 */
function sponsors_get_catalog(): array
{
    return [
        'oipan' => [
            'name' => 'Open Intellect Pacifist Action Network',
            'short' => 'OIPAN',
            'color' => '`#',
            'gold_per_lvl' => 95,
            'gems_bonus' => 1,
            'perk_desc' => 'High-utility survival stipends & cryptic anti-Syndicate intel notes',
            'coded_note' => 'AI-TRANSLATED NOTE: "The cookbook pages remember what the showrunners erased. Keep your boots dry on Floor Nine."',
        ],
        'prism' => [
            'name' => 'Prism Industries (Princess D\'nadia)',
            'short' => 'Prism Industries',
            'color' => '`5',
            'gold_per_lvl' => 110,
            'gems_bonus' => 1,
            'perk_desc' => 'Luxury Viewer Ratings (Charm) boosts & polished royal equipment buffs',
            'coded_note' => 'AI-TRANSLATED NOTE: "Darling, your lighting in the third corridor was atrocious. Wear something shinier for the sponsors."',
        ],
        'plenty' => [
            'name' => 'The Plenty (Galactic Banking Cartel)',
            'short' => 'The Plenty',
            'color' => '`^',
            'gold_per_lvl' => 165,
            'gems_bonus' => 0,
            'perk_desc' => 'Maximum Gold Credit payouts (even after Borant\'s commission)',
            'coded_note' => 'AI-TRANSLATED NOTE: "Market volatility favors aggressive liquidators. Survive two more cycles to maximize our quarterly yield."',
        ],
        'valtay' => [
            'name' => 'Valtay Corporation',
            'short' => 'Valtay Corp',
            'color' => '`@',
            'gold_per_lvl' => 115,
            'gems_bonus' => 1,
            'perk_desc' => 'Balanced tactical upgrades & combat stimulants',
            'coded_note' => 'AI-TRANSLATED NOTE: "Borant\'s management is slipping. Expect an abrupt change in showrunner ownership soon."',
        ],
        'longhaul' => [
            'name' => 'Long Haul Biological Waste Management Solutions',
            'short' => 'Long Haul Bio',
            'color' => '`$',
            'gold_per_lvl' => 125,
            'gems_bonus' => 2,
            'perk_desc' => 'Generous Sponsor Token (Gem) drops & suspiciously elite Celestial box odds',
            'coded_note' => 'AI-TRANSLATED NOTE: "Do not ask why a waste-hauling shell company has a Celestial budget. Just open the crate and stay alive."',
        ],
    ];
}

/**
 * Counts how many Sponsor slots the Crawler has unlocked by Floor.
 */
function sponsors_max_slots(int $floor): int
{
    $slots = 0;
    if ($floor >= (int) get_module_setting('slot1_min_floor', 'sponsors')) {
        $slots++;
    }
    if ($floor >= (int) get_module_setting('slot2_min_floor', 'sponsors')) {
        $slots++;
    }
    if ($floor >= (int) get_module_setting('slot3_min_floor', 'sponsors')) {
        $slots++;
    }
    return $slots;
}

/**
 * Returns active patron IDs across slots 1-3.
 *
 * @return array<int, string>
 */
function sponsors_get_active_patrons(?int $userId = null): array
{
    $active = [];
    for ($i = 1; $i <= 3; $i++) {
        $pid = (string) get_module_pref('patron_slot_' . $i, 'sponsors', $userId);
        if ($pid !== '') {
            $active[$i] = $pid;
        }
    }
    return $active;
}

/**
 * Awards a Benefactor or Fan Box via `systemai` if installed.
 */
function sponsors_grant_box(string $tier, string $boxTitle): void
{
    if (function_exists('systemai_grant_box')) {
        systemai_grant_box($tier, 1);
    } elseif (is_module_active('systemai')) {
        $prefKey = 'box_' . $tier;
        $cur = (int) get_module_pref($prefKey, 'systemai');
        set_module_pref($prefKey, $cur + 1, 'systemai');
    }

    $count = (int) get_module_pref('benefactor_boxes_received', 'sponsors') + 1;
    set_module_pref('benefactor_boxes_received', $count, 'sponsors');

    output(
        '`n`^`b[SYNDICATE DELIVERY]:`b `@You received a `^`b%s (%s Tier)`b`@ in your Safe Room Loot Vault!`0`n',
        $boxTitle,
        strtoupper($tier)
    );
}

function sponsors_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'newday':
            set_module_pref('stipend_claimed_today', 0);
            set_module_pref('fan_box_claimed_today', 0);
            break;

        case 'dragonkill':
            // Floor descent triggers a fresh Benefactor Box from active patrons!
            $patrons = sponsors_get_active_patrons();
            if (count($patrons) > 0) {
                $tier = count($patrons) >= 3 ? 'celestial' : (count($patrons) === 2 ? 'gold' : 'silver');
                sponsors_grant_box($tier, 'Floor-Descent Benefactor Box');
            }
            break;

        case 'battle-victory':
            $patrons = sponsors_get_active_patrons();
            if (count($patrons) > 0) {
                $favor = (int) get_module_pref('sponsor_favor') + count($patrons);
                if ($favor >= 15) {
                    set_module_pref('sponsor_favor', $favor - 15);
                    $tier = (e_rand(1, 100) <= 15) ? 'gold' : 'silver';
                    sponsors_grant_box($tier, 'Highlight-Reel Benefactor Box');
                } else {
                    set_module_pref('sponsor_favor', $favor);
                }
            }
            break;

        case 'village':
            tlschema($args['schemas']['marketnav'] ?? 'village');
            addnav($args['marketnav'] ?? 'Bopca Trade Concourse');
            tlschema();
            $patrons = sponsors_get_active_patrons();
            addnav(
                sprintf('`^Syndicate Sponsors & Fan Boxes `7(%d/3)`0', count($patrons)),
                'runmodule.php?module=sponsors'
            );
            break;

        case 'charstats':
            $patrons = sponsors_get_active_patrons();
            if (count($patrons) > 0) {
                $favor = (int) get_module_pref('sponsor_favor');
                addcharstat('Crawler Status');
                addcharstat('Sponsors', sprintf('`^%d Active`0 `7(%d/15 PR)`0', count($patrons), $favor));
            }
            break;

        case 'bioinfo':
            $acctId = (int) ($args['acctid'] ?? 0);
            if ($acctId > 0) {
                $patrons = sponsors_get_active_patrons($acctId);
                $catalog = sponsors_get_catalog();
                if (count($patrons) > 0) {
                    $names = [];
                    foreach ($patrons as $pid) {
                        if (isset($catalog[$pid])) {
                            $names[] = $catalog[$pid]['color'] . $catalog[$pid]['short'] . '`0';
                        }
                    }
                    if (count($names) > 0) {
                        output('`^Syndicate Sponsors:`0 %s`n', implode(', ', $names));
                    }
                }
            }
            break;
    }

    return $args;
}

function sponsors_run(): void
{
    global $session;

    $op = (string) httpget('op');
    $floor = sponsors_get_floor();
    $maxSlots = sponsors_max_slots($floor);
    $catalog = sponsors_get_catalog();
    $activePatrons = sponsors_get_active_patrons();
    $level = max(1, (int) ($session['user']['level'] ?? 1));
    $commissionPct = (int) get_module_setting('borant_commission_pct');
    $fanCharmReq = max(1, (int) get_module_setting('fan_box_charm_req'));

    page_header('Syndicate Sponsors, Benefactor Boxes & Fan Votes');

    // 1. Sign a Sponsor into an Open Slot
    if ($op === 'sign') {
        $pid = (string) httpget('pid');
        if (!isset($catalog[$pid])) {
            output('`$`b[INVALID PATRON]:`b `7That Syndicate organization is not bidding in this auction.`0`n`n');
        } elseif (in_array($pid, $activePatrons, true)) {
            output('`7You already hold an active patronage contract with `^%s`7.`0`n`n', $catalog[$pid]['name']);
        } elseif (count($activePatrons) >= $maxSlots) {
            output('`$`b[ALL SLOTS FULL]:`b `7You have filled all `^%d`7 currently unlocked Sponsor slot(s). Descend to deeper floors or drop a current sponsor first.`0`n`n', $maxSlots);
        } else {
            for ($slot = 1; $slot <= $maxSlots; $slot++) {
                if (!isset($activePatrons[$slot])) {
                    set_module_pref('patron_slot_' . $slot, $pid);
                    $activePatrons[$slot] = $pid;
                    output(
                        '`@`b[PATRONAGE AUCTION CLOSED!]:`b `2%s`b%s`b`2 has acquired Sponsor Slot #%d! Expect daily stipends, coded AI-filtered notes, and Benefactor Boxes on Floor Descent!`0`n`n',
                        $catalog[$pid]['color'],
                        $catalog[$pid]['name'],
                        $slot
                    );
                    addnews(
                        '`^Syndicate News:`0 %s%s`2 just won a patronage bid to sponsor Crawler `^%s`2!',
                        $catalog[$pid]['color'],
                        $catalog[$pid]['short'],
                        $session['user']['name']
                    );
                    break;
                }
            }
        }
    }

    // 2. Drop a Sponsor Slot
    if ($op === 'drop') {
        $slot = (int) httpget('slot');
        if ($slot >= 1 && $slot <= 3 && isset($activePatrons[$slot])) {
            $oldName = $catalog[$activePatrons[$slot]]['name'] ?? 'Sponsor';
            set_module_pref('patron_slot_' . $slot, '');
            unset($activePatrons[$slot]);
            output('`7You terminated your patronage contract with `^%s`7 (Slot #%d is now open for auction).`0`n`n', $oldName, $slot);
        }
    }

    // 3. Collect Daily Benefactor Stipend & Coded Notes
    if ($op === 'stipend') {
        if (count($activePatrons) === 0) {
            output('`$`b[NO SPONSORS]:`b `7Accept at least one Syndicate Sponsor bid before collecting daily stipends.`0`n`n');
        } elseif ((bool) get_module_pref('stipend_claimed_today')) {
            output('`7You already collected your daily Syndicate Benefactor Stipend for this broadcast cycle.`0`n`n');
        } else {
            set_module_pref('stipend_claimed_today', 1);
            $grossGold = 0;
            $totalGems = 0;
            $notes = [];

            foreach ($activePatrons as $pid) {
                if (isset($catalog[$pid])) {
                    $p = $catalog[$pid];
                    $grossGold += $p['gold_per_lvl'] * $level;
                    $totalGems += $p['gems_bonus'];
                    $notes[] = $p['color'] . $p['short'] . ':`0 `3' . $p['coded_note'] . '`0';
                }
            }

            $borantCut = (int) round($grossGold * ($commissionPct / 100.0));
            $netGold = max(1, $grossGold - $borantCut);

            $session['user']['gold'] += $netGold;
            $session['user']['gems'] += $totalGems;

            output(
                '`@`b[BENEFACTOR STIPEND DELIVERED]:`b `2Your Sponsors wired `^%s Gold Credits`2 (after Borant skimmed %d percent commission) and `^%d Sponsor Tokens (Gems)`2!`0`n',
                number_format($netGold),
                $commissionPct,
                $totalGems
            );
            foreach ($notes as $n) {
                output('%s`n', $n);
            }
            output('`n');
        }
    }

    // 4. Open Daily Viewer-Voted Fan Box
    if ($op === 'fanbox') {
        $charm = (int) ($session['user']['charm'] ?? 0);
        if ((bool) get_module_pref('fan_box_claimed_today')) {
            output('`7Your followers\' 30-hour Fan Box poll is cooling down until the next Broadcast Cycle.`0`n`n');
        } elseif ($charm < $fanCharmReq) {
            output('`$`b[LOW TUNNEL RATINGS]:`b `7You need at least `^%d Viewer Ratings (Charm)`7 before enough galactic viewers favorite you to unlock a Fan Box poll (Current: `^%d`7).`0`n`n', $fanCharmReq, $charm);
        } else {
            set_module_pref('fan_box_claimed_today', 1);
            $roll = e_rand(1, 100);
            if ($roll <= 12 && $charm >= 30) {
                $tier = 'gold';
                $label = 'Gold Viewer-Voted Fan Box';
            } elseif ($roll <= 45) {
                $tier = 'silver';
                $label = 'Silver Viewer-Voted Fan Box';
            } else {
                $tier = 'bronze';
                $label = 'Bronze Nonsensical Fan Box';
            }

            $gagItems = [
                'an Enchanted Neon Fedora that insults nearby goblins',
                'a Collector\'s Edition Princess Donut Plushie with glowing eyes',
                'a Signed Copy of "Goblin Cooking for Dummies"',
                'three cans of Syndicate-Branded Glitter Spray',
            ];
            $gag = $gagItems[array_rand($gagItems)];

            output('`5`b[FAN BOX POLL CLOSED!]:`b `2Your galactic followers voted overwhelmingly to send you `^%s`2 alongside a real Loot Crate!`0`n', $gag);
            sponsors_grant_box($tier, $label);
            output('`n');
        }
    }

    // Render Sponsor Dashboard
    output('`b`^=== SYNDICATE SPONSORSHIP AUCTION & FAN POLLS ===`b`0`n');
    output(
        '`7Current Floor: `^Floor %d`7 | Unlocked Sponsor Slots: `@%d / 3`7 | Active Patrons: `^%d`7 | PR Favor: `^%d / 15`0`n`n',
        $floor,
        $maxSlots,
        count($activePatrons),
        (int) get_module_pref('sponsor_favor')
    );

    rawoutput('<table class="table table-striped table-dark">');
    rawoutput('<thead><tr><th>Syndicate Patron</th><th>Daily Stipend (Gross)</th><th>Daily Gems</th><th>Patronage Specialty</th></tr></thead><tbody>');
    foreach ($catalog as $pid => $p) {
        $signed = in_array($pid, $activePatrons, true) ? ' <strong>[ACTIVE PATRON]</strong>' : '';
        rawoutput(sprintf(
            '<tr><td><strong>%s</strong>%s</td><td>%s Gold</td><td>+%d Gems</td><td>%s</td></tr>',
            htmlentities($p['name'], ENT_QUOTES, 'UTF-8'),
            $signed,
            number_format($p['gold_per_lvl'] * $level),
            $p['gems_bonus'],
            htmlentities($p['perk_desc'], ENT_QUOTES, 'UTF-8')
        ));
    }
    rawoutput('</tbody></table>');

    addnav('Sponsor Deliveries');
    addnav('`@Collect Daily Benefactor Stipend`0', 'runmodule.php?module=sponsors&op=stipend');
    addnav(sprintf('`5Open Viewer Fan Box (%d+ Charm)`0', $fanCharmReq), 'runmodule.php?module=sponsors&op=fanbox');

    if (count($activePatrons) < $maxSlots) {
        addnav('Sign New Sponsor');
        foreach ($catalog as $pid => $p) {
            if (!in_array($pid, $activePatrons, true)) {
                addnav('Sign: ' . $p['short'], 'runmodule.php?module=sponsors&op=sign&pid=' . $pid);
            }
        }
    }

    if (count($activePatrons) > 0) {
        addnav('Manage Contracts');
        foreach ($activePatrons as $slot => $pid) {
            $short = $catalog[$pid]['short'] ?? $pid;
            addnav(sprintf('`$Drop Slot #%d (%s)`0', $slot, $short), 'runmodule.php?module=sponsors&op=drop&slot=' . $slot);
        }
    }

    addnav('Return');
    addnav('Return to Safe Room Hub', 'village.php');

    page_footer();
}
