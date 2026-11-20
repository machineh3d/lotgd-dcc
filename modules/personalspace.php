<?php

declare(strict_types=1);

/**
 * Module: Safe Room Personal Space & Crafting Studio (personalspace.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Personal_Space
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Category:Crafting_Tables
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Demolition_Workshop_Tab
 *
 * Description:
 *   Implements the customizable Safe Room Personal Space ("The Royal Palace of Princess Donut"):
 *   - Build and upgrade Carl's Sapper's Table, Alchemy Table, and Engineer's Table.
 *   - Implements the canonical DCC mechanic: every installed Crafting Table automatically
 *     gains +1 Level whenever the Crawler descends a Stairwell (`dragonkill` hook)!
 *   - Upgrade Princess Donut's Luxury Cat Tree & Scented Candle Lounge and the
 *     "Ultra-Stabilized Size-Adjustable Race-Adjustable Alleviating Sleep Apparatus" (Bed).
 *   - Craft daily combat explosives, potions, gear reinforcements, and trigger Carl's secret
 *     "Indestructible Crafting Table Bunker" exploit!
 */

function personalspace_getmoduleinfo(): array
{
    return [
        'name' => 'Safe Room Personal Space & Crafting Tables',
        'version' => '1.0.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Personal Space & Studio Settings,title',
            'base_table_gold' => 'Base Gold Credit cost to install/upgrade a Crafting Table,int|2000',
            'base_table_gems' => 'Base Sponsor Token (Gem) cost to install/upgrade a Crafting Table,int|4',
            'daily_crafts_allowed' => 'Maximum daily workbench crafts per broadcast cycle,int|2',
            'allow_table_exploit' => 'Allow Carl\'s Indestructible Crafting Table combat shield exploit?,bool|1',
        ],
        'prefs' => [
            'Personal Space Upgrades & Table Levels,title',
            'space_unlocked' => 'Has unlocked the Personal Space portal door?,bool|1',
            'sapper_lvl' => 'Sapper\'s Table (Demolition Workshop) Level,int|0',
            'alchemy_lvl' => 'Alchemy Table Level,int|0',
            'engineer_lvl' => 'Engineer\'s & Metal Working Table Level,int|0',
            'cattree_lvl' => 'Princess Donut\'s Royal Cat Tree Level,int|0',
            'bed_lvl' => 'Ultra-Stabilized Alleviating Sleep Apparatus (Bed) Level,int|0',
            'crafts_today' => 'Workbench crafts completed today,int|0',
            'exploit_used_today' => 'Used Indestructible Table Shield exploit today?,bool|0',
        ],
    ];
}

