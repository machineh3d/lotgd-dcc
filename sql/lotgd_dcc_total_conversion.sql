-- ============================================================================
-- LEGEND OF THE GREEN DRAGON (NB-Core / v1.1.x - v2.0.9+nb)
-- DUNGEON CRAWLER CARL TOTAL CONVERSION SQL PACK
-- Target Repository: https://github.com/machineh3d/lotgd-dcc
-- Engine Base: https://github.com/NB-Core/lotgd
-- Lore Reference: https://dungeon-crawler-carl.fandom.com/wiki/Dungeon_Crawler_Carl_Wiki
-- ============================================================================
-- USAGE INSTRUCTIONS:
-- 1. Back up your existing MariaDB / MySQL database before executing:
--    docker exec -i lotgd-db mysqldump -u YOUR_DB_USER -pYOUR_DB_PASSWORD YOUR_DB_NAME > lotgd_pre_dcc_backup.sql
-- 2. Execute against your database container:
--    docker exec -i lotgd-db mysql -u YOUR_DB_USER -pYOUR_DB_PASSWORD YOUR_DB_NAME < /path/to/lotgd-dcc/sql/lotgd_dcc_total_conversion.sql
-- 3. Clear your LotGD `datacache/` directory (or flush via the Superuser Grotto)
--    so cached translations, settings, and shop inventories reload immediately.
-- ============================================================================

SET NAMES utf8mb4;
START TRANSACTION;

-- ============================================================================
-- 1. CORE SERVER SETTINGS (Village, NPCs, Shops, and Broadcast Branding)
-- ============================================================================
INSERT INTO `settings` (`setting`, `value`) VALUES
('serverdesc', 'Dungeon Crawler World: Earth - Produced by the Borant Corporation'),
('villagename', 'Bopca Safe Room Hub'),
('innname', 'The Desperado Club'),
('barkeep', 'Bopca Protector Staffing Bot'),
('barmaid', 'Juice Box'),
('bard', 'Princess Donut on the Karaoke Stage'),
('deathoverlord', 'The System AI'),
('ToggleRamius', '1'),
('moneydecimal', '0'),
('enabletranslation', '1'),
('defaultlanguage', 'en')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ============================================================================
-- 2. CORE TRANSLATION OVERRIDES (`translations` table, language = 'en')
-- Replaces medieval terminology with Dungeon Crawler World HUD strings
-- without modifying core NB-Core PHP files.
-- ============================================================================
DELETE FROM `translations`
WHERE `language` = 'en'
  AND `author` = 'System AI (DCC Overhaul)';

