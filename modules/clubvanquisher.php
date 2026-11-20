<?php

declare(strict_types=1);

/**
 * Module: Club Vanquisher & Celestial Ascendency Deities (clubvanquisher.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Category:Deities
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Celestial_Ascendency
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Emberus
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Hellik
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Sun_Shrine
 *
 * Description:
 *   Opens Club Vanquisher (the sanctimonious rival to The Desperado Club) and the Shrine Hall
 *   of the Celestial Ascendency Pantheon:
 *   - Pledge worship to one of four Dungeon Deities: Emberus (God of Sun & Ash), Hellik (God of
 *     Sun & Life), Taranis (Regent of Thunder), or Grull (War God of the Skull Empire).
 *   - Unlocks a daily Divine Smite combat specialty (`fightnav-specialties` / `apply-specialties`)
 *     that scales with your Devotion rank.
 *   - Visit Emberus's Sun Shrine to collect Blood Water for crafting/buffs—at the risk of angering
 *     a volatile Level 250 Deity or getting permanently banned from Club Vanquisher!
 */

function clubvanquisher_getmoduleinfo(): array
{
    return [
        'name' => 'Club Vanquisher & Celestial Ascendency Deities',
        'version' => '0.3.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Club Vanquisher & Pantheon Settings,title',
            'tribute_base_gold' => 'Base Gold Credit tribute per level to pray at a Shrine,int|110',
            'smite_uses_per_day' => 'Daily Divine Smite uses granted to devoted Crawlers,int|2',
            'wrath_chance_pct' => 'Percent chance (0-40) a volatile Deity scorches you during prayer,range,0,40,5|12',
        ],
        'prefs' => [
            'Crawler Celestial Ascendency Ledger,title',
            'deity_id' => 'Worshipped Celestial Ascendency Deity ID,text|',
            'devotion_rank' => 'Deity Devotion Rank (scales Divine Smite power),int|0',
            'prayed_today' => 'Offered Shrine Tribute today?,bool|0',
            'smites_left' => 'Remaining Divine Smite charges today,int|0',
            'vanquisher_banned' => 'Banned from Club Vanquisher (like Carl & Prepotente)?,bool|0',
        ],
    ];
}