function personalspace_install(): bool
{
    module_addhook('village');
    module_addhook('newday');
    module_addhook('dragonkill');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function personalspace_uninstall(): bool
{
    return true;
}

/**
 * Returns the catalog of Personal Space stations, their lore, and upgrade scaling.
 *
 * @return array<string, array{
 *   pref: string,
 *   name: string,
 *   color_name: string,
 *   auto_levels_on_dk: bool,
 *   gold_mult: float,
 *   gem_mult: float,
 *   desc: string,
 *   perk_summary: string
 * }>
 */
function personalspace_get_stations(): array
{
    return [
        'sapper' => [
            'pref' => 'sapper_lvl',
            'name' => 'Sapper\'s Table (Demolition Workshop)',
            'color_name' => '`$Sapper\'s Table`0',
            'auto_levels_on_dk' => true,
            'gold_mult' => 1.0,
            'gem_mult' => 1.0,
            'desc' => 'Unlocks the Demolition Workshop Tab in your HUD, stabilizing volatile goblin dynamite and allowing you to craft Proximity-Triggered Hoblobbers and Jug O\' Boom charges. Automatically gains +1 Level on every Floor Descent!',
            'perk_summary' => 'Craft explosive combat buffs (scales with Table Level)',
        ],
        'alchemy' => [
            'pref' => 'alchemy_lvl',
            'name' => 'Alchemy Workstation',
            'color_name' => '`@Alchemy Table`0',
            'auto_levels_on_dk' => true,
            'gold_mult' => 0.9,
            'gem_mult' => 1.0,
            'desc' => 'Distills Bijol Sap, Rev-Up Moonshine, and monster parts into Potions of Iron Skin and full-heal tonics. Automatically gains +1 Level on every Floor Descent!',
            'perk_summary' => 'Brew full-heal & Iron-Skin defense elixirs',
        ],
        'engineer' => [
            'pref' => 'engineer_lvl',
            'name' => 'Engineer\'s & Metal Working Table',
            'color_name' => '`#Engineer\'s Table`0',
            'auto_levels_on_dk' => true,
            'gold_mult' => 1.1,
            'gem_mult' => 1.0,
            'desc' => 'Fabricates Dwarven Batteries, tempers your equipped weapon and armor for the day, and unlocks Carl\'s secret Indestructible Table Bunker exploit. Automatically gains +1 Level on every Floor Descent!',
            'perk_summary' => 'Temper equipped Weapon/Armor & deploy Table Shield',
        ],
        'cattree' => [
            'pref' => 'cattree_lvl',
            'name' => 'Princess Donut\'s Royal Cat Tree & Scented Candle Lounge',
            'color_name' => '`5Royal Cat Tree`0',
            'auto_levels_on_dk' => false,
            'gold_mult' => 0.8,
            'gem_mult' => 0.8,
            'desc' => 'A towering velvet-lined feline throne complete with vanilla scented candles. Keeps Princess Donut and Mongo in high spirits, granting passive Viewer Ratings (Charm) and bonus Max HP every New Day!',
            'perk_summary' => '+Viewer Ratings (Charm) & +Max HP every New Day',
        ],
        'bed' => [
            'pref' => 'bed_lvl',
            'name' => 'Ultra-Stabilized Size-Adjustable Race-Adjustable Alleviating Sleep Apparatus',
            'color_name' => '`^Alleviating Sleep Apparatus`0',
            'auto_levels_on_dk' => false,
            'gold_mult' => 1.2,
            'gem_mult' => 1.2,
            'desc' => 'The gold standard of Safe Room beds. Immediately clears fatigue and grants bonus Floor Stamina (Turns) and permanent Attack/Defense conditioning every New Day broadcast cycle!',
            'perk_summary' => '+Floor Stamina (Turns) & rested combat bonus every New Day',
        ],
    ];
}

/**
 * Calculates the Gold Credit and Sponsor Token cost for the next upgrade tier of a station.
 *
 * @return array{gold: int, gems: int, current_lvl: int, next_lvl: int}
 */
function personalspace_get_upgrade_cost(string $stationKey): array
{
    $stations = personalspace_get_stations();
    $info = $stations[$stationKey];
    $curLvl = max(0, (int) get_module_pref($info['pref'], 'personalspace'));
    $nextLvl = $curLvl + 1;

    $baseGold = max(500, (int) get_module_setting('base_table_gold', 'personalspace'));
    $baseGems = max(1, (int) get_module_setting('base_table_gems', 'personalspace'));

    $goldCost = (int) round($baseGold * $info['gold_mult'] * $nextLvl);
    $gemCost = (int) max(1, round($baseGems * $info['gem_mult'] * $nextLvl));

    return [
        'gold' => $goldCost,
        'gems' => $gemCost,
        'current_lvl' => $curLvl,
        'next_lvl' => $nextLvl,
    ];
}

function personalspace_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'village':
            tlschema($args['schemas']['marketnav'] ?? 'village');
            addnav($args['marketnav'] ?? 'Safe Room Facilities');
            tlschema();
            addnav('`#Personal Space & Crafting Studio`0', 'runmodule.php?module=personalspace');
            break;

        case 'dragonkill':
            // Canonical DCC Rule: Owned Crafting Tables automatically upgrade +1 Level on every Floor Descent!
            $stations = personalspace_get_stations();
            $upgradedNames = [];

            foreach ($stations as $sInfo) {
                if (!$sInfo['auto_levels_on_dk']) {
                    continue;
                }
                $curLvl = (int) get_module_pref($sInfo['pref'], 'personalspace');
                if ($curLvl > 0) {
                    $newLvl = $curLvl + 1;
                    set_module_pref($sInfo['pref'], $newLvl, 'personalspace');
                    $upgradedNames[] = sprintf('%s (`@Lv %d`2)', $sInfo['color_name'], $newLvl);
                }
            }

            if ($upgradedNames !== []) {
                output(
                    "`n`#[PERSONAL SPACE STAIRWELL UPGRADE]: `2As your Personal Space transfers to the next Floor, your installed Crafting Tables automatically level up: %s!`0`n",
                    implode(', ', $upgradedNames)
                );
            }
            break;

        case 'newday':
            set_module_pref('crafts_today', 0, 'personalspace');
            set_module_pref('exploit_used_today', 0, 'personalspace');

            $bedLvl = max(0, (int) get_module_pref('bed_lvl', 'personalspace'));
            $catTreeLvl = max(0, (int) get_module_pref('cattree_lvl', 'personalspace'));

            if ($bedLvl > 0) {
                $bonusTurns = min(12, $bedLvl * 2);
                $session['user']['turns'] += $bonusTurns;
                apply_buff('dcc_sleep_apparatus', [
                    'name' => '`^Alleviating Sleep Apparatus Rested Aura`0',
                    'rounds' => 20 + ($bedLvl * 5),
                    'wearoff' => 'Your rested morning energy from the Sleep Apparatus fades.',
                    'atkmod' => 1.0 + min(0.30, $bedLvl * 0.04),
                    'defmod' => 1.0 + min(0.30, $bedLvl * 0.04),
                    'schema' => 'module-personalspace',
                ]);
                output(
                    "`n`^[SLEEP APPARATUS]: `2You wake up on your **Level %d Alleviating Sleep Apparatus** feeling superhuman! You gain `@+%d Floor Stamina`2 and a rested combat buff!`0`n",
                    $bedLvl,
                    $bonusTurns
                );
            }

            if ($catTreeLvl > 0) {
                $charmGain = min(5, $catTreeLvl);
                $hpGain = $catTreeLvl * 3;
                $session['user']['charm'] += $charmGain;
                $session['user']['hitpoints'] += $hpGain;
                output(
                    "`5[ROYAL CAT TREE]: `3Princess Donut lounges atop her **Level %d Cat Tree** while vanilla candles burn, granting you `@+%d Viewer Ratings (Charm)`3 and `@+%d Bonus HP`3!`0`n",
                    $catTreeLvl,
                    $charmGain,
                    $hpGain
                );
            }
            break;

        case 'charstats':
            $sapper = (int) get_module_pref('sapper_lvl', 'personalspace');
            $alchemy = (int) get_module_pref('alchemy_lvl', 'personalspace');
            $engineer = (int) get_module_pref('engineer_lvl', 'personalspace');
            if ($sapper > 0 || $alchemy > 0 || $engineer > 0) {
                addcharstat('Crafting Studio');
                addcharstat('Table Levels', sprintf('`$S:%d `@A:%d `#E:%d`0', $sapper, $alchemy, $engineer));
            }
            break;

        case 'bioinfo':
            $targetId = (int) ($args['acctid'] ?? 0);
            if ($targetId > 0) {
                $s = (int) get_module_pref('sapper_lvl', 'personalspace', $targetId);
                $a = (int) get_module_pref('alchemy_lvl', 'personalspace', $targetId);
                $e = (int) get_module_pref('engineer_lvl', 'personalspace', $targetId);
                $c = (int) get_module_pref('cattree_lvl', 'personalspace', $targetId);
                $b = (int) get_module_pref('bed_lvl', 'personalspace', $targetId);
                if (($s + $a + $e + $c + $b) > 0) {
                    output(
                        "`n`#Personal Space Studio:`0 `$Sapper Lv%d`0 | `@Alchemy Lv%d`0 | `#Engineer Lv%d`0 | `5Cat Tree Lv%d`0 | `^Bed Lv%d`0`n",
                        $s,
                        $a,
                        $e,
                        $c,
                        $b
                    );
                }
            }
            break;
    }

    return $args;
}

