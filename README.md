# Dungeon Crawler Carl — Total Conversion Pack for LotGD (`v0.6.0-alpha`)

> **Release Status:** `v0.6.0-alpha` (Alpha) — Tested on **NB-Core LotGD v2.0.9+ / PHP 8.4 & 8.5 / MariaDB 10.6+ or MySQL 8.0+**.

A complete **Dungeon Crawler Carl** total conversion for **Legend of the Green Dragon ([NB-Core/lotgd](https://github.com/NB-Core/lotgd))**, featuring 4 database lore & dialogue SQL packs, 13 custom PHP 8.4/8.5 gameplay modules, 2 dark-terminal Twig HUD themes, a pre-baked Docker stack, and an automated GitHub CI/Release pipeline.

## Requirements

* **Base Engine:** [NB-Core LotGD](https://github.com/NB-Core/lotgd) v2.0.9+ (included automatically when using the pre-baked Docker setup)

* **PHP:** 8.3 / 8.4 / 8.5 with `mysqli`, `pdo_mysql`, and `mbstring`

* **Database:** MariaDB 10.6+ or MySQL 8.0+

## Repository Structure

```
lotgd-dcc-pack/
├── VERSION                             # 0.6.0-alpha
├── CHANGELOG.md                        # Full version history
├── README.md                           # Installation, upgrade & release guide
├── install.sh                          # One-command installer for existing Docker or bare-metal servers
├── Dockerfile                          # Pre-baked image extending ghcr.io/nb-core/lotgd:latest
├── docker-compose.yml                  # Turnkey Web + MariaDB stack
├── .env.example                        # Credential placeholder template (copy to .env)
├── .github/
│   └── workflows/
│       └── release.yml                 # PHP 8.4 lint CI & automatic tagged ZIP releases
├── docker/
│   └── apply-dcc-sql.sh                # Seeds or updates all 4 DCC SQL packs in Docker
├── sql/
│   ├── lotgd_dcc_total_conversion.sql  # Settings, sidebar nav overrides, weapons, armor, masters, 51 mobs
│   ├── lotgd_dcc_mounts.sql            # 8 DCC pet companions (Mongo, Bianca, Gustavo 3, etc.)
│   ├── lotgd_dcc_taunts_deaths.sql     # 20 DCC combat taunts & 15 Recap obituaries
│   └── lotgd_dcc_shop_dialogue.sql     # Inside dialogue for Mordecai's Guildhall, Credit Vault & Reconstruction Bay
├── modules/                            # 13 PHP 8.4/8.5 DCC Modules
│   ├── systemai.php                    # "NEW ACHIEVEMENT!" popups, Loot Box Vault & native shop/village hooks
│   ├── dccclasses.php                  # Floor 3 Races & Classes + Floor 6 Subclasses & combat skills
│   ├── stairwellboss.php               # Floor 1-18 Stairwell Guardians, Scolopendra & Quasar's Exit Deals
│   ├── toptencrawlers.php              # Top 10 Leaderboard, Head Bounties, PK Skulls & Odette's After Hours
│   ├── personalspace.php               # Safe Room Personal Space, Sapper/Alchemy/Engineer Tables & Cat Tree
│   ├── bopcaprotector.php              # Bopca Safe Room Bar, Mordecai's Glory Bound Elixir & Floor Quests
│   ├── dungeonbosses.php               # Multi-Tier Boss Chambers (Neighborhood/Borough/City/Province) & Maps
│   ├── factionwars.php                 # Ninth Floor Faction Wars, Larracos Citadel Control & Mercenaries
│   ├── sponsors.php                    # Syndicate Sponsors (3 slots), Benefactor Boxes & Viewer Fan Boxes
│   ├── clubvanquisher.php              # Club Vanquisher & Celestial Ascendency Deities (Emberus, Hellik, etc.)
│   ├── forestcasino.php                # Desperado Club Casino (Tito's Blackjack, Nothing Spot Slots, VIP Tattoo)
│   ├── turnbank.php                    # Borant Stamina Injector & Sponsor Token Exchange Vault
│   └── newdaybar.php                   # Live Syndicate Broadcast & Floor Collapse Timer HUD
└── themes/
    ├── dcc_hud/                        # Full neon Syndicate Broadcast HUD Twig theme
    └── dcc_hud_minimal/                # No-images / no-login-hero CRT terminal HUD theme (default)

```

## Installation Guide

Choose **one** of the three installation paths below depending on how you host LotGD.

### Option A: Fresh Server via Pre-Baked Docker Stack (Recommended)

This builds a ready-to-run container on top of `ghcr.io/nb-core/lotgd:latest` with all 13 modules and both Twig themes already baked in.

1. **Clone the repository and configure your environment:**

   ```
   git clone https://github.com/<your-username>/<your-repo>.git
   cd <your-repo>
   cp .env.example .env
   
   ```

   Edit `.env` and replace `YOUR_DB_NAME`, `YOUR_DB_USER`, `YOUR_DB_PASSWORD`, and `YOUR_DB_ROOT_PASSWORD` with strong credentials. *(Never commit `.env` to Git.)*

2. **Build and start the containers:**

   ```
   docker compose up -d --build
   
   ```

3. **Run the NB-Core web installer once (auto-installs DCC SQL & HUD):**
   Because `migrations/Version20261119000100.php` is baked into the image, `installer.php` automatically imports all 4 DCC SQL packs, enables translations, and sets `defaultskin = dcc_hud_minimal` — no separate SQL script needed for fresh installs!
   
   Open `http://<your-server-ip>:8080/installer.php` in your browser and complete the initial database setup using the credentials from your `.env` file.

4. **Seed the 4 DCC SQL packs and set the default HUD theme:**

   ```
   chmod +x docker/apply-dcc-sql.sh
   DB_CONTAINER="your-db-container"    WEB_CONTAINER="your-web-container"    DB_USER="YOUR_DB_USER"    DB_PASS="YOUR_DB_PASSWORD"    DB_NAME="YOUR_DB_NAME"    ./docker/apply-dcc-sql.sh
   
   ```

5. **Finish with the [Post-Install Setup in Game](https://www.google.com/search?q=%23post-install-setup-in-game) steps below.**

### Option B: Modding an Existing Docker Server (Unraid / Docker Compose)

If you already have a running NB-Core LotGD web container and MariaDB/MySQL container:

```
git clone https://github.com/<your-username>/<your-repo>.git
cd <your-repo>
chmod +x install.sh
WEB_CONTAINER="your-web-container" DB_CONTAINER="your-db-container" DB_USER="YOUR_DB_USER" DB_PASS="YOUR_DB_PASSWORD" DB_NAME="YOUR_DB_NAME" ./install.sh

```

What `install.sh` does automatically:

1. Creates a pre-install database backup in `./backups/`.

2. Imports all 4 SQL packs (`total_conversion`, `mounts`, `taunts_deaths`, `shop_dialogue`).

3. Copies all 13 PHP modules into `/var/www/html/modules/` with `0644` permissions.

4. Clones `aurora` / `aurora_minimal` into `templates_twig/dcc_hud` and `templates_twig/dcc_hud_minimal`, strips stock medieval hero/nav images, injects DCC CSS, and sets `defaultskin = dcc_hud_minimal`.

5. Clears the LotGD template and data caches.

### Option C: Bare-Metal Linux (Apache / Nginx)

```
git clone https://github.com/<your-username>/<your-repo>.git
cd <your-repo>
chmod +x install.sh
LOTGD_ROOT="/var/www/html" DB_HOST="localhost" DB_USER="YOUR_DB_USER" DB_PASS="YOUR_DB_PASSWORD" DB_NAME="YOUR_DB_NAME" ./install.sh

```

*(If your database uses a table prefix such as `lotgd_`, also pass `DB_PREFIX="lotgd_"`.)*

## Post-Install Setup in Game

After completing Option A, B, or C, log in with your Superuser account:

1. **Install & Activate all 13 DCC Modules:**

   * Go to **Borant Showrunner Console (Superuser Grotto)** -> **Manage Modules** -> **Dungeon Crawler World**.

   * Click **Install** and **Activate** on all 13 modules.

   * *Note:* If you see a harmless `DataTables warning (tn/4)` popup on an empty category before installing the first module, click OK—it disappears as soon as at least one module in that category is installed.

2. **Disable Conflicting Stock Medieval Modules:**

   * Under **Manage Modules**, deactivate stock fantasy races and events: `raceelf`, `racedwarf`, `racetroll`, `racehuman`, `oldman`, `statue`, and `fairy`.

3. **Verify Theme & Hard-Refresh:**

   * Confirm your skin is set to **`dcc_hud_minimal`** (or **`dcc_hud`**) in **Game Settings** and **Preferences**, then press `Ctrl+F5` to reload CSS.

## Updating an Existing DCC Server

### If Using Pre-Baked Docker (Option A)

```
git pull
docker compose up -d --build

```

Your database volume (`dcc_db_data`) is preserved automatically. If the release updated any SQL packs, also re-run `./docker/apply-dcc-sql.sh`.

### If Using `install.sh` (Option B or C)

```
git pull
./install.sh   # with your same WEB_CONTAINER / DB_* environment variables

```

> **Important when updating modules with new hooks:** If an update adds new hooks to an existing module (for example `systemai.php`), go to **Manage Modules -> Dungeon Crawler World**, click **Deactivate** and then **Activate** on that module so NB-Core registers the new hooks, then clear `/var/www/html/cache/*`.

## Automated GitHub CI & Publishing Releases

`.github/workflows/release.yml` runs automatically on every push and pull request:

* Lints all PHP files in `modules/` with **PHP 8.4** (`php -l`).

* Validates `install.sh` and `docker/apply-dcc-sql.sh` (`bash -n`).

* Blocks accidental unsafe `%%` specifiers inside `output()` calls.

To publish a new versioned release with an automatically built `.zip` attachment:

```
git tag v0.6.0-alpha
git push origin v0.6.0-alpha

```