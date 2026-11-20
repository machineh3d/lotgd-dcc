-- ============================================================================
-- LEGEND OF THE GREEN DRAGON (NB-Core / v1.1.x - v2.0.9+nb)
-- DUNGEON CRAWLER CARL TAUNTS & DEATH MESSAGES SQL PACK
-- Target Repository: https://github.com/machineh3d/lotgd-dcc
-- Engine Base: https://github.com/NB-Core/lotgd
-- Lore Reference: https://dungeon-crawler-carl.fandom.com/wiki/Dungeon_Crawler_Carl_Wiki
-- ============================================================================
-- USAGE INSTRUCTIONS (ON UNRAID HOST 'Chie'):
--   docker exec -i lotgd-db mysql -u YOUR_DB_USER -pYOUR_DB_PASSWORD YOUR_DB_NAME < /path/to/lotgd-dcc/sql/lotgd_dcc_taunts_deaths.sql
--
-- TOKEN REFERENCE:
--   `taunts` tokens:
--     %W = Winner (Mob/Attacker) | %w = Loser (Crawler) | %x = Winner's Weapon
--     %a = Loser's Armor         | %s = he/she/they     | %o = him/her/them
--     %p = his/her/their
--   `deathmessages` tokens:
--     {goodguyname}, {goodguyweapon}, {goodguyarmor},
--     {badguyname}, {badguyweapon}, {where}
-- ============================================================================

SET NAMES utf8mb4;
START TRANSACTION;

-- ============================================================================
-- 1. DUNGEON CRAWLER CARL COMBAT TAUNTS (`taunts` table)
-- ============================================================================
DELETE FROM `taunts`;

INSERT INTO `taunts` (`tauntid`, `taunt`, `editor`) VALUES
(1,  '`5"MONGO IS APPALLED!"`0 Princess Donut types in ALL-CAPS into the global feed as `#%w`0 gets flattened by `@%W`0''s `%x`0!', 'System AI'),
(2,  '`$"GODDAMNIT, DONUT!"`0 `#%w`0 screams right before `@%W`0''s `%x`0 punches straight through %p `%a`0!', 'System AI'),
(3,  '`5[SYSTEM AI]:`0 `3"Ooooh, look at how `#%w`3''s bare feet twitched when `@%W`3 hit %o with `%x`3! Replaying that in 16K slow-motion!"`0', 'System AI'),
(4,  '`^Mordecai`0 throws his whiskey glass at the Safe Room wall: `2"I told `#%w`2 %p `%a`2 wouldn''t hold up against `@%W`2''s `%x`2!"`0', 'System AI'),
(5,  '`5Odette`0 pauses the holoprojector on *After Hours*: `3"And right HERE is where `#%w`3 forgot to dodge `@%W`3''s `%x`3, darlings!"`0', 'System AI'),
(6,  '`@Prepotente`0 lets out an ear-shattering goat scream right in `#%w`0''s ear as `@%W`0 finishes %o off with `%x`0!', 'System AI'),
(7,  '`5"CARL! DO SOMETHING! %w IS DYING AND IT''S RUINING OUR VIEWER RATINGS!"`0 Princess Donut yowls as `@%W`0 swings `%x`0!', 'System AI'),
(8,  '`3PR Agent `^Zev`3 winces from the Borant booth: "Ooh, `#%w`3... the sponsors are NOT going to renew after what `@%W`3 just did with `%x`3."`0', 'System AI'),
(9,  '`4"You will not break me,"`0 `#%w`0 whispers—right before `@%W`0 breaks %p `%a`0 in half with `%x`0!', 'System AI'),
(10, '`5[SYSTEM AI]:`0 `3"NEW ACHIEVEMENT! Darwin Award Nominee! `#%w`3 tried to tank `@%W`3''s `%x`3 with %p face!"`0', 'System AI'),
(11, '`^Nullian Lawyer Quasar`0 puffs his vape pen: `2"Well, `#%w`2, after that beating from `@%W`2, your life-insurance premium just tripled, kid!"`0', 'System AI'),
(12, '`$"HI ZEV!"`0 Princess Donut waves cheerfully at the camera drone while `#%w`0 is dragged across the floor by `@%W`0!', 'System AI'),
(13, '`@Mongo`0 excitedly tries to eat `#%w`0''s dropped `%a`0 before `@%W`0 even finishes wiping off `%x`0!', 'System AI'),
(14, '`5Samantha`0 rolls past `#%w`0''s crumpled body yelling, `$"I WILL KILL YOUR MOTHER, %W!"`0', 'System AI'),
(15, '`#Katia Grim`0 winces and shifts her mass into a shield: `3"Somebody grab a healing potion—`@%W`3''s `%x`3 just shredded `#%w`3''s `%a`3!"`0', 'System AI'),
(16, '`5[SYSTEM AI]:`0 `3"Quintillions of viewers across three billion star systems just watched `@%W`3 fold `#%w`3 like a cheap lawn chair!"`0', 'System AI'),
(17, '`#Elle McGibbons`0 cackles from mid-air: `3"Honey, my first husband lasted longer than `#%w`3''s `%a`3 did against `@%W`3!"`0', 'System AI'),
(18, '`4A Bopca Cleaner Bot`0 immediately rolls over `#%w`0, beeping indignantly as it vacuums up the pieces left by `@%W`0''s `%x`0!', 'System AI'),
(19, '`5"THAT %a CLASHED WITH YOUR COMPLEXION ANYWAY, %w!"`0 Princess Donut critiques from the safety of her floating pillow.', 'System AI'),
(20, '`4 Prince Maestro`0 snorts with laughter on *Death Watch Extreme* as `@%W`0''s `%x`0 sends `#%w`0 straight to the Reconstruction Bay!', 'System AI');

