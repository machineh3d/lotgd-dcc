<?php

declare(strict_types=1);

/**
 * Module: System AI Achievements & Loot Boxes (systemai.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference: https://dungeon-crawler-carl.fandom.com/wiki/Dungeon_Crawler_Carl_Wiki
 *
 * Description:
 *   Implements the unhinged Syndicate System AI from Matt Dinniman's Dungeon Crawler Carl.
 *   - Monitors combat, death, wealth, and stairwell (Dragon Kill) milestones.
 *   - Renders stylized "NEW ACHIEVEMENT!" HUD popup banners with snarky AI commentary.
 *   - Grants Bronze, Silver, Gold, and Celestial Loot Boxes.
 *   - Adds a Safe Room Loot Box Vault where Crawlers can open boxes for Gold Credits,
 *     Sponsor Tokens (Gems), Floor Stamina (Turns), permanent stat boosts, and combat buffs.
 */

function systemai_getmoduleinfo(): array
{
    return [
        'name' => 'System AI Achievements & Loot Boxes',
        'version' => '1.0.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'System AI Broadcast Settings,title',
            'enable_popups' => 'Render stylized NEW ACHIEVEMENT HUD banners on screen?,bool|1',
            'broadcast_celestial' => 'Broadcast Celestial Loot Box unlocks/openings to Daily News?,bool|1',
            'borant_tax_chance' => 'Percent chance (0-100) Borant Corp audits a Celestial Box (adds lawsuit bonus)?,range,0,100,5|15',
            'foot_bonus_chance' => 'Percent chance (0-100) the AI awards a Foot-Fetish bonus box on combat win?,range,0,50,1|8',
        ],
        'prefs' => [
            'System AI Loot Box Inventory,title',
            'box_bronze' => 'Bronze Loot Boxes owned,int|0',
            'box_silver' => 'Silver Loot Boxes owned,int|0',
            'box_gold' => 'Gold Loot Boxes owned,int|0',
            'box_celestial' => 'Celestial Loot Boxes owned,int|0',
            'total_boxes_opened' => 'Lifetime Loot Boxes opened,int|0',
            'System AI Telemetry Counters,title',
            'total_kills' => 'Lifetime Floor Mob kills tracked,int|0',
            'flawless_kills' => 'Lifetime flawless combat victories,int|0',
            'total_deaths' => 'Lifetime Crawler deaths tracked,int|0',
            'unlocked_achievements' => 'JSON map of unlocked achievement IDs,text|{}',
            'pending_popups' => 'JSON queue of unread achievement popups,text|[]',
        ],
    ];
}

function systemai_install(): bool
{
    module_addhook('newday');
    module_addhook('battle-victory');
    module_addhook('battle-defeat');
    module_addhook('dragonkill');
    module_addhook('village');
    module_addhook('villagetext');
    module_addhook('weaponstext');
    module_addhook('armortext');
    module_addhook('stabletext');
    module_addhook('charstats');
    module_addhook('everyhit-loggedin');
    module_addhook('bioinfo');
    return true;
}

function systemai_uninstall(): bool
{
    return true;
}

/**
 * Returns the master catalog of canonical Dungeon Crawler Carl achievements.
 *
 * @return array<string, array{title: string, desc: string, reward_text: string, boxes: array<string, int>, gold?: int, gems?: int}>
 */
