<?php

declare(strict_types=1);

/**
 * Module: Ninth Floor Faction Wars & Larracos Command (factionwars.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Ninth_Floor
 *   - https://dungeon-crawler-carl.fandom.com/wiki/This_Inevitable_Ruin_(Book)
 *   - https://dungeon-crawler-carl.fandom.com/wiki/War_Mage_Rebellion
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Team_Katia
 *
 * Description:
 *   Expands LotGD Clans (`clan.php`) into the Ninth Floor Faction Wars battlefield around Larracos:
 *   - Enlist in one of five canonical Faction Wars Armies (Princess Posse, Team Katia Vanguard,
 *     War Mage Rebellion, Skull Empire Remnants, or Valtay Mercenary Corps) or fight under your
 *     own player Clan banner.
 *   - Contribute Gold Credits to the global Faction War Chest to seize daily control of Larracos
 *     Citadel, granting the winning Faction a daily combat buff and bonus Floor Stamina Turn.
 *   - Hire daily Syndicate Mercenary Contracts (Veteran Crawlers, Sapper Squads, or War Mage
 *     Artillery) for multi-round combat buffs.
 *   - Tracks War Points earned from Corridor kills (`battle-victory`) and PvP (`pvpwin`) to unlock
 *     Faction Commendation Loot Boxes via `systemai`.
 */

function factionwars_getmoduleinfo(): array
{
    return [
        'name' => 'Ninth Floor Faction Wars & Larracos Command',
        'version' => '0.3.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Faction Wars & Larracos Settings,title',
            'larracos_controller' => 'Current Faction ID holding Larracos Citadel,text|princess_posse',
            'chest_princess_posse' => 'Princess Posse War Chest Score,int|1000',
            'chest_team_katia' => 'Team Katia Vanguard War Chest Score,int|850',
            'chest_war_mages' => 'War Mage Rebellion War Chest Score,int|750',
            'chest_skull_empire' => 'Skull Empire Remnants War Chest Score,int|650',
            'chest_valtay_corps' => 'Valtay Mercenary Corps War Chest Score,int|600',
            'merc_base_cost' => 'Base Gold Credit cost per level for daily Mercenary Contracts,int|120',
            'war_box_threshold' => 'War Points required per Faction Commendation Loot Box,int|25',
        ],
        'prefs' => [
            'Crawler Faction Wars Dossier,title',
            'faction_id' => 'Enlisted Faction Wars Army ID,text|',
            'war_points' => 'Current unspent Faction War Points,int|0',
            'lifetime_war_points' => 'Lifetime Faction War Points earned,int|0',
            'merc_hired_today' => 'Hired a Faction Wars Mercenary Squad today?,bool|0',
            'donated_today' => 'Contributed to the Larracos War Chest today?,bool|0',
        ],
    ];
}

