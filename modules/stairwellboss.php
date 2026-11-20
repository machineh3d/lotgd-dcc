<?php

declare(strict_types=1);

/**
 * Module: Floor 1-18 Stairwell Guardians & Syndicate Exit Deals (stairwellboss.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Category:Dungeon_Floors
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Exit_Deal
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Scolopendra
 *
 * Description:
 *   Dynamically transforms the Green Dragon (`dragon.php`) into canonical Floor 1-18
 *   World Dungeon Guardians via the `adjust-dragon` hook, scaling boss stats by floor.
 *   Unlocks Nullian Lawyer Quasar's Outreach Guild Office starting on Floor 10, allowing
 *   Crawlers to negotiate Syndicate Exit Deals (Game Guide, Talk-Show Host, Demigod Attendant)
 *   or push all the way to Floor 18 to defeat Scolopendra and claim Regency of Earth!
 */

function stairwellboss_getmoduleinfo(): array
{
    return [
        'name' => 'Stairwell Floor Guardians & Exit Deals (Floors 1-18)',
        'version' => '1.0.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'World Dungeon Stairwell Settings,title',
            'exit_deal_min_floor' => 'Floor number where Outreach Guild Exit Deals unlock (default 10),range,2,18,1|10',
            'scale_boss_per_floor' => 'Percent stat scaling per Floor above 1 (0-25% per floor),range,0,25,1|4',
            'scolopendra_pet_chance' => 'Percent chance (0-100) Donut feeds Scolopendra a Paramour Biscuit on Floor 11/18?,range,0,100,5|25',
        ],
        'prefs' => [
            'Crawler Floor & Exit Deal Dossier,title',
            'active_exit_deal' => 'Signed Syndicate Exit Deal ID (empty = active Crawler),text|',
            'exit_deal_floor' => 'Floor reached when Exit Deal was signed,int|0',
            'earth_regent' => 'Has cleared Floor 18 and claimed Regency of Earth?,bool|0',
            'regency_seasons' => 'Number of full 18-Floor Crawl Seasons completed,int|0',
            'sexy_scolopendra_ally' => 'Unlocked Sexy Scolopendra Paramour Biscuit party buff?,bool|0',
        ],
    ];
}

