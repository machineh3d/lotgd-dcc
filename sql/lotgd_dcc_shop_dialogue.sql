-- ============================================================================
-- LEGEND OF THE GREEN DRAGON (NB-Core v2.0.9+ / PHP 8.4 & 8.5)
-- DUNGEON CRAWLER CARL — INSIDE SHOP & CORE PAGE DIALOGUE OVERHAUL
-- File: sql/lotgd_dcc_shop_dialogue.sql (Release v0.3.1-alpha)
-- Repository: https://github.com/machineh3d/lotgd-dcc
-- ============================================================================
-- Reskins the inside dialogue of train.php (Mordecai's Game Guide Guildhall),
-- bank.php (Orren's Syndicate Credit Vault), and shades.php (Borant Corp
-- Medical Reconstruction Bay) via NB-Core's `translations` table.
-- (weapons.php, armor.php, and stables.php are hooked natively via systemai.php.)
-- ============================================================================

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO `settings` (`setting`, `value`) VALUES
('enabletranslation', '1'),
('defaultlanguage', 'en')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

DELETE FROM `translations`
WHERE `language` = 'en'
  AND `author` = 'System AI (Shop Dialogue)';

INSERT INTO `translations` (`language`, `uri`, `intext`, `outtext`, `author`, `version`) VALUES
-- ============================================================================
-- 1. TRAIN.PHP — MORDECAI'S GAME GUIDE GUILDHALL
-- ============================================================================
('en', 'train', 'Bluspring''s Warrior Training', 'Mordecai''s Game Guide Guildhall', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', '`b`cBluspring''s Warrior Training`c`b', '`b`c`#Mordecai''s Game Guide Guildhall`0`c`b', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', 'The sound of conflict surrounds you.  The clang of weapons in grisly battle inspires your warrior heart. ', 'Holoscreens flicker with Dungeon survival telemetry while Mordecai paces beside a combat simulation mat. ', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', 'Question Master', 'Consult Game Guide (Check XP)', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', 'M?Challenge Master', 'M?Start Floor Evaluation Fight', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', 'Fight Your Master', 'Face Floor Evaluation Boss', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', 'You approach `^%s`0 timidly and inquire as to your standing in the class.', 'You step up to `^%s`0 and ask if you have enough XP to qualify for your next Crawler Level.', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', '`n`n`^%s`0 says, "Gee, your muscles are getting bigger than mine..."', '`n`n`^%s`0 checks your HUD telemetry and nods: "Your stats are maxed for this tier. Step onto the mat—you''re ready to level up."', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', '`n`n`^%s`0 states that you will need `%%s`0 more experience before you are ready to challenge him in battle.', '`n`n`^%s`0 taps your HUD readout: you still need `5%s`0 more EXP before Borant clears you for a level evaluation.', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', 'You think that, perhaps, you''ve seen enough of your master for today, the lessons you learned earlier prevent you from so willingly subjecting yourself to that sort of humiliation again.', 'Mordecai waves you off: you already burned your daily evaluation slot—go patch up in the Safe Room and try again next Broadcast Cycle.', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', '`n`nBeing a fair person, your master gives you a healing potion before the fight begins.', '`n`nFollowing Syndicate Guide protocol, your evaluator tosses you an emergency med-stim before the simulation starts.', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'train', '`$Your pride prevents you from running from this conflict!`0', '`$The Borant Evaluation Cage seals shut—you cannot flee a Level Exam!`0', 'System AI (Shop Dialogue)', '2.0.9+nb'),

-- ============================================================================
-- 2. BANK.PHP — ORREN'S SYNDICATE CREDIT VAULT
-- ============================================================================
('en', 'bank', 'Ye Olde Bank', 'Orren''s Syndicate Credit Vault', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', '`^`c`bYe Olde Bank`b`c', '`^`c`bOrren''s Syndicate Credit Vault`b`c', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', '`6As you approach the pair of impressive carved rock crystal doors, they part to allow you entrance into the bank.', '`#Reinforced Syndicate blast doors slide open with a pressurized hiss, admitting you into the Borant Credit Clearinghouse.', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', 'You find yourself standing in a room of exquisitely vaulted ceilings of carved stone.', 'Holographic stock tickers from the Valtay and Plenty banking cartels scroll across the brushed-steel ceiling.', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', 'Light filters through tall windows in shafts of soft radiance.', 'Blue security lasers scan your Crawler HUD barcode as you cross the lobby.', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', 'About you, clerks are bustling back and forth.', 'Contracted Bopca auditors and Syndicate liaison drones whirr overhead.', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', 'The sounds of gold being counted can be heard, though the treasure is nowhere to be seen.`n`n', 'The hum of encrypted credit transfers fills the chamber, though no physical gold is kept on site.`n`n', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', 'You walk up to a counter of jet black marble.`n`n', 'You step up to the bulletproof Syndicate teller console.`n`n', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', '`@Elessa`6, a petite woman in an immaculately tailored business dress, greets you from behind reading spectacles with polished silver frames.`n`n', '`@Liaison Orren''s Credit Clerk`7, an immaculate Syndicate bureaucrat in a pressed Borant blazer, adjusts a glowing monocle HUD.`n`n', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', '`6"`5Greetings, my good lady,`6" you greet her, "`5Might I inquire as to my balance this fine day?`6"`n`n', '`7"`#Pull up my Crawler escrow account,`7" you say, tapping your wristband against the scanner.`n`n', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', '`@Elessa`6 blinks for a moment and then smiles, "`@Hmm, `&%s`@, let''s see.....`6" she mutters as she scans down a page in her ledger.', '`@The Credit Clerk`7 swipes across a floating ledger: "`@Crawler `&%s`@, authenticating biometric signature...`7"', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', '`6"`@Aah, yes, here we are.  You have `^%s gold`@ in our prestigious bank.  Is there anything else I can do for you?`6"', '`7"`@Verified. Your Syndicate escrow balance is `^%s Gold Credits`@. Would you like to deposit, withdraw, or wire funds?`7"', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'bank', '`6"`@Aah, yes, here we are.  You have a `&debt`@ of `^%s gold`@ in our prestigious bank.  Is there anything else I can do for you?`6"', '`7"`@Warning: You carry a `&Borant Lien`7 of `^%s Gold Credits`@. Repay your debt before the showrunners garnish your loot boxes.`7"', 'System AI (Shop Dialogue)', '2.0.9+nb'),

-- ============================================================================
-- 3. SHADES.PHP — BORANT CORP MEDICAL RECONSTRUCTION BAY
-- ============================================================================
('en', 'shades', 'Land of the Shades', 'Borant Corp Medical Reconstruction Bay', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'shades', '`$You walk among the dead now, you are a shade. ', '`$CRITICAL BIOMETRIC FAILURE: You have died in the World Dungeon and been beamed to the Borant Reconstruction Bay. ', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'shades', 'Everywhere around you are the souls of those who have fallen in battle, in old age, and in grievous accidents. ', 'Around you, holographic ghosts of eliminated Crawlers drift between glowing cryo-tubes while recap drones replay their final moments. ', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'shades', 'Each bears telltale signs of the means by which they met their end.`n`n', 'Each Crawler''s HUD flashes their cause of death and their final Sponsor Rating.`n`n', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'shades', 'Their souls whisper their torments, haunting your mind with their despair:`n', 'Dead Crawlers vent over the open Reconstruction Bay comms channel:`n', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'shades', '`nA sepulchral voice intones, "`QIt is now %s in the world above.`$"`n`n', '`nThe System AI chimes over the intercom: "`QCurrent Dungeon Broadcast Clock: %s. Earn 100 AI Favor to sign a Respawn Contract.`$"`n`n', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'shades', 'The Graveyard', 'Enter Reconstruction Bay Terminal', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'shades', 'Return to the news', 'Watch Syndicate Recap Broadcast', 'System AI (Shop Dialogue)', '2.0.9+nb'),
('en', 'nav', 'X?Superuser Grotto', 'X?Borant Showrunner Console', 'System AI (Shop Dialogue)', '2.0.9+nb');

COMMIT;