-- ============================================================================
-- 2. SYNDICATE RECAP OBITUARIES (`deathmessages` table)
--    `forest = 1, graveyard = 0` -> Dungeon Corridor deaths
--    `forest = 0, graveyard = 1` -> Borant Medical Reconstruction Bay failures
-- ============================================================================
DELETE FROM `deathmessages`;

INSERT INTO `deathmessages` (`deathmessageid`, `deathmessage`, `forest`, `graveyard`, `taunt`, `editor`) VALUES
-- DUNGEON CORRIDOR DEATHS (`forest = 1, graveyard = 0`)
(1,  '`5[RECAP BROADCAST]:`0 `4{goodguyname}`2 was eliminated on live galactic television by `@`b{badguyname}`b`2''s `^{badguyweapon}`2 after {goodguyname}''s `{goodguyarmor}`2 failed completely!', 1, 0, 1, 'System AI'),
(2,  '`5[BORANT CASUALTY TICKER]:`0 Crawler `4{goodguyname}`2 swung `^{goodguyweapon}`2 wildly in {where}, but `@`b{badguyname}`b`2 countered with `^{badguyweapon}`2 for an instant knockout!', 1, 0, 1, 'System AI'),
(3,  '`5[AFTER HOURS HIGHLIGHT]:`0 Odette replayed `4{goodguyname}`2 getting steamrolled by `@`b{badguyname}`b`2''s `^{badguyweapon}`2 while wearing `{goodguyarmor}`2!', 1, 0, 1, 'System AI'),
(4,  '`5[SYSTEM AI OBITUARY]:`0 `4{goodguyname}`2''s crawler counter ticked down by one after `@`b{badguyname}`b`2 introduced `^{badguyweapon}`2 to {goodguyname}''s skull!', 1, 0, 1, 'System AI'),
(5,  '`5[DEATH WATCH EXTREME]:`0 Viewer betting pools cashed out as `@`b{badguyname}`b`2 demolished `4{goodguyname}`2 and their `{goodguyweapon}`2 in {where}!', 1, 0, 1, 'System AI'),
(6,  '`5[CLEANER BOT DISPATCH]:`0 A Bopca sanitation crew was summoned to {where} after `@`b{badguyname}`b`2 turned `4{goodguyname}`2''s `{goodguyarmor}`2 into confetti with `^{badguyweapon}`2!', 1, 0, 1, 'System AI'),
(7,  '`5[SPONSOR DROP ALERT]:`0 Three sponsors unfollowed `4{goodguyname}`2 the exact millisecond `@`b{badguyname}`b`2''s `^{badguyweapon}`2 connected in {where}!', 1, 0, 1, 'System AI'),
(8,  '`5[MORDECAI''S SIGH]:`0 Despite carrying `^{goodguyweapon}`2, `4{goodguyname}`2 ignored the floor mechanics and got flattened by `@`b{badguyname}`b`2!', 1, 0, 1, 'System AI'),
(9,  '`5[SYNDICATE HOTLIST]:`0 `@`b{badguyname}`b`2 earned a viral clip by smashing through `4{goodguyname}`2''s `{goodguyarmor}`2 with `^{badguyweapon}`2!', 1, 0, 1, 'System AI'),
(10, '`5[FLOOR COLLAPSE CASUALTY]:`0 `4{goodguyname}`2 couldn''t reach the Bopca Safe Room in time after `@`b{badguyname}`b`2 ambushed them with `^{badguyweapon}`2!', 1, 0, 1, 'System AI'),

-- BORANT MEDICAL RECONSTRUCTION BAY FAILURES (`forest = 0, graveyard = 1`)
(11, '`\$[RECONSTRUCTION BAY]:`0 Clone husk `4{goodguyname}`4 flubbed their mandatory sponsor promo against `@`b{badguyname}`b`4''s `^{badguyweapon}`4 and had their respawn contract delayed!', 0, 1, 1, 'System AI'),
(12, '`\$[BORANT LEGAL AUDIT]:`0 While trying to earn AI Favor in the Reconstruction Bay, `4{goodguyname}`4 was liquidated by `@`b{badguyname}`b`4!', 0, 1, 1, 'System AI'),
(13, '`\$[SYSTEM AI OVERSIGHT]:`0 The System AI rejected `4{goodguyname}`4''s Favor appeal after `@`b{badguyname}`b`4 humiliated them with `^{badguyweapon}`4!', 0, 1, 1, 'System AI'),
(14, '`\$[SHOWRUNNER PENALTY]:`0 Dead crawler `4{goodguyname}`4 was docked AI Favor after losing a studio punishment round to `@`b{badguyname}`b`4!', 0, 1, 1, 'System AI'),
(15, '`\$[CLONE VAT MALFUNCTION]:`0 `4{goodguyname}`4''s neural reconstruction was interrupted when `@`b{badguyname}`b`4 struck with `^{badguyweapon}`4!', 0, 1, 1, 'System AI');

COMMIT;