function systemai_get_achievement_catalog(): array
{
    return [
        'welcome_crawler' => [
            'title' => 'Welcome, Crawler!',
            'desc' => 'You survived the Collapse and walked down the stairwell into a sadistic intergalactic game show instead of freezing to death outside. Statistically speaking, you are still going to die before the Third Floor, but hey—look at you trying!',
            'reward_text' => 'You have received a Bronze Adventurer Box!',
            'boxes' => ['bronze' => 1],
        ],
        'first_blood' => [
            'title' => 'Murder Hobo in Training!',
            'desc' => 'You just killed your very first biologically printed Dungeon inhabitant! Did it have a family? False memories of a childhood pet? Who cares! It had XP inside it!',
            'reward_text' => 'You have received a Bronze Loot Box!',
            'boxes' => ['bronze' => 1],
        ],
        'barefoot_stomper' => [
            'title' => 'Smush It Real Good!',
            'desc' => 'Ooooh. Oh, my. You just finished off a Dungeon creature using your feet. The camera drones caught that in 16K slow-motion. Do it again. Slower next time.',
            'reward_text' => 'You have received a Silver Foot-Enthusiast Box!',
            'boxes' => ['silver' => 1],
        ],
        'flawless_victory' => [
            'title' => 'Untouchable Show-Off!',
            'desc' => 'You just won a mortal combat encounter without losing a single hitpoint! Either you are a tactical genius, or you are shamelessly farming level-one trash mobs in the hallway.',
            'reward_text' => 'You have received a Bronze Loot Box!',
            'boxes' => ['bronze' => 1],
        ],
        'thrillseeker_elite' => [
            'title' => 'Punching Above Your Weight Class!',
            'desc' => 'You picked a fight with a monster higher level than you and actually walked away with all your limbs attached! The syndicate betting pools just lost a fortune!',
            'reward_text' => 'You have received a Silver Brawler Box!',
            'boxes' => ['silver' => 1],
        ],
        'mob_slayer_25' => [
            'title' => 'Borough Pest Exterminator!',
            'desc' => 'You have slaughtered 25 Dungeon inhabitants! The Borant cleanup bots are filing a union grievance over the sheer volume of viscera you are leaving in the corridors.',
            'reward_text' => 'You have received a Silver Loot Box!',
            'boxes' => ['silver' => 1],
        ],
        'mob_slayer_100' => [
            'title' => 'Top-10 Highlight Reel!',
            'desc' => '100 confirmed kills! Odette is already clipping your ultra-violence for tonight\'s broadcast of Dungeon Crawler After Hours!',
            'reward_text' => 'You have received a Gold Loot Box!',
            'boxes' => ['gold' => 1],
        ],
        'mob_slayer_500' => [
            'title' => 'Butcher of the Scolopendra Levels!',
            'desc' => '500 Dungeon creatures eradicated! At this point, YOU are the boss monster roaming the corridors, and the mobs are checking under their beds for you.',
            'reward_text' => 'You have received a Celestial Loot Box!',
            'boxes' => ['celestial' => 1],
        ],
        'first_death' => [
            'title' => 'Meat for the Grinder!',
            'desc' => 'You died! On live intergalactic television! Quintillions of viewers across three billion star systems just watched you get folded like a cheap lawn chair. Fortunately for you, Borant Corporate Legal needs you in the Reconstruction Bay.',
            'reward_text' => 'You have received a Bronze Pity Box!',
            'boxes' => ['bronze' => 1],
        ],
        'repeat_customer' => [
            'title' => 'Borant Reconstruction Frequent Flyer!',
            'desc' => 'Five deaths! Most crawlers have the decency to stay dead the first time, yet here you are, signing another humiliating corporate respawn waiver.',
            'reward_text' => 'You have received a Silver Masochist Box!',
            'boxes' => ['silver' => 1],
        ],
        'stairwell_1' => [
            'title' => 'Down the Stairwell!',
            'desc' => 'You obliterated the Floor Guardian and descended the Stairwell to the next level of the World Dungeon! Less than half the starting crawlers make it this far!',
            'reward_text' => 'You have received a Gold Boss Box!',
            'boxes' => ['gold' => 1],
        ],
        'stairwell_3' => [
            'title' => 'Third Floor Race & Class Eligible!',
            'desc' => 'You have survived three Floor Descents! Mordecai actually looks slightly less depressed when you walk into the Guildhall now!',
            'reward_text' => 'You have received a Gold Loot Box and a Silver Loot Box!',
            'boxes' => ['gold' => 1, 'silver' => 1],
        ],
        'stairwell_6' => [
            'title' => 'Welcome to the Hunting Grounds!',
            'desc' => 'You have reached the Sixth Floor! Wealthy alien tourists are now paying top credit to hunt you for sport. Try not to end up mounted on a Mantid\'s wall!',
            'reward_text' => 'You have received a Celestial Sponsor Box!',
            'boxes' => ['celestial' => 1],
        ],
        'wealth_hoarder' => [
            'title' => 'Bourgeoisie Loot Goblin!',
            'desc' => 'You are walking around the Dungeon with 25,000+ Gold Credits in your pockets! Watch your back—every Player Killer on the floor just got a ping on their map.',
            'reward_text' => 'You have received a Gold Capitalist Box!',
            'boxes' => ['gold' => 1],
        ],
        'loot_goblin_25' => [
            'title' => 'Certified Box Ripper!',
            'desc' => 'You have cracked open 25 Syndicate Loot Boxes! Your fingers are permanently stained with Borant glitter.',
            'reward_text' => 'You have received a Silver Loot Box!',
            'boxes' => ['silver' => 1],
            'hidden' => false,
        ],
        'sponsor_darling' => [
            'title' => 'Prime-Time Tunnel Darling!',
            'desc' => '50+ Viewer Ratings! Princess Donut is furious that your merch is outselling hers in three outer-rim systems.',
            'reward_text' => 'You have received a Gold Fan Box!',
            'boxes' => ['gold' => 1],
            'hidden' => false,
        ],
        'stairwell_9' => [
            'title' => 'Welcome to Faction Wars!',
            'desc' => 'Nine Floors down! Nine alien armies are waiting outside Larracos, and this season the safety protocols are OFF.',
            'reward_text' => 'You have received a Celestial War Box!',
            'boxes' => ['celestial' => 1],
            'hidden' => false,
        ],
        'stairwell_12' => [
            'title' => 'Challenger of the Celestial Ascendency!',
            'desc' => 'Twelve Floors deep! The gods are no longer invulnerable, and Emberus is already glaring at you from his throne.',
            'reward_text' => 'You have received two Celestial Loot Boxes!',
            'boxes' => ['celestial' => 2],
            'hidden' => false,
        ],
        // === HIDDEN / CLASSIFIED SYSTEM AI ACHIEVEMENTS (14) ===
        'one_hp_miracle' => [
            'title' => 'Hanging by a Fingernail!',
            'desc' => 'You are walking around the World Dungeon with literally ONE Hitpoint left! A stiff breeze or an aggressive sneeze would vaporize you right now!',
            'reward_text' => 'You have received a Silver Survivor Box!',
            'boxes' => ['silver' => 1],
            'hidden' => true,
        ],
        'zero_gold_broke' => [
            'title' => 'Penniless & Pathetic!',
            'desc' => 'Zero Gold Credits in your pockets AND zero in Orren\'s vault! Even the goblin beggars are tossing spare change at you out of pity.',
            'reward_text' => 'You have received a Bronze Welfare Box!',
            'boxes' => ['bronze' => 1],
            'hidden' => true,
        ],
        'gem_hoarder_50' => [
            'title' => 'Sponsor Token Dragon!',
            'desc' => '50+ Sponsor Tokens hoarded in your pouch! Are you planning to buy a planet or just sleep on a pile of glowing syndicate vouchers?',
            'reward_text' => 'You have received a Gold Sponsor Box!',
            'boxes' => ['gold' => 1],
            'hidden' => true,
        ],
        'flawless_streak_10' => [
            'title' => 'Stop Showing Off, Carl!',
            'desc' => '10 Flawless combat victories without taking a scratch! The Syndicate showrunners are actively buffing the next floor just to wipe that smirk off your face.',
            'reward_text' => 'You have received a Gold Tactician Box!',
            'boxes' => ['gold' => 1],
            'hidden' => true,
        ],
        'death_ten_times' => [
            'title' => 'Borant\'s Favorite Punching Bag!',
            'desc' => 'Ten televised deaths! The Reconstruction Bay technicians named a cryo-tube after you and installed a punch-card machine by the door.',
            'reward_text' => 'You have received a Gold Pity Box!',
            'boxes' => ['gold' => 1],
            'hidden' => true,
        ],
        'box_addict_50' => [
            'title' => 'Dopamine Junkie!',
            'desc' => '50 Loot Boxes cracked open! The System AI has officially flagged your brain chemistry as 94 percent confetti and pure gambling addiction.',
            'reward_text' => 'You have received a Celestial Mystery Box!',
            'boxes' => ['celestial' => 1],
            'hidden' => true,
        ],
        'club_vanquisher_banned' => [
            'title' => 'Persona Non Grata!',
            'desc' => 'You got yourself permanently banned from Club Vanquisher on live television! Carl and Prepotente both send their warmest regards.',
            'reward_text' => 'You have received a Silver Scandal Box!',
            'boxes' => ['silver' => 1],
            'hidden' => true,
        ],
        'bopca_menace' => [
            'title' => 'Do NOT Touch the Bopca!',
            'desc' => 'You actually tried to start a fight inside a Bopca-protected Safe Room! Did you skip the entire Tutorial Guild briefing?!',
            'reward_text' => 'You have received a Bronze Contempt Box!',
            'boxes' => ['bronze' => 1],
            'hidden' => true,
        ],
        'boss_hunter_5' => [
            'title' => 'Certified Boss Crusher!',
            'desc' => 'Five Dungeon Boss Kill Stars pinned to your HUD! The Neighborhood and Borough Bosses have formed a support group because of you.',
            'reward_text' => 'You have received a Gold Boss-Slayer Box!',
            'boxes' => ['gold' => 1],
            'hidden' => true,
        ],
        'quest_grinder_10' => [
            'title' => 'Mordecai\'s Teacher\'s Pet!',
            'desc' => 'Ten Bopca Safe Room Floor Quests completed! You actually read the quest board instead of just blowing up hallways like a normal anarchist.',
            'reward_text' => 'You have received a Silver Quest-Master Box!',
            'boxes' => ['silver' => 1],
            'hidden' => true,
        ],
        'faction_warlord' => [
            'title' => 'Ninth Floor War Criminal!',
            'desc' => '100+ Faction Wars War Points racked up around Larracos! Several Syndicate senators are drafting an emergency sanctions bill with your name on it.',
            'reward_text' => 'You have received a Celestial Warlord Box!',
            'boxes' => ['celestial' => 1],
            'hidden' => true,
        ],
        'triple_sponsored' => [
            'title' => 'Corporate Sellout Supreme!',
            'desc' => 'All three Syndicate Sponsor slots filled at once! Your armor has more corporate logos on it than an Earth stock car.',
            'reward_text' => 'You have received a Gold Endorsement Box!',
            'boxes' => ['gold' => 1],
            'hidden' => true,
        ],
        'high_roller_100k' => [
            'title' => 'Syndicate Oligarch!',
            'desc' => '100,000+ Gold Credits in your pockets! At this point the Borant Corporation is considering selling you a seat on the board.',
            'reward_text' => 'You have received a Celestial Oligarch Box!',
            'boxes' => ['celestial' => 1],
            'hidden' => true,
        ],
        'midnight_crawler' => [
            'title' => 'Insomniac Night-Shift Crawler!',
            'desc' => 'Crawling through the Dungeon between midnight and 4 AM! Go to sleep, Crawler—the aliens in the outer rim appreciate your dedication, though.',
            'reward_text' => 'You have received a Silver Night-Owl Box!',
            'boxes' => ['silver' => 1],
            'hidden' => true,
        ],
    ];
}

