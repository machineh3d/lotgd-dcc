<?php

declare(strict_types=1);

use Lotgd\Page\Header;
use Lotgd\Translator;

Translator::getInstance()->setSchema('faq');
Header::popupHeader('DCC FAQ — Section 2: Floors, Bosses, Classes & Secrets');

$output->output("`c`b`^SECTION 2: FLOORS 1-18, CLASSES, BOSSES &amp; SECRETS (SPOILERS)`b`c`0`n");
$output->outputNotl("<a href='petition.php?op=faq'>&laquo; Return to FAQ Contents</a><hr/>", true);

$output->output("`^`b1. How do Floor 1 Specialties, Floor 3 Races/Classes, and Floor 6 Subclasses work?`b`0`n");
$output->output("`7- `^Floor 1:`7 Every new Crawler starts as an `@Earth Human`7 and picks one of 3 Floor 1 Survival Specialties (`\$Improvised Demolition`7, `5Broadcast Glamour &amp; Magic`7, or `#Close-Quarters Brawling &amp; Grit`7).`n");
$output->output("`7- `^Floor 3:`7 Visit `#Crawler Race &amp; Class Desk`7 to pick a Syndicate Race (`5Primal`7, `#Changeling`7, `5Sapient Persian Cat`7, `^Highland Caprid`7, `@Armored Crocodilian`7, or stay `@Earth Human`7 for a bonus Silver Earth Box) and Class (`\$Compensated Anarchist`7, `5Former Child Actor`7, `#Mass-Shifting Juggernaut`7, `#Frost Maiden`7).`n");
$output->output("`7- `^Floor 6:`7 Unlock elite Subclasses (`^Agent Provocateur`7, `\$Revolutionary`7, `@Guerilla`7, `5Queen Anne Chonk Warlord`7, `#Valkyrie Dreadnought`7, `#Four-Seasons Archmage`7).`0`n`n");

$output->output("`^`b2. How do Personal Space Crafting Tables scale?`b`0`n");
$output->output("`7Every Crafting Table you install inside your `#Personal Space`7 (`\$Sapper's Table`7, `@Alchemy Table`7, `#Engineer's Table`7) automatically gains `@+1 Level`7 every time you defeat a Stairwell Guardian and descend to a deeper Floor!`0`n`n");

$output->output("`^`b3. What are Multi-Tier Dungeon Boss Chambers &amp; Persistent Maps?`b`0`n");
$output->output("`7Enter `\$Dungeon Boss Chambers`7 from the Corridors or Safe Room to fight canonical Neighborhood, Borough, City, and Province Bosses (`^The Hoarder`7, `^Ball of Swine`7, `^Grimaldi`7, `^Shi Maria`7, `^General Imogen`7). Defeating each tier awards permanent Boss Kill Stars, Boss Loot Boxes, and persistent HUD Maps (`#Neighborhood Map`7, `#Borough Field Guide`7, `#Map of the Stars`7, `#Scolopendra Fault Atlas`7) that grant passive extra Stamina Turns every New Day!`0`n`n");

$output->output("`^`b4. How do Syndicate Sponsors, Faction Wars, and Club Vanquisher work?`b`0`n");
$output->output("`7- `^Syndicate Sponsors:`7 Fill up to 3 Patron slots for daily stipends, coded notes, Floor-Descent Benefactor Boxes, and Viewer-Voted Fan Boxes.`n");
$output->output("`7- `\$Ninth Floor Faction Wars:`7 Enlist in one of 5 armies, fund the global War Chest to capture Larracos Citadel for daily combat buffs, and hire Veteran or War Mage mercenaries.`n");
$output->output("`7- `5Club Vanquisher &amp; Deities:`7 Worship `\$Emberus`7, `^Hellik`7, `#Taranis`7, or `4Grull`7 for scaling Divine Smites—or cause a scene to get permanently banned for Viewer Ratings.`0`n`n");

$output->output("`^`b5. What are Classified / Secret Achievements?`b`0`n");
$output->output("`7Inside your `#System AI Loot Vault -> Achievement Dossier`7, 14 achievements are marked `5??? [CLASSIFIED ACHIEVEMENT]`7. Their unlock conditions are hidden until you stumble into them (surviving at 1 HP, going dead broke, provoking a Bopca, crawling past midnight, earning 5 Boss Stars, and more).`0`n`n");

$output->output("`^`b6. What happens on Floor 10 and Floor 18?`b`0`n");
$output->output("`7Starting on Floor 10, Nullian lawyer `^Quasar`7 opens the Outreach Guild so you can sign a Syndicate Exit Deal—or refuse and push all the way to Floor 18 to battle `\$Scolopendra`7 and claim Regency of Earth!`0`n`n");

$output->outputNotl("<hr/><a href='petition.php?op=faq1'>&laquo; Section 1</a> | <a href='petition.php?op=faq'>Contents</a> | <a href='petition.php?op=faq3'>Next: Section 3 (Technical) &raquo;</a>", true);
