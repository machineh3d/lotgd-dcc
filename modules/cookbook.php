<?php

declare(strict_types=1);

/**
 * Module: Tutorial Guild Orientation, Crawler's Cookbook & Bestiary (cookbook.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Dungeon_Crawler_Carl_Wiki
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Mordecai
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Dungeon
 *
 * Description:
 *   Implements the new-player onboarding briefing and in-game reference manual:
 *   - First-Login Tutorial Guild Orientation: Shows brand-new Crawlers a concise mission
 *     briefing explaining the game loop, Floors 1-18, Stamina, Safe Rooms, and progression.
 *   - Crawler's Reference Manual: Area-by-area guide to every DCC hub location plus a full
 *     directory of Floor 1 Specialties, Floor 3 Races/Classes, and Floor 6 Subclasses.
 *   - Progressive Dungeon Bestiary: Tracks every creature slain (`battle-victory` hook) and
 *     progressively unlocks Creature Name (1+ kill), Combat Stats & Weapon (3+ kills), and
 *     Full System AI Dossier & Mastery Badge (5+ kills).
 */

function cookbook_getmoduleinfo(): array
{
    return [
        'name' => 'Crawler\'s Cookbook, Orientation & Progressive Bestiary',
        'version' => '0.5.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Cookbook & Bestiary Settings,title',
            'kills_for_stats' => 'Kills required to unlock a creature\'s Combat Stats & Weapon,range,1,20,1|3',
            'kills_for_mastery' => 'Kills required to unlock Full System AI Dossier & Mastery Badge,range,2,50,1|5',
            'show_first_login_banner' => 'Display Tutorial Guild Orientation prompt to brand-new Crawlers in Safe Room?,bool|1',
        ],
        'prefs' => [
            'Crawler Cookbook Telemetry,title',
            'seen_orientation' => 'Has read or dismissed the Tutorial Guild Orientation?,bool|0',
            'bestiary_kills' => 'JSON map of slain creature names to kill counts,text|{}',
        ],
    ];
}

function cookbook_install(): bool
{
    module_addhook('village');
    module_addhook('village-desc');
    module_addhook('battle-victory');
    module_addhook('bioinfo');
    return true;
}

function cookbook_uninstall(): bool
{
    return true;
}

/**
 * Decodes the player's Bestiary kill map safely.
 *
 * @return array<string, int>
 */
