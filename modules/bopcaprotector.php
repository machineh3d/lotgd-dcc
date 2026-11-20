<?php

declare(strict_types=1);

/**
 * Module: Bopca Safe Room Bar, Mordecai's Lounge & Floor Quests (bopcaprotector.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Bopca
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Safe_Rooms
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Mordecai
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Loot_Boxes
 *
 * Description:
 *   Replaces stock Inn routines with a floor-adapting Bopca Safe Room Diner & Lounge:
 *   - Dynamically shifts the Safe Room disguise by Floor (Roadside Diner, The One-Eyed Narwhal,
 *     The All-Seeing Spleen, Iron Tangle Espresso Kiosk, Larracos Battlefield Bunker).
 *   - Bopca Protector's Counter serves Hot Dog Baskets (+HP & +1 Stamina Turn), Princess Donut's
 *     Dirty Shirleys (+Charm & Combat Buff), Rev-Up Moonshine, and Mordecai's Glory Bound Elixir
 *     (purges debuffs and clears Borant Gambling Addiction Lockouts).
 *   - Implements the Syndicate Floor Quest Board where Crawlers accept multi-kill/survival quests
 *     tracked via `battle-victory` to earn Bronze, Silver, Gold, and Celestial Quest Boxes!
 *   - Enforces the canonical Safe Room Non-Aggression Field (3 violence strikes = 1 HP ejection).
 */

function bopcaprotector_getmoduleinfo(): array
{
    return [
        'name' => 'Bopca Safe Room Bar, Mordecai\'s Lounge & Floor Quests',
        'version' => '0.2.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Bopca Safe Room & Quest Board Settings,title',
            'meal_base_cost' => 'Base Gold Credit cost per Crawler Level for Bopca Menu items,int|80',
            'glory_bound_gems' => 'Sponsor Token (Gem) cost for Mordecai\'s Glory Bound Detox Elixir,int|2',
            'daily_orders_limit' => 'Maximum Bopca bar orders allowed per broadcast cycle,int|3',
            'daily_quests_limit' => 'Maximum Floor Quests a Crawler can complete per broadcast cycle,int|2',
            'mimic_pub_chance' => 'Percent chance (0-100) a suspicious Floor 5+ Pub is a Mimic Trap when inspected,range,0,100,5|45',
        ],
        'prefs' => [
            'Bopca Safe Room & Quest Telemetry,title',
            'orders_today' => 'Bopca bar orders placed today,int|0',
            'hotdog_used_today' => 'Claimed +1 Turn from Bopca Hot Dog Basket today?,bool|0',
            'inspected_pub_today' => 'Inspected a suspicious Safe Room door with Mordecai today?,bool|0',
            'bopca_strikes' => 'Safe Room Non-Aggression Field violation strikes today,int|0',
            'active_quest_id' => 'Currently active Floor Quest ID (empty if none),text|',
            'quest_progress' => 'Current progress counter toward active Floor Quest,int|0',
            'quests_completed_today' => 'Floor Quests turned in today,int|0',
            'lifetime_quests' => 'Lifetime Floor Quests completed,int|0',
        ],
    ];
}

