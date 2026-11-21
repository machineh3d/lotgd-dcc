<?php

declare(strict_types=1);

use Lotgd\Page\Header;
use Lotgd\Translator;

Translator::getInstance()->setSchema('faq');
Header::popupHeader('DCC FAQ — Section 3: Technical Issues & HUD Troubleshooting');

$output->output("`c`b`#SECTION 3: TECHNICAL ISSUES &amp; HUD TROUBLESHOOTING`b`c`0`n");
$output->outputNotl("<a href='petition.php?op=faq'>&laquo; Return to FAQ Contents</a><hr/>", true);

$output->output("`^`b1. Why did I get a \"Bad Nav\" / Illegal Navigation page?`b`0`n");
$output->output("`7LotGD protects against browser Back-button exploits by only allowing links shown on your current screen. Never use the browser Back button or open multiple tabs of the same account at once—always use the in-game navigation links.`0`n`n");

$output->output("`^`b2. My HUD colors or layout look outdated after a server update.`b`0`n");
$output->output("`7Press `^Ctrl + F5`7 (or clear your browser cache) to reload the latest `#dcc_hud_minimal`7 stylesheet. You can also verify your active skin under `^Preferences`7 in the sidebar.`0`n`n");

$output->output("`^`b3. How do I change the Earth Clock timezone in the top Collapse Timer bar?`b`0`n");
$output->output("`7Use the inline timezone selector directly on the Syndicate Broadcast &amp; Collapse Timer HUD bar, or update your timezone preference under `^Preferences`7.`0`n`n");

$output->output("`^`b4. I found a bug or stuck character—how do I contact the Showrunners?`b`0`n");
$output->output("`7Use the `^Petition for Help`7 form (`#petition.php`7) to message the server administrators with the page name and what you clicked right before the issue happened.`0`n`n");

$output->outputNotl("<hr/><a href='petition.php?op=faq2'>&laquo; Section 2</a> | <a href='petition.php?op=faq'>Return to FAQ Contents</a>", true);
