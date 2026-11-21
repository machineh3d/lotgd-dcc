# ============================================================================
# DUNGEON CRAWLER CARL — PRE-BAKED NB-CORE LOTGD DOCKERFILE
# Base Image: https://github.com/NB-Core/lotgd (ghcr.io/nb-core/lotgd:latest)
# Repository: https://github.com/machineh3d/lotgd-dcc
# ============================================================================
ARG BASE_IMAGE=ghcr.io/nb-core/lotgd:latest
FROM ${BASE_IMAGE}

LABEL org.opencontainers.image.title="Dungeon Crawler Carl — LotGD Total Conversion" \
      org.opencontainers.image.source="https://github.com/machineh3d/lotgd-dcc" \
      org.opencontainers.image.licenses="MIT"

# 1. Remove conflicting stock fantasy modules & bake all 13 DCC PHP 8.4/8.5 modules
RUN rm -f /var/www/html/modules/raceelf.php \
          /var/www/html/modules/racedwarf.php \
          /var/www/html/modules/racetroll.php \
          /var/www/html/modules/racehuman.php \
          /var/www/html/modules/oldman.php \
          /var/www/html/modules/statue.php \
          /var/www/html/modules/fairy.php \
          /var/www/html/modules/specialtydarkarts.php \
          /var/www/html/modules/specialtymysticpower.php \
          /var/www/html/modules/specialtythiefskills.php