function bopcaprotector_install(): bool
{
    module_addhook('village');
    module_addhook('inn');
    module_addhook('newday');
    module_addhook('battle-victory');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function bopcaprotector_uninstall(): bool
{
    return true;
}

/**
 * Returns the Crawler's current Dungeon Floor (dragonkills + 1).
 */
function bopcaprotector_get_floor(?int $userId = null): int
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
 * Returns the floor-specific Safe Room appearance and Bopca Protector description.
 *
 * @return array{title: string, proprietor: string, ambiance: string}
 */
function bopcaprotector_get_saferoom_theme(int $floor): array
{
    if ($floor <= 2) {
        return [
            'title' => 'Roadside Bopca Diner & Safe Room #402',
            'proprietor' => 'Grek-7 (Contracted Bopca Protector)',
            'ambiance' => 'Fluorescent diner lights hum above red vinyl booths. Behind the bulletproof counter, a stout rodent-like Bopca Protector in a Borant apron flips synthetic hot dogs while Mordecai nurses a glass of cheap whiskey in the corner booth.',
        ];
    }
    if ($floor === 3) {
        return [
            'title' => 'The All-Seeing Spleen (Subterranean Trapdoor Pub)',
            'proprietor' => 'Velnor the Bopca Mixologist',
            'ambiance' => 'Tucked beneath a disguised iron trapdoor in the Over City cobbles, this sunken tavern smells of fried skyfowl wings and ozone. Mordecai sits in his Skyfowl form preening his feathers over a stack of Borough maps.',
        ];
    }
    if ($floor === 4) {
        return [
            'title' => 'Iron Tangle Platform 9 Espresso Kiosk & Safe Room',
            'proprietor' => 'Barista Toli (Bopca Protector Union #19)',
            'ambiance' => 'Subway cars screech past the reinforced blast glass outside while a Bopca Protector steams pitch-black Syndicate espresso and pours Dirty Shirleys with extra maraschino cherries for Princess Donut.',
        ];
    }
    if ($floor === 5) {
        return [
            'title' => 'The One-Eyed Narwhal Tavern (Submerged Quadrant Safe Room)',
            'proprietor' => 'Captain Bopca Krell',
            'ambiance' => 'Bioluminescent jellyfish drift past the thick portholes of this underwater tavern where Carl, Donut, and Katia Grim planned their bubble assault under Mordecai\'s watchful eye.',
        ];
    }
    if ($floor <= 8) {
        return [
            'title' => 'Gothic Crypt & Roadside Bopca Cantina',
            'proprietor' => 'High Attendant Zib (Bopca Veteran)',
            'ambiance' => 'From the street it looks like a weathered stone mausoleum, but inside lies a bustling neon-lit fast-food cantina and bar shielded by an impenetrable Borant Non-Aggression Field.',
        ];
    }

    return [
        'title' => 'Larracos Ash-Covered Espresso Stand & Battlefield Bunker',
        'proprietor' => 'Combat-Steward Bopca Rax',
        'ambiance' => 'Artillery shells from the Faction Wars boom in the distance outside, but inside this reinforced bunker the Bopca Protector calmly polishes glasses and slides cold drinks across the blast-proof bar.',
    ];
}

/**
 * Returns the catalog of Syndicate Floor Quests available from the Bopca Quest Board.
 *
 * @return array<string, array{
 *   title: string,
 *   min_floor: int,
 *   target: int,
 *   mode: string,
 *   box_tier: string,
 *   gold_mult: int,
 *   gems: int,
 *   perm_hp: int,
 *   desc: string
 * }>
 */
function bopcaprotector_get_quests(): array
{
    return [
        'hoarder_sweep' => [
            'title' => 'Corridor Sanitization: Scatterer Sweep',
            'min_floor' => 1,
            'target' => 5,
            'mode' => 'any_kill',
            'box_tier' => 'bronze',
            'gold_mult' => 180,
            'gems' => 1,
            'perm_hp' => 0,
            'desc' => 'Eliminate 5 biologically printed Corridor Mobs so the Bopca delivery drones can restock the diner.',
        ],
        'cocker_spaniel_purge' => [
            'title' => 'Princess Donut\'s Anti-Spaniel Campaign',
            'min_floor' => 1,
            'target' => 8,
            'mode' => 'healthy_kill',
            'box_tier' => 'silver',
            'gold_mult' => 320,
            'gems' => 2,
            'perm_hp' => 0,
            'desc' => 'Win 8 Corridor combat encounters while ending the fight above 70 percent Hitpoints to impress the Royal Court of Princess Donut.',
        ],
        'fools_save_together' => [
            'title' => 'Event Quest: The Fools Who Save Together',
            'min_floor' => 2,
            'target' => 12,
            'mode' => 'any_kill',
            'box_tier' => 'gold',
            'gold_mult' => 500,
            'gems' => 3,
            'perm_hp' => 1,
            'desc' => 'Survive 12 high-intensity Corridor skirmishes without dying to prove your squad can survive a collapsing bubble.',
        ],
        'ninth_floor_vanguard' => [
            'title' => 'Larracos Vanguard: War Mage Interdiction',
            'min_floor' => 3,
            'target' => 16,
            'mode' => 'any_kill',
            'box_tier' => 'celestial',
            'gold_mult' => 850,
            'gems' => 5,
            'perm_hp' => 2,
            'desc' => 'Slay 16 elite Dungeon enemies on Floor 3 or deeper to disrupt the Faction supply lines around Larracos.',
        ],
    ];
}

/**
 * Grants a Quest Box via `systemai` if active, and logs the reward.
 */
function bopcaprotector_grant_quest_box(string $tier, string $questTitle): void
{
    if (function_exists('systemai_grant_box')) {
        systemai_grant_box($tier, 1);
    } elseif (is_module_active('systemai')) {
        $prefKey = 'box_' . $tier;
        $cur = (int) get_module_pref($prefKey, 'systemai');
        set_module_pref($prefKey, $cur + 1, 'systemai');
    }

    $tierLabel = strtoupper($tier);
    output(
        '`n`^`b[SYNDICATE QUEST REWARD]:`b `@You have been awarded a `^`b%s QUEST BOX`b`@ for completing `#"%s"`@!`0`n',
        $tierLabel,
        $questTitle
    );
}

function bopcaprotector_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'newday':
            set_module_pref('orders_today', 0);
            set_module_pref('hotdog_used_today', 0);
            set_module_pref('inspected_pub_today', 0);
            set_module_pref('bopca_strikes', 0);
            set_module_pref('quests_completed_today', 0);
            break;

        case 'village':
            $floor = bopcaprotector_get_floor();
            $activeQuest = (string) get_module_pref('active_quest_id');
            $progress = (int) get_module_pref('quest_progress');
            $quests = bopcaprotector_get_quests();

            tlschema($args['schemas']['marketnav'] ?? 'village');
            addnav($args['marketnav'] ?? 'Safe Room Hub');
            tlschema();

            if ($activeQuest !== '' && isset($quests[$activeQuest])) {
                $target = $quests[$activeQuest]['target'];
                if ($progress >= $target) {
                    addnav('`@Bopca Safe Room Bar (QUEST READY!)`0', 'runmodule.php?module=bopcaprotector');
                } else {
                    addnav(
                        sprintf('`#Bopca Safe Room Bar `7(F%d | %d/%d)`0', $floor, $progress, $target),
                        'runmodule.php?module=bopcaprotector'
                    );
                }
            } else {
                addnav(
                    sprintf('`#Bopca Safe Room & Quests `7(Floor %d)`0', $floor),
                    'runmodule.php?module=bopcaprotector'
                );
            }
            break;

        case 'inn':
            addnav('Bopca Safe Room Counter');
            addnav('`#Bopca Protector & Mordecai\'s Booth`0', 'runmodule.php?module=bopcaprotector');
            break;

        case 'battle-victory':
            $activeQuest = (string) get_module_pref('active_quest_id');
            $quests = bopcaprotector_get_quests();
            if ($activeQuest !== '' && isset($quests[$activeQuest])) {
                $q = $quests[$activeQuest];
                $progress = (int) get_module_pref('quest_progress');
                $target = $q['target'];

                if ($progress < $target) {
                    $qualifies = true;
                    if ($q['mode'] === 'healthy_kill') {
                        $hp = (int) ($session['user']['hitpoints'] ?? 1);
                        $maxHp = max(1, (int) ($session['user']['maxhitpoints'] ?? 1));
                        if (($hp * 100) < ($maxHp * 70)) {
                            $qualifies = false;
                        }
                    }

                    if ($qualifies) {
                        $progress++;
                        set_module_pref('quest_progress', $progress);
                        if ($progress >= $target) {
                            output(
                                '`n`^`b[QUEST OBJECTIVE COMPLETE]:`b `@"%s" (%d/%d)! Return to the `#Bopca Safe Room Bar`@ to claim your Quest Box!`0`n',
                                $q['title'],
                                $progress,
                                $target
                            );
                        } else {
                            output(
                                '`n`#`b[FLOOR QUEST TELEMETRY]:`b `7"%s" progress: `^%d / %d`7.`0`n',
                                $q['title'],
                                $progress,
                                $target
                            );
                        }
                    }
                }
            }
            break;

        case 'charstats':
            $activeQuest = (string) get_module_pref('active_quest_id');
            $quests = bopcaprotector_get_quests();
            if ($activeQuest !== '' && isset($quests[$activeQuest])) {
                $q = $quests[$activeQuest];
                $progress = (int) get_module_pref('quest_progress');
                $target = $q['target'];
                $statusText = $progress >= $target
                    ? '`@READY TO TURN IN!`0'
                    : sprintf('`^%d / %d`0', $progress, $target);
                addcharstat('Crawler Status');
                addcharstat('Active Quest', $statusText);
            }
            break;

        case 'bioinfo':
            $acctId = (int) ($args['acctid'] ?? 0);
            if ($acctId > 0) {
                $completed = (int) get_module_pref('lifetime_quests', 'bopcaprotector', $acctId);
                if ($completed > 0) {
                    output('`#Safe Room Floor Quests Completed:`0 `^%d`0`n', $completed);
                }
            }
            break;
    }

    return $args;
}