function factionwars_install(): bool
{
    module_addhook('village');
    module_addhook('header-clan');
    module_addhook('clan-enter');
    module_addhook('newday');
    module_addhook('battle-victory');
    module_addhook('pvpwin');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function factionwars_uninstall(): bool
{
    return true;
}

/**
 * Returns the 5 canonical Ninth Floor Faction Wars armies.
 *
 * @return array<string, array{
 *   name: string,
 *   short: string,
 *   color: string,
 *   leader: string,
 *   setting_key: string,
 *   perk_desc: string,
 *   atk_bonus: float,
 *   def_bonus: float
 * }>
 */
function factionwars_get_factions(): array
{
    return [
        'princess_posse' => [
            'name' => 'The Princess Posse (Crawler Coalition)',
            'short' => 'Princess Posse',
            'color' => '`5',
            'leader' => 'Princess Donut & Carl (with Mordecai)',
            'setting_key' => 'chest_princess_posse',
            'perk_desc' => '+14 percent Attack & +10 percent Defense when holding Larracos',
            'atk_bonus' => 1.14,
            'def_bonus' => 1.10,
        ],
        'team_katia' => [
            'name' => 'Team Katia Armored Vanguard',
            'short' => 'Team Katia',
            'color' => '`#',
            'leader' => 'Katia Grim & Former Surface Crawlers',
            'setting_key' => 'chest_team_katia',
            'perk_desc' => '+10 percent Attack & +16 percent Defense when holding Larracos',
            'atk_bonus' => 1.10,
            'def_bonus' => 1.16,
        ],
        'war_mages' => [
            'name' => 'The War Mage Rebellion (Rogue NPCs)',
            'short' => 'War Mage Rebellion',
            'color' => '`$',
            'leader' => 'Self-Aware Citadel War Mages',
            'setting_key' => 'chest_war_mages',
            'perk_desc' => '+18 percent Attack & +6 percent Defense when holding Larracos',
            'atk_bonus' => 1.18,
            'def_bonus' => 1.06,
        ],
        'skull_empire' => [
            'name' => 'Skull Empire Expeditionary Force',
            'short' => 'Skull Empire',
            'color' => '`4',
            'leader' => 'Syndicate Aristocrat Warlords',
            'setting_key' => 'chest_skull_empire',
            'perk_desc' => '+15 percent Attack & +9 percent Defense when holding Larracos',
            'atk_bonus' => 1.15,
            'def_bonus' => 1.09,
        ],
        'valtay_corps' => [
            'name' => 'Valtay Corporate Mercenary Corps',
            'short' => 'Valtay Corps',
            'color' => '`@',
            'leader' => 'Valtay Syndicate Board Contractors',
            'setting_key' => 'chest_valtay_corps',
            'perk_desc' => '+12 percent Attack & +12 percent Defense when holding Larracos',
            'atk_bonus' => 1.12,
            'def_bonus' => 1.12,
        ],
    ];
}

/**
 * Recomputes which Faction holds Larracos Citadel based on War Chest scores.
 */
function factionwars_update_controller(): string
{
    $factions = factionwars_get_factions();
    $bestId = 'princess_posse';
    $bestScore = -1;

    foreach ($factions as $fid => $f) {
        $score = (int) get_module_setting($f['setting_key'], 'factionwars');
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestId = $fid;
        }
    }

    set_module_setting('larracos_controller', $bestId, 'factionwars');
    return $bestId;
}

/**
 * Awards a Faction Commendation Box via `systemai` if installed.
 */
function factionwars_grant_box(string $tier, string $reason): void
{
    if (function_exists('systemai_grant_box')) {
        systemai_grant_box($tier, 1);
    } elseif (is_module_active('systemai')) {
        $prefKey = 'box_' . $tier;
        $cur = (int) get_module_pref($prefKey, 'systemai');
        set_module_pref($prefKey, $cur + 1, 'systemai');
    }

    output(
        '`n`^`b[FACTION WARS COMMENDATION]:`b `@You earned a `^`b%s Faction Box`b`@ (%s)!`0`n',
        strtoupper($tier),
        $reason
    );
}