function stairwellboss_install(): bool
{
    module_addhook('adjust-dragon');
    module_addhook('dragonkill');
    module_addhook('village');
    module_addhook('forest-header');
    module_addhook('newday');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function stairwellboss_uninstall(): bool
{
    return true;
}

/**
 * Returns the current Floor number (1 to 18+) based on the player's Dragon Kills (`dragonkills + 1`).
 */
function stairwellboss_get_current_floor(?int $userId = null): int
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
 * Returns the complete 18-Floor World Dungeon catalog with Floor Titles, Lore, and Stairwell Guardians.
 *
 * @return array<int, array{
 *   floor_title: string,
 *   boss_name: string,
 *   boss_weapon: string,
 *   hp_mult: float,
 *   atk_mult: float,
 *   def_mult: float,
 *   intro: string
 * }>
 */
function stairwellboss_get_floor_catalog(): array
{
    return [
        1 => [
            'floor_title' => 'Floor 1: Subterranean Tutorial Corridors',
            'boss_name' => '`4The Borough Hoarder & Murder Dozer Pack`0',
            'boss_weapon' => 'Spinning Flail Drum & Avalanche of Feral Cats',
            'hp_mult' => 1.00,
            'atk_mult' => 1.00,
            'def_mult' => 1.00,
            'intro' => 'Blocking the first Stairwell is a roaring Goblin Murder Dozer flanked by the neighborhood Hoarder Boss!',
        ],
        2 => [
            'floor_title' => 'Floor 2: Borough Streets & Desperado Alleys',
            'boss_name' => '`4The Ball of Swine & Summoned Rage Elemental`0',
            'boss_weapon' => 'Twenty-Foot Crushing Hog Sphere & Sanitization Nova',
            'hp_mult' => 1.05,
            'atk_mult' => 1.04,
            'def_mult' => 1.04,
            'intro' => 'The second Stairwell shakes as a massive Ball of Swine and a misplaced Hallway Rage Elemental converge!',
        ],
        3 => [
            'floor_title' => 'Floor 3: The Over City (Scolopendra Lair Tier 1)',
            'boss_name' => '`5Grimaldi\'s Undead Circus & Destabilized Soul Crystal`0',
            'boss_weapon' => 'Collective Vine-Consciousness & Overloaded Soul Blast',
            'hp_mult' => 1.10,
            'atk_mult' => 1.08,
            'def_mult' => 1.08,
            'intro' => 'Inside the Big Top tent, the corrupted Town Mayor\'s Soul Crystal hums at critical mass beside Grimaldi!',
        ],
        4 => [
            'floor_title' => 'Floor 4: The Iron Tangle Subway System',
            'boss_name' => '`$War God Grull (Driven by the Maestro) & Mimic Rex`0',
            'boss_weapon' => 'Hellfire Locomotive Cowcatcher & Skull-Empire Halberd',
            'hp_mult' => 1.15,
            'atk_mult' => 1.12,
            'def_mult' => 1.12,
            'intro' => 'At the Named Track terminus, the Nightmare Express screeches to a halt as War God Grull steps onto the platform!',
        ],
        5 => [
            'floor_title' => 'Floor 5: The Gate of the Feral Gods (Quadrant Bubbles)',
            'boss_name' => '`#The Submerged Quadrant Leviathan & Dirigible Warlord`0',
            'boss_weapon' => 'Pressurized Depth-Charge Maw & Sky-Fortress Bombardment',
            'hp_mult' => 1.20,
            'atk_mult' => 1.16,
            'def_mult' => 1.15,
            'intro' => 'The Air, Land, and Sea quadrant domes shatter simultaneously around the Fifth Floor Stairwell portal!',
        ],
        6 => [
            'floor_title' => 'Floor 6: The Hunting Grounds (Scolopendra Lair Tier 2)',
            'boss_name' => '`@Vrah the Mantid Alpha & The Butcher\'s Masquerade Queen`0',
            'boss_weapon' => 'Cloaked Plasma Scythes & Strangling Blood-Thorn Vines',
            'hp_mult' => 1.25,
            'atk_mult' => 1.20,
            'def_mult' => 1.20,
            'intro' => 'The Masquerade ballroom peace seal shatters! Vrah and an army of Sixth-Floor Hunters surround the Stairwell!',
        ],
        7 => [
            'floor_title' => 'Floor 7: The Glassy Labyrinth Raceways',
            'boss_name' => '`^The Seventh-Floor Labyrinth Architect Sentinel`0',
            'boss_weapon' => 'Cascading Load-Bearing Pillar Collapse',
            'hp_mult' => 1.15,
            'atk_mult' => 1.15,
            'def_mult' => 1.15,
            'intro' => 'Prepotente\'s exploit cracks the starting chamber! You have seconds to smash the Sentinel before the entire floor turns to dust!',
        ],
        8 => [
            'floor_title' => 'Floor 8: The Eye of the Bedlam Bride (T\'Ghee Card Zones)',
            'boss_name' => '`5Shi Maria the Bedlam Bride & Apex Deck-Master`0',
            'boss_weapon' => 'Eight-Eyed Psychic Possession Web & Forbidden Combo Card',
            'hp_mult' => 1.32,
            'atk_mult' => 1.26,
            'def_mult' => 1.25,
            'intro' => 'Two Golden Staircase Keys glow above the Cuba zone arena as the Bedlam Bride lunges for your mind!',
        ],
        9 => [
            'floor_title' => 'Floor 9: Faction Wars — Siege of Larracos (Scolopendra Tier 3)',
            'boss_name' => '`$The Coalition Warlords of Larracos & Awakened Nothing Drain`0',
            'boss_weapon' => 'Orbital Mercenary Artillery & Justice Light\'s Nothing Trap',
            'hp_mult' => 1.40,
            'atk_mult' => 1.32,
            'def_mult' => 1.30,
            'intro' => 'The walls of Larracos burn as the surviving Faction Wars armies clash for the Ninth Floor Stairwell!',
        ],
        10 => [
            'floor_title' => 'Floor 10: The Seven-Heat Mad Max Death Race',
            'boss_name' => '`^The Final-Heat Juggernaut & Scolopendra\'s First Sin`0',
            'boss_weapon' => 'Audience-Voted Apocalypse Cannon & Beast of Burden Curse',
            'hp_mult' => 1.48,
            'atk_mult' => 1.38,
            'def_mult' => 1.35,
            'intro' => 'Outside the Pineapple Cabaret safe haven, the final race heat collides with Scolopendra\'s awakening shockwave!',
        ],
        11 => [
            'floor_title' => 'Floor 11: The Parade of Horribles & Boss Arena',
            'boss_name' => '`5Sponsored Sun God Emberus & Krakaren Prime`0',
            'boss_weapon' => 'Divine Solar Smite & Thousand-Tentacle Arena Crush',
            'hp_mult' => 1.56,
            'atk_mult' => 1.44,
            'def_mult' => 1.40,
            'intro' => 'The Parade of Horribles ends inside the grand arena as Emberus, Taranis, and Krakaren Prime descend!',
        ],
        12 => [
            'floor_title' => 'Floor 12: The Celestial Ascendancy (Scolopendra Tier 4)',
            'boss_name' => '`5The Ascendancy Throne Avatar`0',
            'boss_weapon' => 'Wealthy Sponsor God-Slayer Lance',
            'hp_mult' => 1.65,
            'atk_mult' => 1.50,
            'def_mult' => 1.45,
            'intro' => 'The divine war of the Twelfth Floor reaches its climax at the threshold of the Thirteenth Stairwell!',
        ],
        13 => [
            'floor_title' => 'Floor 13: The Uncharted Extinction Floor',
            'boss_name' => '`$The 30-Minute Extinction Sentinel`0',
            'boss_weapon' => 'Unbalanced Showrunner Death-Clock Protocol',
            'hp_mult' => 1.75,
            'atk_mult' => 1.58,
            'def_mult' => 1.52,
            'intro' => 'Only one crawler in Syndicate history ever set foot on Floor 13—and he died in under thirty minutes!',
        ],
        14 => [
            'floor_title' => 'Floor 14: Recycled Syndicate Vault I',
            'boss_name' => '`#The Residual Primal Harvester`0',
            'boss_weapon' => 'Center-System Eulogist Assimilation Beam',
            'hp_mult' => 1.82,
            'atk_mult' => 1.62,
            'def_mult' => 1.58,
            'intro' => 'Unfinished architectural blueprints from past seasons flicker around the Fourteenth Stairwell Guardian!',
        ],
        15 => [
            'floor_title' => 'Floor 15: The Deep Volcano Caldera (Scolopendra Tier 5)',
            'boss_name' => '`4The Scavenger Trinity of Nekhebit & Apito`0',
            'boss_weapon' => 'Vulture-Goddess Cataclysm Talons',
            'hp_mult' => 1.90,
            'atk_mult' => 1.68,
            'def_mult' => 1.64,
            'intro' => 'Poisonous volcanic fumes billow up from the bottom of the world as the Fifteenth Guardian awakens!',
        ],
        16 => [
            'floor_title' => 'Floor 16: Recycled Syndicate Vault II',
            'boss_name' => '`#The Apothecary\'s Hive-Mind Overseer`0',
            'boss_weapon' => 'Galactic Quarantine Obliterator',
            'hp_mult' => 1.98,
            'atk_mult' => 1.74,
            'def_mult' => 1.70,
            'intro' => 'Syndicate warships orbit nervously above Earth as you breach the Sixteenth Stairwell!',
        ],
        17 => [
            'floor_title' => 'Floor 17: The Showrunner\'s Final Gauntlet',
            'boss_name' => '`$The Borant Accelerated Executioner`0',
            'boss_weapon' => 'Unfiltered System AI Extinction Strike',
            'hp_mult' => 2.10,
            'atk_mult' => 1.82,
            'def_mult' => 1.78,
            'intro' => 'The System AI warns that Floor 17 is the last true barrier before the billionaire resort on Floor 18!',
        ],
        18 => [
            'floor_title' => 'Floor 18: Club Scolopendra & The Regency Exit Door',
            'boss_name' => '`5SCOLOPENDRA, LEVEL 500 WORLD DUNGEON BOSS`0',
            'boss_weapon' => 'Nine-Tier Cataclysm Poison Cloud & Centipede Mandibles',
            'hp_mult' => 2.30,
            'atk_mult' => 1.95,
            'def_mult' => 1.90,
            'intro' => 'Inside the billionaire luxury resort built within her own colossal body, SCOLOPENDRA awakens before the final Regency Exit Door!',
        ],
    ];
}

/**
 * Returns the catalog of Syndicate Exit Deals available via Lawyer Quasar on Floor 10+.
 *
 * @return array<string, array{
 *   title: string,
 *   min_floor: int,
 *   base_cycles: int,
 *   desc: string,
 *   daily_gold_per_lvl: int,
 *   daily_gems: int,
 *   atk_mod: float,
 *   def_mod: float
 * }>
 */
function stairwellboss_get_exit_deals(): array
{
    return [
        'game_guide' => [
            'title' => 'Tutorial Game Guide & Guildmaster Contract (Mordecai\'s Deal)',
            'min_floor' => 10,
            'base_cycles' => 120,
            'desc' => 'Serve Borant as an indentured Game Guide and Guildmaster. Everyone knows guides get out faster than anybody else! Grants a daily Game Guide Stipend (+Gold Credits, +2 Sponsor Tokens/day, and +10% ATK/DEF).',
            'daily_gold_per_lvl' => 300,
            'daily_gems' => 2,
            'atk_mod' => 1.10,
            'def_mod' => 1.10,
        ],
        'demigod_attendant' => [
            'title' => 'Twelfth-Floor Demigod Attendant Contract',
            'min_floor' => 11,
            'base_cycles' => 90,
            'desc' => 'Serve alongside a wealthy sponsored deity during the Celestial Ascendancy. Requires fewer service cycles than a Guildmaster and grants +3 Sponsor Tokens/day and a +18% ATK/DEF divine ward.',
            'daily_gold_per_lvl' => 450,
            'daily_gems' => 3,
            'atk_mod' => 1.18,
            'def_mod' => 1.18,
        ],
        'talk_show_host' => [
            'title' => 'Syndicate After-Hours Media Personality (Odette\'s Deal)',
            'min_floor' => 12,
            'base_cycles' => 60,
            'desc' => 'Negotiated at the threshold of the Thirteenth Floor! Host your own galactic broadcast program with massive syndication royalties (+750 Gold/level, +5 Sponsor Tokens/day, and +25% ATK/DEF).',
            'daily_gold_per_lvl' => 750,
            'daily_gems' => 5,
            'atk_mod' => 1.25,
            'def_mod' => 1.25,
        ],
    ];
}

function stairwellboss_dohook(string $hookname, array $args): array
{
    global $session;

    $floor = stairwellboss_get_current_floor();
    $catalog = stairwellboss_get_floor_catalog();
    $cappedFloor = min(18, $floor);
    $floorData = $catalog[$cappedFloor];

    switch ($hookname) {
        case 'forest-header':
            $regent = ((int) get_module_pref('earth_regent', 'stairwellboss') === 1);
            output(
                "`c`b`4[WORLD DUNGEON TELEMETRY — %s%s]`0`b`c`n",
                strtoupper($floorData['floor_title']),
                $regent ? ' | `5REGENT OF EARTH`4' : ''
            );
            break;

        case 'adjust-dragon':
            // Override Green Dragon stats and identity in `dragon.php`
            $scalePct = (int) get_module_setting('scale_boss_per_floor', 'stairwellboss');
            $extraFactor = 1.0 + (($floor - 1) * ($scalePct / 100.0));

            if (isset($args['creaturename'])) {
                $args['creaturename'] = $floorData['boss_name'];
                $args['creatureweapon'] = $floorData['boss_weapon'];
                $args['creaturehealth'] = (int) max(50, round((int) $args['creaturehealth'] * $floorData['hp_mult'] * $extraFactor));
                $args['creatureattack'] = (int) max(5, round((int) $args['creatureattack'] * $floorData['atk_mult'] * $extraFactor));
                $args['creaturedefense'] = (int) max(5, round((int) $args['creaturedefense'] * $floorData['def_mult'] * $extraFactor));
            } elseif (isset($args['badguy']) && is_array($args['badguy'])) {
                $args['badguy']['creaturename'] = $floorData['boss_name'];
                $args['badguy']['creatureweapon'] = $floorData['boss_weapon'];
                $args['badguy']['creaturehealth'] = (int) max(50, round((int) ($args['badguy']['creaturehealth'] ?? 100) * $floorData['hp_mult'] * $extraFactor));
                $args['badguy']['creatureattack'] = (int) max(5, round((int) ($args['badguy']['creatureattack'] ?? 15) * $floorData['atk_mult'] * $extraFactor));
                $args['badguy']['creaturedefense'] = (int) max(5, round((int) ($args['badguy']['creaturedefense'] ?? 15) * $floorData['def_mult'] * $extraFactor));
            }

            output("`n`4[STAIRWELL GUARDIAN ENCOUNTER — FLOOR %d]: `2%s`0`n`n", $floor, $floorData['intro']);
            break;

        case 'dragonkill':
            // Note: `dragonkills` has just been incremented by 1 in `dragon.php`
            $clearedFloor = max(1, (int) ($session['user']['dragonkills'] ?? 1));
            $nextFloor = $clearedFloor + 1;

            // Check Floor 11 / Floor 18 "The Paramour" Pet Biscuit Easter Egg on Scolopendra!
            $petChance = (int) get_module_setting('scolopendra_pet_chance', 'stairwellboss');
            if (($clearedFloor === 11 || $clearedFloor === 18)
                && (int) get_module_pref('sexy_scolopendra_ally', 'stairwellboss') === 0
                && $petChance > 0
                && e_rand(1, 100) <= $petChance
            ) {
                set_module_pref('sexy_scolopendra_ally', 1, 'stairwellboss');
                output("`n`5[INVENTORY MIX-UP — \"THE PARAMOUR\" BISCUIT!]: `3You hurled an enriched pet biscuit into Scolopendra's maw—but Princess Donut handed you Prepotente's `^\"The Paramour\"`3 biscuit by mistake! Instead of a level-1 pet, **`5Sexy Scolopendra`3** emerges as a Crawler madly in love with you and joins your party permanently!`0`n");
            }

            // Check if Crawler just cleared Floor 18 (Planetary Regency of Earth!)
            if ($clearedFloor >= 18 && (int) get_module_pref('earth_regent', 'stairwellboss') === 0) {
                set_module_pref('earth_regent', 1, 'stairwellboss');
                $seasons = (int) get_module_pref('regency_seasons', 'stairwellboss') + 1;
                set_module_pref('regency_seasons', $seasons, 'stairwellboss');

                $session['user']['gems'] += 50;
                $session['user']['gold'] += 50000;

                if (function_exists('is_module_active') && is_module_active('systemai')) {
                    $cel = (int) get_module_pref('box_celestial', 'systemai');
                    set_module_pref('box_celestial', $cel + 1, 'systemai');
                }

                output("`n`c`b`5=== NEW ACHIEVEMENT: PLANETARY REGENT OF EARTH! ===`0`b`c`n");
                output("`2You have survived all **18 Levels of the World Dungeon**, defeated **Scolopendra**, and stepped through the final door of Club Scolopendra! Per Syndicate law, you have won legal ownership and Regency of Planet Earth (`^50,000 Gold Credits & +50 Sponsor Tokens awarded`2)!`0`n");

                addnews(
                    '`5GALACTIC HISTORY MADE:`0 `@%s`2 has conquered **Floor 18 (Club Scolopendra)**, survived the World Dungeon, and claimed **Planetary Regency of Earth**!',
                    (string) ($session['user']['name'] ?? 'A Crawler')
                );
            } else {
                $minExitFloor = (int) get_module_setting('exit_deal_min_floor', 'stairwellboss');
                if ($nextFloor >= $minExitFloor) {
                    output("`n`@[OUTREACH GUILD NOTIFICATION]: `2You have reached **Floor %d**! Nullian Lawyer **Quasar** is now waiting in your Safe Room with official Syndicate **Exit Deal** contracts!`0`n", $nextFloor);
                }
            }
            break;

        case 'newday':
            $dealId = (string) get_module_pref('active_exit_deal', 'stairwellboss');
            $deals = stairwellboss_get_exit_deals();

            if ($dealId !== '' && isset($deals[$dealId])) {
                $d = $deals[$dealId];
                $lvl = max(1, (int) ($session['user']['level'] ?? 1));
                $gold = $d['daily_gold_per_lvl'] * $lvl;
                $gems = $d['daily_gems'];

                $session['user']['gold'] += $gold;
                $session['user']['gems'] += $gems;

                apply_buff('dcc_exit_deal_aura', [
                    'name' => '`^Syndicate Contract: ' . $d['title'] . '`0',
                    'rounds' => -1,
                    'atkmod' => $d['atk_mod'],
                    'defmod' => $d['def_mod'],
                    'schema' => 'module-stairwellboss',
                ]);

                output(
                    "`n`^[SYNDICATE EXIT CONTRACT STIPEND]: `2Your **%s** contract pays out `^%s Gold Credits`2 and `5%d Sponsor Tokens`2 for this broadcast cycle!`0`n",
                    $d['title'],
                    number_format($gold),
                    $gems
                );
            }

            if ((int) get_module_pref('sexy_scolopendra_ally', 'stairwellboss') === 1) {
                apply_buff('dcc_sexy_scolopendra', [
                    'name' => '`5Party Companion: Sexy Scolopendra`0',
                    'rounds' => 40,
                    'atkmod' => 1.25,
                    'defmod' => 1.25,
                    'minioncount' => 1,
                    'minbadguydamage' => 10,
                    'maxbadguydamage' => 35,
                    'effectmsg' => '`5Sexy Scolopendra blows you a kiss and lashes {badguy} for `^{damage}`5 venom damage!`0',
                    'wearoff' => 'Sexy Scolopendra stops fighting to touch up her makeup.',
                    'schema' => 'module-stairwellboss',
                ]);
            }
            break;

        case 'village':
            $minExitFloor = (int) get_module_setting('exit_deal_min_floor', 'stairwellboss');
            tlschema($args['schemas']['marketnav'] ?? 'village');
            addnav($args['marketnav'] ?? 'Safe Room Facilities');
            tlschema();
            if ($floor >= $minExitFloor) {
                addnav(
                    ['`^Quasar\'s Exit Deals (Floor %d)`0', $floor],
                    'runmodule.php?module=stairwellboss'
                );
            } else {
                addnav(
                    ['World Dungeon Floor Map (F%d)', $floor],
                    'runmodule.php?module=stairwellboss'
                );
            }
            break;

        case 'charstats':
            $dealId = (string) get_module_pref('active_exit_deal', 'stairwellboss');
            $regent = ((int) get_module_pref('earth_regent', 'stairwellboss') === 1);
            addcharstat('World Dungeon');
            addcharstat('Current Floor', sprintf('`^Floor %d`0 / `418`0', $floor));
            if ($regent) {
                addcharstat('Legal Status', '`5Regent of Earth`0');
            } elseif ($dealId !== '') {
                addcharstat('Legal Status', '`@Contracted Veteran`0');
            } else {
                addcharstat('Legal Status', ($floor >= 10) ? '`^Citizen Crawler`0' : '`7Indentured Crawler`0');
            }
            break;

        case 'bioinfo':
            $targetId = (int) ($args['acctid'] ?? 0);
            if ($targetId > 0) {
                $tFloor = stairwellboss_get_current_floor($targetId);
                $tRegent = ((int) get_module_pref('earth_regent', 'stairwellboss', $targetId) === 1);
                $tDeal = (string) get_module_pref('active_exit_deal', 'stairwellboss', $targetId);
                $deals = stairwellboss_get_exit_deals();

                output(
                    "`n`4World Dungeon Depth:`0 `^Floor %d / 18`0 %s`n",
                    $tFloor,
                    $tRegent
                        ? '(`5Planetary Regent of Earth`0)'
                        : (isset($deals[$tDeal]) ? '(`@Signed: ' . $deals[$tDeal]['title'] . '`0)' : '')
                );
            }
            break;
    }

    return $args;
}

function stairwellboss_run(): void
{
    global $session;

    page_header('World Dungeon Floor Atlas & Quasar\'s Outreach Guild');

    $op = (string) httpget('op');
    $floor = stairwellboss_get_current_floor();
    $minExitFloor = (int) get_module_setting('exit_deal_min_floor', 'stairwellboss');
    $catalog = stairwellboss_get_floor_catalog();
    $deals = stairwellboss_get_exit_deals();
    $activeDeal = (string) get_module_pref('active_exit_deal', 'stairwellboss');
    $isRegent = ((int) get_module_pref('earth_regent', 'stairwellboss') === 1);

    output("`c`b`4=== WORLD DUNGEON 18-FLOOR ATLAS & OUTREACH GUILD ===`0`b`c`n");

    // Handle signing an Exit Deal with Nullian Lawyer Quasar
    if ($op === 'sign_deal' && $floor >= $minExitFloor) {
        $dealKey = (string) httpget('id');
        if (isset($deals[$dealKey]) && $floor >= $deals[$dealKey]['min_floor']) {
            set_module_pref('active_exit_deal', $dealKey, 'stairwellboss');
            set_module_pref('exit_deal_floor', $floor, 'stairwellboss');
            $activeDeal = $dealKey;
            $d = $deals[$dealKey];

            output("`@[SYNDICATE EXIT CONTRACT EXECUTED!]: `2Nullian Lawyer **Quasar** stamps your holographic citizenship papers and puffs his vape pen!`n");
            output("`2You have signed the **%s** on **Floor %d**! You will now receive `%s Gold Credits/level`2, `5%d Sponsor Tokens/day`2, and a permanent combat aura every New Day!`0`n`n", $d['title'], $floor, number_format($d['daily_gold_per_lvl']), $d['daily_gems']);

            addnews(
                '`^OUTREACH GUILD NEWS:`0 `@%s`2 met with Nullian Lawyer Quasar on **Floor %d** and signed a Syndicate Exit Deal (`^%s`2)!',
                (string) ($session['user']['name'] ?? 'A Crawler'), $floor, $d['title']
            );
        }
    }

    // Section 1: Nullian Lawyer Quasar's Outreach Guild (Floor 10+)
    rawoutput('<div style="background:#11161d;border:2px solid #ffd700;border-radius:8px;padding:14px 18px;margin-bottom:16px;color:#e6edf3;font-family:Consolas,monospace;">');
    rawoutput('<div style="font-size:1.18em;font-weight:bold;color:#ffd700;margin-bottom:6px;">&#9878;&#65039; QUASAR\'S OUTREACH GUILD &amp; EXIT DEAL LAW OFFICE</div>');

    if ($floor < $minExitFloor) {
        rawoutput('<div style="color:#8b949e;">Per Syndicate statute, Crawlers gain galactic citizenship and access to Outreach Guild Exit Deals upon reaching <strong style="color:#00f0ff;">Floor ' . $minExitFloor . '</strong>. You are currently on <strong style="color:#ff2a85;">Floor ' . $floor . '</strong>. Keep descending the Stairwells!</div>');
    } else {
        rawoutput('<div style="color:#c9d1d9;margin-bottom:10px;">Nullian attorney <strong>Quasar</strong> adjusts his loud pinstripe suit: <em>"Congrats on surviving to Floor ' . $floor . ', kid! You\'re officially a Syndicate citizen now. Look over these Borant buyout offers—the deeper you go, the fewer cycles you owe \'em!"</em></div>');

        foreach ($deals as $dKey => $dInfo) {
            $unlocked = ($floor >= $dInfo['min_floor']);
            $cyclesOwed = max(10, $dInfo['base_cycles'] - (($floor - $dInfo['min_floor']) * 12));
            $isCurrent = ($activeDeal === $dKey);

            rawoutput('<div style="background:#161b22;border:1px solid ' . ($isCurrent ? '#3fb950' : '#30363d') . ';border-radius:6px;padding:10px 12px;margin-bottom:8px;">');
            rawoutput('<div style="font-weight:bold;color:' . ($unlocked ? '#00f0ff' : '#6e7681') . ';">' . htmlspecialchars($dInfo['title'], ENT_QUOTES, 'UTF-8') . ' — <span style="color:#ffd700;">' . $cyclesOwed . ' Service Cycles</span></div>');
            rawoutput('<div style="font-size:0.9em;color:#c9d1d9;margin:4px 0;">' . htmlspecialchars($dInfo['desc'], ENT_QUOTES, 'UTF-8') . '</div>');

            if ($isCurrent) {
                rawoutput('<span style="color:#3fb950;font-weight:bold;">&#10004; ACTIVE CONTRACT</span>');
            } elseif ($unlocked) {
                $signUrl = "runmodule.php?module=stairwellboss&op=sign_deal&id={$dKey}";
                addnav('', $signUrl);
                rawoutput('<a href="' . htmlspecialchars($signUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#ffd700;font-weight:bold;">[Sign Contract with Quasar]</a>');
            } else {
                rawoutput('<span style="color:#f85149;font-size:0.85em;">&#128274; Requires Floor ' . $dInfo['min_floor'] . '</span>');
            }
            rawoutput('</div>');
        }
    }
    rawoutput('</div>');

    // Section 2: 18-Floor World Dungeon Progression Map
    output("`b`^18-Level World Dungeon Stairwell Progression:`0`b`n");
    rawoutput('<table border="0" cellpadding="6" cellspacing="1" width="100%" style="background:#0d1117;border:1px solid #30363d;">');
    rawoutput('<tr style="background:#161b22;color:#00f0ff;font-weight:bold;"><td>Floor</td><td>World Dungeon Level</td><td>Stairwell Floor Guardian</td><td>Status</td></tr>');

    foreach ($catalog as $fNum => $fData) {
        if ($fNum < $floor || $isRegent) {
            $statusHtml = '<span style="color:#3fb950;font-weight:bold;">&#10004; CLEARED</span>';
            $rowBg = '#0f1a15';
        } elseif ($fNum === $floor) {
            $statusHtml = '<span style="color:#ffd700;font-weight:bold;">&#9876; ACTIVE FLOOR</span>';
            $rowBg = '#1f1a0e';
        } else {
            $statusHtml = '<span style="color:#6e7681;">&#128274; Deeper Level</span>';
            $rowBg = '#0d1117';
        }

        // Strip LotGD color codes for clean HTML table display
        $cleanBoss = (string) preg_replace('/`./', '', $fData['boss_name']);

        rawoutput('<tr style="background:' . $rowBg . ';border-bottom:1px solid #21262d;">');
        rawoutput('<td style="font-weight:bold;color:#f0f6fc;">Floor ' . $fNum . '</td>');
        rawoutput('<td style="color:#c9d1d9;">' . htmlspecialchars($fData['floor_title'], ENT_QUOTES, 'UTF-8') . '</td>');
        rawoutput('<td style="color:#ff2a85;font-weight:bold;">' . htmlspecialchars($cleanBoss, ENT_QUOTES, 'UTF-8') . '</td>');
        rawoutput('<td>' . $statusHtml . '</td>');
        rawoutput('</tr>');
    }
    rawoutput('</table>');

    addnav('Navigation');
    addnav('Return to Safe Room', 'village.php');
    addnav('Enter Dungeon Corridors', 'forest.php');

    page_footer();
}