function bopcaprotector_run(): void
{
    global $session;

    $op = (string) httpget('op');
    $floor = bopcaprotector_get_floor();
    $theme = bopcaprotector_get_saferoom_theme($floor);
    $quests = bopcaprotector_get_quests();

    $level = max(1, (int) ($session['user']['level'] ?? 1));
    $baseCost = max(20, (int) get_module_setting('meal_base_cost')) * $level;
    $gloryGems = max(1, (int) get_module_setting('glory_bound_gems'));
    $ordersToday = (int) get_module_pref('orders_today');
    $ordersLimit = max(1, (int) get_module_setting('daily_orders_limit'));
    $questsToday = (int) get_module_pref('quests_completed_today');
    $questsLimit = max(1, (int) get_module_setting('daily_quests_limit'));

    page_header('Bopca Safe Room & Mordecai\'s Lounge');

    output('`b`#%s — Floor %d`b`0`n', $theme['title'], $floor);
    output('`7Proprietor: `^%s`7 | Game Guide on Duty: `@Mordecai`0`n', $theme['proprietor']);
    output('`3%s`0`n`n', $theme['ambiance']);

    // 1. Order a Bopca Menu Item
    if ($op === 'order') {
        $item = (string) httpget('item');
        if ($ordersToday >= $ordersLimit) {
            output('`$`b[BOPCA RATION LIMIT]:`b `7%s taps the Borant rationing sign. You have already placed `^%d / %d`7 Safe Room bar orders today.`0`n`n', $theme['proprietor'], $ordersToday, $ordersLimit);
        } elseif ($item === 'hotdog') {
            $cost = $baseCost;
            if ((int) $session['user']['gold'] < $cost) {
                output('`$`b[INSUFFICIENT CREDITS]:`b `7You need `^%s Gold Credits`7 for a Bopca Hot Dog & Fries Basket.`0`n`n', number_format($cost));
            } else {
                $session['user']['gold'] -= $cost;
                $maxHp = (int) $session['user']['maxhitpoints'];
                if ((int) $session['user']['hitpoints'] < $maxHp) {
                    $session['user']['hitpoints'] = $maxHp;
                }
                set_module_pref('orders_today', $ordersToday + 1);
                $ordersToday++;

                output('`@`b[BOPCA DINER SPECIAL]:`b `2You wolf down a steaming basket of Bopca synthetic stadium hot dogs and waffle fries! Your Hitpoints are restored to `^%d`2!`0`n', $maxHp);

                if (!(bool) get_module_pref('hotdog_used_today')) {
                    $session['user']['turns'] = (int) $session['user']['turns'] + 1;
                    set_module_pref('hotdog_used_today', 1);
                    output('`^Bonus Safe Room Sustenance: You gain `@+1 Floor Stamina Turn`^ for today!`0`n`n');
                } else {
                    output('`7(You already received your +1 Stamina Turn from a Hot Dog Basket today.)`0`n`n');
                }
            }
        } elseif ($item === 'shirley') {
            $cost = (int) round($baseCost * 1.4);
            if ((int) $session['user']['gold'] < $cost) {
                output('`$`b[INSUFFICIENT CREDITS]:`b `7You need `^%s Gold Credits`7 for a Royal Dirty Shirley.`0`n`n', number_format($cost));
            } else {
                $session['user']['gold'] -= $cost;
                $session['user']['charm'] = (int) ($session['user']['charm'] ?? 0) + 2;
                set_module_pref('orders_today', $ordersToday + 1);
                $ordersToday++;

                buff_add([
                    'name' => '`5Princess Donut\'s Dirty Shirley Sugar Rush`0',
                    'rounds' => 18,
                    'wearoff' => '`7The maraschino cherry sugar high fades from your bloodstream.`0',
                    'atkmod' => 1.12,
                    'defmod' => 1.12,
                    'roundmsg' => '`5Your Dirty Shirley buzz keeps you light on your feet for the cameras!`0',
                    'schema' => 'module-bopcaprotector',
                ]);

                output('`5`b[ROYAL COURT COCKTAIL]:`b `2The Bopca slides over a frosted bowl of grenadine, vodka, lemon-lime soda, and twelve maraschino cherries. Princess Donut approves! You gain `^+2 Viewer Ratings (Charm)`2 and a 12 percent Attack & Defense buff for 18 rounds!`0`n`n');
            }
        } elseif ($item === 'moonshine') {
            $cost = (int) round($baseCost * 1.6);
            if ((int) $session['user']['gold'] < $cost) {
                output('`$`b[INSUFFICIENT CREDITS]:`b `7You need `^%s Gold Credits`7 for a shot of Rev-Up Moonshine.`0`n`n', number_format($cost));
            } else {
                $session['user']['gold'] -= $cost;
                set_module_pref('orders_today', $ordersToday + 1);
                $ordersToday++;

                buff_add([
                    'name' => '`$Rev-Up Goblin Moonshine Overdrive`0',
                    'rounds' => 15,
                    'wearoff' => '`7Your throat stops smoking as the Rev-Up Moonshine wears off.`0',
                    'atkmod' => 1.24,
                    'defmod' => 0.95,
                    'roundmsg' => '`$Volatile Rev-Up Moonshine fumes supercharge your strikes!`0',
                    'schema' => 'module-bopcaprotector',
                ]);

                output('`$`b[HIGH-PROOF VOLATILITY]:`b `2Mordecai winces as you down a glowing mason jar of Rev-Up Moonshine! Your Attack surges by 24 percent for 15 combat rounds!`0`n`n');
            }
        } elseif ($item === 'glorybound') {
            if ((int) $session['user']['gems'] < $gloryGems) {
                output('`$`b[INSUFFICIENT SPONSOR TOKENS]:`b `7Mordecai needs `^%d Sponsor Tokens (Gems)`7 to synthesize a Glory Bound Detox Elixir.`0`n`n', $gloryGems);
            } else {
                $session['user']['gems'] -= $gloryGems;
                $maxHp = (int) $session['user']['maxhitpoints'];
                $session['user']['hitpoints'] = $maxHp + (int) round($maxHp * 0.15);
                set_module_pref('orders_today', $ordersToday + 1);
                $ordersToday++;

                // Clear Desperado Club Gambling Addiction Lockout if forestcasino is active
                if (is_module_active('forestcasino')) {
                    set_module_pref('addiction_lockout', 0, 'forestcasino');
                    set_module_pref('daily_plays', 0, 'forestcasino');
                }

                buff_add([
                    'name' => '`#Mordecai\'s Glory Bound Clarity`0',
                    'rounds' => 25,
                    'wearoff' => '`7The golden glow of Mordecai\'s Glory Bound potion subsides.`0',
                    'atkmod' => 1.15,
                    'defmod' => 1.18,
                    'roundmsg' => '`#Glory Bound clarity sharpens your reflexes against Dungeon horrors!`0',
                    'schema' => 'module-bopcaprotector',
                ]);

                output('`#`b[GLORY BOUND DETOX COMPLETE]:`b `2Mordecai hands you a swirling golden phial. Your Hitpoints surge to `^%d`2 (115 percent Max HP), any Desperado Club Gambling Addiction Lockout is purged, and you gain `#Glory Bound Clarity`2 for 25 combat rounds!`0`n`n', (int) $session['user']['hitpoints']);
            }
        }
    }

    // 2. Inspect a Suspicious Pub Door (Floor 5/6 Mimic Safe Room Check)
    if ($op === 'inspect_pub') {
        if ((bool) get_module_pref('inspected_pub_today')) {
            output('`7You and Mordecai already scouted the neighboring pub entrances on this quadrant today.`0`n`n');
        } else {
            set_module_pref('inspected_pub_today', 1);
            $mimicChance = (int) get_module_setting('mimic_pub_chance');
            $roll = e_rand(1, 100);

            if ($roll <= $mimicChance) {
                $goldReward = $baseCost * 3;
                $xpReward = max(50, (int) round(((int) $session['user']['experience']) * 0.03));
                $session['user']['gold'] += $goldReward;
                $session['user']['experience'] += $xpReward;

                output('`$`b[FAKE SAFE ROOM DETECTED!]:`b `2Mordecai grabs your shoulder just before you touch the brass handle of the pub next door: `#"Wait! That proprietor isn\'t a Bopca—it\'s a Level %d Mimic Pub!"`2 You toss an explosive charge through the mail slot, collecting `^%s Gold Credits`2 and `^%s EXP`2 from the wreckage!`0`n`n', $level + 3, number_format($goldReward), number_format($xpReward));
            } else {
                $session['user']['turns'] = (int) $session['user']['turns'] + 1;
                output('`@`b[VERIFIED BOPCA SAFE ROOM]:`b `2Mordecai checks the Syndicate guild seal above the doorframe: `#"Genuine Bopca contract. You found a shortcut through the back alley!"`2 You gain `@+1 Floor Stamina Turn`2!`0`n`n');
            }
        }
    }

    // 3. Accept / Turn In / Abandon Floor Quests
    if ($op === 'accept_quest') {
        $qid = (string) httpget('qid');
        $currentActive = (string) get_module_pref('active_quest_id');
        if ($questsToday >= $questsLimit) {
            output('`$`b[QUEST QUOTA REACHED]:`b `7You have already completed `^%d / %d`7 Syndicate Floor Quests today. Check back after the next Broadcast Cycle!`0`n`n', $questsToday, $questsLimit);
        } elseif ($currentActive !== '') {
            output('`$`b[QUEST ALREADY ACTIVE]:`b `7Finish or abandon your current Floor Quest before accepting a new contract.`0`n`n');
        } elseif (!isset($quests[$qid])) {
            output('`$`b[INVALID QUEST]:`b `7That Syndicate quest contract is no longer on the board.`0`n`n');
        } elseif ($floor < $quests[$qid]['min_floor']) {
            output('`$`b[FLOOR LOCKED]:`b `7You must reach `^Floor %d`7 before the System AI unlocks this quest contract.`0`n`n', $quests[$qid]['min_floor']);
        } else {
            set_module_pref('active_quest_id', $qid);
            set_module_pref('quest_progress', 0);
            output('`@`b[NEW QUEST ACCEPTED!]:`b `2Your HUD chimes: `^"%s"`2 — %s`0`n`n', $quests[$qid]['title'], $quests[$qid]['desc']);
        }
    } elseif ($op === 'claim_quest') {
        $activeQuest = (string) get_module_pref('active_quest_id');
        $progress = (int) get_module_pref('quest_progress');
        if ($activeQuest === '' || !isset($quests[$activeQuest])) {
            output('`$`b[NO ACTIVE QUEST]:`b `7You do not have an active Floor Quest to turn in.`0`n`n');
        } else {
            $q = $quests[$activeQuest];
            if ($progress < $q['target']) {
                output('`$`b[OBJECTIVE INCOMPLETE]:`b `7You have only completed `^%d / %d`7 objectives for `^"%s"`7.`0`n`n', $progress, $q['target'], $q['title']);
            } else {
                $goldReward = $q['gold_mult'] * $level;
                $gemReward = $q['gems'];
                $hpReward = $q['perm_hp'];

                $session['user']['gold'] += $goldReward;
                $session['user']['gems'] += $gemReward;
                if ($hpReward > 0) {
                    $session['user']['maxhitpoints'] += $hpReward;
                    $session['user']['hitpoints'] += $hpReward;
                }

                set_module_pref('active_quest_id', '');
                set_module_pref('quest_progress', 0);
                set_module_pref('quests_completed_today', $questsToday + 1);
                $questsToday++;
                $lifetime = (int) get_module_pref('lifetime_quests') + 1;
                set_module_pref('lifetime_quests', $lifetime);

                output('`@`b[FLOOR QUEST COMPLETED!]:`b `2You turn in `#%s`2 to %s and Mordecai!`0`n', $q['title'], $theme['proprietor']);
                output('`2You receive `^%s Gold Credits`2 and `^%d Sponsor Tokens (Gems)`2!`0`n', number_format($goldReward), $gemReward);
                if ($hpReward > 0) {
                    output('`2Permanent vitality surge: `@+%d Permanent Max Hitpoints`2!`0`n', $hpReward);
                }

                bopcaprotector_grant_quest_box($q['box_tier'], $q['title']);

                addnews(
                    '`#Crawler `^%s`# completed the Syndicate Quest `^"%s"`# and earned a `^%s Quest Box`#!',
                    $session['user']['name'],
                    $q['title'],
                    ucfirst($q['box_tier'])
                );
            }
        }
    } elseif ($op === 'abandon_quest') {
        set_module_pref('active_quest_id', '');
        set_module_pref('quest_progress', 0);
        output('`7You tear down your active Floor Quest contract from the HUD tracker.`0`n`n');
    }

    // 4. Test Safe Room Non-Aggression Field ("Attack the Bopca")
    if ($op === 'provoke_bopca') {
        $strikes = (int) get_module_pref('bopca_strikes') + 1;
        set_module_pref('bopca_strikes', $strikes);

        if ($strikes < 3) {
            output('`$`b[SAFE ROOM VIOLATION WARNING #%d/3]:`b `4You swing your weapon toward the counter! An invisible Borant containment barrier freezes your arm mid-swing while %s glares at you with glowing red eyes. `#"Are you out of your mind?!"`4 Mordecai yells. `#"Do that %d more time(s) and the Bopca will vent you into the Corridor at 1 HP!"`0`n`n', $strikes, $theme['proprietor'], 3 - $strikes);
        } else {
            $session['user']['hitpoints'] = 1;
            output('`$`b[BOPCA TELEKINETIC EJECTION!]:`b `4That was Strike Three! %s snaps a clawed finger, crushing you to `^1 Hitpoint`4 with the Safe Room\'s gravity field and hurling you headfirst through the blast doors!`0`n`n', $theme['proprietor']);
            addnews(
                '`$%s`4 violated the Bopca Safe Room Non-Aggression Pact three times and was telekinetically ejected at 1 HP!',
                $session['user']['name']
            );
        }
    }

    // Render Bopca Bar Menu & Active Quest Status
    output('`b`^=== BOPCA SAFE ROOM MENU (Orders Today: %d / %d) ===`b`0`n', $ordersToday, $ordersLimit);
    rawoutput('<table class="table table-striped table-dark" style="margin-bottom:16px;">');
    rawoutput('<thead><tr><th>Item</th><th>Cost</th><th>Safe Room Effect</th></tr></thead><tbody>');
    rawoutput(sprintf(
        '<tr><td><strong>Bopca Hot Dog &amp; Fries Basket</strong></td><td>%s Gold</td><td>Restores 100 percent HP + <strong>+1 Floor Turn</strong> (once/day)</td></tr>',
        number_format($baseCost)
    ));
    rawoutput(sprintf(
        '<tr><td><strong>Princess Donut\'s Dirty Shirley</strong></td><td>%s Gold</td><td>+2 Viewer Ratings (Charm) + 12 percent ATK/DEF Buff (18 rds)</td></tr>',
        number_format((int) round($baseCost * 1.4))
    ));
    rawoutput(sprintf(
        '<tr><td><strong>Rev-Up Goblin Moonshine Shot</strong></td><td>%s Gold</td><td>+24 percent Attack Overdrive Buff (15 rds)</td></tr>',
        number_format((int) round($baseCost * 1.6))
    ));
    rawoutput(sprintf(
        '<tr><td><strong>Mordecai\'s "Glory Bound" Elixir</strong></td><td>%d Gems</td><td>115 percent Overheal + Purges Casino Lockout + 15 percent ATK / 18 percent DEF (25 rds)</td></tr>',
        $gloryGems
    ));
    rawoutput('</tbody></table>');

    // Render Syndicate Floor Quest Board
    $activeQuest = (string) get_module_pref('active_quest_id');
    $progress = (int) get_module_pref('quest_progress');
    output('`b`#=== SYNDICATE FLOOR QUEST BOARD (Completed Today: %d / %d) ===`b`0`n', $questsToday, $questsLimit);

    if ($activeQuest !== '' && isset($quests[$activeQuest])) {
        $aq = $quests[$activeQuest];
        output(
            '`@Active Contract:`0 `^`b%s`b`0 — `7Progress: `^%d / %d`7 (%s)`0`n`n',
            $aq['title'],
            $progress,
            $aq['target'],
            $aq['desc']
        );
    }

    rawoutput('<table class="table table-striped table-dark">');
    rawoutput('<thead><tr><th>Quest Contract</th><th>Min Floor</th><th>Objective</th><th>Reward</th></tr></thead><tbody>');
    foreach ($quests as $qid => $q) {
        $goldReward = $q['gold_mult'] * $level;
        $hpText = $q['perm_hp'] > 0 ? sprintf(' + +%d Max HP', $q['perm_hp']) : '';
        rawoutput(sprintf(
            '<tr><td><strong>%s</strong><br><small>%s</small></td><td>Floor %d+</td><td>%d Kills</td><td><strong>%s Quest Box</strong> + %s Gold + %d Gems%s</td></tr>',
            htmlentities($q['title'], ENT_QUOTES, 'UTF-8'),
            htmlentities($q['desc'], ENT_QUOTES, 'UTF-8'),
            $q['min_floor'],
            $q['target'],
            ucfirst($q['box_tier']),
            number_format($goldReward),
            $q['gems'],
            $hpText
        ));
    }
    rawoutput('</tbody></table>');

    // Navigation
    addnav('Bopca Safe Room Bar');
    addnav(sprintf('Order Hot Dog Basket (%sG)', number_format($baseCost)), 'runmodule.php?module=bopcaprotector&op=order&item=hotdog');
    addnav(sprintf('Order Dirty Shirley (%sG)', number_format((int) round($baseCost * 1.4))), 'runmodule.php?module=bopcaprotector&op=order&item=shirley');
    addnav(sprintf('Order Rev-Up Moonshine (%sG)', number_format((int) round($baseCost * 1.6))), 'runmodule.php?module=bopcaprotector&op=order&item=moonshine');
    addnav(sprintf('Drink Glory Bound Elixir (%d Gems)', $gloryGems), 'runmodule.php?module=bopcaprotector&op=order&item=glorybound');

    addnav('Mordecai & Recon');
    addnav('Inspect Suspicious Pub Door', 'runmodule.php?module=bopcaprotector&op=inspect_pub');
    addnav('`$Swing Weapon at Bopca (Bad Idea)`0', 'runmodule.php?module=bopcaprotector&op=provoke_bopca');

    addnav('Syndicate Floor Quests');
    if ($activeQuest !== '' && isset($quests[$activeQuest])) {
        if ($progress >= $quests[$activeQuest]['target']) {
            addnav('`@Turn In Completed Quest!`0', 'runmodule.php?module=bopcaprotector&op=claim_quest');
        }
        addnav('`$Abandon Current Quest`0', 'runmodule.php?module=bopcaprotector&op=abandon_quest');
    } else {
        foreach ($quests as $qid => $q) {
            if ($floor >= $q['min_floor']) {
                addnav(
                    sprintf('Accept: %s', $q['title']),
                    'runmodule.php?module=bopcaprotector&op=accept_quest&qid=' . $qid
                );
            }
        }
    }

    addnav('Return');
    addnav('Return to Safe Room Hub', 'village.php');
    addnav('Return to Resting Quarters (Inn)', 'inn.php');

    page_footer();
}
