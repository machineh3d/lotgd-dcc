<?php

declare(strict_types=1);

use Lotgd\Modules\HookHandler;
use Lotgd\Page\Header;
use Lotgd\Translator;

Translator::getInstance()->setSchema('faq');
Header::popupHeader('Dungeon Crawler World: Earth — Official Crawler FAQ');

$output->output("`#`bWELCOME TO DUNGEON CRAWLER WORLD: EARTH`b`0`n`n");
$output->output("`7The surface buildings of Earth have collapsed, a glowing subterranean stairwell opened beneath your feet, and you are now an involuntary contestant on the Syndicate's most-watched death-game broadcast—produced by the `^Borant Corporation`7.`n`n");
$output->output("`3You duck into a Bopca-staffed Safe Room and grab a laminated pamphlet stamped with a glowing System AI warning: `&\"Everything You Need to Know to Survive Floor One (And Not Become a Recap Clip).\"`0`n`n");
$output->output("`^`bContents:`b`0`n");

HookHandler::hook('faq-pretoc');
$output->output("`^`bCrawler Survival Manual & FAQ`b`0`n");
$output->outputNotl("&#149; <a href='petition.php?op=faq1'>`@Section 1: General Gameplay &amp; Floor 1 Survival Basics`0</a><br/>", true);
$output->outputNotl("&#149; <a href='petition.php?op=faq2'>`^Section 2: Floors 1-18, Bosses, Classes, Sponsors &amp; Secrets (Spoilers)`0</a><br/>", true);
$output->outputNotl("&#149; <a href='petition.php?op=faq3'>`#Section 3: Technical Issues, HUD Themes &amp; Navigation`0</a><br/>", true);
HookHandler::hook('faq-toc');
HookHandler::hook('faq-posttoc');

$output->output("`n`7Good luck, Crawler. Try not to bore the sponsors.`n`#— The System AI &amp; Tutorial Guild`0`n");
