<?php

declare(strict_types=1);

/**
 * Module: Multi-Tier Dungeon Boss Encounters & Persistent Cartography (dungeonbosses.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Boss
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Category:Neighborhood_Boss
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Category:Borough_Boss
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Category:City_Boss
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Hoarder
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Ball_of_Swine
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Neighborhood_Map
 *
 * Description:
 *   Implements canonical multi-tier Dungeon Crawler Carl Boss Chambers:
 *   - Four Boss Tiers: Neighborhood Bosses (The Hoarder, Heather the Bear, The Divider),
 *     Borough Bosses (Ball of Swine, Upgraded Street Preacher, Krakaren Clone),
 *     City Bosses (Grimaldi the Pestiferous Vine, Shi Maria), and Province Bosses (The Juicer, General Imogen).
 *   - Sealed Boss Chamber multi-round tactical combat (Standard Assault, Sapper Blitz, Mordecai Read)
 *     where Crawlers cannot flee unless they burn a daily Emergency Smoke Curtain!
 *   - Awards Boss Boxes (Bronze/Silver/Gold/Celestial via `systemai`), bonus Survivor's Boxes when winning
 *     under 20 percent HP, persistent Cartography drops (`Neighborhood Map`, `Borough Field Guide`,
 *     `Map of the Stars`, `Scolopendra Fault Atlas`), and permanent Boss Kill Stars (`★`) in HUD & Bios!
 */

function dungeonbosses_getmoduleinfo(): array
{
    return [
        'name' => 'Multi-Tier Dungeon Bosses (Neighborhood / Borough / City)',
        'version' => '0.2.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Dungeon Boss Chamber Settings,title',
            'daily_boss_attempts' => 'Maximum Boss Chamber challenges allowed per broadcast cycle,int|2',
            'roaming_discovery_chance' => 'Percent chance (0-100) to discover Boss Radar Intel after a Corridor victory,range,0,50,1|14',
            'survivor_box_hp_pct' => 'Max HP percent threshold (1-35) to award a bonus Survivor\'s Loot Box on win,range,5,35,5|20',
        ],
        'prefs' => [
            'Dungeon Boss Stars & Cartography,title',
            'stars_neighborhood' => 'Bronze Neighborhood Boss Stars earned,int|0',
            'stars_borough' => 'Silver Borough Boss Stars earned,int|0',
            'stars_city' => 'Gold City Boss Stars earned,int|0',
            'stars_province' => 'Celestial Province Boss Stars earned,int|0',
            'has_neighborhood_map' => 'Owns persistent Neighborhood Map?,bool|0',
            'has_borough_guide' => 'Owns persistent Borough Field Guide?,bool|0',
            'has_map_of_stars' => 'Owns persistent Map of the Stars?,bool|0',
            'has_fault_atlas' => 'Owns persistent Scolopendra Fault Atlas?,bool|0',
            'boss_intel' => 'Boss Radar Intel tokens gathered from Corridor scouting,int|0',
            'attempts_today' => 'Boss Chambers entered today,int|0',
            'smoke_used_today' => 'Used Emergency Smoke Curtain to escape a Boss Chamber today?,bool|0',
            'unlocked_boss_babe' => 'Unlocked the "Boss Babe!" achievement?,bool|0',
            'unlocked_borough_ach' => 'Unlocked the "Borough Boss!" achievement?,bool|0',
            'active_boss_id' => 'Currently engaged Boss ID inside sealed chamber,text|',
            'active_boss_hp' => 'Current HP of engaged Boss inside sealed chamber,int|0',
            'active_boss_maxhp' => 'Max HP of engaged Boss inside sealed chamber,int|0',
            'active_boss_round' => 'Current combat round inside Boss Chamber,int|0',
        ],
    ];
}

