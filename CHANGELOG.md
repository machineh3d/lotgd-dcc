# Changelog

## [v0.4.3-alpha] - 2026-11-20

### Fixed
- **Combat Skill Labels & Specialty Selection Screen (`modules/dccclasses.php`, `sql/lotgd_dcc_shop_dialogue.sql`)**: Replaced escaped `&#9889;` entities in combat nav labels with clean `> ` prefixes, added full skill breakdowns (cost, rounds, bonus damage) for all 3 Floor 1 Specialties on the selection screen, and translated `newday.php` specialty prompts (`A little history about yourself` / `What do you recall doing as a child?`).

## [v0.4.2-alpha] - 2026-11-20

### Added
- **DCC Floor 1 Starting Specialties (`modules/dccclasses.php`, `Dockerfile`, `install.sh`)**: Replaced stock medieval specialties (`specialtydarkarts`, `specialtymysticpower`, `specialtythiefskills`) with 3 canonical Floor 1 Survival Specialties (**Improvised Demolition**, **Broadcast Glamour & Magic**, and **Close-Quarters Brawling & Grit**) with 3 combat skill tiers each.

## [v0.4.1-alpha] - 2026-11-20

### Fixed
- **New Crawler Starting Race (`modules/dccclasses.php`)**: Added `chooserace` and `setrace` hooks so brand-new accounts register cleanly as `Earth Human (Surface Survivor)` on Floor 1 instead of seeing "No races installed" after stock medieval races are stripped.

## [v0.4.0-alpha] - 2026-11-20

### Added
- **Expanded System AI Achievements & Secret Redacted Achievements (`modules/systemai.php`)**: Expanded the catalog from 14 to **32 achievements** (18 visible + **14 Hidden/Classified Secret Achievements**). Locked secret achievements appear as `??? [CLASSIFIED ACHIEVEMENT]` with redacted conditions and rewards in the Dossier until discovered, and trigger a special `★ SECRET ACHIEVEMENT UNLOCKED!` HUD popup when unlocked.

## [v0.3.11-alpha] - 2026-11-20

### Changed
- **Clean Centered Top Banner (`themes/dcc_hud_minimal`, `themes/dcc_hud`, `modules/systemai.php`)**: Keeps the area title centered, hides MoTD and Petition links cleanly, always displays Syndicate Comms (0 unread or unread), and removes the experimental top-bar admin quick links.

## [v0.3.10-alpha] - 2026-11-20

### Fixed
- **Native Top-Banner Admin Quick Links (`modules/systemai.php`, `themes/`)**: Switched from JS DOM injection to NB-Core's native `everyheader` + `motd-link` hooks—replacing MoTD with `Grotto | Modules | Users` pills for admins/module editors and hiding MoTD cleanly for regular Crawlers.

## [v0.3.9-alpha] - 2026-11-20

### Fixed
- **Syndicate Comms Link Always Visible (`themes/dcc_hud_minimal`, `themes/dcc_hud`)**: Scoped `.motd` hiding strictly to `.site-header .header-info .motd` so NB-Core's `<a class="motd">` Syndicate Comms link stays visible even with 0 unread messages.
- **Admin Quick Links Compatibility (`modules/systemai.php`)**: Fixed `SU_MANAGE_MODULES` / `SU_EDIT_USERS` fallback checks so `Grotto | Modules | Users` quick links render reliably in `.site-header .header-info` for admins and module editors.

## [v0.3.8-alpha] - 2026-11-19

### Changed
- **Compact Top HUD Banner (`themes/dcc_hud_minimal`, `themes/dcc_hud`)**: Removed MoTD and Petition links from `.site-header` and auto-sized the Syndicate Comms pill to tightly fit its text on a clean single-row flex bar.

## [v0.3.7-alpha] - 2026-11-19