function factionwars_dohook(string $hookname, array $args): array
{
    global $session;

    $factions = factionwars_get_factions();

    switch ($hookname) {
        case 'newday':
            set_module_pref('merc_hired_today', 0);
            set_module_pref('donated_today', 0);

            $myFaction = (string) get_module_pref('faction_id');
            $controller = factionwars_update_controller();
            if ($myFaction !== '' && $myFaction === $controller && isset($factions[$myFaction])) {
                $f = $factions[$myFaction];
                $session['user']['turns'] = (int) $session['user']['turns'] + 1;
                buff_add([
                    'name' => $f['color'] . 'Larracos Citadel Control (' . $f['short'] . ')`0',
                    'rounds' => 25,
                    'wearoff' => '`7The Larracos supply-line artillery support shifts to another sector.`0',
                    'atkmod' => $f['atk_bonus'],
                    'defmod' => $f['def_bonus'],
                    'roundmsg' => $f['color'] . 'Larracos Citadel logistics bolster your Faction Wars strike!`0',
                    'schema' => 'module-factionwars',
                ]);
                output(
                    '`n%s`b[LARRACOS CITADEL CONTROLLED!]:`b `2Your army (%s`2) currently holds Larracos! You receive `@+1 Floor Stamina Turn`2 and a 25-round Citadel combat buff!`0`n',
                    $f['color'],
                    $f['name']
                );
            }
            break;

        case 'village':
            tlschema($args['schemas']['tavernnav'] ?? 'village');
            addnav($args['tavernnav'] ?? 'Safe Room Lounge');
            tlschema();
            addnav('`$Ninth Floor Faction Wars`0', 'runmodule.php?module=factionwars');
            break;

        case 'header-clan':
        case 'clan-enter':
            addnav('Faction Wars Command');
            addnav('`$Larracos War Room & Mercenaries`0', 'runmodule.php?module=factionwars');
            break;

        case 'battle-victory':
            $myFaction = (string) get_module_pref('faction_id');
            if ($myFaction !== '' && isset($factions[$myFaction])) {
                $wp = (int) get_module_pref('war_points') + 1;
                $life = (int) get_module_pref('lifetime_war_points') + 1;
                set_module_pref('war_points', $wp);
                set_module_pref('lifetime_war_points', $life);

                // Also add +10 to the faction's global War Chest score
                $key = $factions[$myFaction]['setting_key'];
                $score = (int) get_module_setting($key, 'factionwars') + 10;
                set_module_setting($key, $score, 'factionwars');
                factionwars_update_controller();
            }
            break;

        case 'pvpwin':
            $myFaction = (string) get_module_pref('faction_id');
            if ($myFaction !== '' && isset($factions[$myFaction])) {
                $wp = (int) get_module_pref('war_points') + 5;
                $life = (int) get_module_pref('lifetime_war_points') + 5;
                set_module_pref('war_points', $wp);
                set_module_pref('lifetime_war_points', $life);

                $key = $factions[$myFaction]['setting_key'];
                $score = (int) get_module_setting($key, 'factionwars') + 75;
                set_module_setting($key, $score, 'factionwars');
                factionwars_update_controller();

                output('`$`b[FACTION WARS SKIRMISH]:`b `2Your PvP victory adds `^+5 War Points`2 and `^+75 War Chest Score`2 for %s`2!`0`n', $factions[$myFaction]['short']);
            }
            break;

        case 'charstats':
            $myFaction = (string) get_module_pref('faction_id');
            if ($myFaction !== '' && isset($factions[$myFaction])) {
                $f = $factions[$myFaction];
                $wp = (int) get_module_pref('war_points');
                addcharstat('Crawler Status');
                addcharstat('Faction Army', sprintf('%s%s`0 `7(%d WP)`0', $f['color'], $f['short'], $wp));
            }
            break;

        case 'bioinfo':
            $acctId = (int) ($args['acctid'] ?? 0);
            if ($acctId > 0) {
                $myFaction = (string) get_module_pref('faction_id', 'factionwars', $acctId);
                $life = (int) get_module_pref('lifetime_war_points', 'factionwars', $acctId);
                if ($myFaction !== '' && isset($factions[$myFaction])) {
                    $f = $factions[$myFaction];
                    output('`$Faction Wars Army:`0 %s%s`0 `7(Lifetime War Points: %d)`0`n', $f['color'], $f['name'], $life);
                }
            }
            break;
    }

    return $args;
}