function cookbook_get_kills(?int $userId = null): array
{
    $raw = (string) get_module_pref('bestiary_kills', 'cookbook', $userId);
    if ($raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    $clean = [];
    foreach ($decoded as $k => $v) {
        $clean[(string) $k] = (int) $v;
    }
    return $clean;
}

/**
 * Normalizes a creature name for consistent Bestiary matching.
 */
function cookbook_normalize_name(string $name): string
{
    // Strip LotGD color codes (`x) and trim whitespace
    $stripped = (string) preg_replace('/`./', '', $name);
    return strtolower(trim($stripped));
}

function cookbook_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'village':
            tlschema($args['schemas']['infonav'] ?? 'village');
            addnav($args['infonav'] ?? 'Broadcast Feed');
            tlschema();
            addnav('`#Crawler\'s Cookbook & Bestiary`0', 'runmodule.php?module=cookbook');
            break;

        case 'village-desc':
            if (
                (int) get_module_setting('show_first_login_banner', 'cookbook') === 1
                && !(bool) get_module_pref('seen_orientation', 'cookbook')
            ) {
                addnav('', 'runmodule.php?module=cookbook&op=orientation');
                addnav('', 'runmodule.php?module=cookbook&op=dismiss_intro');
                rawoutput(
                    '<div style="background:linear-gradient(135deg,#0d1520 0%,#151b2b 100%);border:2px solid #00f0ff;border-left:6px solid #ff2a85;border-radius:6px;padding:14px 18px;margin:10px 0 16px 0;font-family:Consolas,monospace;color:#e6edf3;">'
                    . '<div style="color:#00f0ff;font-weight:900;font-size:1.08rem;letter-spacing:1px;margin-bottom:6px;">&#9679; TUTORIAL GUILD ORIENTATION — NEW CRAWLER BRIEFING</div>'
                    . '<p style="margin:0 0 8px 0;line-height:1.45;">Welcome to <strong>Dungeon Crawler World: Earth</strong>! Your goal is to survive the <strong>World Dungeon</strong>: patrol the <strong>Dungeon Corridors</strong> to earn EXP and Gold Credits, upgrade your gear at the <strong>Bopca Munitions Counter</strong> and <strong>Tactical Outfitter</strong>, challenge <strong>Mordecai\'s Game Guide Guildhall</strong> to reach Level 15, and defeat the <strong>Stairwell Floor Guardian</strong> to descend deeper toward Floor 18.</p>'
                    . '<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">'
                    . '<a href="runmodule.php?module=cookbook&op=orientation" style="background:#00f0ff;color:#0b0f14;padding:5px 12px;border-radius:4px;font-weight:800;text-decoration:none;">Open Full Orientation &amp; Manual</a>'
                    . '<a href="runmodule.php?module=cookbook&op=dismiss_intro" style="background:rgba(255,42,133,0.18);color:#ff7eb6;border:1px solid #ff2a85;padding:5px 12px;border-radius:4px;font-weight:700;text-decoration:none;">Dismiss Briefing</a>'
                    . '</div></div>'
                );
            }
            break;

        case 'battle-victory':
            $badguy = $args['badguy'] ?? null;
            if (is_array($badguy) && !empty($badguy['creaturename'])) {
                $rawName = (string) $badguy['creaturename'];
                $norm = cookbook_normalize_name($rawName);
                if ($norm !== '') {
                    $kills = cookbook_get_kills();
                    $prev = $kills[$norm] ?? 0;
                    $now = $prev + 1;
                    $kills[$norm] = $now;
                    set_module_pref('bestiary_kills', json_encode($kills, JSON_UNESCAPED_UNICODE), 'cookbook');

                    $statsReq = max(1, (int) get_module_setting('kills_for_stats', 'cookbook'));
                    $masterReq = max($statsReq + 1, (int) get_module_setting('kills_for_mastery', 'cookbook'));
                    $displayName = (string) preg_replace('/`./', '', $rawName);

                    if ($now === 1) {
                        output('`n`#`b[COOKBOOK BESTIARY]:`b `2New hostile logged: `^%s`2! (Slay %d total to unlock full Combat Stats)`0`n', $displayName, $statsReq);
                    } elseif ($now === $statsReq) {
                        output('`n`#`b[COOKBOOK BESTIARY]:`b `2Combat Stats & Weapon intel unlocked for `^%s`2! (Slay %d total for Mastery)`0`n', $displayName, $masterReq);
                    } elseif ($now === $masterReq) {
                        output('`n`^`b[COOKBOOK BESTIARY MASTERED!]:`b `@Full System AI Dossier unlocked for `^%s`@!`0`n', $displayName);
                    }
                }
            }
            break;

        case 'bioinfo':
            $acctId = (int) ($args['acctid'] ?? 0);
            if ($acctId > 0) {
                $kills = cookbook_get_kills($acctId);
                $discovered = count($kills);
                if ($discovered > 0) {
                    $masterReq = max(2, (int) get_module_setting('kills_for_mastery', 'cookbook'));
                    $mastered = 0;
                    foreach ($kills as $cnt) {
                        if ($cnt >= $masterReq) {
                            $mastered++;
                        }
                    }
                    output('`#Cookbook Bestiary Entries:`0 `^%d Discovered`0 `7(%d Mastered)`0`n', $discovered, $mastered);
                }
            }
            break;
    }

    return $args;
}

/**
 * Generates a tactical System AI note for a mastered creature based on its level and stats.
 */