function dungeonbosses_install(): bool
{
    module_addhook('forest');
    module_addhook('village');
    module_addhook('newday');
    module_addhook('battle-victory');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function dungeonbosses_uninstall(): bool
{
    return true;
}

/**
 * Returns the catalog of canonical Dungeon Crawler Carl Bosses across 4 tiers.
 *
 * @return array<string, array{
 *   name: string,
 *   tier: string,
 *   tier_label: string,
 *   min_level: int,
 *   hp_mult: float,
 *   atk_mult: float,
 *   def_mult: float,
 *   box_tier: string,
 *   star_pref: string,
 *   map_pref: string,
 *   map_name: string,
 *   gold_mult: int,
 *   gems: int,
 *   weapon: string,
 *   intro: string
 * }>
 */
function dungeonbosses_get_catalog(): array
{
    return [
        'hoarder' => [
            'name' => 'The First-Floor Hoarder',
            'tier' => 'neighborhood',
            'tier_label' => '`6Neighborhood Boss`0',
            'min_level' => 1,
            'hp_mult' => 1.15,
            'atk_mult' => 1.05,
            'def_mult' => 0.95,
            'box_tier' => 'bronze',
            'star_pref' => 'stars_neighborhood',
            'map_pref' => 'has_neighborhood_map',
            'map_name' => 'Persistent Neighborhood Map',
            'gold_mult' => 250,
            'gems' => 1,
            'weapon' => 'Disgorged Scatterer Swarm & Avalanche of Transplanted Trash',
            'intro' => 'A towering, mutated hoarder trapped inside a subterranean chamber of rotting Earth couches and televisions shrieks in panicked Spanish before vomiting a wave of chittering Scatterer minions!',
        ],
        'heather_bear' => [
            'name' => 'Heather the Roller-Skating Circus Bear',
            'tier' => 'neighborhood',
            'tier_label' => '`6Neighborhood Boss`0',
            'min_level' => 3,
            'hp_mult' => 1.25,
            'atk_mult' => 1.12,
            'def_mult' => 1.02,
            'box_tier' => 'bronze',
            'star_pref' => 'stars_neighborhood',
            'map_pref' => 'has_neighborhood_map',
            'map_name' => 'Persistent Neighborhood Map',
            'gold_mult' => 320,
            'gems' => 1,
            'weapon' => 'Steel-Wheeled Roller Skates & Mauling Circus Claws',
            'intro' => 'Wearing a tattered pink tutu and rusted roller skates from Grimaldi\'s Traveling Circus, a massive black bear barrels down the chamber ramp at 30 miles per hour!',
        ],
        'divider' => [
            'name' => 'The Divider (Skyfowl Settlement Terror)',
            'tier' => 'neighborhood',
            'tier_label' => '`6Neighborhood Boss`0',
            'min_level' => 5,
            'hp_mult' => 1.35,
            'atk_mult' => 1.18,
            'def_mult' => 1.08,
            'box_tier' => 'bronze',
            'star_pref' => 'stars_neighborhood',
            'map_pref' => 'has_neighborhood_map',
            'map_name' => 'Persistent Neighborhood Map',
            'gold_mult' => 400,
            'gems' => 2,
            'weapon' => 'Mitosis Bone-Cleaver & Splitting Tendrils',
            'intro' => 'Outside the Medium Skyfowl Settlement, a grotesque biological monstrosity splits its own torso in half, lunging from two angles at once!',
        ],
        'ball_of_swine' => [
            'name' => 'The Ball of Swine ("The Porkchop Express")',
            'tier' => 'borough',
            'tier_label' => '`7Borough Boss`0',
            'min_level' => 4,
            'hp_mult' => 1.55,
            'atk_mult' => 1.25,
            'def_mult' => 1.15,
            'box_tier' => 'silver',
            'star_pref' => 'stars_borough',
            'map_pref' => 'has_borough_guide',
            'map_name' => 'Borough Field Guide',
            'gold_mult' => 550,
            'gems' => 2,
            'weapon' => 'Twenty-Foot Rolling Sphere of Fused Razorbacks',
            'intro' => 'Hundreds of shrieking hogs fused into a single twenty-foot rolling meat boulder smash through the cobblestone chamber pillars, threatening to flatten everything in their path!',
        ],
        'street_preacher' => [
            'name' => 'The Rebalanced Street Preacher',
            'tier' => 'borough',
            'tier_label' => '`7Borough Boss`0',
            'min_level' => 6,
            'hp_mult' => 1.65,
            'atk_mult' => 1.32,
            'def_mult' => 1.20,
            'box_tier' => 'silver',
            'star_pref' => 'stars_borough',
            'map_pref' => 'has_borough_guide',
            'map_name' => 'Borough Field Guide',
            'gold_mult' => 680,
            'gems' => 3,
            'weapon' => 'Showrunner-Buffed Megaphone Shockwave & Hellfire Sermon',
            'intro' => 'Recently upgraded from a Neighborhood Boss to a Borough Boss by the Borant showrunners during a mid-floor balance patch, the Street Preacher raises a crackling megaphone!',
        ],
        'krakaren_clone' => [
            'name' => 'The Krakaren Clone (MLM Hive-Mother)',
            'tier' => 'borough',
            'tier_label' => '`7Borough Boss`0',
            'min_level' => 8,
            'hp_mult' => 1.75,
            'atk_mult' => 1.38,
            'def_mult' => 1.25,
            'box_tier' => 'silver',
            'star_pref' => 'stars_borough',
            'map_pref' => 'has_borough_guide',
            'map_name' => 'Borough Field Guide',
            'gold_mult' => 800,
            'gems' => 3,
            'weapon' => 'Neurotoxic Nutritional Shake Tentacles & Downline Guilt Trip',
            'intro' => 'A writhing cephalopod matriarch demands to speak to your Dungeon Manager while whipping twelve acidic tentacles across the chamber floor!',
        ],
        'grimaldi' => [
            'name' => 'Grimaldi the Pestiferous Vine',
            'tier' => 'city',
            'tier_label' => '`^City Boss`0',
            'min_level' => 9,
            'hp_mult' => 2.00,
            'atk_mult' => 1.48,
            'def_mult' => 1.32,
            'box_tier' => 'gold',
            'star_pref' => 'stars_city',
            'map_pref' => 'has_map_of_stars',
            'map_name' => 'Map of the Stars',
            'gold_mult' => 1100,
            'gems' => 4,
            'weapon' => 'Over City Soul-Crystal Roots & Undead Calliope Blast',
            'intro' => 'Under the rotted canvas of the Big Top, Grimaldi\'s collective vine consciousness pulses with the unstable glow of a stolen Soul Crystal!',
        ],
        'shi_maria' => [
            'name' => 'Shi Maria, The Bedlam Bride',
            'tier' => 'city',
            'tier_label' => '`^City Boss`0',
            'min_level' => 11,
            'hp_mult' => 2.20,
            'atk_mult' => 1.58,
            'def_mult' => 1.40,
            'box_tier' => 'gold',
            'star_pref' => 'stars_city',
            'map_pref' => 'has_map_of_stars',
            'map_name' => 'Map of the Stars',
            'gold_mult' => 1400,
            'gems' => 5,
            'weapon' => 'Insanity Webbing, Venomous Mandibles & Third-Eye Hex',
            'intro' => 'An arachnid nightmare draped in a bloodstained bridal veil descends from the chamber ceiling, whispering psychic curses that try to tattoo her eye onto your chest!',
        ],
        'general_imogen' => [
            'name' => 'General Imogen (Province High Commander)',
            'tier' => 'province',
            'tier_label' => '`5Province Boss`0',
            'min_level' => 13,
            'hp_mult' => 2.50,
            'atk_mult' => 1.72,
            'def_mult' => 1.50,
            'box_tier' => 'celestial',
            'star_pref' => 'stars_province',
            'map_pref' => 'has_fault_atlas',
            'map_name' => 'Scolopendra Fault Atlas',
            'gold_mult' => 2000,
            'gems' => 7,
            'weapon' => 'Nine-Tier Arcane Bombardment & Dread-Legion Runeblade',
            'intro' => 'Flanked by burning war banners, Province Boss General Imogen unleashes a barrage of abyssal spellfire that shakes the entire Dungeon quadrant!',
        ],
    ];
}

/**
 * Formats a Crawler's permanent Boss Kill Star string (`★`) for HUD and Bio display.
 */
function dungeonbosses_format_stars(?int $userId = null): string
{
    $nb = (int) get_module_pref('stars_neighborhood', 'dungeonbosses', $userId);
    $bo = (int) get_module_pref('stars_borough', 'dungeonbosses', $userId);
    $ci = (int) get_module_pref('stars_city', 'dungeonbosses', $userId);
    $pr = (int) get_module_pref('stars_province', 'dungeonbosses', $userId);

    $parts = [];
    if ($pr > 0) {
        $parts[] = sprintf('`5✪%d`0', $pr);
    }
    if ($ci > 0) {
        $parts[] = sprintf('`^★%d`0', $ci);
    }
    if ($bo > 0) {
        $parts[] = sprintf('`7★%d`0', $bo);
    }
    if ($nb > 0) {
        $parts[] = sprintf('`6★%d`0', $nb);
    }

    return implode(' ', $parts);
}

/**
 * Awards a Boss Box (or Survivor's Box) via `systemai` if active.
 */
function dungeonbosses_grant_box(string $tier, string $label): void
{
    if (function_exists('systemai_grant_box')) {
        systemai_grant_box($tier, 1);
    } elseif (is_module_active('systemai')) {
        $prefKey = 'box_' . $tier;
        $cur = (int) get_module_pref($prefKey, 'systemai');
        set_module_pref($prefKey, $cur + 1, 'systemai');
    }

    output(
        '`n`^`b[BOSS LOOT BOX]:`b `@The System AI deposits a `^`b%s (%s Tier)`b`@ directly into your inventory!`0`n',
        $label,
        strtoupper($tier)
    );
}

function dungeonbosses_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'newday':
            set_module_pref('attempts_today', 0);
            set_module_pref('smoke_used_today', 0);
            set_module_pref('active_boss_id', '');
            set_module_pref('active_boss_hp', 0);

            // Grant daily Cartography passive perks from persistent boss maps
            $bonusTurns = 0;
            $mapNames = [];
            if ((bool) get_module_pref('has_neighborhood_map')) {
                $bonusTurns += 1;
                $mapNames[] = 'Neighborhood Map';
            }
            if ((bool) get_module_pref('has_borough_guide')) {
                $bonusTurns += 1;
                $mapNames[] = 'Borough Field Guide';
            }
            if ((bool) get_module_pref('has_map_of_stars')) {
                $bonusTurns += 1;
                $mapNames[] = 'Map of the Stars';
            }
            if ((bool) get_module_pref('has_fault_atlas')) {
                $bonusTurns += 2;
                $mapNames[] = 'Scolopendra Fault Atlas';
            }

            if ($bonusTurns > 0) {
                $session['user']['turns'] = (int) $session['user']['turns'] + $bonusTurns;
                output(
                    '`n`#`b[PERSISTENT BOSS CARTOGRAPHY]:`b `2Your HUD overlays routes from your `^%s`2, granting `@+%d Floor Stamina Turn(s)`2 for today\'s crawl!`0`n',
                    implode(', ', $mapNames),
                    $bonusTurns
                );
            }
            break;

        case 'forest':
            addnav('Dungeon Boss Chambers');
            addnav('`$Boss Chambers & Field Guides`0', 'runmodule.php?module=dungeonbosses');
            break;

        case 'village':
            tlschema($args['schemas']['fightnav'] ?? 'village');
            addnav($args['fightnav'] ?? 'Combat Corridors');
            tlschema();
            addnav('`$Dungeon Boss Chambers`0', 'runmodule.php?module=dungeonbosses');
            break;

        case 'battle-victory':
            $chance = (int) get_module_setting('roaming_discovery_chance');
            if (e_rand(1, 100) <= $chance) {
                $intel = (int) get_module_pref('boss_intel') + 1;
                set_module_pref('boss_intel', $intel);
                output(
                    '`n`$`b[BOSS CHAMBER RADAR PING]:`b `7Your minimap picks up acoustic vibrations from a nearby Boss Chamber! You gain `^+1 Boss Radar Intel`7 (Total: `^%d`7 — increases damage inside Boss Chambers).`0`n',
                    $intel
                );
            }
            break;

        case 'charstats':
            $stars = dungeonbosses_format_stars();
            if ($stars !== '') {
                addcharstat('Crawler Status');
                addcharstat('Boss Stars', $stars);
            }
            break;

        case 'bioinfo':
            $acctId = (int) ($args['acctid'] ?? 0);
            if ($acctId > 0) {
                $stars = dungeonbosses_format_stars($acctId);
                if ($stars !== '') {
                    output('`^Dungeon Boss Kill Stars:`0 %s`n', $stars);
                }
            }
            break;
    }

    return $args;
}

