<?php

declare(strict_types=1);

use Lotgd\Page\Header;
use Lotgd\Translator;

Translator::getInstance()->setSchema('faq');
Header::popupHeader('DCC FAQ — Section 2: Floors, Bosses, Classes & Secrets');

$output->output("`c`b`^SECTION 2: FLOORS 1-18, CLASSES, BOSSES &amp; SECRETS (SPOILERS)`b`c`0`n");
$output->outputNotl("<a href='petition.php?op=faq'>&laquo; Return to FAQ Contents</a><hr/>", true);

$output->output("`^`b1. How do Floor 1 Specialties, Floor 3 Races/Classes, and Floor 6 Subclasses work?`b`0`n");
$output->output("`7- `^Floor 1:`7 Every new Crawler starts as an `Earth Human` and picks one of 3 Floor 1 Survival Specialties (`Improvised Demolition`, `Broadcast Glamour &amp; Magic`, or `Close-Quarters Brawling &amp; Grit`).`n");
$output->output("`7- `^Floor 3:`7 Visit `Crawler Race &amp; Class Desk` to pick a Syndicate Race (`Primal`, `Changeling`, `Sapient Persian Cat`, `Highland Caprid`, `Armored Crocodilian`, or stay `Earth Human` for a bonus Silver Earth Box) and Class (`Compensated Anarchist`, `Former Child Actor`, `Mass-Shifting Juggernaut`, `Frost Maiden`).`n");
$output->output("`7- `^Floor 6:`7 Unlock elite Subclasses (`Agent Provocateur`, `Revolutionary`, `Guerilla`, `Queen Anne Chonk Warlord`, `Valkyrie Dreadnought`, `Four-Seasons Archmage`).`n`n");

$output->output("`^`b2. How do Personal Space Crafting Tables scale?`b`0`n");
$output->output("`7Every Crafting Table you install inside your `#Personal Space`7 (`Sapper's Table`, `Alchemy Table`, `Engineer's Table`) automatically gains `@+1 Level`7 every time you defeat a Stairwell Guardian and descend to a deeper Floor!`n`n");

$output->output("`^`b3. What are Multi-Tier Dungeon Boss Chambers &amp; Persistent Maps?`b`0`n");
$output->output("`7Enter `$Dungeon Boss Chambers`7 from the Corridors or Safe Room to fight canonical Neighborhood, Borough, City, and Province Bosses (`The Hoarder`, `Ball of Swine`, `Grimaldi`, `Shi Maria`, `General Imogen`). Defeating each tier awards permanent Boss Kill Stars (`★`), Boss Loot Boxes, and persistent HUD Maps (`Neighborhood Map`, `Borough Field Guide`, `Map of the Stars`, `Scolopendra Fault Atlas`) that grant passive extra Stamina Turns every New Day!`n`n");

$output->output("`^`b4. How do Syndicate Sponsors, Faction Wars, and Club Vanquisher work?`b`0`n");
$output->output("`7- `^Syndicate Sponsors:`7 Fill up to 3 Patron slots for daily stipends, coded notes, Floor-Descent Benefactor Boxes, and Viewer-Voted Fan Boxes.`n");
$output->output("`7- `$Ninth Floor Faction Wars:`7 Enlist in one of 5 armies, fund the global War Chest to capture Larracos Citadel for daily combat buffs, and hire Veteran or War Mage mercenaries.`n");
$output->output("`7- `5Club Vanquisher &amp; Deities:`7 Worship `Emberus`, `Hellik`, `Taranis`, or `Grull` for scaling Divine Smites—or cause a scene to get permanently banned for Viewer Ratings.`n`n");

$output->output("`^`b5. What are Classified / Secret Achievements?`b`0`n");
$output->output("`7Inside your `System AI Loot Vault -> Achievement Dossier`, 14 achievements are marked `??? [CLASSIFIED ACHIEVEMENT]`. Their unlock conditions are hidden until you stumble into them (surviving at 1 HP, going dead broke, provoking a Bopca, crawling past midnight, earning 5 Boss Stars, and more).`n`n");

$output->output("`^`b6. What happens on Floor 10 and Floor 18?`b`0`n");
$output->output("`7Starting on Floor 10, Nullian lawyer `Quasar` opens the Outreach Guild so you can sign a Syndicate Exit Deal—or refuse and push all the way to Floor 18 to battle `$Scolopendra`7 and claim Regency of Earth!`n`n");

$output->outputNotl("<hr/><a href='petition.php?op=faq1'>&laquo; Section 1</a> | <a href='petition.php?op=faq'>Contents</a> | <a href='petition.php?op=faq3'>Next: Section 3 (Technical) &raquo;</a>", true);