/**
 * Decodes a JSON module preference safely into an associative array.
 */
function systemai_get_json_pref(string $prefName, ?int $userId = null): array
{
    $raw = (string) get_module_pref($prefName, 'systemai', $userId);
    if ($raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Saves an array into a JSON module preference.
 */
function systemai_set_json_pref(string $prefName, array $data, ?int $userId = null): void
{
    set_module_pref($prefName, json_encode($data, JSON_UNESCAPED_UNICODE), 'systemai', $userId);
}

/**
 * Unlocks an achievement for the current user if not already unlocked,
 * grants its Loot Box rewards, and queues a HUD popup banner.
 */
function systemai_unlock_achievement(string $achId): bool
{
    global $session;

    $catalog = systemai_get_achievement_catalog();
    if (!isset($catalog[$achId])) {
        return false;
    }

    $unlocked = systemai_get_json_pref('unlocked_achievements');
    if (isset($unlocked[$achId])) {
        return false;
    }

    $ach = $catalog[$achId];
    $unlocked[$achId] = date('Y-m-d H:i:s');
    systemai_set_json_pref('unlocked_achievements', $unlocked);

    // Grant Loot Boxes
    foreach ($ach['boxes'] as $tier => $qty) {
        $prefKey = 'box_' . $tier;
        $current = (int) get_module_pref($prefKey, 'systemai');
        set_module_pref($prefKey, $current + (int) $qty, 'systemai');
    }

    // Broadcast Celestial unlocks if configured
    if (!empty($ach['boxes']['celestial']) && (int) get_module_setting('broadcast_celestial', 'systemai') === 1) {
        $crawlerName = (string) ($session['user']['name'] ?? 'A Crawler');
        addnews(
            '`5SYSTEM AI ALERT:`0 `@%s`2 just unlocked the achievement `^"%s"`2 and received a `5Celestial Loot Box`2!',
            $crawlerName, $ach['title']
        );
    }

    // Queue popup notification
    $pending = systemai_get_json_pref('pending_popups');
    $pending[] = [
        'id' => $achId,
        'title' => $ach['title'],
        'desc' => $ach['desc'],
        'reward' => $ach['reward_text'],
        'hidden' => !empty($ach['hidden']),
    ];
    systemai_set_json_pref('pending_popups', $pending);

    return true;
}

/**
 * Renders any queued "NEW ACHIEVEMENT!" banners at the top of the page.
 */
function systemai_render_pending_popups(): void
{
    if ((int) get_module_setting('enable_popups', 'systemai') !== 1) {
        return;
    }

    $pending = systemai_get_json_pref('pending_popups');
    if ($pending === []) {
        return;
    }

    // Clear queue immediately so popups only render once
    systemai_set_json_pref('pending_popups', []);

    rawoutput('
    <style>
    .dcc-ai-popup {
        background: linear-gradient(135deg, #0d1117 0%, #1a0f1f 60%, #2a0818 100%);
        border: 2px solid #ff2a85;
        border-left: 6px solid #00f0ff;
        border-radius: 6px;
        box-shadow: 0 0 18px rgba(255, 42, 133, 0.45), inset 0 0 12px rgba(0, 240, 255, 0.15);
        color: #f0f6fc;
        font-family: "Consolas", "Courier New", monospace;
        margin: 10px auto;
        max-width: 760px;
        padding: 14px 18px;
        position: relative;
    }
    .dcc-ai-popup-header {
        color: #ff2a85;
        font-size: 1.15em;
        font-weight: 800;
        letter-spacing: 1.5px;
        margin-bottom: 4px;
        text-transform: uppercase;
        text-shadow: 0 0 8px rgba(255, 42, 133, 0.7);
    }
    .dcc-ai-popup-title {
        color: #00f0ff;
        font-size: 1.08em;
        font-weight: 700;
        margin-bottom: 6px;
    }
    .dcc-ai-popup-desc {
        color: #d0d7de;
        font-size: 0.94em;
        font-style: italic;
        line-height: 1.45;
        margin-bottom: 8px;
    }
    .dcc-ai-popup-reward {
        background: rgba(255, 215, 0, 0.12);
        border: 1px dashed #ffd700;
        border-radius: 4px;
        color: #ffd700;
        display: inline-block;
        font-size: 0.92em;
        font-weight: 700;
        padding: 4px 10px;
    }
    </style>
    ');

    foreach ($pending as $popup) {
        $title = htmlspecialchars((string) ($popup['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $desc = htmlspecialchars((string) ($popup['desc'] ?? ''), ENT_QUOTES, 'UTF-8');
        $reward = htmlspecialchars((string) ($popup['reward'] ?? ''), ENT_QUOTES, 'UTF-8');

        rawoutput('<div class="dcc-ai-popup">');
        $hdr = !empty($popup['hidden']) ? '&#9733; SECRET ACHIEVEMENT UNLOCKED!' : '&#9888; NEW ACHIEVEMENT!';
        rawoutput('<div class="dcc-ai-popup-header">' . $hdr . '</div>');
        rawoutput('<div class="dcc-ai-popup-title">' . $title . '</div>');
        rawoutput('<div class="dcc-ai-popup-desc">"' . $desc . '"</div>');
        rawoutput('<div class="dcc-ai-popup-reward">&#127873; REWARD: ' . $reward . ' (Open in Safe Room)</div>');
        rawoutput('</div>');
    }
}


function systemai_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'villagetext':
            $args['gatenav'] = 'Dungeon Access';
            $args['fightnav'] = 'Tactical & Guild Quarter';
            $args['marketnav'] = 'Bopca Trade Concourse';
            $args['tavernnav'] = 'Safe Room Lounge';
            $args['infonav'] = 'Broadcast Feed';
            $args['weaponshop'] = 'Bopca Munitions & Explosives';
            $args['armorshop'] = 'Safe Room Tactical Outfitter';
            $args['stablename'] = 'Enhanced Pet Biscuit Menagerie';
            $args['mercenarycamp'] = 'Syndicate Mercenary Guild';
            $args['clock'] = 'The Syndicate Broadcast Clock reads `^%s`@.`n';
            break;

        case 'weaponstext':
            $args['title'] = 'Bopca Munitions & Explosives Counter';
            $args['desc'] = [
                '`#A stout Bopca quartermaster stands behind a reinforced blast-glass rack packed with goblin explosives, enchanted riot gear, and salvaged Earth weaponry.`n`n',
                '`7"Keep your hands off the live fuses, Crawler," the Bopca grunts while calibrating a Syndicate credit scanner.`n`n',
            ];
            $args['tradein'] = [
                '`7The Bopca eyes your equipped weapon and offers a standard Borant trade-in credit toward an upgrade.`n`n',
            ];
            $args['nosuchweapon'] = '`#The Bopca quartermaster`7 checks the Syndicate inventory screen and shakes his head—that weapon SKU does not exist on this floor.';
            $args['notenoughgold'] = '`7You try to palm the `5%s`7 off the rack without enough Gold Credits, but the Safe Room Non-Aggression Field zaps you straight onto the floor! The Bopca shakes his head: "`#Nice try, Crawler. Come back when you have the credits.`7"';
            $args['payweapon'] = '`#The Bopca quartermaster`7 scans your old `5%s`7 into the recycling chute and slides a freshly calibrated `5%s`7 across the counter. Your HUD chirps as the new weapon binds to your loadout!';
            break;

        case 'armortext':
            $args['title'] = 'Safe Room Tactical Outfitter';
            $args['desc'] = [
                '`5Holographic mannequins rotate inside the Safe Room Tactical Outfitter, displaying everything from frostbite-resistant boxer shorts to Syndicate tactical rigs.`n`n',
                '`7A Bopca tailor adjusts a glowing measuring tape while Princess Donut critiques the color palette from a nearby stool.`n`n',
            ];
            $args['tradein'] = [
                '`7The outfitter appraises your current gear and applies your trade-in credit to the terminal.`n`n',
            ];
            $args['nosuchweapon'] = '`#The Bopca outfitter`5 blinks at your HUD request—that armor item is not stocked on this floor.';
            $args['notenoughgold'] = '`5You try to walk out wearing the `5%s`5 without paying, but the Safe Room theft-deterrent collar shocks you flat onto the tile! "`#Credits upfront, Crawler,`5" the clerk warns.';
            $args['payarmor'] = '`#The Bopca outfitter`5 takes your old `5%s`5 and hands over your new `5%s`5! You equip the `&%s`5 as your HUD updates your Defense rating.';
            break;

        case 'stabletext':
            $args['title'] = 'Enhanced Pet Biscuit Menagerie';
            $args['desc'] = [
                '`#Rows of glowing stasis kennels hum inside the Enhanced Pet Biscuit Menagerie, packed with Syndicate cleaner bots, velociraptors, and magically mutated companions.`n`n',
                '`7A Borant Beast-Handler taps a clipboard: "All companions are System-AI compliant. Mostly. Do not feed Mongo after midnight."`n`n',
            ];
            $args['nosuchbeast'] = '`7"`&No such companion is registered in the Menagerie kennels, Crawler!`7" the handler barks.';
            $args['replacemount'] = '`7You transfer %s`7 to your Personal Space lounge, wire the Syndicate credits, and bond with your new companion `&%s`7!`n`n';
            $args['newmount'] = '`7You wire the credits and break open an Enhanced Pet Biscuit—`&%s`7 bounds out of the kennel and bonds to your party!`n`n';
            break;

        case 'everyhit-loggedin':
            $goldNow = (int) ($session['user']['gold'] ?? 0);
            $bankNow = (int) ($session['user']['goldinbank'] ?? 0);
            $gemsNow = (int) ($session['user']['gems'] ?? 0);
            $hpNow = (int) ($session['user']['hitpoints'] ?? 0);
            $lvlNow = (int) ($session['user']['level'] ?? 1);
            $charmNow = (int) ($session['user']['charm'] ?? 0);

            if ($goldNow >= 25000) {
                systemai_unlock_achievement('wealth_hoarder');
            }
            if ($goldNow >= 100000) {
                systemai_unlock_achievement('high_roller_100k');
            }
            if ($lvlNow >= 2 && $goldNow === 0 && $bankNow === 0) {
                systemai_unlock_achievement('zero_gold_broke');
            }
            if ($gemsNow >= 50) {
                systemai_unlock_achievement('gem_hoarder_50');
            }
            if ($charmNow >= 50) {
                systemai_unlock_achievement('sponsor_darling');
            }
            if ((bool) ($session['user']['alive'] ?? false) && $hpNow === 1) {
                systemai_unlock_achievement('one_hp_miracle');
            }
            $hourNow = (int) date('G');
            if ($hourNow >= 0 && $hourNow < 4) {
                systemai_unlock_achievement('midnight_crawler');
            }

            // Cross-module hidden achievement telemetry checks
            if (is_module_active('clubvanquisher') && (bool) get_module_pref('vanquisher_banned', 'clubvanquisher')) {
                systemai_unlock_achievement('club_vanquisher_banned');
            }
            if (is_module_active('bopcaprotector')) {
                if ((int) get_module_pref('bopca_strikes', 'bopcaprotector') > 0) {
                    systemai_unlock_achievement('bopca_menace');
                }
                if ((int) get_module_pref('lifetime_quests', 'bopcaprotector') >= 10) {
                    systemai_unlock_achievement('quest_grinder_10');
                }
            }
            if (is_module_active('dungeonbosses')) {
                $totalStars = (int) get_module_pref('stars_neighborhood', 'dungeonbosses')
                    + (int) get_module_pref('stars_borough', 'dungeonbosses')
                    + (int) get_module_pref('stars_city', 'dungeonbosses')
                    + (int) get_module_pref('stars_province', 'dungeonbosses');
                if ($totalStars >= 5) {
                    systemai_unlock_achievement('boss_hunter_5');
                }
            }
            if (is_module_active('factionwars') && (int) get_module_pref('lifetime_war_points', 'factionwars') >= 100) {
                systemai_unlock_achievement('faction_warlord');
            }
            if (is_module_active('sponsors')) {
                $s1 = (string) get_module_pref('patron_slot_1', 'sponsors');
                $s2 = (string) get_module_pref('patron_slot_2', 'sponsors');
                $s3 = (string) get_module_pref('patron_slot_3', 'sponsors');
                if ($s1 !== '' && $s2 !== '' && $s3 !== '') {
                    systemai_unlock_achievement('triple_sponsored');
                }
            }
            systemai_render_pending_popups();
            break;

        case 'newday':
            systemai_unlock_achievement('welcome_crawler');

            $dks = (int) ($session['user']['dragonkills'] ?? 0);
            if ($dks >= 1) {
                systemai_unlock_achievement('stairwell_1');
            }
            if ($dks >= 3) {
                systemai_unlock_achievement('stairwell_3');
            }
            if ($dks >= 6) {
                systemai_unlock_achievement('stairwell_6');
            }
            if ($dks >= 9) {
                systemai_unlock_achievement('stairwell_9');
            }
            if ($dks >= 12) {
                systemai_unlock_achievement('stairwell_12');
            }
            systemai_render_pending_popups();
            break;

        case 'battle-victory':
            $kills = (int) get_module_pref('total_kills', 'systemai') + 1;
            set_module_pref('total_kills', $kills, 'systemai');

            systemai_unlock_achievement('first_blood');

            if ($kills >= 25) {
                systemai_unlock_achievement('mob_slayer_25');
            }
            if ($kills >= 100) {
                systemai_unlock_achievement('mob_slayer_100');
            }
            if ($kills >= 500) {
                systemai_unlock_achievement('mob_slayer_500');
            }

            // Check Flawless Victory (HP at or above MaxHP)
            $hp = (int) ($session['user']['hitpoints'] ?? 0);
            $maxHp = (int) ($session['user']['maxhitpoints'] ?? 1);
            if ($hp >= $maxHp) {
                $flawless = (int) get_module_pref('flawless_kills', 'systemai') + 1;
                set_module_pref('flawless_kills', $flawless, 'systemai');
            if ($flawless >= 10) {
                systemai_unlock_achievement('flawless_streak_10');
            }
                systemai_unlock_achievement('flawless_victory');
            }

            // Check Thrillseeking / Higher-Level Mob Victory
            $creatureLevel = (int) ($args['creaturelevel'] ?? ($args['badguy']['creaturelevel'] ?? 0));
            $userLevel = (int) ($session['user']['level'] ?? 1);
            if ($creatureLevel > $userLevel) {
                systemai_unlock_achievement('thrillseeker_elite');
            }

            // Check System AI Foot-Fetish Trigger (Boots, Barefoot, Fists, or Toe Ring equipped)
            $weaponName = strtolower((string) ($session['user']['weapon'] ?? ''));
            $armorName = strtolower((string) ($session['user']['armor'] ?? ''));
            $isFootThemed = str_contains($weaponName, 'boot')
                || str_contains($weaponName, 'foot')
                || str_contains($weaponName, 'fist')
                || str_contains($armorName, 'crocs')
                || str_contains($armorName, 'slipper')
                || str_contains($armorName, 'toe ring');

            if ($isFootThemed) {
                systemai_unlock_achievement('barefoot_stomper');
                $chance = (int) get_module_setting('foot_bonus_chance', 'systemai');
                if ($chance > 0 && e_rand(1, 100) <= $chance) {
                    $curBronze = (int) get_module_pref('box_bronze', 'systemai');
                    set_module_pref('box_bronze', $curBronze + 1, 'systemai');
                    output("`n`5[SYSTEM AI FOOT TELEMETRY]: `3\"Ooooh, the way your heel came down on that thing... Have a bonus `^Bronze Pedicure Box`3, my little stomper!\"`0`n");
                }
            }

            systemai_render_pending_popups();
            break;

        case 'battle-defeat':
            $deaths = (int) get_module_pref('total_deaths', 'systemai') + 1;
            set_module_pref('total_deaths', $deaths, 'systemai');

            systemai_unlock_achievement('first_death');
            if ($deaths >= 5) {
                systemai_unlock_achievement('repeat_customer');
            }
            if ($deaths >= 10) {
                systemai_unlock_achievement('death_ten_times');
            }
            systemai_render_pending_popups();
            break;

        case 'dragonkill':
            $dks = (int) ($session['user']['dragonkills'] ?? 0);
            systemai_unlock_achievement('stairwell_1');

            // Award a guaranteed Gold Boss Box for clearing a Floor Guardian
            $curGold = (int) get_module_pref('box_gold', 'systemai');
            set_module_pref('box_gold', $curGold + 1, 'systemai');
            output("`n`5[SYSTEM AI]: `^\"Floor Guardian eradicated! A `6Gold Boss Box`^ has been deposited into your Safe Room Vault!\"`0`n");

            if ($dks >= 3) {
                systemai_unlock_achievement('stairwell_3');
            }
            if ($dks >= 6) {
                systemai_unlock_achievement('stairwell_6');
            }
            systemai_render_pending_popups();
            break;

        case 'village':
            $totalBoxes = systemai_count_total_boxes();
            tlschema($args['schemas']['marketnav'] ?? 'village');
            addnav($args['marketnav'] ?? 'Safe Room Facilities');
            tlschema();
            if ($totalBoxes > 0) {
                addnav(
                    ['`5System AI Loot Vault `0(`^%d`0)', $totalBoxes],
                    'runmodule.php?module=systemai'
                );
            } else {
                addnav('System AI Loot Vault', 'runmodule.php?module=systemai');
            }
            break;

        case 'charstats':
            $b = (int) get_module_pref('box_bronze', 'systemai');
            $s = (int) get_module_pref('box_silver', 'systemai');
            $g = (int) get_module_pref('box_gold', 'systemai');
            $c = (int) get_module_pref('box_celestial', 'systemai');
            $unlockedCount = count(systemai_get_json_pref('unlocked_achievements'));
            $totalAch = count(systemai_get_achievement_catalog());

            addcharstat('Syndicate HUD');
            addcharstat('Achievements', sprintf('`^%d`0/`@%d`0', $unlockedCount, $totalAch));
            addcharstat('Loot Boxes', sprintf('`qB:%d `7S:%d `^G:%d `5C:%d`0', $b, $s, $g, $c));
            break;

        case 'bioinfo':
            $targetId = (int) ($args['acctid'] ?? 0);
            if ($targetId > 0) {
                $unlocked = systemai_get_json_pref('unlocked_achievements', $targetId);
                $catalog = systemai_get_achievement_catalog();
                $opened = (int) get_module_pref('total_boxes_opened', 'systemai', $targetId);
                output(
                    "`n`5System AI Crawler Record:`0 `^%d/%d Achievements`0 (`@%d Loot Boxes Opened`0)`n",
                    count($unlocked),
                    count($catalog),
                    $opened
                );
                if ($unlocked !== []) {
                    $badges = [];
                    foreach (array_keys($unlocked) as $achKey) {
                        if (isset($catalog[$achKey])) {
                            $badges[] = '`@[' . $catalog[$achKey]['title'] . ']`0';
                        }
                    }
                    output("`7Unlocked Titles:`0 %s`n", implode(' ', $badges));
                }
            }
            break;
    }

    return $args;
}

/**
 * Helper to count all unopened Loot Boxes owned by the current user.
 */
function systemai_count_total_boxes(): int
{
    return (int) get_module_pref('box_bronze', 'systemai')
        + (int) get_module_pref('box_silver', 'systemai')
        + (int) get_module_pref('box_gold', 'systemai')
        + (int) get_module_pref('box_celestial', 'systemai');
}

/**
 * Opens a single Loot Box of the specified tier and grants randomized rewards.
 */
function systemai_open_single_box(string $tier): void
{
    global $session;

    $prefKey = 'box_' . $tier;
    $count = (int) get_module_pref($prefKey, 'systemai');
    if ($count <= 0) {
        output("`\$[SYSTEM AI]: \"Nice try, Crawler. You don't have any %s Loot Boxes in your inventory!\"`0`n", ucfirst($tier));
        return;
    }

    set_module_pref($prefKey, $count - 1, 'systemai');
    $opened = (int) get_module_pref('total_boxes_opened', 'systemai') + 1;
    set_module_pref('total_boxes_opened', $opened, 'systemai');
    if ($opened >= 25) {
        systemai_unlock_achievement('loot_goblin_25');
    }
    if ($opened >= 50) {
        systemai_unlock_achievement('box_addict_50');
    }

    $level = max(1, (int) ($session['user']['level'] ?? 1));

    switch ($tier) {
        case 'bronze':
            $roll = e_rand(1, 4);
            if ($roll === 1) {
                $gold = e_rand(75, 200) * $level;
                $session['user']['gold'] += $gold;
                output("`q[BRONZE LOOT BOX]: `2A glittery cardboard flap pops open! You receive `^%d Gold Credits`2 and a pack of damp matches!`0`n", $gold);
            } elseif ($roll === 2) {
                $gems = e_rand(1, 2);
                $session['user']['gems'] += $gems;
                output("`q[BRONZE LOOT BOX]: `2Inside is a corporate voucher worth `5%d Sponsor Token(s)`2!`0`n", $gems);
            } elseif ($roll === 3) {
                $turns = e_rand(1, 2);
                $session['user']['turns'] += $turns;
                output("`q[BRONZE LOOT BOX]: `2You pull out a syringe of lukewarm Crawler Caffeine! You gain `@+%d Floor Stamina`2!`0`n", $turns);
            } else {
                apply_buff('dcc_bronze_buzz', [
                    'name' => '`qRev-Up Moonshine Buzz`0',
                    'rounds' => 15,
                    'wearoff' => 'The cheap Clurichaun moonshine wears off, leaving a mild headache.',
                    'atkmod' => 1.12,
                    'defmod' => 1.05,
                    'roundmsg' => 'Your Rev-Up Moonshine buzz fuels a reckless swing!',
                    'schema' => 'module-systemai',
                ]);
                output("`q[BRONZE LOOT BOX]: `2You uncork a jug of `^Rev-Up Moonshine`2! (`@+12 percent Attack for 15 combat rounds`2)`0`n");
            }
            break;

        case 'silver':
            $roll = e_rand(1, 4);
            if ($roll === 1) {
                $gold = e_rand(250, 500) * $level;
                $session['user']['gold'] += $gold;
                output("`7[SILVER LOOT BOX]: `2Silver confetti explodes in your face! You collect `^%d Gold Credits`2!`0`n", $gold);
            } elseif ($roll === 2) {
                $gems = e_rand(3, 5);
                $turns = 2;
                $session['user']['gems'] += $gems;
                $session['user']['turns'] += $turns;
                output("`7[SILVER LOOT BOX]: `2A fan-club care package grants `5%d Sponsor Tokens`2 and `@+%d Floor Stamina`2!`0`n", $gems, $turns);
            } elseif ($roll === 3) {
                $session['user']['maxhitpoints'] += 2;
                $session['user']['hitpoints'] += 2;
                output("`7[SILVER LOOT BOX]: `2You consume an `@Enhanced Crawler Biscuit`2! Your **Permanent Max HP** increases by `@+2`2!`0`n");
            } else {
                apply_buff('dcc_silver_pedicure', [
                    'name' => '`5System AI Deluxe Pedicure Glow`0',
                    'rounds' => 25,
                    'wearoff' => 'The holographic pedicure sparkle finally fades from your feet.',
                    'atkmod' => 1.22,
                    'defmod' => 1.18,
                    'roundmsg' => 'The System AI\'s camera drones zoom in on your feet, boosting your combat aura!',
                    'schema' => 'module-systemai',
                ]);
                output("`7[SILVER LOOT BOX]: `2You equip the `5System AI Deluxe Pedicure Kit`2! (`@+22 percent ATK / +18 percent DEF for 25 rounds`2)`0`n");
            }
            break;

        case 'gold':
            $roll = e_rand(1, 4);
            if ($roll === 1) {
                $gold = e_rand(600, 1100) * $level;
                $gems = e_rand(6, 10);
                $session['user']['gold'] += $gold;
                $session['user']['gems'] += $gems;
                output("`^[GOLD LOOT BOX]: `2A fanfare of synth-horns blares! You receive `^%d Gold Credits`2 and `5%d Sponsor Tokens`2!`0`n", $gold, $gems);
            } elseif ($roll === 2) {
                $session['user']['attack'] += 1;
                $session['user']['turns'] += 4;
                output("`^[GOLD LOOT BOX]: `2You apply a `\$Bomb Upgrade Patch`2 to your jacket! **Permanent Attack +1** and `@+4 Floor Stamina`2!`0`n");
            } elseif ($roll === 3) {
                $session['user']['defense'] += 1;
                $session['user']['maxhitpoints'] += 3;
                $session['user']['hitpoints'] += 3;
                output("`^[GOLD LOOT BOX]: `2You slide on an `^Enchanted Iron-Skin Ring`2! **Permanent Defense +1** and **Permanent Max HP +3**!`0`n");
            } else {
                apply_buff('dcc_gold_hoblobber', [
                    'name' => '`^Carl\'s Jug O\' Boom Bandolier`0',
                    'rounds' => 35,
                    'wearoff' => 'You throw your last Hoblobber stick.',
                    'atkmod' => 1.38,
                    'defmod' => 1.25,
                    'minioncount' => 1,
                    'maxbadguydamage' => 15 + ($level * 2),
                    'minbadguydamage' => 5 + $level,
                    'effectmsg' => '`^Your Hoblobber detonates on impact for `4{damage}`^ blast damage!`0',
                    'schema' => 'module-systemai',
                ]);
                output("`^[GOLD LOOT BOX]: `2You strap on `^Carl's Jug O' Boom Bandolier`2! (`@+38 percent ATK, +25 percent DEF, and explosive shrapnel for 35 rounds`2)`0`n");
            }
            break;

        case 'celestial':
            $taxChance = (int) get_module_setting('borant_tax_chance', 'systemai');
            $audited = ($taxChance > 0 && e_rand(1, 100) <= $taxChance);

            if ($audited) {
                output("`n`\$[BORANT LEGAL INTERCEPTION]: `4The Borant Corporation attempted to freeze your Celestial Box to avoid paying Syndicate luxury taxes! `5The System AI overruled their injunction and awarded punitive damages!`0`n");
            }

            $gold = (e_rand(2500, 4000) * $level) + ($audited ? 10000 : 0);
            $gems = e_rand(20, 30) + ($audited ? 10 : 0);
            $session['user']['gold'] += $gold;
            $session['user']['gems'] += $gems;
            $session['user']['attack'] += 2;
            $session['user']['defense'] += 2;
            $session['user']['maxhitpoints'] += 5;
            $session['user']['hitpoints'] += 5;
            $session['user']['turns'] += 8;

            apply_buff('dcc_celestial_ascendancy', [
                'name' => '`5Celestial Avatar of the Twelfth Floor`0',
                'rounds' => 60,
                'wearoff' => 'Your Celestial Sponsor aura finally dissipates.',
                'atkmod' => 1.65,
                'defmod' => 1.50,
                'lifetap' => 0.25,
                'roundmsg' => '`5Celestial Sponsor energy surges through your strikes, draining life from your foe!`0',
                'schema' => 'module-systemai',
            ]);

            output(
                "`5[CELESTIAL LOOT BOX]: `2Golden choir music shakes the Safe Room ceiling! You receive `^%d Gold Credits`2, `5%d Sponsor Tokens`2, `@+8 Floor Stamina`2, **+2 Permanent ATK**, **+2 Permanent DEF**, **+5 Permanent Max HP**, and the `5Celestial Avatar`2 combat buff for 60 rounds!`0`n",
                $gold,
                $gems
            );

            if ((int) get_module_setting('broadcast_celestial', 'systemai') === 1) {
                $crawlerName = (string) ($session['user']['name'] ?? 'A Crawler');
                addnews(
                    '`5SYNDICATE BREAKING NEWS:`0 `@%s`2 just cracked open a `5Celestial Loot Box`2 inside the Safe Room despite Borant Corporation\'s objections!',
                    $crawlerName
                );
            }
            break;
    }
}

/**
 * Main Safe Room Loot Vault & Achievement Hall page handler.
 */
function systemai_run(): void
{
    page_header('System AI Loot Vault & Achievement Terminal');

    $op = (string) httpget('op');

    output("`c`b`5=== SYSTEM AI LOOT VAULT & ACHIEVEMENT TERMINAL ===`0`b`c`n");

    // Render any queued popups first
    systemai_render_pending_popups();

    if ($op === 'open') {
        $tier = (string) httpget('tier');
        if (in_array($tier, ['bronze', 'silver', 'gold', 'celestial'], true)) {
            systemai_open_single_box($tier);
            output("`n");
        }
    } elseif ($op === 'open_all') {
        $total = systemai_count_total_boxes();
        if ($total <= 0) {
            output("`\$[SYSTEM AI]: \"You have zero boxes to open, Crawler. Go step on some goblins first!\"`0`n`n");
        } else {
            foreach (['bronze', 'silver', 'gold', 'celestial'] as $tier) {
                $qty = (int) get_module_pref('box_' . $tier, 'systemai');
                for ($i = 0; $i < $qty; $i++) {
                    systemai_open_single_box($tier);
                }
            }
            output("`n");
        }
    }

    $b = (int) get_module_pref('box_bronze', 'systemai');
    $s = (int) get_module_pref('box_silver', 'systemai');
    $g = (int) get_module_pref('box_gold', 'systemai');
    $c = (int) get_module_pref('box_celestial', 'systemai');
    $opened = (int) get_module_pref('total_boxes_opened', 'systemai');
    $kills = (int) get_module_pref('total_kills', 'systemai');
    $flawless = (int) get_module_pref('flawless_kills', 'systemai');
    $deaths = (int) get_module_pref('total_deaths', 'systemai');

    if ($op === 'achievements') {
        $catalog = systemai_get_achievement_catalog();
        $unlocked = systemai_get_json_pref('unlocked_achievements');

        $hiddenTotal = 0;
        $hiddenFound = 0;
        foreach ($catalog as $id => $info) {
            if (!empty($info['hidden'])) {
                $hiddenTotal++;
                if (isset($unlocked[$id])) {
                    $hiddenFound++;
                }
            }
        }

        output(
            "`3The holographic terminal projects your Syndicate Crawler dossier (`^%d / %d Achievements Unlocked`3 | `5%d / %d Secret Achievements Discovered`3):`0`n`n",
            count($unlocked),
            count($catalog),
            $hiddenFound,
            $hiddenTotal
        );

        rawoutput('<table border="0" cellpadding="6" cellspacing="1" width="100%" style="background:#11161d;border:1px solid #30363d;">');
        rawoutput('<tr style="background:#1f2937;color:#00f0ff;font-weight:bold;"><td>Status</td><td>Achievement</td><td>System AI Commentary</td><td>Reward</td></tr>');

        foreach ($catalog as $id => $info) {
            $isUnlocked = isset($unlocked[$id]);
            $isHidden = !empty($info['hidden']);

            if ($isHidden && !$isUnlocked) {
                $statusBadge = '<span style="color:#ff2a85;font-weight:bold;">&#128274; SECRET</span>';
                $rowBg = '#0b0e14';
                $titleHtml = '<span style="color:#8b949e;letter-spacing:1px;">??? [CLASSIFIED ACHIEVEMENT]</span>';
                $descHtml = '<span style="color:#6e7681;">Unlock condition redacted by the System AI. Keep doing reckless things in the Dungeon to discover it.</span>';
                $rewardHtml = '<span style="color:#6e7681;">??? [REDACTED]</span>';
            } else {
                $statusBadge = $isUnlocked
                    ? ($isHidden
                        ? '<span style="color:#ff2a85;font-weight:bold;">&#9733; SECRET UNLOCKED</span><br><small style="color:#8b949e;">' . htmlspecialchars((string) $unlocked[$id], ENT_QUOTES, 'UTF-8') . '</small>'
                        : '<span style="color:#2ea043;font-weight:bold;">&#10004; UNLOCKED</span><br><small style="color:#8b949e;">' . htmlspecialchars((string) $unlocked[$id], ENT_QUOTES, 'UTF-8') . '</small>')
                    : '<span style="color:#6e7681;">&#128274; LOCKED</span>';
                $rowBg = $isUnlocked ? '#161b22' : '#0d1117';
                $titleColor = $isUnlocked ? '#ffd700' : '#8b949e';
                $titleHtml = '<span style="color:' . $titleColor . ';font-weight:bold;">' . htmlspecialchars($info['title'], ENT_QUOTES, 'UTF-8') . '</span>';
                $descHtml = '"<span style="color:#c9d1d9;font-style:italic;">' . htmlspecialchars($info['desc'], ENT_QUOTES, 'UTF-8') . '</span>"';
                $rewardHtml = '<span style="color:#ff2a85;font-weight:bold;">' . htmlspecialchars($info['reward_text'], ENT_QUOTES, 'UTF-8') . '</span>';
            }

            rawoutput('<tr style="background:' . $rowBg . ';border-bottom:1px solid #21262d;">');
            rawoutput('<td>' . $statusBadge . '</td>');
            rawoutput('<td>' . $titleHtml . '</td>');
            rawoutput('<td>' . $descHtml . '</td>');
            rawoutput('<td>' . $rewardHtml . '</td>');
            rawoutput('</tr>');
        }
        rawoutput('</table>');
    } else {
        output("`3A floating Syndicate kiosk hums in the corner of the Bopca Safe Room. Per Borant Corporation safety regulations, **Loot Boxes may only be opened inside a designated Safe Room** to prevent mid-combat item flooding.`0`n`n");

        output("`b`^Crawler Telemetry Summary:`0`b`n");
        output("`7• Confirmed Floor Kills:`0 `@%d`0 (`^%d Flawless`0)`n", $kills, $flawless);
        output("`7• Televised Reconstructions (Deaths):`0 `\$%d`0`n", $deaths);
        output("`7• Lifetime Loot Boxes Cracked:`0 `5%d`0`n`n", $opened);

        output("`b`5Unopened Safe Room Vault Inventory:`0`b`n");
        output("`q• Bronze Loot Boxes:`0 `^%d`0`n", $b);
        output("`7• Silver Loot Boxes:`0 `^%d`0`n", $s);
        output("`^• Gold Loot Boxes:`0 `^%d`0`n", $g);
        output("`5• Celestial Loot Boxes:`0 `^%d`0`n`n", $c);
    }

    addnav('Loot Box Actions');
    if ($b > 0) {
        addnav(['Open Bronze Box (%d)', $b], 'runmodule.php?module=systemai&op=open&tier=bronze');
    }
    if ($s > 0) {
        addnav(['Open Silver Box (%d)', $s], 'runmodule.php?module=systemai&op=open&tier=silver');
    }
    if ($g > 0) {
        addnav(['Open Gold Box (%d)', $g], 'runmodule.php?module=systemai&op=open&tier=gold');
    }
    if ($c > 0) {
        addnav(['`5Open Celestial Box (%d)`0', $c], 'runmodule.php?module=systemai&op=open&tier=celestial');
    }
    $total = $b + $s + $g + $c;
    if ($total > 1) {
        addnav(['`^Open ALL Boxes (%d)`0', $total], 'runmodule.php?module=systemai&op=open_all');
    }

    addnav('System AI Terminal');
    if ($op === 'achievements') {
        addnav('Return to Loot Vault', 'runmodule.php?module=systemai');
    } else {
        addnav('View Achievement Dossier', 'runmodule.php?module=systemai&op=achievements');
    }
    addnav('Return to Safe Room', 'village.php');

    page_footer();
}