function dungeonbosses_run(): void
{
    global $session;

    $op = (string) httpget('op');
    $catalog = dungeonbosses_get_catalog();
    $level = max(1, (int) ($session['user']['level'] ?? 1));
    $dks = max(0, (int) ($session['user']['dragonkills'] ?? 0));
    $attemptsToday = (int) get_module_pref('attempts_today');
    $attemptsLimit = max(1, (int) get_module_setting('daily_boss_attempts'));
    $intel = (int) get_module_pref('boss_intel');
    $activeBossId = (string) get_module_pref('active_boss_id');

    page_header('Dungeon Boss Chambers & Cartography');

    // If the Crawler is already locked inside an active Boss Chamber, force them into the chamber loop
    if ($activeBossId !== '' && isset($catalog[$activeBossId]) && $op !== 'fight_round' && $op !== 'smoke_escape') {
        $op = 'chamber_status';
    }

    // 1. Enter a Boss Chamber
    if ($op === 'enter') {
        $bid = (string) httpget('bid');
        if (!isset($catalog[$bid])) {
            output('`$`b[CHAMBER SEALED]:`b `7That Boss Chamber does not exist on this quadrant.`0`n`n');
        } elseif ($attemptsToday >= $attemptsLimit) {
            output('`$`b[DAILY BOSS LIMIT]:`b `7The Borant Showrunners only unlock `^%d / %d`7 Boss Chamber seals per Crawler each broadcast cycle.`0`n`n', $attemptsToday, $attemptsLimit);
        } elseif ((int) $session['user']['turns'] < 1) {
            output('`$`b[EXHAUSTED]:`b `7You need at least `^1 Floor Stamina Turn`7 to breach a Boss Chamber.`0`n`n');
        } elseif ($level < $catalog[$bid]['min_level']) {
            output('`$`b[LEVEL LOCKED]:`b `7Mordecai grabs your collar: `#"Are you suicidal?! You need to be at least Level %d before entering %s\'s chamber!"`0`n`n', $catalog[$bid]['min_level'], $catalog[$bid]['name']);
        } else {
            $boss = $catalog[$bid];
            $session['user']['turns'] = max(0, (int) $session['user']['turns'] - 1);
            set_module_pref('attempts_today', $attemptsToday + 1);
            $attemptsToday++;

            $playerMaxHp = max(20, (int) ($session['user']['maxhitpoints'] ?? 20));
            $bossMaxHp = max(35, (int) round($playerMaxHp * $boss['hp_mult']) + ($level * 8) + ($dks * 10));

            set_module_pref('active_boss_id', $bid);
            set_module_pref('active_boss_hp', $bossMaxHp);
            set_module_pref('active_boss_maxhp', $bossMaxHp);
            set_module_pref('active_boss_round', 1);
            $activeBossId = $bid;

            output('`$`b[BOSS CHAMBER SEALED!]:`b `4Heavy blast doors slam shut behind you! On early floors, Crawlers cannot flee a Boss Chamber once combat begins!`0`n`n');
            output('`^`b%s`b `7(%s)`0`n', $boss['name'], $boss['tier_label']);
            output('`3%s`0`n`n', $boss['intro']);

            if ($boss['tier'] === 'borough' && !(bool) get_module_pref('unlocked_borough_ach')) {
                set_module_pref('unlocked_borough_ach', 1);
                output('`5`b[NEW ACHIEVEMENT! Borough Boss!]:`b `#You have entered a Borough Boss chamber for the first time! Statistically speaking, this is where most amateur crawlers become a cautionary clip on the recap show!`0`n`n');
                dungeonbosses_grant_box('bronze', 'Borough Boss! Achievement Box');
            }

            $op = 'chamber_status';
        }
    }

    // 2. Emergency Smoke Curtain Escape
    if ($op === 'smoke_escape' && $activeBossId !== '' && isset($catalog[$activeBossId])) {
        if ((bool) get_module_pref('smoke_used_today')) {
            output('`$`b[NO SMOKE CURTAINS LEFT]:`b `7You already burned your emergency Smoke Curtain today! The Chamber doors remain locked—you must fight!`0`n`n');
            $op = 'chamber_status';
        } else {
            set_module_pref('smoke_used_today', 1);
            set_module_pref('active_boss_id', '');
            set_module_pref('active_boss_hp', 0);
            $activeBossId = '';
            output('`#`b[EMERGENCY SMOKE CURTAIN DEPLOYED!]:`b `2You detonate a Goblin Smoke Curtain at your feet and dive through a maintenance chute before the Boss can finish you off! (Note: A Boss that is escaped yields no Boss Box.)`0`n`n');
        }
    }

    // 3. Execute a Boss Chamber Combat Round
    if ($op === 'fight_round' && $activeBossId !== '' && isset($catalog[$activeBossId])) {
        $boss = $catalog[$activeBossId];
        $stance = (string) httpget('stance');
        $bossHp = (int) get_module_pref('active_boss_hp');
        $bossMaxHp = max(1, (int) get_module_pref('active_boss_maxhp'));
        $round = (int) get_module_pref('active_boss_round');

        $playerAtk = max(4, (int) ($session['user']['attack'] ?? 4));
        $playerDef = max(4, (int) ($session['user']['defense'] ?? 4));
        $bossAtk = max(4, (int) round($playerDef * $boss['atk_mult']) + $level);
        $bossDef = max(3, (int) round($playerAtk * $boss['def_mult']));

        // Apply Boss Radar Intel bonus (+5 percent damage per intel token, up to +35 percent)
        $intelBonusPct = min(35, $intel * 5);
        $atkMult = 1.0 + ($intelBonusPct / 100.0);
        $defMult = 1.0;
        $stanceLabel = 'Standard Weapon Assault';

        if ($stance === 'blitz') {
            $atkMult += 0.35;
            $defMult -= 0.20;
            $stanceLabel = 'Sapper Demolition & Spell Blitz';
        } elseif ($stance === 'read') {
            $atkMult -= 0.12;
            $defMult += 0.32;
            $stanceLabel = 'Mordecai\'s Defensive Pattern Read';
        }

        $rawPlayerDmg = max(3, (int) round(($playerAtk * $atkMult * e_rand(85, 125) / 100) - ($bossDef * 0.45)));
        $rawBossDmg = max(2, (int) round(($bossAtk * e_rand(80, 120) / 100) - ($playerDef * $defMult * 0.55)));

        $bossHp = max(0, $bossHp - $rawPlayerDmg);
        set_module_pref('active_boss_hp', $bossHp);
        set_module_pref('active_boss_round', $round + 1);

        // Unlock "Boss Babe!" achievement on first ever boss damage dealt
        if (!(bool) get_module_pref('unlocked_boss_babe')) {
            set_module_pref('unlocked_boss_babe', 1);
            output('`5`b[NEW ACHIEVEMENT! Boss Babe!]:`b `#You just damaged a Dungeon Boss for the very first time! According to the System AI, this is the single most common final achievement for crawlers who survive the tutorial!`0`n`n');
        }

        output('`b`#--- BOSS CHAMBER ROUND %d (%s) ---`b`0`n', $round, $stanceLabel);
        output('`@You strike `^%s`@ for `^`b%d`b`@ damage!`0`n', $boss['name'], $rawPlayerDmg);

        if ($bossHp > 0) {
            $session['user']['hitpoints'] = (int) $session['user']['hitpoints'] - $rawBossDmg;
            output(
                '`$%s`$ unleashes `^%s`$, hitting you for `$`b%d`b`$ damage!`0`n`n',
                $boss['name'],
                $boss['weapon'],
                $rawBossDmg
            );
        }

        // Check Victory vs Defeat vs Continue
        if ($bossHp <= 0) {
            // Consume up to 2 intel tokens on kill
            if ($intel > 0) {
                set_module_pref('boss_intel', max(0, $intel - 2));
            }
            set_module_pref('active_boss_id', '');
            set_module_pref('active_boss_hp', 0);
            $activeBossId = '';

            $goldReward = $boss['gold_mult'] * $level;
            $gemReward = $boss['gems'];
            $xpReward = max(120, (int) round(((int) $session['user']['experience']) * 0.08) + ($level * 90));

            $session['user']['gold'] += $goldReward;
            $session['user']['gems'] += $gemReward;
            $session['user']['experience'] += $xpReward;

            $curStars = (int) get_module_pref($boss['star_pref']) + 1;
            set_module_pref($boss['star_pref'], $curStars);

            output('`n`@`b========================================================`b`0`n');
            output('`@`b[DUNGEON BOSS SLAIN!]:`b `2You have defeated `^%s`2 (%s)!`0`n', $boss['name'], $boss['tier_label']);
            output(
                '`2Rewards: `^%s Gold Credits`2, `^%d Sponsor Tokens (Gems)`2, `^%s EXP`2, and a permanent `^Boss Kill Star (★)`2 beside your name!`0`n',
                number_format($goldReward),
                $gemReward,
                number_format($xpReward)
            );

            // Grant Boss Box matching tier
            dungeonbosses_grant_box($boss['box_tier'], $boss['name'] . ' Boss Box');

            // Persistent Cartography drop
            if (!(bool) get_module_pref($boss['map_pref'])) {
                set_module_pref($boss['map_pref'], 1);
                output(
                    '`#`b[PERSISTENT LOOT DROPPED]:`b `2You loot the `^`b%s`b`2 from the Boss\'s corpse! It permanently upgrades your HUD minimap and grants extra Floor Stamina Turns every New Day!`0`n',
                    $boss['map_name']
                );
            }

            // Survivor's Box check (ending battle at or below threshold percent HP)
            $hpNow = max(1, (int) $session['user']['hitpoints']);
            $maxHpNow = max(1, (int) $session['user']['maxhitpoints']);
            $survThreshold = (int) get_module_setting('survivor_box_hp_pct');
            if (($hpNow * 100) <= ($maxHpNow * $survThreshold)) {
                output('`$`b[CLUTCH SURVIVOR BONUS!]:`b `2You survived the Boss Chamber with less than %d percent Hitpoints remaining!`0`n', $survThreshold);
                dungeonbosses_grant_box('silver', 'Clutch Survivor\'s Box');
            }

            output('`@`b========================================================`b`0`n`n');

            addnews(
                '`^Crawler `^%s`^ slew the %s `^`b%s`b`^, earning a Boss Box and a permanent Boss Star (`^★`0`^)!',
                $session['user']['name'],
                $boss['tier_label'],
                $boss['name']
            );
        } elseif ((int) $session['user']['hitpoints'] <= 0) {
            // Crawler survived at 1 HP via emergency Bopca drone extraction (or knocked out)
            $session['user']['hitpoints'] = 1;
            $goldLoss = (int) round(((int) $session['user']['gold']) * 0.15);
            $session['user']['gold'] = max(0, (int) $session['user']['gold'] - $goldLoss);
            set_module_pref('active_boss_id', '');
            set_module_pref('active_boss_hp', 0);
            $activeBossId = '';

            output('`$`b[CHAMBERWIPE — EMERGENCY DRONE EXTRACTION]:`b `4%s batters you to the floor! Just before the killing blow lands, a Borant camera drone teleports your broken body out of the chamber for dramatic replay value. You survive at `^1 Hitpoint`4, losing `^%s Gold Credits`4 in dropped gear!`0`n`n', $boss['name'], number_format($goldLoss));
            addnews(
                '`$%s`4 was mauled by `^%s`4 inside a sealed Boss Chamber and barely escaped with 1 HP!',
                $session['user']['name'],
                $boss['name']
            );
        } else {
            $op = 'chamber_status';
        }
    }

    // 4. Render Active Sealed Chamber Combat HUD
    if ($op === 'chamber_status' && $activeBossId !== '' && isset($catalog[$activeBossId])) {
        $boss = $catalog[$activeBossId];
        $bossHp = (int) get_module_pref('active_boss_hp');
        $bossMaxHp = max(1, (int) get_module_pref('active_boss_maxhp'));
        $round = (int) get_module_pref('active_boss_round');
        $playerHp = (int) $session['user']['hitpoints'];
        $playerMaxHp = max(1, (int) $session['user']['maxhitpoints']);

        output('`b`$=== SEALED BOSS CHAMBER: %s (%s) ===`b`0`n', $boss['name'], $boss['tier_label']);
        output('`7Combat Round: `^#%d`7 | Boss Radar Intel Bonus: `@+%d percent DMG`0`n', $round, min(35, $intel * 5));
        output('`4Boss Hitpoints:`0 `^%d / %d`0 | `@Your Hitpoints:`0 `^%d / %d`0`n`n', $bossHp, $bossMaxHp, $playerHp, $playerMaxHp);

        addnav('Boss Chamber Tactics');
        addnav('`@Standard Assault (Balanced)`0', 'runmodule.php?module=dungeonbosses&op=fight_round&stance=standard');
        addnav('`$Sapper & Spell Blitz (+35 percent ATK)`0', 'runmodule.php?module=dungeonbosses&op=fight_round&stance=blitz');
        addnav('`#Mordecai\'s Defensive Read (+32 percent DEF)`0', 'runmodule.php?module=dungeonbosses&op=fight_round&stance=read');

        if (!(bool) get_module_pref('smoke_used_today')) {
            addnav('Emergency Escape');
            addnav('`7Deploy Smoke Curtain (1/Day)`0', 'runmodule.php?module=dungeonbosses&op=smoke_escape');
        }

        page_footer();
        return;
    }

    // 5. Render Main Boss Chamber Lobby & Cartography Dossier
    $stars = dungeonbosses_format_stars();
    output('`b`$=== WORLD DUNGEON BOSS CHAMBERS & FIELD GUIDES ===`b`0`n');
    output(
        '`7Daily Chamber Challenges: `^%d / %d`7 | Boss Radar Intel: `^%d`7 (+%d percent Boss DMG) | Earned Boss Stars: %s`0`n`n',
        $attemptsToday,
        $attemptsLimit,
        $intel,
        min(35, $intel * 5),
        $stars !== '' ? $stars : '`7None yet`0'
    );

    // Cartography Status
    $mapsOwned = [];
    if ((bool) get_module_pref('has_neighborhood_map')) {
        $mapsOwned[] = '`6Neighborhood Map (+1 Turn/day)`0';
    }
    if ((bool) get_module_pref('has_borough_guide')) {
        $mapsOwned[] = '`7Borough Field Guide (+1 Turn/day)`0';
    }
    if ((bool) get_module_pref('has_map_of_stars')) {
        $mapsOwned[] = '`^Map of the Stars (+1 Turn/day)`0';
    }
    if ((bool) get_module_pref('has_fault_atlas')) {
        $mapsOwned[] = '`5Scolopendra Fault Atlas (+2 Turns/day)`0';
    }
    output(
        '`#Persistent Cartography Unlocked:`0 %s`n`n',
        count($mapsOwned) > 0 ? implode(' | ', $mapsOwned) : '`7Defeat a Boss from each tier to loot persistent HUD maps!`0'
    );

    rawoutput('<table class="table table-striped table-dark">');
    rawoutput('<thead><tr><th>Boss &amp; Chamber Lore</th><th>Tier</th><th>Min Level</th><th>Rewards &amp; Persistent Loot</th></tr></thead><tbody>');
    foreach ($catalog as $bid => $b) {
        $goldReward = $b['gold_mult'] * $level;
        rawoutput(sprintf(
            '<tr><td><strong>%s</strong><br><small>%s</small></td><td>%s</td><td>Level %d+</td><td><strong>%s Boss Box</strong> + %s Gold + %d Gems<br><small>Drops: <em>%s</em></small></td></tr>',
            htmlentities($b['name'], ENT_QUOTES, 'UTF-8'),
            htmlentities($b['intro'], ENT_QUOTES, 'UTF-8'),
            ucfirst($b['tier']),
            $b['min_level'],
            ucfirst($b['box_tier']),
            number_format($goldReward),
            $b['gems'],
            htmlentities($b['map_name'], ENT_QUOTES, 'UTF-8')
        ));
    }
    rawoutput('</tbody></table>');

    addnav('Enter Boss Chamber (1 Turn)');
    foreach ($catalog as $bid => $b) {
        if ($level >= $b['min_level']) {
            addnav(
                sprintf('[%s] %s', ucfirst($b['tier']), $b['name']),
                'runmodule.php?module=dungeonbosses&op=enter&bid=' . $bid
            );
        }
    }

    addnav('Return');
    addnav('Return to Dungeon Corridors', 'forest.php');
    addnav('Return to Safe Room Hub', 'village.php');

    page_footer();
}