function clubvanquisher_install(): bool
{
    module_addhook('village');
    module_addhook('gardens');
    module_addhook('newday');
    module_addhook('fightnav-specialties');
    module_addhook('apply-specialties');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function clubvanquisher_uninstall(): bool
{
    return true;
}

/**
 * Returns the catalog of Dungeon Crawler Carl Celestial Ascendency Deities.
 *
 * @return array<string, array{
 *   name: string,
 *   short: string,
 *   title: string,
 *   color: string,
 *   smite_name: string,
 *   atk_mod: float,
 *   def_mod: float,
 *   desc: string
 * }>
 */
function clubvanquisher_get_deities(): array
{
    return [
        'emberus' => [
            'name' => 'Emberus, God of Sun & Ash',
            'short' => 'Emberus',
            'title' => 'High Temple of the Ending Sun',
            'color' => '`$',
            'smite_name' => 'Ending Sun Ash-Smite',
            'atk_mod' => 1.26,
            'def_mod' => 1.04,
            'desc' => 'Blind, grief-stricken, and hair-trigger furious since the murder of his son Geyrun. Grants blistering solar smites and Sun Shrine blood-water buffs.',
        ],
        'hellik' => [
            'name' => 'Hellik, God of Sun & Life',
            'short' => 'Hellik',
            'title' => 'Radiant Twin Covenant',
            'color' => '`^',
            'smite_name' => 'Solar Rebirth Flare',
            'atk_mod' => 1.14,
            'def_mod' => 1.22,
            'desc' => 'Emberus\'s twin rival who covets the Celestial Throne. Channels restorative sunlight and heavy defensive warding.',
        ],
        'taranis' => [
            'name' => 'Taranis, Regent of Thunder',
            'short' => 'Taranis',
            'title' => 'Throne of the Celestial Ascendency',
            'color' => '`#',
            'smite_name' => 'Celestial Thunderstrike',
            'atk_mod' => 1.22,
            'def_mod' => 1.14,
            'desc' => 'Current Regent of the Celestial Throne. Balances crackling lightning offense with storm-plate protection.',
        ],
        'grull' => [
            'name' => 'Grull, War God of the Skull Empire',
            'short' => 'Grull',
            'title' => 'Crimson War Altar',
            'color' => '`4',
            'smite_name' => 'Hellfire Halberd Cleave',
            'atk_mod' => 1.30,
            'def_mod' => 0.96,
            'desc' => 'Child of Taranis and Apito. Demands relentless aggression, trading defense for brutal battlefield carnage.',
        ],
    ];
}

function clubvanquisher_dohook(string $hookname, array $args): array
{
    global $session;

    $deities = clubvanquisher_get_deities();

    switch ($hookname) {
        case 'newday':
            set_module_pref('prayed_today', 0);
            $deityId = (string) get_module_pref('deity_id');
            if ($deityId !== '' && isset($deities[$deityId])) {
                $uses = max(1, (int) get_module_setting('smite_uses_per_day'));
                set_module_pref('smites_left', $uses);
            } else {
                set_module_pref('smites_left', 0);
            }
            break;

        case 'village':
            tlschema($args['schemas']['tavernnav'] ?? 'village');
            addnav($args['tavernnav'] ?? 'Safe Room Lounge');
            tlschema();
            addnav('`^Club Vanquisher & Pantheon Shrines`0', 'runmodule.php?module=clubvanquisher');
            break;

        case 'gardens':
            addnav('Club Vanquisher');
            addnav('`^Enter Club Vanquisher & Shrines`0', 'runmodule.php?module=clubvanquisher');
            break;

        case 'fightnav-specialties':
            $deityId = (string) get_module_pref('deity_id');
            $smites = (int) get_module_pref('smites_left');
            if ($deityId !== '' && isset($deities[$deityId]) && $smites > 0) {
                $d = $deities[$deityId];
                $script = (string) ($args['script'] ?? '');
                addnav('Celestial Ascendency');
                addnav(
                    sprintf('%s%s `7(%d left)`0', $d['color'], $d['smite_name'], $smites),
                    $script . 'op=fight&skill=DCC_DEITY&l=1',
                    true
                );
            }
            break;

        case 'apply-specialties':
            $skill = (string) httpget('skill');
            if ($skill === 'DCC_DEITY') {
                $deityId = (string) get_module_pref('deity_id');
                $smites = (int) get_module_pref('smites_left');
                if ($deityId !== '' && isset($deities[$deityId]) && $smites > 0) {
                    $d = $deities[$deityId];
                    $rank = max(1, (int) get_module_pref('devotion_rank'));
                    set_module_pref('smites_left', $smites - 1);

                    $atkBoost = min(1.55, $d['atk_mod'] + ($rank * 0.02));
                    $defBoost = min(1.45, $d['def_mod'] + ($rank * 0.01));

                    buff_add([
                        'name' => $d['color'] . $d['smite_name'] . ' (Rank ' . $rank . ')`0',
                        'rounds' => 6,
                        'wearoff' => '`7The divine aura of ' . $d['short'] . ' recedes from the corridor.`0',
                        'atkmod' => $atkBoost,
                        'defmod' => $defBoost,
                        'roundmsg' => $d['color'] . $d['short'] . ' channels ' . $d['smite_name'] . ' through your weapon!`0',
                        'schema' => 'module-clubvanquisher',
                    ]);
                    output('`n%s`b[DIVINE INVOCATION]:`b `2You invoke `^%s`2! %s answers from the Twelfth Floor Pantheon for 6 combat rounds!`0`n', $d['color'], $d['smite_name'], $d['name']);
                }
            }
            break;

        case 'charstats':
            $deityId = (string) get_module_pref('deity_id');
            if ($deityId !== '' && isset($deities[$deityId])) {
                $d = $deities[$deityId];
                $rank = (int) get_module_pref('devotion_rank');
                addcharstat('Crawler Status');
                addcharstat('Patron Deity', sprintf('%s%s`0 `7(Rank %d)`0', $d['color'], $d['short'], $rank));
            }
            break;

        case 'bioinfo':
            $acctId = (int) ($args['acctid'] ?? 0);
            if ($acctId > 0) {
                $deityId = (string) get_module_pref('deity_id', 'clubvanquisher', $acctId);
                $rank = (int) get_module_pref('devotion_rank', 'clubvanquisher', $acctId);
                $banned = (bool) get_module_pref('vanquisher_banned', 'clubvanquisher', $acctId);
                if ($deityId !== '' && isset($deities[$deityId])) {
                    $d = $deities[$deityId];
                    output('`^Celestial Patron Deity:`0 %s%s`0 `7(Devotion Rank %d)`0`n', $d['color'], $d['name'], $rank);
                }
                if ($banned) {
                    output('`$Club Vanquisher Status:`0 `4PERMANENTLY BANNED`0`n');
                }
            }
            break;
    }

    return $args;
}

function clubvanquisher_run(): void
{
    global $session;

    $op = (string) httpget('op');
    $deities = clubvanquisher_get_deities();
    $myDeity = (string) get_module_pref('deity_id');
    $rank = (int) get_module_pref('devotion_rank');
    $banned = (bool) get_module_pref('vanquisher_banned');
    $level = max(1, (int) ($session['user']['level'] ?? 1));
    $tributeCost = max(40, (int) get_module_setting('tribute_base_gold')) * $level;

    page_header('Club Vanquisher & Celestial Ascendency Shrines');

    // 1. Pledge Worship to a Deity
    if ($op === 'pledge') {
        $did = (string) httpget('did');
        if (!isset($deities[$did])) {
            output('`$`b[UNKNOWN DEITY]:`b `7That deity does not hold a seat in the Celestial Ascendency.`0`n`n');
        } else {
            set_module_pref('deity_id', $did);
            if ($rank < 1) {
                set_module_pref('devotion_rank', 1);
                $rank = 1;
            }
            set_module_pref('smites_left', max(1, (int) get_module_setting('smite_uses_per_day')));
            $myDeity = $did;

            output(
                '`@`b[PATRON DEITY PLEDGED]:`b `2You kneel before %s`b%s`b`2! You have unlocked the combat invocation `^%s`2 inside the Dungeon Corridors!`0`n`n',
                $deities[$did]['color'],
                $deities[$did]['name'],
                $deities[$did]['smite_name']
            );
            addnews(
                '`^Crawler `^%s`^ pledged devotion to %s%s`^ in the Celestial Ascendency Pantheon!',
                $session['user']['name'],
                $deities[$did]['color'],
                $deities[$did]['short']
            );
        }
    }

    // 2. Offer Daily Shrine Tribute (Risk of Divine Wrath vs Blessing)
    if ($op === 'pray') {
        if ($myDeity === '' || !isset($deities[$myDeity])) {
            output('`$`b[NO PATRON DEITY]:`b `7Pledge yourself to a Celestial Ascendency deity before offering tribute.`0`n`n');
        } elseif ((bool) get_module_pref('prayed_today')) {
            output('`7You have already knelt at the Shrine during this broadcast cycle.`0`n`n');
        } elseif ((int) $session['user']['gold'] < $tributeCost) {
            output('`$`b[INSUFFICIENT TRIBUTE]:`b `7The shrine clerics demand `^%s Gold Credits`7.`0`n`n', number_format($tributeCost));
        } else {
            $session['user']['gold'] -= $tributeCost;
            set_module_pref('prayed_today', 1);

            $d = $deities[$myDeity];
            $wrathChance = (int) get_module_setting('wrath_chance_pct');
            if (e_rand(1, 100) <= $wrathChance) {
                $hpLoss = max(1, (int) round(((int) $session['user']['hitpoints']) * 0.25));
                $session['user']['hitpoints'] = max(1, (int) $session['user']['hitpoints'] - $hpLoss);
                output(
                    '`$`b[DIVINE WRATH!]:`b `4%s is in a foul mood today and blasts you across the marble floor for `^%d damage`4! Still, your survival amuses the Pantheon (+1 Devotion Rank)!`0`n`n',
                    $d['name'],
                    $hpLoss
                );
            } else {
                $maxHp = (int) $session['user']['maxhitpoints'];
                $session['user']['hitpoints'] = $maxHp;
                buff_add([
                    'name' => $d['color'] . $d['short'] . '\'s Sun-Shrine Blood Water Blessing`0',
                    'rounds' => 18,
                    'wearoff' => '`7The divine blessing from the Shrine fades.`0',
                    'atkmod' => $d['atk_mod'],
                    'defmod' => $d['def_mod'],
                    'roundmsg' => $d['color'] . $d['short'] . '\'s shrine blessing radiates from your armor!`0',
                    'schema' => 'module-clubvanquisher',
                ]);
                output(
                    '`@`b[SHRINE BLESSING GRANTED]:`b `2%s accepts your offering! Your HP is restored to full and you gain an 18-round Shrine Blessing!`0`n`n',
                    $d['name']
                );
            }

            $rank++;
            set_module_pref('devotion_rank', $rank);
        }
    }

    // 3. Cause a Scene in Club Vanquisher (Get Banned for Infamy + Charm)
    if ($op === 'cause_scene') {
        if ($banned) {
            output('`7The angelic bouncers already have your mugshot nailed to the front doors beside Carl and Prepotente.`0`n`n');
        } else {
            set_module_pref('vanquisher_banned', 1);
            $banned = true;
            $session['user']['charm'] = (int) ($session['user']['charm'] ?? 0) + 5;
            output(
                '`$`b[PERMANENTLY BANNED FROM CLUB VANQUISHER!]:`b `2You loudly insult the sanctimonious Templars in the main hall! The cameras eat it up (`^+5 Viewer Ratings / Charm`2), and you are booted straight onto the street—just like Carl and Prepotente! (You can still use the back-alley Pantheon Shrines.)`0`n`n'
            );
            addnews(
                '`$`b[SCANDAL]:`b `^%s`$ caused a riot inside Club Vanquisher and was permanently banned on live Syndicate television!',
                $session['user']['name']
            );
        }
    }

    // Render Club Vanquisher & Shrine Hall
    output('`b`^=== CLUB VANQUISHER & CELESTIAL ASCENDENCY SHRINES ===`b`0`n');
    if ($banned) {
        output('`4[CLUB STATUS: BANNED — Using Back-Alley Shrine Access Only]`0`n');
    } else {
        output('`3Marble columns, harp music, and smug paladins fill Club Vanquisher—the pious rival to The Desperado Club. Behind the sanctuary curtains stand the altars of the Twelfth Floor Deities.`0`n');
    }
    output(
        '`7Devotion Rank: `^%d`7 | Daily Divine Smites Remaining: `@%d`0`n`n',
        $rank,
        (int) get_module_pref('smites_left')
    );

    rawoutput('<table class="table table-striped table-dark">');
    rawoutput('<thead><tr><th>Celestial Deity</th><th>Divine Smite Skill</th><th>Lore &amp; Temperament</th></tr></thead><tbody>');
    foreach ($deities as $did => $d) {
        $active = ($did === $myDeity) ? ' <strong>[YOUR DEITY]</strong>' : '';
        rawoutput(sprintf(
            '<tr><td><strong>%s</strong>%s<br><small>%s</small></td><td><strong>%s</strong></td><td>%s</td></tr>',
            htmlentities($d['name'], ENT_QUOTES, 'UTF-8'),
            $active,
            htmlentities($d['title'], ENT_QUOTES, 'UTF-8'),
            htmlentities($d['smite_name'], ENT_QUOTES, 'UTF-8'),
            htmlentities($d['desc'], ENT_QUOTES, 'UTF-8')
        ));
    }
    rawoutput('</tbody></table>');

    addnav('Shrine & Club Actions');
    if ($myDeity !== '' && isset($deities[$myDeity])) {
        addnav(sprintf('`@Offer Shrine Tribute (%sG)`0', number_format($tributeCost)), 'runmodule.php?module=clubvanquisher&op=pray');
    }
    if (!$banned) {
        addnav('`$Insult the Templars (Get Banned for +5 Charm)`0', 'runmodule.php?module=clubvanquisher&op=cause_scene');
    }

    addnav('Pledge to a Deity');
    foreach ($deities as $did => $d) {
        if ($did !== $myDeity) {
            addnav('Worship ' . $d['short'], 'runmodule.php?module=clubvanquisher&op=pledge&did=' . $did);
        }
    }

    addnav('Return');
    addnav('Club Vanquisher Gardens', 'gardens.php');
    addnav('Return to Safe Room Hub', 'village.php');

    page_footer();
}
