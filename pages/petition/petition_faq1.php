<?php

declare(strict_types=1);

use Lotgd\Page\Header;
use Lotgd\Translator;

Translator::getInstance()->setSchema('faq');
Header::popupHeader('DCC FAQ — Section 1: General Gameplay & Survival Basics');

$output->output("`c`b`#SECTION 1: GENERAL GAMEPLAY &amp; SURVIVAL BASICS`b`c`0`n");
$output->outputNotl("<a href='petition.php?op=faq'>&laquo; Return to FAQ Contents</a><hr/>", true);

$output->output("`^`b1. What is the main goal of Dungeon Crawler World?`b`0`n");
$output->output("`7Survive the World Dungeon and descend as deep as you can (Floors 1 through 18). On each Floor, patrol the `#Dungeon Corridors`7 to earn EXP and Gold Credits, train with `#Mordecai's Game Guide Guildhall`7 up to Level 15, and defeat the `\$Stairwell Floor Guardian`7 to descend to the next Floor.`n`n");

$output->output("`^`b2. What are Floor Stamina (Turns) and Broadcast Cycles (New Days)?`b`0`n");
$output->output("`7Every combat encounter in the Corridors costs 1 Floor Stamina Turn. When you run out of Stamina, wait for the next Broadcast Cycle (shown on your live top HUD Collapse Timer), eat a Bopca Hot Dog Basket (+1 Turn/day), withdraw stored turns from the `#Borant Stamina Vault`7, or crack open Loot Boxes.`n`n");

$output->output("`^`b3. How do I level up?`b`0`n");
$output->output("`7Once you gather enough EXP in the Corridors, visit `#Mordecai's Game Guide Guildhall`7 and choose `^Consult Game Guide`7 to check your progress. When ready, challenge your Floor Evaluator to advance to the next level (up to Level 15).`n`n");

$output->output("`^`b4. What happens if I die?`b`0`n");
$output->output("`7Death is not permanent! You are beamed to the `\$Borant Corp Medical Reconstruction Bay`7. Film humiliating sponsor promos (`Torment`) to earn `^100 AI Favor`7, then sign a Borant Respawn Contract to return to the living—or wait for the next Broadcast Cycle.`n`n");

$output->output("`^`b5. What are Gold Credits, Sponsor Tokens, and Loot Boxes?`b`0`n");
$output->output("`7- `^Gold Credits:`7 Used for Weapons (`Bopca Munitions`), Armor (`Safe Room Tactical Outfitter`), Pets (`Enhanced Pet Biscuit Menagerie`), Bopca food, and Personal Space tables.`n");
$output->output("`7- `5Sponsor Tokens (Gems):`7 Rare premium currency used for high-tier pets, Crafting Table upgrades, Mordecai's Glory Bound Detox Elixir, and Stamina Vault exchanges.`n");
$output->output("`7- `@Loot Boxes:`7 Awarded by the System AI for achievements, Floor Quests, Boss kills, and Sponsors. Open them inside the Safe Room Loot Vault!`n`n");

$output->output("`^`b6. Where can I learn what every area does or track monsters I have fought?`b`0`n");
$output->output("`7Open the `#Crawler's Cookbook &amp; Bestiary`7 under `Broadcast Feed` in the Safe Room Hub for an area-by-area guide, an ability directory, and a progressive Bestiary that unlocks enemy stats as you slay them.`n`n");

$output->outputNotl("<hr/><a href='petition.php?op=faq'>&laquo; Return to FAQ Contents</a> | <a href='petition.php?op=faq2'>Next: Section 2 (Spoilers &amp; Deep Systems) &raquo;</a>", true);