function factionwars_run(): void
{
    global $session;

    $op = (string) httpget('op');
    $factions = factionwars_get_factions();
    $myFaction = (string) get_module_pref('faction_id');
    $level = max(1, (int) ($session['user']['level'] ?? 1));
    $mercBase = max(40, (int) get_module_setting('merc_base_cost')) * $level;
    $boxCost = max(10, (int) get_module_setting('war_box_threshold'));

    page_header('Ninth Floor Faction Wars — Larracos War Room');

    // 1. Enlist or Switch Faction Army
    if ($op === 'enlist') {
        $fid = (string) httpget('fid');
        if (!isset($factions[$fid])) {
            output('`$`b[INVALID FACTION]:`b `7That Faction Wars army is not registered on the Ninth Floor.`0`n`n');
        } else {
            set_module_pref('faction_id', $fid);
            $myFaction = $fid;
            output(
                '`@`b[FACTION COMMISSION SIGNED]:`b `2You have enlisted under %s`b%s`b`2 (Command: `^%s`2)! Every Corridor kill and PvP win now earns War Points and pushes your army toward control of Larracos Citadel!`0`n`n',
                $factions[$fid]['color'],
                $factions[$fid]['name'],
                $factions[$fid]['leader']
            );
            addnews(
                '`$Crawler `^%s`$ enlisted with %s%s`$ for the Ninth Floor Faction Wars!',
                $session['user']['name'],
                $factions[$fid]['color'],
                $factions[$fid]['short']
            );
        }
    }

    // 2. Contribute Gold Credits to Faction War Chest
    if ($op === 'donate') {
        if ($myFaction === '' || !isset($factions[$myFaction])) {
            output('`$`b[ENLIST FIRST]:`b `7Join a Faction Wars army before funding the Larracos War Chest.`0`n`n');
        } elseif ((bool) get_module_pref('donated_today')) {
            output('`7You have already wired your daily War Chest requisition for this broadcast cycle.`0`n`n');
        } else {
            $amount = $level * 250;
            if ((int) $session['user']['gold'] < $amount) {
                output('`$`b[INSUFFICIENT CREDITS]:`b `7You need `^%s Gold Credits`7 to fund a Larracos supply drop.`0`n`n', number_format($amount));
            } else {
                $session['user']['gold'] -= $amount;
                set_module_pref('donated_today', 1);

                $key = $factions[$myFaction]['setting_key'];
                $prevController = (string) get_module_setting('larracos_controller', 'factionwars');
                $newScore = (int) get_module_setting($key, 'factionwars') + 250;
                set_module_setting($key, $newScore, 'factionwars');
                $newController = factionwars_update_controller();

                $wp = (int) get_module_pref('war_points') + 4;
                $life = (int) get_module_pref('lifetime_war_points') + 4;
                set_module_pref('war_points', $wp);
                set_module_pref('lifetime_war_points', $life);

                output(
                    '`@`b[WAR CHEST FUNDED]:`b `2You wired `^%s Gold Credits`2 to %s%s`2! Your army gains `^+250 Citadel Control`2 and you earn `^+4 War Points`2!`0`n`n',
                    number_format($amount),
                    $factions[$myFaction]['color'],
                    $factions[$myFaction]['short']
                );

                if ($newController === $myFaction && $prevController !== $myFaction) {
                    addnews(
                        '`^`b[FACTION WARS ALERT]:`b %s%s`^ has seized control of Larracos Citadel thanks to supplies from `^%s`^!',
                        $factions[$myFaction]['color'],
                        $factions[$myFaction]['name'],
                        $session['user']['name']
                    );
                }
            }
        }
    }

    // 3. Hire a Daily Mercenary Contract
    if ($op === 'hire_merc') {
        $mercType = (string) httpget('type');
        if ($myFaction === '' || !isset($factions[$myFaction])) {
            output('`$`b[ENLIST FIRST]:`b `7Mercenary squads only deploy for enlisted Faction Wars combatants.`0`n`n');
        } elseif ((bool) get_module_pref('merc_hired_today')) {
            output('`7You already have a Mercenary Contract active for today\'s broadcast cycle.`0`n`n');
        } elseif ($mercType === 'veterans') {
            $cost = $mercBase;
            if ((int) $session['user']['gold'] < $cost) {
                output('`$`b[INSUFFICIENT CREDITS]:`b `7Former Crawler Veterans require `^%s Gold Credits`7.`0`n`n', number_format($cost));
            } else {
                $session['user']['gold'] -= $cost;
                set_module_pref('merc_hired_today', 1);
                buff_add([
                    'name' => '`#Veteran Crawler Homecoming Squad`0',
                    'rounds' => 20,
                    'wearoff' => '`7Your Former Crawler Veteran escorts pull back to the staging perimeter.`0',
                    'atkmod' => 1.18,
                    'defmod' => 1.15,
                    'roundmsg' => '`#Grizzled Veteran Crawlers flank your target with coordinated kill-shots!`0',
                    'schema' => 'module-factionwars',
                ]);
                output('`#`b[VETERAN MERCENARIES DEPLOYED]:`b `2Battle-hardened Former Crawlers join your squad (+18 percent ATK / +15 percent DEF for 20 rounds)!`0`n`n');
            }
        } elseif ($mercType === 'warmages') {
            $cost = (int) round($mercBase * 1.5);
            if ((int) $session['user']['gold'] < $cost) {
                output('`$`b[INSUFFICIENT CREDITS]:`b `7Rogue War Mage Artillery requires `^%s Gold Credits`7.`0`n`n', number_format($cost));
            } else {
                $session['user']['gold'] -= $cost;
                set_module_pref('merc_hired_today', 1);
                buff_add([
                    'name' => '`$Larracos War Mage Artillery Barrage`0',
                    'rounds' => 16,
                    'wearoff' => '`7The Rogue War Mages exhaust their mana crystals and disengage.`0',
                    'atkmod' => 1.30,
                    'defmod' => 1.00,
                    'roundmsg' => '`$Arcane siege bolts from the War Mage battery rain down on your foe!`0',
                    'schema' => 'module-factionwars',
                ]);
                output('`$`b[WAR MAGE BATTERY ONLINE]:`b `2Rogue War Mages lock onto your HUD beacon (+30 percent ATK for 16 rounds)!`0`n`n');
            }
        }
    }

    // 4. Redeem War Points for a Faction Commendation Loot Box
    if ($op === 'redeem_box') {
        $wp = (int) get_module_pref('war_points');
        if ($wp < $boxCost) {
            output('`$`b[INSUFFICIENT WAR POINTS]:`b `7You need `^%d War Points`7 to requisition a Faction Commendation Box (Current: `^%d`7).`0`n`n', $boxCost, $wp);
        } else {
            set_module_pref('war_points', $wp - $boxCost);
            $tier = (e_rand(1, 100) <= 20) ? 'gold' : 'silver';
            factionwars_grant_box($tier, 'Ninth Floor War Point Requisition');
        }
    }

    // Display Larracos Battlefield Overview
    $controller = factionwars_update_controller();
    $ctrlName = $factions[$controller]['name'] ?? 'Contested';
    $wp = (int) get_module_pref('war_points');

    output('`b`$=== NINTH FLOOR: FACTION WARS & LARRACOS CITADEL ===`b`0`n');
    output('`3Nine rival armies clash across the shattered plains of Larracos while the System AI disables safety protocols. Enlist with an army, fund your War Chest to seize the Citadel, and deploy mercenary squads!`0`n`n');
    output(
        '`^Current Citadel Controller:`0 %s`b%s`b`0 | `7Your War Points: `^%d`7 (Box Cost: %d WP)`0`n`n',
        $factions[$controller]['color'] ?? '`^',
        $ctrlName,
        $wp,
        $boxCost
    );

    rawoutput('<table class="table table-striped table-dark">');
    rawoutput('<thead><tr><th>Faction Army</th><th>Command</th><th>War Chest Score</th><th>Citadel Perk</th></tr></thead><tbody>');
    foreach ($factions as $fid => $f) {
        $score = (int) get_module_setting($f['setting_key'], 'factionwars');
        $badge = ($fid === $controller) ? ' <strong>[HOLDS LARRACOS]</strong>' : '';
        $mine = ($fid === $myFaction) ? ' <em>(Your Army)</em>' : '';
        rawoutput(sprintf(
            '<tr><td><strong>%s</strong>%s%s</td><td>%s</td><td><strong>%s</strong></td><td>%s</td></tr>',
            htmlentities($f['name'], ENT_QUOTES, 'UTF-8'),
            $badge,
            $mine,
            htmlentities($f['leader'], ENT_QUOTES, 'UTF-8'),
            number_format($score),
            htmlentities($f['perk_desc'], ENT_QUOTES, 'UTF-8')
        ));
    }
    rawoutput('</tbody></table>');

    addnav('Faction Operations');
    if ($myFaction !== '' && isset($factions[$myFaction])) {
        addnav(sprintf('Fund War Chest (%sG)', number_format($level * 250)), 'runmodule.php?module=factionwars&op=donate');
        addnav(sprintf('Hire Veteran Squad (%sG)', number_format($mercBase)), 'runmodule.php?module=factionwars&op=hire_merc&type=veterans');
        addnav(sprintf('Hire War Mage Battery (%sG)', number_format((int) round($mercBase * 1.5))), 'runmodule.php?module=factionwars&op=hire_merc&type=warmages');
        addnav(sprintf('`@Redeem Commendation Box (%d WP)`0', $boxCost), 'runmodule.php?module=factionwars&op=redeem_box');
    }

    addnav('Enlist / Switch Army');
    foreach ($factions as $fid => $f) {
        if ($fid !== $myFaction) {
            addnav('Enlist: ' . $f['short'], 'runmodule.php?module=factionwars&op=enlist&fid=' . $fid);
        }
    }

    addnav('Return');
    addnav('Clan Halls (Crawler Parties)', 'clan.php');
    addnav('Return to Safe Room Hub', 'village.php');

    page_footer();
}
