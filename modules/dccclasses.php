<?php

declare(strict_types=1);

/**
 * Module: Floor 3 Crawler Races, Classes & Subclasses (dccclasses.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Compensated_Anarchist_Class
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Category:Crawler_Races
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Class
 *
 * Description:
 *   Implements the canonical Dungeon Crawler Carl Floor 3 Race & Class selection system
 *   (plus Floor 6 Subclass specialization) overseen by Game Guide Mordecai.
 *   - Races: Primal, Changeling/Doppelgänger, Tortoiseshell Persian Cat, Caprid,
 *     Crocodilian, and Earth Human (grants a bonus Silver Earth Box if `systemai` is active).
 *   - Classes: Compensated Anarchist, Former Child Actor, Mass-Shifting Juggernaut,
 *     and Frost Maiden Cryo-Savant.
 *   - Floor 6 Subclasses for Compensated Anarchist: Agent Provocateur, Revolutionary, and Guerilla.
 *   - Full combat integration via `fightnav-specialties` and `apply-specialties` hooks.
 */

function dccclasses_getmoduleinfo(): array
{
    return [
        'name' => 'Floor 3 Crawler Races & Classes',
        'version' => '1.0.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'World Dungeon Floor Gatekeeping,title',
            'min_dk_for_floor3' => 'Minimum Floors Descended (Dragon Kills) for Floor 3 Race/Class selection (0 = immediate),range,0,10,1|2',
            'min_dk_for_floor6' => 'Minimum Floors Descended (Dragon Kills) for Floor 6 Subclass specialization,range,1,15,1|5',
            'base_daily_charges' => 'Base daily Class Skill Charges granted each New Day,int|6',
            'allow_respec_gems' => 'Sponsor Tokens (Gems) required for an emergency Borant Respec (0 = disable),int|15',
        ],
        'prefs' => [
            'Crawler Race & Class Dossier,title',
            'crawler_race' => 'Selected Floor 3 Crawler Race ID,text|',
            'crawler_class' => 'Selected Floor 3 Crawler Class ID,text|',
            'crawler_subclass' => 'Selected Floor 6 Subclass ID,text|',
            'skill_charges' => 'Remaining Class Skill Charges today,int|6',
            'max_charges' => 'Maximum Class Skill Charges today,int|6',
            'earth_box_claimed' => 'Has claimed the Silver Earth Class/Race incentive box?,bool|0',
        ],
    ];
}

