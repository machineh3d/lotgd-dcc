# LotGD-DCC Native Android JSON API Bridge

This lightweight PHP bridge lets the **lotgd-dcc-android** native app talk to your [NB-Core LotGD](https://github.com/NB-Core/lotgd) server using structured JSON—without editing any NB-Core source files and without affecting normal web browsers.

## How It Works
- Loaded via PHP `auto_prepend_file` (`99-dcc-api.ini`).
- Normal browser requests skip it immediately (zero impact on desktop/mobile web themes).
- Requests with header `X-DCC-Client: 1` (or query `?dcc_json=1`) return structured JSON containing:
  - `player`: exact numeric stats from `$session['user']` (`hp`, `maxHp`, `turns`, `gold`, `goldInBank`, `gems`, `level`, `floor`, `title`, `name`, `alive`)
  - `navGroups`: grouped navigation sections and exact `allowednavs`-safe URLs
  - `paragraphs`: rich text spans with LotGD `` ` `` color codes mapped to hex colors
  - `forms`: structured form schemas for login, shops, Bopca quests, feedback, and preferences
  - `charStats` & `mail`: sidebar stats and Syndicate Comms unread counts

## Quick Install on Unraid (`dcc-web-prod`)
```bash
chmod +x install-bridge.sh
./install-bridge.sh dcc-web-prod
```

## Bake Into Your `lotgd-dcc` Dockerfile
Add these two lines to your `lotgd-dcc` `Dockerfile` so the bridge is included on every future rebuild:
```dockerfile
COPY bridge/dcc_api_bridge.php /var/www/html/dcc_api_bridge.php
COPY bridge/99-dcc-api.ini /usr/local/etc/php/conf.d/99-dcc-api.ini
```

## Verify It Works
```bash
curl -s -H "X-DCC-Client: 1" https://your-server-domain/home.php | head -c 400
```
You should see `"apiVersion":1,"ok":true,"pageType":"login"...`.