### Fixed
- **Pre-Login Banner & Syndicate Comms Link (`sql/lotgd_dcc_shop_dialogue.sql`)**: Added exact `Header.php` default title overrides (`Legend of the Green Dragon` -> `Dungeon Crawler World: Earth`), `meta_description`, and the `PageParts.php` top-bar mail format string (`Ye Olde Mail: %s new, %s old` -> `Syndicate Comms: %s unread, %s archived`).

## [v0.3.6-alpha] - 2026-11-19

### Added
- **Complete Core Page Immersion Pass (`sql/lotgd_dcc_shop_dialogue.sql`)**: Set `gamename = Dungeon Crawler World: Earth` for the top site banner, and added exact `translations` overrides for `home.php` (pre-login broadcast welcome), `forest.php`/`Forest.php` (World Dungeon Corridors), `mail.php` (Syndicate Crawler Comms), `account.php` (Floors Descended & Stairwell Stat Points), and `clan.php`/`pages/clan/` (Faction Wars Command & Crawler Parties).

## [v0.3.5-alpha] - 2026-11-19

### Changed
- **Stripped Stock Medieval Modules at Build Time (`Dockerfile`, `install.sh`, `docker/apply-dcc-sql.sh`)**: Removes `raceelf.php`, `racedwarf.php`, `racetroll.php`, `racehuman.php`, `oldman.php`, `statue.php`, and `fairy.php` from `/var/www/html/modules/` during image build so `installer.php` never pre-installs stock fantasy races.

## [v0.3.4-alpha] - 2026-11-19

### Fixed
- **Docker State Persistence & NB-Core Entrypoint Compatibility (`docker-compose.yml`, `.env.example`)**: Added persistent `dcc_state:/var/lib/lotgd` volume (so `dbconnect.php` and `installation-complete` survive rebuilds/updates), passed required `MYSQL_*` variables and `LOTGD_INSTALL_ENABLED` to `dcc-web`, and documented generating non-example secrets via `openssl`.

## [v0.3.3-alpha] - 2026-11-19

### Added
- **Zero-Touch `installer.php` Doctrine Migration (`migrations/Version20261119000100.php`)**: Hooked directly into NB-Core's `Lotgd\Migrations` runner so `installer.php` automatically imports all 4 DCC SQL packs, applies table prefixes, enables translations, and sets `defaultskin = dcc_hud_minimal`.

## [v0.3.2-alpha] - 2026-11-19

### Added
- **Automated GitHub CI & Release Workflow (`.github/workflows/release.yml`)**: Runs PHP 8.4 syntax linting (`php -l`), shell validation (`bash -n`), and double-percent checks on every push; automatically packages and publishes a GitHub Release ZIP when a `v*` tag is pushed.
- **Pre-Baked Docker Stack (`Dockerfile`, `docker-compose.yml`, `.env.example`, `docker/apply-dcc-sql.sh`)**: Extends `ghcr.io/nb-core/lotgd:latest` with all 13 modules and both Twig themes baked directly into the image for one-command fresh installs and seamless container updates.
- **Inside Shop & Reconstruction Bay Reskin (`sql/lotgd_dcc_shop_dialogue.sql` + `systemai.php` hooks)**: Added native `weaponstext`, `armortext`, and `stabletext` hooks plus `translations` overrides for `train.php`, `bank.php`, and `shades.php`.

### Changed
- Scrubbed all documentation, templates, and SQL headers to use clean `YOUR_*` placeholders only.

## [v0.3.0-alpha] - 2026-11-19