function dccclasses_install(): bool
{
    module_addhook('village');
    module_addhook('newday');
    module_addhook('fightnav-specialties');
    module_addhook('apply-specialties');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function dccclasses_uninstall(): bool
{
    return true;
}

/**
 * Returns the catalog of Floor 3 Crawler Races.
 *
 * @return array<string, array{
 *   name: string,
 *   color_name: string,
 *   desc: string,
 *   atk_bonus: int,
 *   def_bonus: int,
 *   hp_bonus: int,
 *   turns_bonus: int,
 *   charges_bonus: int
 * }>
 */
function dccclasses_get_races(): array
{
    return [
        'primal' => [
            'name' => 'Primal',
            'color_name' => '`5Primal`0',
            'desc' => 'The mysterious progenitor race of the Syndicate (chosen by Carl). Grants +2 extra Daily Skill Charges, +2 Floor Stamina, and +2 Attack as your ancient neural pathways unlock Level-20 skill mastery.',
            'atk_bonus' => 2,
            'def_bonus' => 1,
            'hp_bonus' => 5,
            'turns_bonus' => 2,
            'charges_bonus' => 2,
        ],
        'changeling' => [
            'name' => 'Changeling / Doppelgänger',
            'color_name' => '`#Changeling`0',
            'desc' => 'Morphing flesh-sculptors (chosen by Mordecai and Katia Grim). Grants +3 Defense, +10 Max HP, and +1 Skill Charge through adaptive cellular mass-shifting.',
            'atk_bonus' => 1,
            'def_bonus' => 3,
            'hp_bonus' => 10,
            'turns_bonus' => 1,
            'charges_bonus' => 1,
        ],
        'persian_cat' => [
            'name' => 'Sapient Tortoiseshell Persian Cat',
            'color_name' => '`^Sapient Show Cat`0',
            'desc' => 'Grand Champion feline royalty (Princess Donut\'s breed). Grants +3 extra Daily Skill Charges, +2 Attack, and +1 Floor Stamina—though you refuse to walk through puddles.',
            'atk_bonus' => 2,
            'def_bonus' => 1,
            'hp_bonus' => 0,
            'turns_bonus' => 1,
            'charges_bonus' => 3,
        ],
        'caprid' => [
            'name' => 'Highland Caprid (Sapient Goat)',
            'color_name' => '`@Highland Caprid`0',
            'desc' => 'Stubborn bipedal mountain goat (Prepotente\'s race). Grants +2 Attack, +2 Defense, +8 Max HP, and +2 Skill Charges fueled by ear-shattering screams and potion chugging.',
            'atk_bonus' => 2,
            'def_bonus' => 2,
            'hp_bonus' => 8,
            'turns_bonus' => 1,
            'charges_bonus' => 2,
        ],
        'crocodilian' => [
            'name' => 'Armored Crocodilian',
            'color_name' => '`2Crocodilian`0',
            'desc' => 'Reptilian apex predator (Florin\'s race). Grants +3 Attack, +4 Defense, and +15 Max HP via thick interlocking osteoderm scales.',
            'atk_bonus' => 3,
            'def_bonus' => 4,
            'hp_bonus' => 15,
            'turns_bonus' => 0,
            'charges_bonus' => 1,
        ],
        'earth_human' => [
            'name' => 'Earth Human (Purebred)',
            'color_name' => '`&Earth Human`0',
            'desc' => 'Around 80% of crawlers keep their original DNA. Balanced +2 Attack, +2 Defense, +6 Max HP, +2 Floor Stamina, plus a one-time Silver Earth Box incentive!',
            'atk_bonus' => 2,
            'def_bonus' => 2,
            'hp_bonus' => 6,
            'turns_bonus' => 2,
            'charges_bonus' => 1,
        ],
    ];
}

/**
 * Returns the catalog of Floor 3 Crawler Classes and their 3 active combat skills.
 *
 * @return array<string, array{
 *   name: string,
 *   color_name: string,
 *   lore: string,
 *   is_earth_class: bool,
 *   skills: array<int, array{
 *     id: string,
 *     name: string,
 *     cost: int,
 *     buff_name: string,
 *     rounds: int,
 *     atkmod: float,
 *     defmod: float,
 *     min_dmg: int,
 *     max_dmg: int,
 *     lifetap: float,
 *     badguy_atkmod: float,
 *     roundmsg: string,
 *     effectmsg: string,
 *     wearoff: string
 *   }>
 * }>
 */
function dccclasses_get_classes(): array
{
    return [
        'compensated_anarchist' => [
            'name' => 'Compensated Anarchist',
            'color_name' => '`4Compensated Anarchist`0',
            'lore' => 'Carl\'s rare Monk/Rogue Earth Class: "A trapmaking, bomb-making, social-media dynamo." Excels at bare-footed unarmed strikes, Hoblobber traps, and Soul-Crystal explosives. Receives a Silver Earth Box upon selection!',
            'is_earth_class' => true,
            'skills' => [
                1 => [
                    'id' => 'anarchist_stomp',
                    'name' => 'Pedicure Heel Stomp & Fear',
                    'cost' => 1,
                    'buff_name' => '`4Anarchist Barefoot Stomp & Fear`0',
                    'rounds' => 5,
                    'atkmod' => 1.35,
                    'defmod' => 1.15,
                    'min_dmg' => 4,
                    'max_dmg' => 12,
                    'lifetap' => 0.0,
                    'badguy_atkmod' => 0.85,
                    'roundmsg' => '`4The System AI zooms in on your glittering heel as you stomp {badguy} into the floor tiles!`0',
                    'effectmsg' => '`4Your crushing heel strike inflicts an extra `^{damage}`4 unarmed impact damage!`0',
                    'wearoff' => 'The slow-motion foot camera drones finally pan away.',
                ],
                2 => [
                    'id' => 'anarchist_hoblobber',
                    'name' => 'Chistera Hoblobber Barrage',
                    'cost' => 2,
                    'buff_name' => '`^Chistera Hoblobber Barrage`0',
                    'rounds' => 6,
                    'atkmod' => 1.45,
                    'defmod' => 1.20,
                    'min_dmg' => 10,
                    'max_dmg' => 28,
                    'lifetap' => 0.0,
                    'badguy_atkmod' => 0.80,
                    'roundmsg' => '`^You whip a fused Hobgoblin stick out of your Chistera basket!`0',
                    'effectmsg' => '`^BOOM! Impact-detonated goblin dynamite blasts {badguy} for `4{damage}`^ shrapnel damage!`0',
                    'wearoff' => 'Your smoke curtain clears and you holster your Chistera.',
                ],
                3 => [
                    'id' => 'anarchist_doomsday',
                    'name' => 'Carl\'s Jug O\' Boom Sabotage',
                    'cost' => 3,
                    'buff_name' => '`$Anarchist Cookbook Jug O\' Boom`0',
                    'rounds' => 8,
                    'atkmod' => 1.75,
                    'defmod' => 1.40,
                    'min_dmg' => 22,
                    'max_dmg' => 55,
                    'lifetap' => 0.0,
                    'badguy_atkmod' => 0.70,
                    'roundmsg' => '`$You consult a hidden passage from the Dungeon Anarchist\'s Cookbook and prime a moonshine jug!`0',
                    'effectmsg' => '`$KABOOM!! Your Jug O\' Boom engulfs {badguy} in goblin oil fire for `^{damage}`$ blast damage!`0',
                    'wearoff' => 'The ringing in your ears from the Jug O\' Boom finally subsides.',
                ],
            ],
        ],
        'former_child_actor' => [
            'name' => 'Former Child Actor (Grand Champion)',
            'color_name' => '`5Former Child Actor`0',
            'lore' => 'Princess Donut\'s high-Charisma celebrity spellcaster class. Channels fan-club ratings into Clockwork Triplicate Magic Missiles, Diva Tantrums, and Laundry Day buff-stripping.',
            'is_earth_class' => true,
            'skills' => [
                1 => [
                    'id' => 'actor_missile',
                    'name' => 'Clockwork Triplicate Magic Missile',
                    'cost' => 1,
                    'buff_name' => '`5Clockwork Triplicate Magic Missile`0',
                    'rounds' => 5,
                    'atkmod' => 1.30,
                    'defmod' => 1.20,
                    'min_dmg' => 6,
                    'max_dmg' => 16,
                    'lifetap' => 0.0,
                    'badguy_atkmod' => 0.90,
                    'roundmsg' => '`5Three glowing arcane bolts fire from the gemstones of your Tiara!`0',
                    'effectmsg' => '`5Your Magic Missiles unerringly slam into {badguy} for `^{damage}`5 arcane damage!`0',
                    'wearoff' => 'Your tiara gemstones cool down.',
                ],
                2 => [
                    'id' => 'actor_tantrum',
                    'name' => 'ALL-CAPS Chat Diva Tantrum',
                    'cost' => 2,
                    'buff_name' => '`^"CARL, DO SOMETHING!" Diva Aura`0',
                    'rounds' => 6,
                    'atkmod' => 1.40,
                    'defmod' => 1.50,
                    'min_dmg' => 8,
                    'max_dmg' => 22,
                    'lifetap' => 0.15,
                    'badguy_atkmod' => 0.65,
                    'roundmsg' => '`^You scream in ALL-CAPS into the party chat, dazzling {badguy} with celebrity outrage!`0',
                    'effectmsg' => '`^The Princess Posse sponsor beam scorches {badguy} for `5{damage}`^ damage!`0',
                    'wearoff' => 'You compose yourself and groom your fur for the cameras.',
                ],
                3 => [
                    'id' => 'actor_laundry',
                    'name' => 'Laundry Day & Bolt of Ophiotaurus',
                    'cost' => 3,
                    'buff_name' => '`#Laundry Day God-Slayer Barrage`0',
                    'rounds' => 8,
                    'atkmod' => 1.80,
                    'defmod' => 1.45,
                    'min_dmg' => 25,
                    'max_dmg' => 60,
                    'lifetap' => 0.20,
                    'badguy_atkmod' => 0.60,
                    'roundmsg' => '`#You cast Laundry Day, stripping {badguy}\'s protective wards wide open!`0',
                    'effectmsg' => '`#Your Bolt of Ophiotaurus pierces {badguy}\'s defenses for `^{damage}`# true damage!`0',
                    'wearoff' => 'The celestial resonance of the Bolt of Ophiotaurus fades.',
                ],
            ],
        ],
        'mass_juggernaut' => [
            'name' => 'Mass-Shifting Juggernaut',
            'color_name' => '`#Mass-Shifting Juggernaut`0',
            'lore' => 'Katia Grim\'s heavy front-line tank class. Absorbs scrap steel to multiply mass, reflect melee blows, and obliterate enemies with a Piston-Driven Battering Ram.',
            'is_earth_class' => false,
            'skills' => [
                1 => [
                    'id' => 'jug_armor',
                    'name' => 'Scrap-Metal Mass Absorption',
                    'cost' => 1,
                    'buff_name' => '`#Scrap-Metal Carapace`0',
                    'rounds' => 6,
                    'atkmod' => 1.20,
                    'defmod' => 1.55,
                    'min_dmg' => 3,
                    'max_dmg' => 10,
                    'lifetap' => 0.10,
                    'badguy_atkmod' => 0.85,
                    'roundmsg' => '`#Welded plates of dwarven steel shift across your body to deflect {badguy}\'s blows!`0',
                    'effectmsg' => '`#Spikes erupt from your shifted armor, impaling {badguy} for `^{damage}`# damage!`0',
                    'wearoff' => 'You shed the excess scrap metal weight.',
                ],
                2 => [
                    'id' => 'jug_piston',
                    'name' => 'Piston-Driven Battering Ram',
                    'cost' => 2,
                    'buff_name' => '`@Piston-Driven Battering Ram`0',
                    'rounds' => 6,
                    'atkmod' => 1.60,
                    'defmod' => 1.35,
                    'min_dmg' => 12,
                    'max_dmg' => 32,
                    'lifetap' => 0.0,
                    'badguy_atkmod' => 0.80,
                    'roundmsg' => '`@You shift your entire arm into a pneumatic siege ram!`0',
                    'effectmsg' => '`@CLANG!! The piston ram launches {badguy} down the corridor for `^{damage}`@ crushing damage!`0',
                    'wearoff' => 'Your pneumatic piston vents steam and retracts.',
                ],
                3 => [
                    'id' => 'jug_cowcatcher',
                    'name' => 'Iron Tangle Cowcatcher Overdrive',
                    'cost' => 3,
                    'buff_name' => '`$Locomotive Cowcatcher Overdrive`0',
                    'rounds' => 8,
                    'atkmod' => 1.70,
                    'defmod' => 1.75,
                    'min_dmg' => 20,
                    'max_dmg' => 50,
                    'lifetap' => 0.25,
                    'badguy_atkmod' => 0.65,
                    'roundmsg' => '`$You bulk to three tons of unstoppable locomotive mass and charge!`0',
                    'effectmsg' => '`$Your cowcatcher charge flattens {badguy} for `^{damage}`$ collision damage!`0',
                    'wearoff' => 'You compress back down to normal humanoid dimensions.',
                ],
            ],
        ],
        'frost_maiden' => [
            'name' => 'Frost Maiden Cryo-Savant',
            'color_name' => '`#Frost Maiden`0',
            'lore' => 'Elle McGibbons\'s airborne ice-sorceress class. Freezes corridor mobs solid, erects Meadow Lark blizzard shields, and unleashes Four-Seasons elemental tempests.',
            'is_earth_class' => false,
            'skills' => [
                1 => [
                    'id' => 'frost_lance',
                    'name' => 'Subzero Icicle Volley',
                    'cost' => 1,
                    'buff_name' => '`#Subzero Icicle Volley`0',
                    'rounds' => 5,
                    'atkmod' => 1.30,
                    'defmod' => 1.25,
                    'min_dmg' => 5,
                    'max_dmg' => 15,
                    'lifetap' => 0.0,
                    'badguy_atkmod' => 0.80,
                    'roundmsg' => '`#Razor-sharp icicles form in mid-air and pepper {badguy}!`0',
                    'effectmsg' => '`#Frostbite shatters across {badguy} for `^{damage}`# cryo damage!`0',
                    'wearoff' => 'The ambient corridor temperature rises back above freezing.',
                ],
                2 => [
                    'id' => 'frost_blizzard',
                    'name' => 'Meadow Lark Blizzard Ward',
                    'cost' => 2,
                    'buff_name' => '`#Meadow Lark Blizzard Ward`0',
                    'rounds' => 7,
                    'atkmod' => 1.35,
                    'defmod' => 1.65,
                    'min_dmg' => 9,
                    'max_dmg' => 24,
                    'lifetap' => 0.15,
                    'badguy_atkmod' => 0.65,
                    'roundmsg' => '`#Howling arctic winds freeze {badguy}\'s joints mid-swing!`0',
                    'effectmsg' => '`#Your hail vortex shreds {badguy} for `^{damage}`# ice damage!`0',
                    'wearoff' => 'The swirling snowstorm settles into frost on the floor.',
                ],
                3 => [
                    'id' => 'frost_tempest',
                    'name' => 'Absolute Zero Four-Seasons Convergence',
                    'cost' => 3,
                    'buff_name' => '`5Absolute Zero Convergence`0',
                    'rounds' => 8,
                    'atkmod' => 1.85,
                    'defmod' => 1.50,
                    'min_dmg' => 24,
                    'max_dmg' => 58,
                    'lifetap' => 0.20,
                    'badguy_atkmod' => 0.55,
                    'roundmsg' => '`5You hover above the ground as flash-freezing lightning arcs across the room!`0',
                    'effectmsg' => '`5Absolute Zero shatters {badguy}\'s frozen armor for `^{damage}`5 catastrophic damage!`0',
                    'wearoff' => 'Your Frost Maiden aura subsides to a gentle flurry.',
                ],
            ],
        ],
    ];
}

/**
 * Returns Floor 6 Subclass Specializations (including Carl's 3 canonical Compensated Anarchist upgrades).
 *
 * @return array<string, array{name: string, color_name: string, parent_class: string, desc: string, atk_bonus: int, def_bonus: int, charges_bonus: int}>
 */
function dccclasses_get_subclasses(): array
{
    return [
        'agent_provocateur' => [
            'name' => 'Agent Provocateur',
            'color_name' => '`$Agent Provocateur`0',
            'parent_class' => 'compensated_anarchist',
            'desc' => 'Carl\'s Floor 6 choice: Unlocks the Advanced Bomb Maker\'s Workshop, +3 Attack, +1 Defense, and +3 Daily Skill Charges for relentless explosive sabotage.',
            'atk_bonus' => 3,
            'def_bonus' => 1,
            'charges_bonus' => 3,
        ],
        'revolutionary' => [
            'name' => 'Revolutionary',
            'color_name' => '`4Revolutionary`0',
            'parent_class' => 'compensated_anarchist',
            'desc' => 'Mordecai\'s recommended Floor 6 upgrade: Unarmed melee powerhouse with poison-cloud immunity, +4 Attack, +3 Defense, and +1 Daily Skill Charge.',
            'atk_bonus' => 4,
            'def_bonus' => 3,
            'charges_bonus' => 1,
        ],
        'guerilla' => [
            'name' => 'Guerilla',
            'color_name' => '`@Guerilla`0',
            'parent_class' => 'compensated_anarchist',
            'desc' => 'Ranged crossbow & stealth trap specialist: Unlocks Camouflage ambush tactics, +2 Attack, +4 Defense, and +2 Daily Skill Charges.',
            'atk_bonus' => 2,
            'def_bonus' => 4,
            'charges_bonus' => 2,
        ],
        'queen_anne_chonk' => [
            'name' => 'Queen Anne Chonk Warlord',
            'color_name' => '`5Queen Anne Chonk Warlord`0',
            'parent_class' => 'former_child_actor',
            'desc' => 'Princess Posse Faction Warlord specialization: +3 Attack, +2 Defense, and +3 Daily Skill Charges for non-stop tiara spellcasting.',
            'atk_bonus' => 3,
            'def_bonus' => 2,
            'charges_bonus' => 3,
        ],
        'valkyrie_dreadnought' => [
            'name' => 'Valkyrie Dreadnought',
            'color_name' => '`#Valkyrie Dreadnought`0',
            'parent_class' => 'mass_juggernaut',
            'desc' => 'Heavy siege specialization: +3 Attack, +5 Defense, and +2 Daily Skill Charges.',
            'atk_bonus' => 3,
            'def_bonus' => 5,
            'charges_bonus' => 2,
        ],
        'four_seasons_archmage' => [
            'name' => 'Four-Seasons Archmage',
            'color_name' => '`#Four-Seasons Archmage`0',
            'parent_class' => 'frost_maiden',
            'desc' => 'Apex elemental caster: +4 Attack, +2 Defense, and +3 Daily Skill Charges.',
            'atk_bonus' => 4,
            'def_bonus' => 2,
            'charges_bonus' => 3,
        ],
    ];
}

function dccclasses_dohook(string $hookname, array $args): array
{
    global $session;

    $races = dccclasses_get_races();
    $classes = dccclasses_get_classes();
    $subclasses = dccclasses_get_subclasses();

    $raceId = (string) get_module_pref('crawler_race', 'dccclasses');
    $classId = (string) get_module_pref('crawler_class', 'dccclasses');
    $subId = (string) get_module_pref('crawler_subclass', 'dccclasses');

    switch ($hookname) {
        case 'village':
            $dks = (int) ($session['user']['dragonkills'] ?? 0);
            $minFloor3 = (int) get_module_setting('min_dk_for_floor3', 'dccclasses');
            tlschema($args['schemas']['fightnav'] ?? 'village');
            addnav($args['fightnav'] ?? 'Mordecai\'s Guildhall');
            tlschema();

            if ($dks >= $minFloor3 && ($raceId === '' || $classId === '')) {
                addnav('`^Floor 3 Race & Class Selection (!)`0', 'runmodule.php?module=dccclasses');
            } else {
                addnav('Crawler Race & Class Desk', 'runmodule.php?module=dccclasses');
            }
            break;

        case 'newday':
            $baseCharges = max(1, (int) get_module_setting('base_daily_charges', 'dccclasses'));
            $bonusCharges = 0;
            $atkBonus = 0;
            $defBonus = 0;
            $turnsBonus = 0;

            if (isset($races[$raceId])) {
                $r = $races[$raceId];
                $atkBonus += $r['atk_bonus'];
                $defBonus += $r['def_bonus'];
                $turnsBonus += $r['turns_bonus'];
                $bonusCharges += $r['charges_bonus'];
            }

            if (isset($subclasses[$subId])) {
                $s = $subclasses[$subId];
                $atkBonus += $s['atk_bonus'];
                $defBonus += $s['def_bonus'];
                $bonusCharges += $s['charges_bonus'];
            }

            $totalCharges = $baseCharges + $bonusCharges;
            set_module_pref('max_charges', $totalCharges, 'dccclasses');
            set_module_pref('skill_charges', $totalCharges, 'dccclasses');

            if ($turnsBonus > 0) {
                $session['user']['turns'] += $turnsBonus;
            }

            if ($atkBonus > 0 || $defBonus > 0) {
                apply_buff('dcc_race_class_passive', [
                    'name' => '`@Crawler Race & Class Passive Aura`0',
                    'rounds' => -1,
                    'atkmod' => 1.0 + ($atkBonus * 0.03),
                    'defmod' => 1.0 + ($defBonus * 0.03),
                    'schema' => 'module-dccclasses',
                ]);
            }

            if (isset($classes[$classId])) {
                output(
                    "`n`@[MORDECAI'S GUILDHALL]: `2Your %s / %s abilities are refreshed (`^%d Skill Charges`2%s)!`0`n",
                    $races[$raceId]['color_name'] ?? '`7Human`0',
                    $classes[$classId]['color_name'],
                    $totalCharges,
                    $turnsBonus > 0 ? sprintf(', `@+%d Floor Stamina`2', $turnsBonus) : ''
                );
            }
            break;

        case 'fightnav-specialties':
            if (!isset($classes[$classId])) {
                break;
            }
            $charges = (int) get_module_pref('skill_charges', 'dccclasses');
            if ($charges <= 0) {
                break;
            }

            $script = (string) ($args['script'] ?? '');
            $cInfo = $classes[$classId];
            addnav(['%s (`^%d`0)', $cInfo['color_name'], $charges]);

            foreach ($cInfo['skills'] as $tier => $skill) {
                if ($charges >= $skill['cost']) {
                    addnav(
                        ['&#9889; %s (`^%d`0)', $skill['name'], $skill['cost']],
                        $script . 'op=fight&skill=dccclasses&l=' . (int) $tier,
                        true
                    );
                }
            }
            break;

        case 'apply-specialties':
            $skillParam = (string) httpget('skill');
            if ($skillParam !== 'dccclasses' || !isset($classes[$classId])) {
                break;
            }

            $tier = (int) httpget('l');
            $cInfo = $classes[$classId];
            if (!isset($cInfo['skills'][$tier])) {
                break;
            }

            $skill = $cInfo['skills'][$tier];
            $charges = (int) get_module_pref('skill_charges', 'dccclasses');

            if ($charges < $skill['cost']) {
                output("`\$[HUD ERROR]: Not enough Class Skill Charges remaining to trigger %s!`0`n", $skill['name']);
                break;
            }

            set_module_pref('skill_charges', $charges - $skill['cost'], 'dccclasses');
            $level = max(1, (int) ($session['user']['level'] ?? 1));
            $subBonus = isset($subclasses[$subId]) ? 1.12 : 1.0;

            apply_buff('dcc_active_skill_' . $skill['id'], [
                'name' => $skill['buff_name'],
                'rounds' => $skill['rounds'],
                'wearoff' => $skill['wearoff'],
                'atkmod' => round($skill['atkmod'] * $subBonus, 2),
                'defmod' => round($skill['defmod'] * $subBonus, 2),
                'badguyatkmod' => $skill['badguy_atkmod'],
                'lifetap' => $skill['lifetap'],
                'minioncount' => 1,
                'minbadguydamage' => $skill['min_dmg'] + $level,
                'maxbadguydamage' => $skill['max_dmg'] + ($level * 2),
                'roundmsg' => $skill['roundmsg'],
                'effectmsg' => $skill['effectmsg'],
                'schema' => 'module-dccclasses',
            ]);
            break;

        case 'charstats':
            if ($raceId !== '' || $classId !== '') {
                $charges = (int) get_module_pref('skill_charges', 'dccclasses');
                $maxCharges = (int) get_module_pref('max_charges', 'dccclasses');
                addcharstat('Crawler Class HUD');
                addcharstat('Crawler Race', $races[$raceId]['color_name'] ?? '`7Unassigned`0');
                addcharstat('Floor 3 Class', $classes[$classId]['color_name'] ?? '`7Unassigned`0');
                if ($subId !== '' && isset($subclasses[$subId])) {
                    addcharstat('Floor 6 Spec', $subclasses[$subId]['color_name']);
                }
                addcharstat('Skill Charges', sprintf('`^%d`0/`@%d`0', $charges, $maxCharges));
            }
            break;

        case 'bioinfo':
            $targetId = (int) ($args['acctid'] ?? 0);
            if ($targetId > 0) {
                $tRace = (string) get_module_pref('crawler_race', 'dccclasses', $targetId);
                $tClass = (string) get_module_pref('crawler_class', 'dccclasses', $targetId);
                $tSub = (string) get_module_pref('crawler_subclass', 'dccclasses', $targetId);
                if (isset($races[$tRace]) || isset($classes[$tClass])) {
                    output(
                        "`n`4Third-Floor Build:`0 %s — %s%s`n",
                        $races[$tRace]['color_name'] ?? '`7Earth Native`0',
                        $classes[$tClass]['color_name'] ?? '`7Unclassed Crawler`0',
                        isset($subclasses[$tSub]) ? ' (' . $subclasses[$tSub]['color_name'] . ')' : ''
                    );
                }
            }
            break;
    }

    return $args;
}

/**
 * Awards the canonical Silver Earth Box via `systemai` if the player selects an Earth Race/Class.
 */
function dccclasses_grant_earth_incentive(string $reason): void
{
    if ((int) get_module_pref('earth_box_claimed', 'dccclasses') === 1) {
        return;
    }
    set_module_pref('earth_box_claimed', 1, 'dccclasses');

    if (function_exists('is_module_active') && is_module_active('systemai')) {
        $silver = (int) get_module_pref('box_silver', 'systemai');
        set_module_pref('box_silver', $silver + 1, 'systemai');
        output("`n`5[SYSTEM AI EARTH INCENTIVE]: `3\"As an incentive for choosing an Earth heritage (%s), a `7Silver Earth Box`3 has been deposited into your Safe Room Loot Vault!\"`0`n", $reason);
    } else {
        global $session;
        $session['user']['gems'] += 3;
        output("`n`5[SYSTEM AI EARTH INCENTIVE]: `3\"As an incentive for choosing an Earth heritage (%s), you receive `5+3 Sponsor Tokens`3!\"`0`n", $reason);
    }
}

function dccclasses_run(): void
{
    global $session;

    page_header('Mordecai\'s Third-Floor Race & Class Guildhall');

    $op = (string) httpget('op');
    $races = dccclasses_get_races();
    $classes = dccclasses_get_classes();
    $subclasses = dccclasses_get_subclasses();

    $dks = (int) ($session['user']['dragonkills'] ?? 0);
    $minFloor3 = (int) get_module_setting('min_dk_for_floor3', 'dccclasses');
    $minFloor6 = (int) get_module_setting('min_dk_for_floor6', 'dccclasses');
    $respecCost = (int) get_module_setting('allow_respec_gems', 'dccclasses');

    $raceId = (string) get_module_pref('crawler_race', 'dccclasses');
    $classId = (string) get_module_pref('crawler_class', 'dccclasses');
    $subId = (string) get_module_pref('crawler_subclass', 'dccclasses');

    output("`c`b`4=== MORDECAI'S THIRD-FLOOR RACE & CLASS GUILDHALL ===`0`b`c`n");

    // ========================================================================
    // SELECTION HANDLERS
    // ========================================================================
    if ($op === 'pick_race' && $dks >= $minFloor3 && $raceId === '') {
        $choice = (string) httpget('id');
        if (isset($races[$choice])) {
            $r = $races[$choice];
            set_module_pref('crawler_race', $choice, 'dccclasses');
            $raceId = $choice;

            // Apply one-time HP & Skill Charge boost immediately
            $session['user']['maxhitpoints'] += $r['hp_bonus'];
            $session['user']['hitpoints'] += $r['hp_bonus'];
            $session['user']['turns'] += $r['turns_bonus'];
            $curCharges = (int) get_module_pref('skill_charges', 'dccclasses') + $r['charges_bonus'];
            $maxCharges = (int) get_module_pref('max_charges', 'dccclasses') + $r['charges_bonus'];
            set_module_pref('skill_charges', $curCharges, 'dccclasses');
            set_module_pref('max_charges', $maxCharges, 'dccclasses');

            output("`@[CELLULAR TRANSFORMATION COMPLETE!]: `2Your DNA restructures into a %s! (`@+%d Max HP, +%d Stamina, +%d Daily Skill Charges`2)`0`n", $r['color_name'], $r['hp_bonus'], $r['turns_bonus'], $r['charges_bonus']);
            if ($choice === 'earth_human') {
                dccclasses_grant_earth_incentive('Earth Human Race');
            }
            output("`n");
        }
    } elseif ($op === 'pick_class' && $dks >= $minFloor3 && $classId === '') {
        $choice = (string) httpget('id');
        if (isset($classes[$choice])) {
            $c = $classes[$choice];
            set_module_pref('crawler_class', $choice, 'dccclasses');
            $classId = $choice;

            output("`@[THIRD-FLOOR CLASS LOCKED IN!]: `2Mordecai stamps your dossier. You are now a %s! Your active combat skills are now available inside the Dungeon corridors!`0`n", $c['color_name']);
            if ($c['is_earth_class']) {
                dccclasses_grant_earth_incentive($c['name']);
            }
            output("`n");
        }
    } elseif ($op === 'pick_sub' && $dks >= $minFloor6 && $classId !== '' && $subId === '') {
        $choice = (string) httpget('id');
        if (isset($subclasses[$choice]) && $subclasses[$choice]['parent_class'] === $classId) {
            $s = $subclasses[$choice];
            set_module_pref('crawler_subclass', $choice, 'dccclasses');
            $subId = $choice;

            $curCharges = (int) get_module_pref('skill_charges', 'dccclasses') + $s['charges_bonus'];
            $maxCharges = (int) get_module_pref('max_charges', 'dccclasses') + $s['charges_bonus'];
            set_module_pref('skill_charges', $curCharges, 'dccclasses');
            set_module_pref('max_charges', $maxCharges, 'dccclasses');

            output("`@[SIXTH-FLOOR SPECIALIZATION UNLOCKED!]: `2You have advanced to %s (`@+%d Daily Skill Charges & +12 percent Skill Potency`2)!`0`n`n", $s['color_name'], $s['charges_bonus']);
        }
    } elseif ($op === 'respec' && $respecCost > 0) {
        if ((int) ($session['user']['gems'] ?? 0) < $respecCost) {
            output("`\$[BORANT RESPEC DENIED]: `4Emergency genetic and class reconstruction costs `5%d Sponsor Tokens`4!`0`n`n", $respecCost);
        } else {
            $session['user']['gems'] -= $respecCost;
            set_module_pref('crawler_race', '', 'dccclasses');
            set_module_pref('crawler_class', '', 'dccclasses');
            set_module_pref('crawler_subclass', '', 'dccclasses');
            $raceId = '';
            $classId = '';
            $subId = '';
            output("`@[EMERGENCY BORANT RECONSTRUCTION]: `2Your Race, Class, and Subclass slots have been wiped clean. Choose carefully this time, Crawler!`0`n`n");
        }
    }

    // ========================================================================
    // GATEKEEPING & SELECTION UI
    // ========================================================================
    if ($dks < $minFloor3) {
        output(
            "`3Game Guide **Mordecai** pours himself a glass of cheap whiskey and points at the Floor Chart. `2\"Slow down, kid. In Dungeon Crawler World, Crawlers don't unlock Race and Class selection until they survive the first two floors and reach the **Third Floor** (`^%d Floors Descended required — you currently have %d`2). Clear the Stairwell Guardians first!\"`0`n`n",
            $minFloor3,
            $dks
        );
    } else {
        output("`3Game Guide **Mordecai** flips open a hovering Syndicate selection menu. `2\"Welcome to the Third Floor milestone, Crawler. Out of 398 races and 40,000 classes in the Syndicate database, the System AI has curated the top high-survival builds for your profile.\"`0`n`n");

        // 1. RACE SELECTION TABLE
        if ($raceId === '') {
            output("`b`^STEP 1: Select Your Permanent Crawler Race:`0`b`n");
            rawoutput('<table border="0" cellpadding="6" cellspacing="1" width="100%" style="background:#0d1117;border:1px solid #30363d;margin-bottom:14px;">');
            rawoutput('<tr style="background:#161b22;color:#00f0ff;font-weight:bold;"><td>Race</td><td>Syndicate Racial Perks</td><td>Action</td></tr>');
            foreach ($races as $rKey => $rInfo) {
                $link = "runmodule.php?module=dccclasses&op=pick_race&id={$rKey}";
                addnav('', $link);
                rawoutput('<tr style="border-bottom:1px solid #21262d;">');
                rawoutput('<td style="color:#ffd700;font-weight:bold;">' . htmlspecialchars($rInfo['name'], ENT_QUOTES, 'UTF-8') . '</td>');
                rawoutput('<td style="color:#c9d1d9;">' . htmlspecialchars($rInfo['desc'], ENT_QUOTES, 'UTF-8') . '</td>');
                rawoutput('<td><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" style="color:#3fb950;font-weight:bold;">[Select Race]</a></td>');
                rawoutput('</tr>');
            }
            rawoutput('</table>');
        }

        // 2. CLASS SELECTION TABLE
        if ($classId === '') {
            output("`b`^STEP 2: Select Your Floor 3 Crawler Class:`0`b`n");
            rawoutput('<table border="0" cellpadding="6" cellspacing="1" width="100%" style="background:#0d1117;border:1px solid #30363d;margin-bottom:14px;">');
            rawoutput('<tr style="background:#161b22;color:#ff2a85;font-weight:bold;"><td>Class</td><td>System AI Description &amp; Active Combat Kit</td><td>Action</td></tr>');
            foreach ($classes as $cKey => $cInfo) {
                $link = "runmodule.php?module=dccclasses&op=pick_class&id={$cKey}";
                addnav('', $link);
                $skillNames = [];
                foreach ($cInfo['skills'] as $sk) {
                    $skillNames[] = $sk['name'] . ' (' . $sk['cost'] . '⚡)';
                }
                rawoutput('<tr style="border-bottom:1px solid #21262d;">');
                rawoutput('<td style="color:#ffd700;font-weight:bold;">' . htmlspecialchars($cInfo['name'], ENT_QUOTES, 'UTF-8') . '</td>');
                rawoutput('<td style="color:#c9d1d9;">' . htmlspecialchars($cInfo['lore'], ENT_QUOTES, 'UTF-8') . '<br><small style="color:#00f0ff;"><strong>Active Skills:</strong> ' . htmlspecialchars(implode(' • ', $skillNames), ENT_QUOTES, 'UTF-8') . '</small></td>');
                rawoutput('<td><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" style="color:#3fb950;font-weight:bold;">[Select Class]</a></td>');
                rawoutput('</tr>');
            }
            rawoutput('</table>');
        }

        // 3. FLOOR 6 SUBCLASS SPECIALIZATION
        if ($classId !== '' && $subId === '') {
            if ($dks < $minFloor6) {
                output("`7• **Sixth-Floor Subclass Specialization:** Locked until you reach `^%d Floors Descended`7 (Current: `^%d`7).`0`n`n", $minFloor6, $dks);
            } else {
                output("`b`5STEP 3: Select Your Sixth-Floor Subclass Specialization:`0`b`n");
                foreach ($subclasses as $sKey => $sInfo) {
                    if ($sInfo['parent_class'] === $classId) {
                        $link = "runmodule.php?module=dccclasses&op=pick_sub&id={$sKey}";
                        addnav(['Specialize: %s', $sInfo['name']], $link);
                        output("`7• %s:`0 %s`n", $sInfo['color_name'], $sInfo['desc']);
                    }
                }
                output("`n");
            }
        }
    }

    // Current Dossier Summary
    if ($raceId !== '' || $classId !== '') {
        output("`b`^Your Active Crawler Build:`0`b`n");
        output("`7• **Crawler Race:**`0 %s`n", $races[$raceId]['color_name'] ?? '`7Unselected`0');
        output("`7• **Floor 3 Class:**`0 %s`n", $classes[$classId]['color_name'] ?? '`7Unselected`0');
        output("`7• **Floor 6 Subclass:**`0 %s`n", $subclasses[$subId]['color_name'] ?? '`7Locked / Unselected`0');
        output("`7• **Active Combat Charges:**`0 `^%d / %d`0`n`n", (int) get_module_pref('skill_charges', 'dccclasses'), (int) get_module_pref('max_charges', 'dccclasses'));

        if ($respecCost > 0) {
            addnav('Borant Reconstruction');
            addnav(
                sprintf('Emergency Respec (%d Tokens)', $respecCost),
                'runmodule.php?module=dccclasses&op=respec'
            );
        }
    }

    addnav('Return');
    addnav('Return to Safe Room', 'village.php');

    page_footer();
}