COPY modules/*.php /var/www/html/modules/
COPY pages/petition/petition_faq*.php /var/www/html/pages/petition/

# 2. Bake SQL lore packs & helper script for post-install / upgrade seeding
COPY sql/ /opt/lotgd-dcc/sql/
COPY migrations/Version20261119000100.php /var/www/html/migrations/Version20261119000100.php
COPY docker/apply-dcc-sql.sh /usr/local/bin/apply-dcc-sql
RUN chmod +x /usr/local/bin/apply-dcc-sql

# 3. Build & bake dcc_hud and dcc_hud_minimal Twig themes from aurora base
COPY themes/dcc_hud/config.json /tmp/dcc_themes/dcc_hud/config.json
COPY themes/dcc_hud/dcc_syndicate_hud.css /tmp/dcc_themes/dcc_hud/dcc_syndicate_hud.css
COPY themes/dcc_hud_minimal/config.json /tmp/dcc_themes/dcc_hud_minimal/config.json
COPY themes/dcc_hud_minimal/dcc_syndicate_hud_minimal.css /tmp/dcc_themes/dcc_hud_minimal/dcc_syndicate_hud_minimal.css
COPY themes/dcc_hud_mobile/config.json /tmp/dcc_themes/dcc_hud_mobile/config.json
COPY themes/dcc_hud_mobile/dcc_syndicate_hud_mobile.css /tmp/dcc_themes/dcc_hud_mobile/dcc_syndicate_hud_mobile.css

RUN set -eux; \
    for theme in dcc_hud dcc_hud_minimal dcc_hud_mobile; do \
        rm -rf "/var/www/html/templates_twig/${theme}"; \
        if [ -d "/var/www/html/templates_twig/aurora_minimal" ] && [ "${theme}" = "dcc_hud_minimal" ] || [ "${theme}" = "dcc_hud_mobile" ]; then \
            cp -a "/var/www/html/templates_twig/aurora_minimal" "/var/www/html/templates_twig/${theme}"; \
            sed -i "s|templates_twig/aurora_minimal|templates_twig/${theme}|g" "/var/www/html/templates_twig/${theme}/"*.twig; \
        else \
            cp -a "/var/www/html/templates_twig/aurora" "/var/www/html/templates_twig/${theme}"; \
            sed -i "s|templates_twig/aurora|templates_twig/${theme}|g" "/var/www/html/templates_twig/${theme}/"*.twig; \
        fi; \
    done; \
    sed -i 's|<div class="login-hero"></div>||g' \
        /var/www/html/templates_twig/dcc_hud/login*.twig \
        /var/www/html/templates_twig/dcc_hud_minimal/login*.twig /var/www/html/templates_twig/dcc_hud_mobile/login*.twig; \
    sed -i -E 's~<img[^>]*nav-(top|bottom)\.png[^>]*>~~g' \
        /var/www/html/templates_twig/dcc_hud_minimal/page.twig /var/www/html/templates_twig/dcc_hud_mobile/page.twig; \
    sed -i -E 's|<img[^>]*title-logo\.png[^>]*>|<div style="color:#00f0ff;font-family:Consolas,monospace;font-weight:900;font-size:1.25rem;letter-spacing:1.5px;">&#9679; DUNGEON CRAWLER WORLD</div>|g' \
        /var/www/html/templates_twig/dcc_hud_minimal/page.twig /var/www/html/templates_twig/dcc_hud_mobile/page.twig; \
    cp /tmp/dcc_themes/dcc_hud/config.json /var/www/html/templates_twig/dcc_hud/config.json; \
    cp /tmp/dcc_themes/dcc_hud/dcc_syndicate_hud.css /var/www/html/templates_twig/dcc_hud/assets/dcc_syndicate_hud.css; \
    cat /tmp/dcc_themes/dcc_hud/dcc_syndicate_hud.css >> /var/www/html/templates_twig/dcc_hud/assets/style.css; \
    cp /tmp/dcc_themes/dcc_hud_minimal/config.json /var/www/html/templates_twig/dcc_hud_minimal/config.json; \
    cp /tmp/dcc_themes/dcc_hud_minimal/dcc_syndicate_hud_minimal.css /var/www/html/templates_twig/dcc_hud_minimal/assets/dcc_syndicate_hud_minimal.css; \
    cat /tmp/dcc_themes/dcc_hud_minimal/dcc_syndicate_hud_minimal.css >> /var/www/html/templates_twig/dcc_hud_minimal/assets/style.css; \
    cp /tmp/dcc_themes/dcc_hud_mobile/config.json /var/www/html/templates_twig/dcc_hud_mobile/config.json; \
    cp /tmp/dcc_themes/dcc_hud_mobile/dcc_syndicate_hud_mobile.css /var/www/html/templates_twig/dcc_hud_mobile/assets/dcc_syndicate_hud_mobile.css; \
    cat /tmp/dcc_themes/dcc_hud_mobile/dcc_syndicate_hud_mobile.css >> /var/www/html/templates_twig/dcc_hud_mobile/assets/style.css; \
    printf '.login-hero{display:none!important;height:0!important;min-height:0!important;padding:0!important;margin:0!important;}.page-header{min-height:0!important;height:auto!important;padding:10px 16px!important;background-image:none!important;align-items:center!important;}\n' \
        >> /var/www/html/templates_twig/dcc_hud_minimal/assets/style.css; \
    php -r '$r = "<footer class=\"bootstrap-footer row\"><div class=\"col-12\"><div class=\"dcc-mod-credit\">&#9679; Dungeon Crawler World: Earth &mdash; Total Conversion by <a href=\"https://github.com/machineh3d/lotgd-dcc\" target=\"_blank\" rel=\"noopener\">Devin Falconer (machineh3d)</a></div><div class=\"dcc-engine-meta\">{{ copyright|raw }} &middot; {{ version }} &middot; {{ pagegen|raw }}</div></div></footer>"; foreach (["dcc_hud","dcc_hud_minimal","dcc_hud_mobile"] as $t) { $f = "/var/www/html/templates_twig/$t/page.twig"; if (is_file($f)) { file_put_contents($f, preg_replace("~<footer class=\"bootstrap-footer row\">.*?</footer>~s", $r, file_get_contents($f))); } $pf = "/var/www/html/templates_twig/$t/popup.twig"; if (is_file($pf)) { file_put_contents($pf, str_replace("{{ copyright|raw }}", "<div class=\"dcc-mod-credit\">&#9679; Dungeon Crawler World: Earth &mdash; Total Conversion by <a href=\"https://github.com/machineh3d/lotgd-dcc\" target=\"_blank\" rel=\"noopener\">Devin Falconer (machineh3d)</a></div>{{ copyright|raw }}", file_get_contents($pf))); } }'; \
    rm -rf /tmp/dcc_themes; \
    chown -R www-data:www-data /var/www/html/modules /var/www/html/templates_twig/dcc_hud /var/www/html/templates_twig/dcc_hud_minimal /var/www/html/templates_twig/dcc_hud_mobile; \
    chmod 0644 /var/www/html/modules/*.php; \
    rm -rf /var/www/html/cache/* /var/www/html/datacache/* /var/www/html/data/cache/* 2>/dev/null || true