function personalspace_run(): void
{
    global $session;

    page_header('Safe Room Personal Space & Enhanced Crafting Studio');

    $op = (string) httpget('op');
    $stations = personalspace_get_stations();
    $maxCrafts = max(1, (int) get_module_setting('daily_crafts_allowed', 'personalspace'));
    $craftsUsed = max(0, (int) get_module_pref('crafts_today', 'personalspace'));
    $craftsLeft = max(0, $maxCrafts - $craftsUsed);

    // ========================================================================
    // 1. UPGRADE STATION HANDLER
    // ========================================================================
    if ($op === 'upgrade') {
        $key = (string) httpget('station');
        if (isset($stations[$key])) {
            $sInfo = $stations[$key];
            $cost = personalspace_get_upgrade_cost($key);
            $userGold = (int) ($session['user']['gold'] ?? 0);
            $userGems = (int) ($session['user']['gems'] ?? 0);

            if ($userGold < $cost['gold'] || $userGems < $cost['gems']) {
                output(
                    "`\$[BOPCA CONTRACTOR ERROR]: `4Upgrading **%s** to Level %d requires `^%s Gold Credits`4 and `5%d Sponsor Tokens`4!`0`n`n",
                    $sInfo['name'],
                    $cost['next_lvl'],
                    number_format($cost['gold']),
                    $cost['gems']
                );
            } else {
                $session['user']['gold'] -= $cost['gold'];
                $session['user']['gems'] -= $cost['gems'];
                set_module_pref($sInfo['pref'], $cost['next_lvl'], 'personalspace');

                output(
                    "`@[PERSONAL SPACE UPGRADED!]: `2Bopca Protector engineers materialize and install your **%s (Level %d)**!`0`n",
                    $sInfo['name'],
                    $cost['next_lvl']
                );

                // Trigger System AI crafting achievement note on first table installation
                if ($cost['next_lvl'] === 1) {
                    output("`5[SYSTEM AI]: `3\"NEW ACHIEVEMENT! Do-It-Yourself Murderer! You placed a %s in your Personal Space! Remember: Crafting Tables automatically gain +1 Level every time you go down the stairs!\"`0`n", $sInfo['name']);
                }
                output("`n");
            }
        }
    }

    // ========================================================================
    // 2. WORKBENCH CRAFTING HANDLERS (SAPPER, ALCHEMY, ENGINEER, EXPLOIT)
    // ========================================================================
    elseif ($op === 'craft') {
        $bench = (string) httpget('bench');
        if ($craftsLeft <= 0) {
            output("`\$[WORKBENCH OVERHEATED]: `4You have already completed `^%d / %d`4 workbench projects this broadcast cycle. Let the fume hoods vent until New Day!`0`n`n", $craftsUsed, $maxCrafts);
        } elseif ($bench === 'sapper') {
            $lvl = (int) get_module_pref('sapper_lvl', 'personalspace');
            if ($lvl <= 0) {
                output("`\$You need to install a **Sapper's Table** first!`0`n`n");
            } else {
                set_module_pref('crafts_today', $craftsUsed + 1, 'personalspace');
                $craftsLeft--;
                $minDmg = 8 + ($lvl * 4);
                $maxDmg = 20 + ($lvl * 9);

                apply_buff('dcc_crafted_hoblobbers', [
                    'name' => sprintf('`$Sapper Lv%d Proximity Hoblobbers`0', $lvl),
                    'rounds' => 25 + ($lvl * 5),
                    'wearoff' => 'You detonate the last of your Sapper-crafted Proximity Hoblobbers.',
                    'atkmod' => 1.20 + min(0.60, $lvl * 0.05),
                    'minioncount' => 1,
                    'minbadguydamage' => $minDmg,
                    'maxbadguydamage' => $maxDmg,
                    'effectmsg' => '`$Your Sapper-stabilized Proximity Hoblobber blasts {badguy} for `^{damage}`$ demolition damage!`0',
                    'schema' => 'module-personalspace',
                ]);
                output("`@[DEMOLITION WORKSHOP]: `2At your **Level %d Sapper's Table**, you crimp proximity triggers onto a batch of Hobgoblin Dynamite (`+%d–%d explosive shrapnel damage for %d rounds`)!`0`n`n", $lvl, $minDmg, $maxDmg, 25 + ($lvl * 5));
            }
        } elseif ($bench === 'alchemy') {
            $lvl = (int) get_module_pref('alchemy_lvl', 'personalspace');
            if ($lvl <= 0) {
                output("`\$You need to install an **Alchemy Table** first!`0`n`n");
            } else {
                set_module_pref('crafts_today', $craftsUsed + 1, 'personalspace');
                $craftsLeft--;
                $tempHp = $lvl * 6;
                $session['user']['hitpoints'] = (int) $session['user']['maxhitpoints'] + $tempHp;

                apply_buff('dcc_crafted_ironskin', [
                    'name' => sprintf('`@Alchemy Lv%d Potion of Iron Skin`0', $lvl),
                    'rounds' => 30 + ($lvl * 5),
                    'wearoff' => 'Your alchemical Iron Skin potion wears off.',
                    'defmod' => 1.25 + min(0.75, $lvl * 0.06),
                    'lifetap' => 0.15,
                    'roundmsg' => '`@Your Bijol-infused Iron Skin deflects incoming shrapnel and regenerates tissue!`0',
                    'schema' => 'module-personalspace',
                ]);
                output("`@[ALCHEMY WORKSTATION]: `2You distil Bijol Sap and Mold Lion extract at your **Level %d Alchemy Table**! Your HP is restored to `@%d (Overhealed +%d HP)`2 and you gain the **Potion of Iron Skin** buff!`0`n`n", $lvl, (int) $session['user']['hitpoints'], $tempHp);
            }
        } elseif ($bench === 'engineer') {
            $lvl = (int) get_module_pref('engineer_lvl', 'personalspace');
            if ($lvl <= 0) {
                output("`\$You need to install an **Engineer's Table** first!`0`n`n");
            } else {
                set_module_pref('crafts_today', $craftsUsed + 1, 'personalspace');
                $craftsLeft--;
                $statBoost = max(1, (int) ceil($lvl / 2));
                $session['user']['attack'] += $statBoost;
                $session['user']['defense'] += $statBoost;

                output("`@[ENGINEER'S TABLE]: `2You wire a **Dwarven Battery Fabricator** into your equipped gear at your **Level %d Engineer's Table**, granting **+%d Attack** and **+%d Defense**!`0`n`n", $lvl, $statBoost, $statBoost);
            }
        }
    } elseif ($op === 'table_exploit') {
        $engLvl = (int) get_module_pref('engineer_lvl', 'personalspace');
        if ((int) get_module_setting('allow_table_exploit', 'personalspace') !== 1 || $engLvl <= 0) {
            output("`\$You need at least a Level 1 **Engineer's Table** to rig the Indestructible Table Bunker exploit!`0`n`n");
        } elseif ((int) get_module_pref('exploit_used_today', 'personalspace') === 1) {
            output("`\$Mordecai hisses: `2\"Careful, kid! Don't use the indestructible table bug twice in one broadcast cycle or the showrunners will patch it!\"`0`n`n");
        } else {
            set_module_pref('exploit_used_today', 1, 'personalspace');
            apply_buff('dcc_indestructible_table_bug', [
                'name' => '`#Unpatched Exploit: Indestructible Crafting Table Bunker`0',
                'rounds' => 15,
                'wearoff' => 'You pull the indestructible Engineer\'s Table back into your inventory before Borant notices.',
                'defmod' => 2.50,
                'badguyatkmod' => 0.45,
                'roundmsg' => '`#You duck behind your invulnerable Crafting Table as {badguy}\'s attack harmlessly bounces off the workbench surface!`0',
                'schema' => 'module-personalspace',
            ]);
            output("`#[UNPATCHED DUNGEON EXPLOIT ARMED!]: `2You load your indestructible **Engineer's Table** into your quick-slot! For your next **15 combat rounds**, you have a massive **2.5x Defense / -55 percent Enemy Attack** bunker shield!`0`n`n");
        }
    }

    // ========================================================================
    // 3. RENDER PERSONAL SPACE STUDIO DASHBOARD
    // ========================================================================
    rawoutput('
    <div style="background:linear-gradient(135deg,#0d1520 0%,#162436 55%,#0b111a 100%);border:2px solid #3fb950;border-radius:8px;padding:14px 18px;margin-bottom:14px;color:#f0f6fc;font-family:Consolas,monospace;box-shadow:0 0 16px rgba(63,185,80,0.28);">
        <div style="font-size:1.25em;font-weight:900;color:#3fb950;letter-spacing:1.2px;">&#128736;&#65039; SAFE ROOM PERSONAL SPACE &amp; ENHANCED CRAFTING STUDIO &#128736;&#65039;</div>
        <div style="font-size:0.9em;color:#8b949e;margin-top:4px;">"The Royal Palace of Princess Donut" — Modular Pocket Base &amp; Demolition Workshop</div>
        <div style="display:flex;flex-wrap:wrap;gap:18px;font-size:0.9em;border-top:1px solid rgba(63,185,80,0.3);margin-top:8px;padding-top:8px;">
            <span><strong style="color:#00f0ff;">Gold Credits:</strong> <span style="color:#ffd700;font-weight:bold;">' . number_format((int) ($session['user']['gold'] ?? 0)) . '</span></span>
            <span><strong style="color:#00f0ff;">Sponsor Tokens:</strong> <span style="color:#ff2a85;font-weight:bold;">' . (int) ($session['user']['gems'] ?? 0) . '</span></span>
            <span><strong style="color:#00f0ff;">Daily Workbench Crafts Left:</strong> <span style="color:#3fb950;font-weight:bold;">' . $craftsLeft . ' / ' . $maxCrafts . '</span></span>
        </div>
    </div>
    ');

    rawoutput('<table border="0" cellpadding="7" cellspacing="1" width="100%" style="background:#0d1117;border:1px solid #30363d;margin-bottom:14px;font-family:Consolas,monospace;">');
    rawoutput('<tr style="background:#161b22;color:#00f0ff;font-weight:bold;"><td>Station / Upgrade</td><td>Level</td><td>Function &amp; Stairwell Scaling</td><td>Upgrade Cost</td></tr>');

    foreach ($stations as $sKey => $sInfo) {
        $cost = personalspace_get_upgrade_cost($sKey);
        $lvlBadge = ($cost['current_lvl'] > 0)
            ? '<span style="color:#3fb950;font-weight:bold;">Lv ' . $cost['current_lvl'] . '</span>'
            : '<span style="color:#6e7681;">Unbuilt</span>';
        $autoTag = $sInfo['auto_levels_on_dk']
            ? '<br><small style="color:#ffd700;">&#9733; Auto-Upgrades +1 Level on every Floor Descent!</small>'
            : '';

        $upUrl = "runmodule.php?module=personalspace&op=upgrade&station={$sKey}";
        addnav('', $upUrl);

        rawoutput('<tr style="border-bottom:1px solid #21262d;">');
        rawoutput('<td style="color:#f0f6fc;font-weight:bold;">' . htmlspecialchars($sInfo['name'], ENT_QUOTES, 'UTF-8') . '</td>');
        rawoutput('<td>' . $lvlBadge . '</td>');
        rawoutput('<td style="color:#c9d1d9;font-size:0.9em;">' . htmlspecialchars($sInfo['desc'], ENT_QUOTES, 'UTF-8') . $autoTag . '</td>');
        rawoutput('<td style="white-space:nowrap;"><a href="' . htmlspecialchars($upUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#ffd700;font-weight:bold;">[' . ($cost['current_lvl'] > 0 ? 'Upgrade Lv ' . $cost['next_lvl'] : 'Install Lv 1') . ']</a><br><small style="color:#8b949e;">' . number_format($cost['gold']) . 'G + ' . $cost['gems'] . ' Tokens</small></td>');
        rawoutput('</tr>');
    }
    rawoutput('</table>');

    // Workbench Navigation
    $sapperLvl = (int) get_module_pref('sapper_lvl', 'personalspace');
    $alchemyLvl = (int) get_module_pref('alchemy_lvl', 'personalspace');
    $engineerLvl = (int) get_module_pref('engineer_lvl', 'personalspace');

    addnav('Use Crafting Tables');
    if ($sapperLvl > 0 && $craftsLeft > 0) {
        addnav(['`$Sapper: Rig Hoblobbers (Lv%d)`0', $sapperLvl], 'runmodule.php?module=personalspace&op=craft&bench=sapper');
    }
    if ($alchemyLvl > 0 && $craftsLeft > 0) {
        addnav(['`@Alchemy: Brew Iron Skin (Lv%d)`0', $alchemyLvl], 'runmodule.php?module=personalspace&op=craft&bench=alchemy');
    }
    if ($engineerLvl > 0 && $craftsLeft > 0) {
        addnav(['`#Engineer: Temper Gear (Lv%d)`0', $engineerLvl], 'runmodule.php?module=personalspace&op=craft&bench=engineer');
    }
    if ($engineerLvl > 0
        && (int) get_module_setting('allow_table_exploit', 'personalspace') === 1
        && (int) get_module_pref('exploit_used_today', 'personalspace') === 0
    ) {
        addnav('`#Arm Indestructible Table Bug`0', 'runmodule.php?module=personalspace&op=table_exploit');
    }

    addnav('Upgrade Studio');
    foreach ($stations as $sKey => $sInfo) {
        $cost = personalspace_get_upgrade_cost($sKey);
        addnav(
            ['%s -> Lv%d (%sG/%dT)', explode(' ', $sInfo['name'])[0], $cost['next_lvl'], number_format($cost['gold']), $cost['gems']],
            "runmodule.php?module=personalspace&op=upgrade&station={$sKey}"
        );
    }

    addnav('Return');
    addnav('Exit to Bopca Safe Room', 'village.php');

    page_footer();
}