### Added
- **`modules/factionwars.php` (Ninth Floor Faction Wars & Larracos Command)**: Enlist in 5 canonical Faction Wars armies, fund the global War Chest to capture Larracos Citadel for daily combat buffs & turns, hire Veteran/War Mage mercenary squads, and redeem War Points for Faction Boxes.
- **`modules/sponsors.php` (Syndicate Sponsors, Benefactor Boxes & Fan Boxes)**: Up to 3 Sponsor slots (OIPAN, Prism Industries, The Plenty, Valtay Corp, Long Haul Bio) delivering daily stipends, coded AI-filtered notes, Floor-Descent Benefactor Boxes, and Viewer-Voted Fan Boxes.
- **`modules/clubvanquisher.php` (Club Vanquisher & Celestial Ascendency Deities)**: Pledge worship to Emberus, Hellik, Taranis, or Grull for scaling Divine Smite combat skills, offer Shrine tributes (risking divine wrath), or get banned from Club Vanquisher for Viewer Ratings.
- **Exact Sidebar Navigation Overrides**: `lotgd_dcc_total_conversion.sql` enables `enabletranslation` with exact `village` `X?` hotkey strings, and `systemai.php` hooks `villagetext` for core shop and section renames.

## [v0.2.0-alpha] - 2026-11-17

### Added
- **`modules/bopcaprotector.php` (Bopca Safe Room Bar, Mordecai's Lounge & Floor Quests)**:
  - Floor-adapting Safe Room disguises (Roadside Diner, *The All-Seeing Spleen*, Iron Tangle Espresso Kiosk, *The One-Eyed Narwhal*, Gothic Crypt Cantina, and Larracos Battlefield Bunker).
  - Bopca Protector's Menu: Bopca Hot Dog & Fries Basket (100% HP heal + `+1` Floor Stamina Turn/day), Princess Donut's Dirty Shirley (`+2` Charm + 12% ATK/DEF buff), Rev-Up Goblin Moonshine (`+24%` ATK overdrive), and Mordecai's *Glory Bound* Detox Elixir (`115%` overheal, clears Desperado Club Addiction Lockout, and grants 25-round clarity buff).
  - Floor 5/6 Suspicious Pub Inspection mini-event with Mordecai (detects Mimic Pubs for bonus Gold/EXP or discovers verified Bopca shortcuts for `+1` Stamina Turn).
  - Syndicate Floor Quest Board (`battle-victory` kill tracking) awarding Bronze, Silver, Gold, and Celestial Quest Boxes (`systemai` integration), Gold, Gems, and permanent Max HP.
  - Safe Room Non-Aggression Field enforcement (3 strikes = telekinetic ejection at 1 HP).
- **`modules/dungeonbosses.php` (Multi-Tier Dungeon Boss Encounters & Persistent Cartography)**:
  - 9 canonical Dungeon Bosses across 4 tiers: Neighborhood Bosses (*The Hoarder*, *Heather the Roller-Skating Circus Bear*, *The Divider*), Borough Bosses (*The Ball of Swine*, *The Rebalanced Street Preacher*, *The Krakaren Clone*), City Bosses (*Grimaldi the Pestiferous Vine*, *Shi Maria*), and Province Boss (*General Imogen*).
  - Sealed Boss Chamber multi-round tactical combat (Standard Assault, Sapper Blitz, Mordecai's Defensive Read) with once-per-day Emergency Smoke Curtain escape.
  - Persistent Cartography drops (*Neighborhood Map*, *Borough Field Guide*, *Map of the Stars*, *Scolopendra Fault Atlas*) granting passive daily Floor Stamina Turns on every `newday`.
  - Automatic Boss Boxes, Clutch Survivor's Boxes (when winning under 20% HP), *Boss Babe!* and *Borough Boss!* achievements, and permanent Boss Kill Stars (`★` / `✪`) in HUD & player bios.

## [v0.1.0-alpha] - 2026-11-17

### Added
- **Core Lore & Data SQL Packs (`sql/`)**:
  - `lotgd_dcc_total_conversion.sql`, `lotgd_dcc_mounts.sql`, `lotgd_dcc_taunts_deaths.sql`.
- **Initial 8 PHP 8.4/8.5 Modules (`modules/`)**:
  - `systemai.php`, `dccclasses.php`, `stairwellboss.php`, `toptencrawlers.php`, `personalspace.php`, `forestcasino.php`, `turnbank.php`, `newdaybar.php`.
- **NB-Core Twig Themes (`themes/`)**:
  - `dcc_hud` and `dcc_hud_minimal`.