INSERT INTO `translations` (`language`, `uri`, `intext`, `outtext`, `author`, `version`) VALUES
-- Global / Common HUD & Stat Labels
('en', 'common', 'Gold', 'Gold Credits', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Gems', 'Sponsor Tokens', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Turns', 'Floor Stamina', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Forest Fights', 'Floor Encounters', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Dragon Kills', 'Floors Descended', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Dragon Kill', 'Floor Descent', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Village', 'Safe Room', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Forest', 'Dungeon Floor', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Clan', 'Crawler Party', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Clans', 'Crawler Parties', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Hitpoints', 'Crawler HP', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Charm', 'Viewer Ratings', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Spirits', 'AI Favor', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Mount', 'Pet Companion', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Superuser Grotto', 'Borant Showrunner Console', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Daily News', 'Recap Broadcast', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'List Warriors', 'Active Crawlers', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'common', 'Hall of Fame', 'Top 10 Crawler Leaderboard', 'System AI (DCC Overhaul)', '2.0.9+nb'),

-- Village / Safe Room Navigation (`village` & `nav` schemas, exact X? hotkey prefixes)
('en', 'village', 'City Gates', 'Dungeon Access', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'Blades Boulevard', 'Tactical & Guild Quarter', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'Market Street', 'Bopca Trade Concourse', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'Tavern Street', 'Safe Room Lounge', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'Info', 'Broadcast Feed', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'F?Forest', 'F?Dungeon Corridors', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'S?Slay Other Players', 'S?Crawler PvP Arena', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'Mercenary Camp', 'Syndicate Mercenary Guild', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'u?Bluspring''s Warrior Training', 'u?Mordecai''s Game Guide Guildhall', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'J?JCP''s Hunter Lodge', 'J?Showrunner Patronage Lodge', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'W?MightyE''s Weaponry', 'W?Bopca Munitions & Explosives', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'A?Pegasus Armor', 'A?Safe Room Tactical Outfitter', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'B?Ye Olde Bank', 'B?Orren''s Syndicate Credit Vault', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'Z?Ze Gypsy Tent', 'Z?Zev''s Sponsor & PR Booth', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'E?Eye-catching Pavilion', 'E?Showrunner Bonus Pavilion', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'M?Merick''s Stables`0', 'M?Enhanced Pet Biscuit Menagerie`0', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'G?The Gardens', 'G?Club Vanquisher Sanctuary', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'R?Curious Looking Rock', 'R?System AI Maintenance Terminal', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'C?Clan Halls', 'C?Faction Wars Command', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'N?Daily News', 'N?Recap Broadcast', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'L?List Warriors', 'L?Active Crawlers', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'village', 'o?Hall o'' Fame', 'o?Top 10 Crawler Leaderboard', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'nav', 'X?`bSuperuser Grotto`b', 'X?`bBorant Showrunner Console`b', 'System AI (DCC Overhaul)', '2.0.9+nb'),

-- Forest / Dungeon Floor (`forest.php`)
('en', 'forest.php', 'The Forest', 'The World Dungeon Corridors', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'forest.php', 'Look for Something to Kill', 'Patrol the Corridor for Mobs', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'forest.php', 'Slum', 'Clear Low-Level Trash Mobs (Slum)', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'forest.php', 'Thrillseeking', 'Hunt High-Level Elite Mobs (Thrillseeking)', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'forest.php', 'Seek out the Green Dragon', 'Locate the Floor Boss & Stairwell', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'forest.php', 'Healer''s Hut', 'Safe Room Emergency Restroom & Med-Bay', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'forest.php', 'Return to the Village', 'Retreat to the Bopca Safe Room', 'System AI (DCC Overhaul)', '2.0.9+nb'),

-- Dragon / Floor Boss & Stairwell (`dragon.php`)
('en', 'dragon.php', 'The Green Dragon', 'The Scolopendra Floor Guardian', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'dragon.php', 'You have slain the Green Dragon!', 'NEW ACHIEVEMENT! You have obliterated the Floor Guardian and unlocked the Stairwell!', 'System AI (DCC Overhaul)', '2.0.9+nb'),

-- Graveyard / Borant Reconstruction Bay (`graveyard.php`)
('en', 'graveyard.php', 'The Graveyard', 'Borant Corp Medical Reconstruction Bay', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'graveyard.php', 'Torment', 'Film Humiliating Sponsor Promos for AI Favor', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'graveyard.php', 'Mausoleum', 'Consult the System AI Oversight Terminal', 'System AI (DCC Overhaul)', '2.0.9+nb'),
('en', 'graveyard.php', 'Restore Your Soul', 'Sign Borant Respawn Contract (100 AI Favor)', 'System AI (DCC Overhaul)', '2.0.9+nb'),

-- New Day / Syndicate Broadcast (`newday.php`)
('en', 'newday.php', 'It is a new day!', 'WELCOME, CRAWLERS! A new broadcast cycle in the World Dungeon has begun!', 'System AI (DCC Overhaul)', '2.0.9+nb');

-- ============================================================================
-- 3. WEAPONS CATALOG (`weapons` table, Levels 1-15 + High-Floor Tier)
-- Replaces standard medieval weapons with canonical Dungeon Crawler Carl arms.
-- Standard LotGD gold values preserved for balanced economy progression.
-- ============================================================================
DELETE FROM `weapons`;

INSERT INTO `weapons` (`weaponid`, `weaponname`, `value`, `damage`, `level`) VALUES
-- Base Tier (Available from Floor 0 / First Crawl)
(1,  'Improvised Folding Chair of the Retirement Home',            48,    1,  0),
(2,  'Toad Cudgel of the First Floor',                            225,    2,  0),
(3,  'Left-Hand Common Fingerless Brawler Glove',                 585,    3,  0),
(4,  'Bates Zip-Up Tactical Stomping Boots (Barefoot Optional)',  990,    4,  0),
(5,  'Spunky Jefferson the Enchanted Nickel Sock',               1575,    5,  0),
(6,  'Enchanted Shuriken of Bloodlust',                          2250,    6,  0),
(7,  'Hobgoblin Hoblobber Impact Detonator',                     2790,    7,  0),
(8,  'Enchanted Shade Gnoll Telescoping Riot Baton',             3420,    8,  0),
(9,  'Velma the Goblin-Rigged Flamethrower',                     4230,    9,  0),
(10, 'Enchanted Fang Caps of the Expectorating Tizheruk',        5040,   10,  0),
(11, 'Swordsman Guard Colossal Broadsword',                      5850,   11,  0),
(12, 'Carl''s Chistera Basket with Hoblobber Fuse Rod',          6840,   12,  0),
(13, 'Enchanted Repeating Crossbow of the Scavenger Mother',     8010,   13,  0),
(14, 'Enchanted War Gauntlet of the Exalted Grull',              9000,   14,  0),
(15, 'Carl''s Jug O'' Boom (Goblin Dynamite Special)',          10350,   15,  0),

-- Veteran Tier (Unlocked after 1+ Floor Descents / `level` = 1)
(16, 'Dirty Little Phase Bolt Launcher',                          990,    4,  1),
(17, 'Katia''s Piston-Driven Mass-Shifting Battering Ram',       2790,    7,  1),
(18, 'Princess Donut''s Magic Missile Tiara Array',              5040,   10,  1),
(19, 'Bolt of Ophiotaurus God-Slayer Crossbow',                  8010,   13,  1),
(20, 'Carl''s Doomsday Scenario (Unstable Soul Crystal Bomb)',  10350,   15,  1);

-- ============================================================================
-- 4. ARMOR CATALOG (`armor` table — singular in LotGD, Levels 1-15 + Veteran Tier)
-- Replaces standard medieval armor with canonical Dungeon Crawler Carl gear.
-- Standard LotGD gold values preserved for balanced economy progression.
-- ============================================================================
DELETE FROM `armor`;

INSERT INTO `armor` (`armorid`, `armorname`, `value`, `defense`, `level`) VALUES
-- Base Tier (Available from Floor 0 / First Crawl)
(1,  'Subzero Seattle Crocs and Unpadded Leather Jacket',          48,    1,  0),
(2,  'Heart-Patterned Boxer Shorts of Frostbite Survival',        225,    2,  0),
(3,  'Pair of Quiet Slippers & Meadow Lark Bathrobe',             585,    3,  0),
(4,  'Danger Dingo Pelt Poncho',                                  990,    4,  0),
(5,  '201st Unit Surplus Tactical Vest',                         1575,    5,  0),
(6,  'Enchanted BigBoi Boxers',                                  2250,    6,  0),
(7,  'Toe Ring of the Splatter Skunk & Pedicure Polish',         2790,    7,  0),
(8,  'Enchanted Kneepads of the Squishy Target',                 3420,    8,  0),
(9,  'Enchanted Collar Charm of the Effete Bourgeoisie',         4230,    9,  0),
(10, 'Swordsman Guard Reinforced Breastplate',                   5040,   10,  0),
(11, 'Cloak of the Benevolent Champion (Bomb Upgrade Patch)',    5850,   11,  0),
(12, 'Enchanted Night Wyrm''s Ring of Divine Suffering',         6840,   12,  0),
(13, 'Battle Rattle Harness of the Iron Tangle',                 8010,   13,  0),
(14, 'Enchanted Crown of the Sepsis Whore',                      9000,   14,  0),
(15, 'Anarchist''s Battle Vest with Scavenger''s Daughter Patch', 10350, 15,  0),

-- Veteran Tier (Unlocked after 1+ Floor Descents / `level` = 1)
(16, 'Crupper of the Benevolent Champion',                        990,    4,  1),
(17, 'Feral Gods Protective Shell Ward Tattoo',                  2790,    7,  1),
(18, 'Butcher''s Masquerade Thorn-Proof Gala Attire',            5040,   10,  1),
(19, 'Faction Wars Posse Warlord Plate',                         8010,   13,  1),
(20, 'Celestial Sponsor Barrier of the Royal Court',            10350,   15,  1);

-- ============================================================================
-- 5. TRAINING MASTERS (`masters` table, Levels 1-15)
-- Mordecai's Morphs & Dungeon Guides for level-up challenges in `train.php`.
-- ============================================================================
DELETE FROM `masters`;

INSERT INTO `masters` (`creatureid`, `creaturename`, `creaturelevel`, `creatureweapon`, `creaturelose`, `creaturewin`, `creaturegold`, `creatureexp`, `creaturehealth`, `creatureattack`, `creaturedefense`) VALUES
(1,  'Mordecai (Tutorial Rat-Kin Form)',        1,  'Clipped Syllabus & Empty Flask',       'Kid, you actually read the prompt! Go take your level.',                            'Did you even open your HUD menu?! Drink a potion and try again!',                0, 0,  11,  2,  2),
(2,  'Bopca Safety Orientation Drone',          2,  'Compliance Taser',                     'Orientation passed. Please do not assault Bopca union staff.',                      'Orientation failed! Have you considered taking a voluntary exit deal?',           0, 0,  22,  4,  4),
(3,  'Mordecai (Irritated Bugbear Form)',       3,  'Half-Empty Bottle of Rev-Up',          'Not bad. Keep your head down before the third floor race selection.',               'You telegraphed every swing! The Krakaren is going to eat you alive.',           0, 0,  33,  6,  6),
(4,  'Tiatha the Blitz-Stick Manager',          4,  'Sparking Blitz Stick',                 'Whoa, okay, you win! Let me get back to my nap.',                                   'Even strung out on three Blitz Sticks, I saw that punch coming!',                0, 0,  44,  8,  8),
(5,  'Zev (Borant PR Media Coach)',             5,  'Syndicate Censorship Wand',            'Great camera angle on that finisher! The chat loves it!',                           'Terrible ratings! Nobody sponsors a crawler with footwork that sloppy!',         0, 0,  55, 10, 10),
(6,  'Mordecai (Incubus Tactician Form)',       6,  'Alchemical Flash Vial',                'Solid execution. You might actually survive the Iron Tangle.',                      'Stop relying on raw stats and pay attention to the mechanics!',                  0, 0,  66, 12, 12),
(7,  'Signet the Illusionist Duelist',          7,  'Animated Ink Whip',                    'Your resolve cuts deeper than scripted memories. Advance, Crawler.',                'You are fighting shadows while the showrunners laugh at us both.',               0, 0,  77, 14, 14),
(8,  'Odette (Retired Crab-Suit Veteran)',      8,  'Talk Show Holoprojector Beam',         'Magnificent! Wait until we replay that combo on After Hours!',                      'Darling, if you fight like that on camera, you won''t make it to Floor Six.',    0, 0,  88, 16, 16),
(9,  'Chaco the Game Guide Instructor',         9,  'Weighted Guild Cane',                  'Class dismissed. You''ve earned your next stat allocation.',                        'Back to the practice mats, rookie. The Hunters don''t pull punches.',            0, 0,  99, 18, 18),
(10, 'Mordecai (Skyfowl Alchemist Form)',      10,  'Volatile Brimstone Shaker',            'Alright, I admit it—you''re ready for the Butcher''s Masquerade.',                  'You walked right into the splash zone! Watch the floor telegraphs!',             0, 0, 110, 20, 20),
(11, 'Milk the Calligraphy Sage',              11,  'Runed Ink Brush of the Posse',         'Take this lesson with you to the deeper floors, friend.',                           'Your form lacks focus. Breathe before the stairwell opens.',                     0, 0, 121, 22, 22),
(12, 'Justice Light (Former Crawler Echo)',    12,  'Ancient Trap-Maker''s Pendulum',       'Good. Break their rules, break their traps, and keep descending.',                  'A trap only works when you fail to look up. Look up, Crawler.',                  0, 0, 132, 24, 24),
(13, 'Rosetta (Anarchist Mentor)',             13,  'Concealed Demolition Detonator',       'You read between the lines of the Cookbook well. Move along.',                       'Too loud, too predictable. The Syndicate expects that move.',                    0, 0, 143, 26, 26),
(14, 'Orren the Syndicate Liaison Officer',    14,  'Subspace Gravity Gavel',               'By Syndicate regulation 409-B, your advancement is certified.',                     'Request denied. Your combat telemetry does not meet Floor 15 thresholds.',       0, 0, 154, 28, 28),
(15, 'The System AI (Holographic Foot Avatar)',15,  'Unfiltered Achievement Notification',  'Ooooh, look at you go! Now march down to the Stairwell and step on something!',     'NEW ACHIEVEMENT! Total Humiliation! You just got bodied by a customer support AI!',0, 0, 165, 30, 30);

-- ============================================================================
-- 6. DUNGEON CREATURES (`creatures` table, Levels 1-15 + Graveyard Remnants)
-- 45 Floor Mobs (`forest = 1, graveyard = 0`) + 6 Graveyard Tasks (`graveyard = 1`)
-- Scaled to standard LotGD HP, Attack, Defense, EXP, and Gold curves.
-- ============================================================================
DELETE FROM `creatures`;

INSERT INTO `creatures` (`creaturename`, `creaturelevel`, `creatureweapon`, `creaturelose`, `creaturewin`, `creaturegold`, `creatureexp`, `creaturehealth`, `creatureattack`, `creaturedefense`, `createdby`, `forest`, `graveyard`) VALUES
-- LEVEL 1 (Floor 1 Entry Corridors)
('Scavenger Goblin Scout',                       1, 'Rusty Pipe Shiv',                         'The goblin squeals as your foot crushes its weapon—and the System AI purrs approvingly.',          'The level-1 goblin shanked you in the hallway outside the tutorial door!',              36,  10,  10,  1,  1, 'System AI', 1, 0),
('Meth-Head Bad Llama',                          1, 'Acidic Green Spit & Yellow Teeth',        'The Bad Llama coughs up a baggie of questionable crystals and collapses.',                         'You just got spit-roasted by a twitchy alpaca in a subterranean parking garage!',       38,  11,  11,  1,  1, 'System AI', 1, 0),
('Laminac Fairy Pest',                           1, 'Tiny Sparking Wand',                      'You swat the winged pest out of the air and pocket its glittery wings.',                           'Death by six-inch fairy! The galactic chat is spamming laughing emojis!',               36,  10,  10,  1,  1, 'System AI', 1, 0),

-- LEVEL 2 (Floor 1 Sub-Bosses & Trash Packs)
('Goblin Chieftain on a Chopper',                2, 'Sawed-Off Scrap Crossbow',                'The chopper skids into a wall, sending goblin parts and spare tires flying!',                      'Run over by a motorized goblin tricycle on the very first floor!',                      97,  24,  21,  3,  3, 'System AI', 1, 0),
('Feral Danger Dingo',                           2, 'Rabid Ankle-Biting Jaws',                 'The Danger Dingo whimpers and drops a prime pelt at your feet.',                                   'A pack of mangy dungeon dingoes just turned you into Kibble!',                          99,  24,  22,  3,  3, 'System AI', 1, 0),
('Rotting Zombie Rotweiler',                     2, 'Exposed Ribcage Tackle',                  'You smash the undead hound back into the concrete floor tiles.',                                   'Chewed up before you even found a decent pair of pants!',                               95,  23,  21,  3,  3, 'System AI', 1, 0),

-- LEVEL 3 (Floor 1 Borough Bosses)
('Goblin Murder Dozer',                          3, 'Spinning Flail Drum & Steam Boiler',      'You jam the intake valve, blowing the Murder Dozer''s boiler sky-high!',                           'Flattened like roadkill under a goblin construction vehicle!',                         148,  34,  33,  5,  4, 'System AI', 1, 0),
('The Juicer (Roided-Out Bugbear Boss)',         3, '100-Pound Cast Iron Dumbbell',            'The Juicer drops his protein shaker, flexes one last time, and keels over!',                       'The Juicer just curled your spine like a warm-up rep, bro!',                           150,  35,  33,  5,  4, 'System AI', 1, 0),
('The Hoarder (Neighborhood Boss)',              3, 'Avalanche of Mouldy Trash & Cats',        'The Hoarder''s mountain of junk collapses inward as you claim the Borough Field Guide.',           'Buried alive under thirty years of expired coupons and feral cats!',                   146,  34,  32,  5,  4, 'System AI', 1, 0),

-- LEVEL 4 (Floor 2: Desperado & Borough Streets)
('Clurichaun Drunken Leprechaun',                4, 'Jagged Bottle of Rev-Up Moonshine',       'The Clurichaun hiccups, drops his moonshine stash, and vanishes in green smoke.',                  'Beaten senseless by a belligerent two-foot drunkard!',                                 162,  45,  43,  7,  6, 'System AI', 1, 0),
('Krakaren MLM Sales Clone',                     4, 'Weaponized Essential Oil Diffuser',       'You smash her starter kit before she can recruit you into her downline!',                          'You signed up for the Krakaren''s pyramid scheme and paid with your life!',            165,  45,  44,  7,  6, 'System AI', 1, 0),
('Slime Imp Swarm',                              4, 'Corrosive Mucus Globules',                'You torch the gelatinous swarm before it can breach the barricade.',                               'Dissolved into goo by a swarm of giggling slime imps!',                                160,  44,  43,  7,  6, 'System AI', 1, 0),

-- LEVEL 5 (Floor 2: Elite Threats & Bosses)
('The Ball of Swine',                            5, 'Rolling Mass of Squealing Crushing Hogs', 'Your portable redoubt holds firm as the Ball of Swine breaks apart in squealing chaos!',           'Rolled into pork sausage by a twenty-foot sphere of angry pigs!',                      198,  55,  55,  9,  7, 'System AI', 1, 0),
('Player-Killer Frank Q',                        5, 'Bloodied Skull-Marked Cleaver',           'Frank Q drops his weapon and bolts down the corridor as his PK skull cracks!',                     'Ganked in an alley by a desperate crawler hunting viewer bounties!',                   200,  56,  54,  9,  7, 'System AI', 1, 0),
('Summoned Rage Elemental',                      5, 'Level-99 Hallway Sanitization Nova',      'You bait the Rage Elemental into a stairwell portal loop and survive the blast!',                  'Vaporized instantly because somebody broke Borant''s hallway urination bylaw!',        196,  55,  53,  9,  7, 'System AI', 1, 0),

-- LEVEL 6 (Floor 3: Overworld Ruins & Skyfowl Towns)
('Shade Gnoll Riot Trooper',                     6, 'Electrified Crowd-Control Shield',        'You shatter the gnoll''s riot visor and loot its telescoping baton.',                              'Clubbed into submission by the third floor''s corrupt town guard!',                    234,  66,  65, 11,  8, 'System AI', 1, 0),
('Mold Lion Stalker',                            6, 'Spore-Choking Pounce',                    'You carve out the Mold Lion''s heart before its fungal spores can take root.',                     'Choked out by sentient basement mildew shaped like a cougar!',                         236,  66,  66, 11,  8, 'System AI', 1, 0),
('Gorgon Circus Enforcer',                       6, 'Petrifying Gaze & Weighted Whip',         'You use the polished reflection of your shield to blind the Gorgon and strike!',                   'Turned into a decorative lawn statue outside the Big Top tent!',                       232,  65,  64, 11,  8, 'System AI', 1, 0),

-- LEVEL 7 (Floor 3: Circus & Soul Crystal Crisis)
('Grull''s Fanatical Maestro Cultist',           7, 'Tusked Pig-Iron Halberd',                 'The Skull Empire zealot collapses while cursing Princess Donut''s name!',                          'Sacrificed on live intergalactic television to appease the Maestro!',                  268,  77,  76, 13, 10, 'System AI', 1, 0),
('Corrupted Skyfowl Town Mayor',                 7, 'Overcharged Soul Crystal Scepter',        'You yank the destabilized Soul Crystal into your inventory before the town blows!',                'Reduced to ash by an overloaded third-floor ritual spell!',                            272,  78,  77, 13, 10, 'System AI', 1, 0),
('Hekla''s Renegade Valkyrie Sniper',            7, 'Poison-Tipped Repeating Crossbow',        'You close the gap and disarm the treacherous crawler before she can reload.',                      'Pinned to the floorboards by an ambush from a rival crawler party!',                   265,  76,  75, 13, 10, 'System AI', 1, 0),

-- LEVEL 8 (Floor 4: The Iron Tangle Subway System)
('Iron Tangle Mantaur Conductor',                8, 'Runaway Locomotive Cowcatcher',           'You derail the Mantaur''s handcar and send him tumbling off the elevated tracks!',                 'Punched through a subway windshield by a half-man, half-centaur musclehead!',          302,  89,  87, 15, 11, 'System AI', 1, 0),
('Abyssal Train Ghoul',                          8, 'Torn Subway Turnstile Bar',               'You kick the screeching ghoul off the roof of the moving train car!',                              'Dragged off the platform into the endless abyss beneath the tracks!',                  300,  88,  86, 15, 11, 'System AI', 1, 0),
('Dwarven Automaton Track-Welder',               8, 'Overheated Plasma Rivet Gun',             'You rip the Dwarven Battery pack out of its chassis, shutting the machine down!',                  'Spot-welded to the third rail by a malfunctioning maintenance bot!',                   305,  90,  88, 15, 11, 'System AI', 1, 0),

-- LEVEL 9 (Floor 4: Colored Line Terminus Bosses)
('The Nightmare Express Engine',                 9, 'Hellfire Cowcatcher & Screaming Steam',   'You plant a case of Hobgoblin Dynamite on the coupling and blow the engine off the bridge!',       'Run down at 120 miles per hour on the Named Track transfer line!',                     336, 101,  98, 17, 13, 'System AI', 1, 0),
('Kraken-Spawn Tunnel Horror',                   9, 'Subway-Crushing Tentacles',               'You sever the tentacle blocking the portal gate and dive through to safety!',                      'Pulled into the flooded transfer station by a primordial tentacle beast!',             339, 102,  99, 17, 13, 'System AI', 1, 0),
('Mimic Vending Machine of Station 44',          9, 'Spring-Loaded Coin Slot Jaws',            'You wedge a gangway chock into its hinged mouth and loot the coin hopper!',                        'Eaten alive because you tried to buy a discounted Snickers bar!',                      334, 100,  97, 17, 13, 'System AI', 1, 0),

-- LEVEL 10 (Floor 5: The Gate of the Feral Gods Bubbles)
('Feral Bad-Dog Quadrant Alpha',                10, 'Pack-Tactics Bite & Collar Spikes',       'You shatter the quadrant barrier and put down the mutated pack leader!',                           'Mauled in the canine quadrant while the cat fancy cheered from the stands!',           369, 114, 109, 19, 14, 'System AI', 1, 0),
('Submerged Quadrant Leviathan',                10, 'Pressurized Depth-Charge Maw',            'You detonate a depth charge inside its gills and drain the quadrant bubble!',                      'Drowned in a flooded dome five miles beneath the Earth''s crust!',                     372, 115, 110, 19, 14, 'System AI', 1, 0),
('Ferdinand the Brainwashed Persian Show Cat',  10, 'Feral Hiss & Enchanted Fedora Scratch',   'You knock Ferdinand''s fedora off, breaking his aggro long enough to secure the gate!',            'Out-scratched by Princess Donut''s deadbeat ex-boyfriend in a fancy hat!',             366, 113, 108, 19, 14, 'System AI', 1, 0),

-- LEVEL 11 (Floor 6: The Hunting Grounds)
('Vrah the Mantid Sixth-Floor Hunter',          11, 'Cloaked Scythe-Blades & Plasma Caster',   'You strip Vrah''s active camo with a flare and shatter her chitinous carapace!',                   'Torn apart by an invisible alien tourist paying to hunt Earth crawlers!',              402, 127, 120, 21, 15, 'System AI', 1, 0),
('Kua-Tin Mercenary Trophy Stalker',            11, 'High-Velocity Gauss Harpoon',             'You reverse the hunter''s snare trap and claim his entire safari war chest!',                      'Mounted as a taxidermy trophy in a Kua-Tin executive''s den!',                         405, 128, 121, 21, 15, 'System AI', 1, 0),
('Orcish Skull-Empire Bloodhound Handler',      11, 'Shock-Leash & Armor-Piercing Slugs',      'You turn the hunter''s own war-hounds against him and loot his bounty tokens!',                    'Run down by Sixth-Floor safari hounds outside the Desperado entrance!',                400, 126, 119, 21, 15, 'System AI', 1, 0),

-- LEVEL 12 (Floor 6: The Butcher's Masquerade)
('Butcher''s Masquerade Vine-Golem',            12, 'Strangling Blood-Thorn Whip',             'You torch the overgrown ballroom vines with Velma the Flamethrower!',                              'Strangled by sentient topiary in the middle of the Masquerade gala!',                  435, 141, 131, 23, 17, 'System AI', 1, 0),
('Elite Hunter War-Party Captain',              12, 'Orbital-Drop Power Armor & Vibro-Axe',    'You crack his power-core shielding as the truce timer hits zero!',                                 'Cut down the second the Masquerade peace seal shattered!',                             438, 142, 132, 23, 17, 'System AI', 1, 0),
('Possessed Flesh-Golem Butler',                12, 'Silver Serving Tray Guillotine',          'You decapitate the stitched abomination before it can serve the main course!',                     'Carved up like roast beef at Signet''s haunted dinner party!',                         432, 140, 130, 23, 17, 'System AI', 1, 0),

-- LEVEL 13 (Floor 8: The Eye of the Bedlam Bride & Card Battles)
('Shi Maria the Bedlam Bride',                  13, 'Eight-Eyed Psychic Web & Venom Fang',     'You resist her psychic possession and slam the T''Ghee containment card shut!',                    'Your mind was hijacked by an insane spider-deity on Floor Eight!',                     467, 156, 142, 25, 18, 'System AI', 1, 0),
('Corrupted Deck-Master Totem',                 13, 'Summoned Five-Card Combo Barrage',        'You rip the enemy Deck-Master''s shiny foil card in half!',                                        'Wiped out by an illegal five-card combo before your deck cooldown reset!',             470, 157, 143, 25, 18, 'System AI', 1, 0),
('Memory-Ghost of Past Earth Trauma',           13, 'Weaponized Guilt & Phantom Chains',       'You refuse to break before the cameras and shatter the fabricated phantom!',                       'Broken by the eighth floor''s psychological warfare traps!',                           464, 155, 141, 25, 18, 'System AI', 1, 0),

-- LEVEL 14 (Floor 9: Faction Wars - Larracos)
('Lemig Spliced War-Mage',                      14, 'Artillery Spellbook & Trench Mortar',     'You blow up the Lemig''s forward bunker and capture their Faction Wars banner!',                   'Shelled into craters during the siege of Larracos!',                                   499, 172, 153, 27, 20, 'System AI', 1, 0),
('Blood Sultanate Siege Behemoth',              14, 'Twin-Linked Siege Ballista',              'You climb the behemoth''s tusk and drop a Hoblobber straight down its throat!',                    'Trampled under the Blood Sultanate''s armored war-elephants on Floor Nine!',           502, 173, 154, 27, 20, 'System AI', 1, 0),
('Dream-Elf Operative of the Prism',            14, 'Phase-Shifting Monofilament Saber',       'You predict the operative''s blink-step and counter with a point-blank blast!',                    'Assassinated in the Faction Wars trenches by a sponsored mercenary!',                  496, 171, 152, 27, 20, 'System AI', 1, 0),

-- LEVEL 15 (Floor 12 & Deep Scolopendra Ascendancy Threats)
('Sponsored Avatar of Emberus',                 15, 'Solar Flare Smite & Molten Greatsword',   'You strip the deity''s sponsor immunity with a god-slayer bolt and extinguish the flame!',         'Smited from orbit by an angry sponsored god of the Celestial Ascendancy!',             531, 189, 165, 29, 21, 'System AI', 1, 0),
('Krakaren Prime Arena Abomination',            15, 'Thousand-Tentacle Psychic Tsunami',       'You detonate your entire explosive stockpile, blasting the apex horror back into the pit!',        'Consumed by the apex Hive-Mother at the bottom of the volcano world!',                 535, 190, 165, 29, 21, 'System AI', 1, 0),
('Awakened Scolopendra Nymph',                  15, 'Nine-Tier Cataclysm Poison Cloud',        'You survive the toxic shockwave and clear the final barrier to the Stairwell!',                    'Struck down by Scolopendra''s nine-tier venom inches from the Stairwell!',             528, 188, 164, 29, 21, 'System AI', 1, 0),

-- GRAVEYARD / BORANT RECONSTRUCTION BAY TASKS (`forest = 0, graveyard = 1`)
('Unpaid Borant Ad-Read Teleprompter',           1, 'Cringeworthy Sponsor Script',             'You read the Rev-Up Moonshine ad with enough sarcasm that the AI grants you Favor!',               'You flubbed your lines and got shocked by the stage manager!',                          0,  10,  15,  2,  2, 'System AI', 0, 1),
('Sadistic Death-Watch Game Show Wheel',         3, 'Spinning Blades & Studio Audience Laughs','You dodge the buzzsaws while the trillion-viewer audience cheers your hustle!',                    'Sliced in half on live galactic daytime television!',                                   0,  15,  30,  5,  5, 'System AI', 0, 1),
('Syndicate Legal Compliance Auditor',           5, 'Subpoena of Clause 44-C',                 'You cite a loophole from the Dungeon Anarchist''s Cookbook and stun the bureaucrat!',              'Buried in 40,000 pages of Borant corporate liability waivers!',                         0,  20,  50,  8,  8, 'System AI', 0, 1),
('Holographic Foot-Fetish Pedicure Drone',       7, 'Aggressively Buffing Pumice Stone',       'You let the AI''s creepy drone photograph your feet, earning maximum AI Favor!',                   'You refused to take your boots off and the System AI docked your Favor!',               0,  25,  70, 11, 11, 'System AI', 0, 1),
('Recycled Season-42 NPC Husk',                 10, 'Glitched Memory Loop Blade',              'You put the malfunctioning actor out of its misery as the showrunners cut the feed.',              'Overwhelmed by a weeping NPC who suddenly remembered its real family!',                 0,  30, 100, 15, 15, 'System AI', 0, 1),
('Borant Tax-Evasion Liquidation Bot',          13, 'Repo-Claw & Asset Freeze Ray',            'You short-circuit the repo bot and forge your own Reconstruction Bay release papers!',             'Liquidated as a tax write-off for the Borant Corporation''s quarterly filings!',        0,  40, 130, 20, 20, 'System AI', 0, 1);

COMMIT;