function cookbook_generate_tactical_note(string $name, int $lvl, int $hp, int $atk, int $def): string
{
    if ($lvl <= 3) {
        return sprintf('Early-floor corridor fodder (Level %d). Safe for warming up Floor 1 Survival Specialties and farming starter Gold Credits.', $lvl);
    }
    if ($lvl <= 7) {
        return sprintf('Mid-tier corridor threat (Level %d, %d Base HP). Keep your Armor updated at the Safe Room Tactical Outfitter before engaging repeatedly.', $lvl, $hp);
    }
    if ($lvl <= 11) {
        return sprintf('Heavy Floor combatant (Level %d, %d ATK / %d DEF). Bring Sapper explosives, Bopca Dirty Shirleys, or a Floor 3 Class skill.', $lvl, $atk, $def);
    }
    return sprintf('Apex Floor predator (Level %d, %d Base HP). High lethal rating—stack Personal Space buffs, Mercenaries, or Divine Smites before fighting.', $lvl, $hp);
}

function cookbook_run(): void
{
    global $session;

    $op = (string) httpget('op');
    if ($op === 'dismiss_intro') {
        set_module_pref('seen_orientation', 1, 'cookbook');
        redirect('village.php');
        return;
    }

    if ($op === '' || $op === 'orientation') {
        set_module_pref('seen_orientation', 1, 'cookbook');
    }

    page_header('The Dungeon Anarchist\'s Cookbook — Crawler Reference Manual');

    if ($op === '' || $op === 'orientation') {
        output('`b`#=== TUTORIAL GUILD ORIENTATION: HOW TO SURVIVE THE WORLD DUNGEON ===`b`0`n`n');
        output('`3The Borant Corporation collapsed Earth\'s surface buildings into an 18-Floor subterranean game show watched by trillions across the Syndicate. Here is your survival loop:`0`n`n');

        output('`^`b1. Patrol the Dungeon Corridors & Level Up (Levels 1 to 15)`b`0`n');
        output('`7Enter the `#Dungeon Corridors`7 to spend your daily Floor Stamina (Turns) fighting biologically printed mobs for EXP and Gold Credits. Once you have enough EXP, visit `#Mordecai\'s Game Guide Guildhall`7 to pass your level evaluation fight.`0`n`n');

        output('`^`b2. Gear Up & Open Loot Boxes in the Safe Room`b`0`n');
        output('`7Spend Gold Credits at the `#Bopca Munitions & Explosives`7 counter and `#Safe Room Tactical Outfitter`7, bond with a pet at the `#Enhanced Pet Biscuit Menagerie`7, and crack open your System AI `5Loot Boxes`7 inside the Safe Room Vault.`0`n`n');

        output('`^`b3. Descend the Stairwell (Floors 1 through 18)`b`0`n');
        output('`7At Level 15, locate the `$Floor Boss & Stairwell`7 in the Corridors. Defeating the Stairwell Guardian advances you to the next Dungeon Floor, awards permanent Stairwell Stat Points, and automatically levels up every Crafting Table in your `#Personal Space`7 by +1!`0`n`n');

        output('`^`b4. Unlock Deeper Systems as You Descend`b`0`n');
        output('`7- `^Floor 1:`7 Pick your starting Survival Specialty, run Bopca Floor Quests, and hunt Neighborhood/Borough Bosses.`n');
        output('`7- `^Floor 3:`7 Unlock full `#Syndicate Races & Classes`7 (Primal, Changeling, Persian Cat, Compensated Anarchist, etc.).`n');
        output('`7- `^Floor 6:`7 Unlock elite `#Subclass Specializations`7.`n');
        output('`7- `^Floor 10+:`7 Negotiate a `#Syndicate Exit Deal`7 with Quasar, or push all the way to Floor 18 to challenge `$Scolopendra`7!`0`n`n');
    } elseif ($op === 'areas') {
        output('`b`#=== SAFE ROOM & WORLD DUNGEON AREA DIRECTORY ===`b`0`n`n');

        $areas = [
            ['Dungeon Corridors', 'Primary combat zone. Spend Floor Stamina to fight mobs, log Bestiary entries, find Boss Radar Intel, and reach the Floor Stairwell at Level 15.'],
            ['Bopca Safe Room & Quests', 'Order Hot Dog Baskets (+HP & +1 Turn/day), Dirty Shirleys (+Charm/buffs), Mordecai\'s Glory Bound Detox Elixir, and complete multi-kill Floor Quests for Quest Boxes.'],
            ['System AI Loot Vault & Dossier', 'Open Bronze, Silver, Gold, and Celestial Loot Boxes and track all 32 System AI achievements (including 14 Classified Secret Achievements).'],
            ['Dungeon Boss Chambers', 'Sealed multi-round fights against Neighborhood, Borough, City, and Province Bosses for Boss Boxes, permanent Kill Stars, and passive-Turn HUD Maps.'],
            ['Safe Room Personal Space', 'Build Sapper\'s, Alchemy, and Engineer\'s Crafting Tables (auto-leveling on every Floor Descent) plus Princess Donut\'s Cat Tree and Alleviating Sleep Bed.'],
            ['Bopca Munitions & Tactical Outfitter', 'Upgrade your equipped Weapon and Armor for the current Floor.'],
            ['Enhanced Pet Biscuit Menagerie', 'Acquire DCC companions (Mongo, Bianca, Sir Rendlegore, Clockwork Triplicate, etc.) that fight beside you every round.'],
            ['Syndicate Sponsors & Fan Boxes', 'Fill up to 3 Patron slots for daily stipends, coded notes, Floor-Descent Benefactor Boxes, and Viewer-Voted Fan Boxes.'],
            ['Ninth Floor Faction Wars', 'Enlist in one of 5 Faction Wars armies, fund the Larracos War Chest for Citadel control buffs, and hire Veteran/War Mage mercenary squads.'],
            ['The Desperado Club Casino', 'Play Tito\'s Goblin Blackjack and The Nothing Spot Slots (watch out for Borant Gambling Addiction Lockouts—curable with Mordecai\'s Glory Bound Elixir).'],
            ['Club Vanquisher & Pantheon Shrines', 'Pledge worship to Emberus, Hellik, Taranis, or Grull for scaling Divine Smite skills—or insult the Templars to get banned for Viewer Ratings.'],
            ['Top 10 Leaderboard & After Hours', 'View top Crawlers, escalating Head Bounties, PvP PK Skulls, and do daily talk-show interviews with Odette.'],
            ['Borant Stamina Vault', 'Store unused Floor Stamina Turns across days and exchange Gold/Gems for extra Stamina.'],
        ];

        rawoutput('<table class="table table-striped table-dark">');
        rawoutput('<thead><tr><th>Area / Hub Location</th><th>What It Is For</th></tr></thead><tbody>');
        foreach ($areas as $a) {
            rawoutput(sprintf(
                '<tr><td><strong>%s</strong></td><td>%s</td></tr>',
                htmlentities($a[0], ENT_QUOTES, 'UTF-8'),
                htmlentities($a[1], ENT_QUOTES, 'UTF-8')
            ));
        }
        rawoutput('</tbody></table>');
    } elseif ($op === 'abilities') {
        output('`b`#=== SPECIALTIES, RACES & CLASSES REFERENCE ===`b`0`n`n');

        output('`^`bFloor 1 Survival Specialties (Chosen on Day 1)`b`0`n');
        rawoutput('<table class="table table-striped table-dark" style="margin-bottom:16px;">');
        rawoutput('<thead><tr><th>Specialty</th><th>Tier 1 Skill (1 Use)</th><th>Tier 2 Skill (2 Uses)</th><th>Tier 3 Skill (3 Uses)</th></tr></thead><tbody>');
        rawoutput('<tr><td><strong>Improvised Demolition</strong></td><td>Hobgoblin Flashbang</td><td>Improvised Pipe-Bomb Toss</td><td>Sapper Fuse-Chain Detonation</td></tr>');
        rawoutput('<tr><td><strong>Broadcast Glamour &amp; Magic</strong></td><td>Magic Missile Barrage</td><td>Syndicate Spotlight Shield</td><td>Prime-Time Rating Overload</td></tr>');
        rawoutput('<tr><td><strong>Close-Quarters Brawling &amp; Grit</strong></td><td>Barefoot Stomp Crush</td><td>Tactical Riot-Baton Counter</td><td>Adrenaline Berserker Grapple</td></tr>');
        rawoutput('</tbody></table>');

        output('`^`bFloor 3 Syndicate Races & Classes (Unlocked via Mordecai\'s Desk)`b`0`n');
        rawoutput('<table class="table table-striped table-dark">');
        rawoutput('<thead><tr><th>Category</th><th>Options &amp; Highlights</th></tr></thead><tbody>');
        rawoutput('<tr><td><strong>Floor 3 Races</strong></td><td><strong>Primal</strong> (+Skill Charges, +Stamina, +ATK), <strong>Changeling</strong> (Balanced +DEF), <strong>Sapient Tortoiseshell Persian Cat</strong> (+Charm &amp; Magic), <strong>Highland Caprid</strong> (High ATK/Stamina), <strong>Armored Crocodilian</strong> (Tank DEF/HP), <strong>Earth Human</strong> (Bonus Silver Earth Box).</td></tr>');
        rawoutput('<tr><td><strong>Floor 3 Classes</strong></td><td><strong>Compensated Anarchist</strong> (Explosives &amp; Gauntlets), <strong>Former Child Actor</strong> (Tiara Magic &amp; Buffs), <strong>Mass-Shifting Juggernaut</strong> (Heavy Armor &amp; Ramming), <strong>Frost Maiden</strong> (Cryo-Spells &amp; Shields).</td></tr>');
        rawoutput('<tr><td><strong>Floor 6 Subclasses</strong></td><td><strong>Agent Provocateur</strong>, <strong>Revolutionary</strong>, <strong>Guerilla</strong>, <strong>Queen Anne Chonk Warlord</strong>, <strong>Valkyrie Dreadnought</strong>, and <strong>Four-Seasons Archmage</strong>.</td></tr>');
        rawoutput('</tbody></table>');
    } elseif ($op === 'bestiary') {
        $statsReq = max(1, (int) get_module_setting('kills_for_stats', 'cookbook'));
        $masterReq = max($statsReq + 1, (int) get_module_setting('kills_for_mastery', 'cookbook'));
        $kills = cookbook_get_kills();

        $creaturesTable = db_prefix('creatures');
        $sql = "SELECT creaturename, creaturelevel, creatureweapon, creaturehealth, creatureattack, creaturedefense
                FROM {$creaturesTable}
                WHERE graveyard = 0
                ORDER BY creaturelevel ASC, creaturename ASC";
        $res = db_query($sql);

        $rows = [];
        $discoveredCount = 0;
        $masteredCount = 0;
        while ($row = db_fetch_assoc($res)) {
            $rawName = (string) ($row['creaturename'] ?? '');
            $cleanName = (string) preg_replace('/`./', '', $rawName);
            $norm = cookbook_normalize_name($rawName);
            $cnt = $kills[$norm] ?? 0;
            if ($cnt >= 1) {
                $discoveredCount++;
            }
            if ($cnt >= $masterReq) {
                $masteredCount++;
            }
            $rows[] = [
                'name' => $cleanName,
                'level' => (int) ($row['creaturelevel'] ?? 1),
                'weapon' => (string) preg_replace('/`./', '', (string) ($row['creatureweapon'] ?? '')),
                'hp' => (int) ($row['creaturehealth'] ?? 10),
                'atk' => (int) ($row['creatureattack'] ?? 1),
                'def' => (int) ($row['creaturedefense'] ?? 1),
                'kills' => $cnt,
            ];
        }

        output('`b`#=== DUNGEON ANARCHIST\'S BESTIARY ===`b`0`n');
        output(
            '`7Discovered: `^%d / %d`7 | Mastered: `@%d`7 | Unlock Tiers: `^1 Kill`7 = Identify, `^%d Kills`7 = Stats &amp; Weapon, `^%d Kills`7 = Full Tactical Dossier`0`n`n',
            $discoveredCount,
            count($rows),
            $masteredCount,
            $statsReq,
            $masterReq
        );

        rawoutput('<table class="table table-striped table-dark">');
        rawoutput('<thead><tr><th>Lvl</th><th>Creature</th><th>Kills</th><th>Combat Intel &amp; System AI Notes</th></tr></thead><tbody>');

        foreach ($rows as $r) {
            $cnt = $r['kills'];
            if ($cnt === 0) {
                rawoutput(sprintf(
                    '<tr style="opacity:0.65;"><td>%d</td><td><em>??? [UNIDENTIFIED HOSTILE]</em></td><td>0</td><td><small>Encounter and defeat this Floor %d creature to log it in your Cookbook.</small></td></tr>',
                    $r['level'],
                    $r['level']
                ));
            } elseif ($cnt < $statsReq) {
                rawoutput(sprintf(
                    '<tr><td>%d</td><td><strong>%s</strong></td><td>%d / %d</td><td><small>Identified. Slay <strong>%d more</strong> to unlock Weapon &amp; Combat Stats.</small></td></tr>',
                    $r['level'],
                    htmlentities($r['name'], ENT_QUOTES, 'UTF-8'),
                    $cnt,
                    $masterReq,
                    $statsReq - $cnt
                ));
            } elseif ($cnt < $masterReq) {
                rawoutput(sprintf(
                    '<tr><td>%d</td><td><strong>%s</strong></td><td>%d / %d</td><td><strong>Weapon:</strong> %s<br><small>Base HP: %d | ATK: %d | DEF: %d — Slay <strong>%d more</strong> for Mastery Dossier.</small></td></tr>',
                    $r['level'],
                    htmlentities($r['name'], ENT_QUOTES, 'UTF-8'),
                    $cnt,
                    $masterReq,
                    htmlentities($r['weapon'], ENT_QUOTES, 'UTF-8'),
                    $r['hp'],
                    $r['atk'],
                    $r['def'],
                    $masterReq - $cnt
                ));
            } else {
                $note = cookbook_generate_tactical_note($r['name'], $r['level'], $r['hp'], $r['atk'], $r['def']);
                rawoutput(sprintf(
                    '<tr><td>%d</td><td><strong style="color:#ffd700;">&#9733; %s</strong></td><td><strong style="color:#2ea043;">%d</strong></td><td><strong>Weapon:</strong> %s <small>(HP: %d | ATK: %d | DEF: %d)</small><br><small style="color:#00f0ff;">%s</small></td></tr>',
                    $r['level'],
                    htmlentities($r['name'], ENT_QUOTES, 'UTF-8'),
                    $cnt,
                    htmlentities($r['weapon'], ENT_QUOTES, 'UTF-8'),
                    $r['hp'],
                    $r['atk'],
                    $r['def'],
                    htmlentities($note, ENT_QUOTES, 'UTF-8')
                ));
            }
        }
        rawoutput('</tbody></table>');
    }

    addnav('Crawler\'s Cookbook');
    addnav('Tutorial Guild Orientation', 'runmodule.php?module=cookbook&op=orientation');
    addnav('Safe Room & Area Guide', 'runmodule.php?module=cookbook&op=areas');
    addnav('Specialties, Races & Classes', 'runmodule.php?module=cookbook&op=abilities');
    addnav('`^Progressive Dungeon Bestiary`0', 'runmodule.php?module=cookbook&op=bestiary');

    addnav('Return');
    addnav('Return to Safe Room Hub', 'village.php');

    page_footer();
}