<?php

declare(strict_types=1);

/**
 * Dungeon Crawler World: Earth (lotgd-dcc) — Native Android JSON API Bridge (v0.4.0)
 *
 * Loaded via PHP auto_prepend_file (/etc/php/8.4/apache2/conf.d/99-dcc-api.ini).
 * - Regular browser requests pass through untouched with zero overhead.
 * - Requests with header `X-DCC-Client: 1` (or `?dcc_json=1`) receive structured
 *   JSON including exact HP, XP progress to next trainer challenge, Stamina,
 *   Gold Credits, Sponsor Tokens, next new-day countdown seconds, grouped navs,
 *   structured vendor/data tables, interactive forms, and Syndicate Comms counts.
 */

if (PHP_SAPI === 'cli') {
    return;
}

$dccIsApiClient = (
    (isset($_SERVER['HTTP_X_DCC_CLIENT']) && $_SERVER['HTTP_X_DCC_CLIENT'] === '1')
    || (isset($_GET['dcc_json']) && $_GET['dcc_json'] === '1')
);

if (!$dccIsApiClient) {
    return;
}

if (!defined('DCC_API_BRIDGE_ACTIVE')) {
    define('DCC_API_BRIDGE_ACTIVE', true);
    @ini_set('zlib.output_compression', '0');
    ob_start('dcc_api_transform_output');
}

/**
 * @return array<string, string>
 */
function dcc_api_color_map(): array
{
    return [
        'colDkBlue'    => '#3B82F6',
        'colDkGreen'   => '#22C55E',
        'colDkCyan'    => '#06B6D4',
        'colDkRed'     => '#EF4444',
        'colDkMagenta' => '#D946EF',
        'colDkYellow'  => '#EAB308',
        'colDkWhite'   => '#CBD5E1',
        'colDkBlack'   => '#64748B',
        'colDkOrange'  => '#F97316',
        'colDkPink'    => '#EC4899',
        'colLtBlue'    => '#60A5FA',
        'colLtGreen'   => '#4ADE80',
        'colLtCyan'    => '#22D3EE',
        'colLtRed'     => '#F87171',
        'colLtMagenta' => '#E879F9',
        'colLtYellow'  => '#FACC15',
        'colLtWhite'   => '#F8FAFC',
        'colLtBlack'   => '#94A3B8',
        'colLtOrange'  => '#FB923C',
        'colLtPink'    => '#F472B6',
        'colMdGrey'    => '#94A3B8',
        'colLtGrey'    => '#CBD5E1',
        'colMdBlue'    => '#60A5FA',
        'colYellow'    => '#FACC15',
        'colGold'      => '#F59E0B',
        'colPurple'    => '#C084FC',
        'colViolet'    => '#A78BFA',
        'colTeal'      => '#2DD4BF',
        'colCrimson'   => '#FB7185',
    ];
}

function dcc_api_strip_lotgd_codes(string $text): string
{
    return trim((string) preg_replace('/`./u', '', $text));
}

function dcc_api_clean_text(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\u{00A0}", ' ', $text);
    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function dcc_api_resolve_color(string $classAttr): ?string
{
    if ($classAttr === '') {
        return null;
    }
    $map = dcc_api_color_map();
    foreach (preg_split('/\s+/', trim($classAttr)) ?: [] as $cls) {
        if (isset($map[$cls])) {
            return $map[$cls];
        }
    }
    return null;
}

function dcc_api_exp_for_level(int $level, int $dks): int
{
    if ($level <= 0) {
        return 0;
    }
    if (class_exists('\\Lotgd\\PlayerFunctions') && method_exists('\\Lotgd\\PlayerFunctions', 'expForNextLevel')) {
        try {
            return max(0, (int) round((float) \Lotgd\PlayerFunctions::expForNextLevel($level, $dks)));
        } catch (\Throwable $e) {
            // Fall through
        }
    }
    $base = [0, 100, 400, 1002, 1912, 3140, 4707, 6641, 8985, 11795, 15143, 19121, 23840, 29437, 36071, 43930];
    $b = $base[$level] ?? ($level * $level * 200);
    return (int) round($b + ($dks * 25 * $level));
}

/**
 * Computes real-time seconds until the next in-game day and the length of one
 * in-game day in real-time seconds, using NB-Core's Settings/DateTime classes.
 *
 * @return array{next: int, perDay: int}
 */
function dcc_api_new_day_timing(string $plainTextFallback = ''): array
{
    $daysPerDay = 4;
    $gameOffset = 0;
    if (class_exists('\\Lotgd\\Settings') && method_exists('\\Lotgd\\Settings', 'getInstance')) {
        try {
            $s = \Lotgd\Settings::getInstance();
            $daysPerDay = max(1, (int) $s->getSetting('daysperday', 4));
            $gameOffset = (int) $s->getSetting('gameoffsetseconds', 0);
        } catch (\Throwable $e) {
            // Keep defaults
        }
    }

    $perDay = (int) round(86400 / $daysPerDay);
    $next = 0;

    if (class_exists('\\Lotgd\\DateTime') && method_exists('\\Lotgd\\DateTime', 'secondsToNextGameDay')) {
        try {
            $rawNext = (int) \Lotgd\DateTime::secondsToNextGameDay(); $next = ($rawNext > $perDay) ? (($rawNext > time()) ? ($rawNext - time()) : 0) : max(0, $rawNext);
        } catch (\Throwable $e) {
            $next = 0;
        }
    }

    if ($next <= 0 && function_exists('secondstonextgameday')) {
        try {
            $next = max(0, (int) secondstonextgameday());
        } catch (\Throwable $e) {
            $next = 0;
        }
    }

    // Fallback: parse "Next new game day in: Xh, Ym, Zs" if present on page
    if ($next <= 0 && $plainTextFallback !== '' && preg_match('/(\d+)h,\s*(\d+)m,\s*(\d+)s/i', $plainTextFallback, $m)) {
        $next = ((int) $m[1] * 3600) + ((int) $m[2] * 60) + (int) $m[3];
    }

    // Deterministic LotGD fallback using daysperday + gameoffsetseconds
    if ($next <= 0 && $perDay > 0) {
        $now = time() + $gameOffset;
        $elapsed = $now % $perDay;
        $next = max(1, $perDay - $elapsed);
    }

    if ($next > $perDay && $perDay > 0) { $next = ($next % $perDay) ?: $perDay; } return ['next' => $next, 'perDay' => $perDay];
}

function dcc_api_is_data_table(DOMXPath $xpath, DOMElement $table): bool
{
    $nestedForms = $xpath->query('.//form', $table);
    if ($nestedForms !== false && $nestedForms->length > 0) {
        return false;
    }

    $rows = $xpath->query('.//tr', $table);
    if ($rows === false || $rows->length < 2) {
        return false;
    }

    $multiColRows = 0;
    foreach ($rows as $row) {
        if (!($row instanceof DOMElement)) {
            continue;
        }
        $cells = $xpath->query('./td | ./th', $row);
        if ($cells !== false && $cells->length >= 2) {
            $multiColRows++;
        }
    }

    return $multiColRows >= 2;
}

/**
 * @return list<array<string, mixed>>
 */
function dcc_api_extract_tables(DOMXPath $xpath, DOMNode $scope): array
{
    $tables = [];
    $tableNodes = $xpath->query('.//table', $scope);
    if ($tableNodes === false) {
        return $tables;
    }

    foreach ($tableNodes as $tableEl) {
        if (!($tableEl instanceof DOMElement) || !dcc_api_is_data_table($xpath, $tableEl)) {
            continue;
        }

        $rows = $xpath->query('.//tr', $tableEl);
        if ($rows === false) {
            continue;
        }

        $headers = [];
        $dataRows = [];

        foreach ($rows as $rowIdx => $rowEl) {
            if (!($rowEl instanceof DOMElement)) {
                continue;
            }
            $cells = $xpath->query('./td | ./th', $rowEl);
            if ($cells === false || $cells->length < 2) {
                continue;
            }

            $rowClass = strtolower($rowEl->getAttribute('class'));
            $isHeaderRow = ($rowIdx === 0) && (
                str_contains($rowClass, 'trhead')
                || $xpath->query('./th', $rowEl)->length > 0
                || $xpath->query('./td/b | ./td/strong', $rowEl)->length >= 2
            );

            $cellTexts = [];
            $actionUrl = null;
            $actionLabel = null;

            foreach ($cells as $cell) {
                if (!($cell instanceof DOMElement)) {
                    continue;
                }
                $txt = dcc_api_clean_text($cell->textContent);
                $cellTexts[] = $txt;

                if (!$isHeaderRow && $actionUrl === null) {
                    $links = $xpath->query('.//a[@href]', $cell);
                    if ($links !== false && $links->length > 0 && $links->item(0) instanceof DOMElement) {
                        $href = trim($links->item(0)->getAttribute('href'));
                        if ($href !== '' && $href !== '#' && !str_starts_with(strtolower($href), 'javascript:')) {
                            $actionUrl = $href;
                            $lbl = dcc_api_clean_text($links->item(0)->textContent);
                            $actionLabel = $lbl !== '' ? $lbl : null;
                        }
                    }
                }
            }

            if ($isHeaderRow) {
                $headers = $cellTexts;
            } else {
                if (implode('', $cellTexts) === '') {
                    continue;
                }
                $dataRows[] = [
                    'cells'       => $cellTexts,
                    'actionUrl'   => $actionUrl,
                    'actionLabel' => $actionLabel,
                ];
            }
        }

        if (count($dataRows) > 0) {
            $tables[] = [
                'headers' => $headers,
                'rows'    => $dataRows,
            ];
        }
    }

    return $tables;
}

/**
 * @param list<list<array<string, mixed>>> $paragraphs
 * @param list<array<string, mixed>>       $currentLine
 */
function dcc_api_collect_spans(
    DOMXPath $xpath,
    DOMNode $node,
    array &$paragraphs,
    array &$currentLine,
    ?string $color = null,
    bool $bold = false,
    bool $italic = false,
    ?string $link = null
): void {
    if ($node instanceof DOMText) {
        $raw = html_entity_decode($node->wholeText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $raw = (string) preg_replace('/[ \t]+/', ' ', $raw);
        if (trim($raw) === '' && $raw !== ' ') {
            return;
        }
        $parts = explode("\n", $raw);
        foreach ($parts as $idx => $part) {
            if ($idx > 0 && count($currentLine) > 0) {
                $paragraphs[] = $currentLine;
                $currentLine = [];
            }
            if ($part !== '') {
                $currentLine[] = [
                    'text'   => $part,
                    'color'  => $color,
                    'bold'   => $bold,
                    'italic' => $italic,
                    'link'   => $link,
                ];
            }
        }
        return;
    }

    if (!($node instanceof DOMElement)) {
        return;
    }

    $tag = strtolower($node->tagName);

    if (in_array($tag, ['script', 'style', 'noscript', 'form', 'svg', 'iframe'], true)) {
        return;
    }

    if ($tag === 'table' && dcc_api_is_data_table($xpath, $node)) {
        return;
    }

    if ($tag === 'br' || $tag === 'hr') {
        if (count($currentLine) > 0) {
            $paragraphs[] = $currentLine;
            $currentLine = [];
        }
        return;
    }

    $isBlock = in_array($tag, ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'li', 'tr', 'blockquote'], true);
    if ($isBlock && count($currentLine) > 0) {
        $paragraphs[] = $currentLine;
        $currentLine = [];
    }

    $nextColor = $color;
    $classAttr = $node->getAttribute('class');
    $resolved = dcc_api_resolve_color($classAttr);
    if ($resolved !== null) {
        $nextColor = $resolved;
    }

    $nextBold = $bold || in_array($tag, ['b', 'strong', 'h1', 'h2', 'h3', 'h4', 'th'], true);
    $nextItalic = $italic || in_array($tag, ['i', 'em'], true);
    $nextLink = $link;
    if ($tag === 'a') {
        $href = trim($node->getAttribute('href'));
        if ($href !== '' && !str_starts_with(strtolower($href), 'javascript:')) {
            $nextLink = $href;
        }
    }

    foreach ($node->childNodes as $child) {
        dcc_api_collect_spans($xpath, $child, $paragraphs, $currentLine, $nextColor, $nextBold, $nextItalic, $nextLink);
        if ($tag === 'td' || $tag === 'th') {
            $currentLine[] = [
                'text'   => '  ',
                'color'  => $nextColor,
                'bold'   => false,
                'italic' => false,
                'link'   => null,
            ];
        }
    }

    if ($isBlock && count($currentLine) > 0) {
        $paragraphs[] = $currentLine;
        $currentLine = [];
    }
}

/**
 * @return list<array<string, mixed>>
 */
function dcc_api_extract_forms(DOMXPath $xpath, DOMNode $scope): array
{
    $forms = [];
    $formNodes = $xpath->query('.//form', $scope);
    if ($formNodes === false) {
        return $forms;
    }

    foreach ($formNodes as $idx => $formEl) {
        if (!($formEl instanceof DOMElement)) {
            continue;
        }

        $action = trim($formEl->getAttribute('action'));
        if (str_contains(strtolower($action), 'paypal.com')) {
            continue;
        }

        $method = strtoupper(trim($formEl->getAttribute('method') ?: 'POST'));
        $fields = [];
        $submitLabel = 'Submit';

        $controls = $xpath->query('.//input | .//textarea | .//select | .//button', $formEl);
        if ($controls !== false) {
            foreach ($controls as $ctrl) {
                if (!($ctrl instanceof DOMElement)) {
                    continue;
                }
                $tag = strtolower($ctrl->tagName);
                $type = $tag === 'input'
                    ? strtolower(trim($ctrl->getAttribute('type') ?: 'text'))
                    : $tag;
                $name = trim($ctrl->getAttribute('name'));
                $value = $ctrl->getAttribute('value');
                $placeholder = trim($ctrl->getAttribute('placeholder'));

                if (in_array($type, ['submit', 'button', 'image', 'reset'], true) || $tag === 'button') {
                    if ($type === 'reset') {
                        continue;
                    }
                    $btnText = dcc_api_clean_text($tag === 'button' ? $ctrl->textContent : ($value !== '' ? $value : 'Submit'));
                    if ($btnText !== '') {
                        $submitLabel = $btnText;
                    }
                    if ($name !== '' && $type !== 'image') {
                        $fields[] = [
                            'name'        => $name,
                            'type'        => 'submit',
                            'value'       => $value !== '' ? $value : $btnText,
                            'label'       => $btnText,
                            'placeholder' => '',
                            'checked'     => false,
                            'options'     => [],
                        ];
                    }
                    continue;
                }

                if ($name === '') {
                    continue;
                }

                $label = '';
                $id = trim($ctrl->getAttribute('id'));
                if ($id !== '') {
                    $labelNode = $xpath->query(".//label[@for='" . addslashes($id) . "']", $formEl);
                    if ($labelNode !== false && $labelNode->length > 0 && $labelNode->item(0) !== null) {
                        $label = dcc_api_clean_text($labelNode->item(0)->textContent);
                    }
                }
                if ($label === '' && $placeholder !== '') {
                    $label = $placeholder;
                }
                if ($label === '') {
                    $label = ucfirst(str_replace(['_', '-'], ' ', $name));
                }

                $options = [];
                if ($tag === 'select') {
                    $optNodes = $xpath->query('.//option', $ctrl);
                    if ($optNodes !== false) {
                        foreach ($optNodes as $opt) {
                            if (!($opt instanceof DOMElement)) {
                                continue;
                            }
                            $optVal = $opt->hasAttribute('value') ? $opt->getAttribute('value') : dcc_api_clean_text($opt->textContent);
                            $optLbl = dcc_api_clean_text($opt->textContent);
                            $options[] = [
                                'value'    => $optVal,
                                'label'    => $optLbl,
                                'selected' => $opt->hasAttribute('selected'),
                            ];
                            if ($opt->hasAttribute('selected')) {
                                $value = $optVal;
                            }
                        }
                    }
                    if ($value === '' && count($options) > 0) {
                        $value = (string) $options[0]['value'];
                    }
                } elseif ($tag === 'textarea') {
                    $value = $ctrl->textContent;
                }

                $fields[] = [
                    'name'        => $name,
                    'type'        => $type,
                    'value'       => $value,
                    'label'       => $label,
                    'placeholder' => $placeholder,
                    'checked'     => $ctrl->hasAttribute('checked'),
                    'options'     => $options,
                ];
            }
        }

        $hasRealInput = false;
        foreach ($fields as $fld) {
            if (in_array($fld['type'] ?? '', ['text', 'password', 'textarea', 'select', 'checkbox', 'number', 'email'], true)) {
                $hasRealInput = true;
                break;
            }
        }
        if (!$hasRealInput && strtolower($submitLabel) === 'submit') {
            continue;
        }

        if (count($fields) > 0 || $action !== '') {
            $forms[] = [
                'id'          => $formEl->getAttribute('id') ?: ('form_' . $idx),
                'action'      => $action,
                'method'      => $method,
                'submitLabel' => $submitLabel,
                'fields'      => $fields,
            ];
        }
    }

    return $forms;
}

/**
 * @return list<array<string, mixed>>
 */
function dcc_api_extract_nav_groups(DOMXPath $xpath): array
{
    $groups = [];
    $seenUrls = [];

    $scope = null;
    foreach (['//*[@id="leftSidebar"]', '//aside[@aria-label="Primary"]', '//aside[contains(@class,"sidebar")][1]'] as $q) {
        $res = $xpath->query($q);
        if ($res !== false && $res->length > 0) {
            $scope = $res->item(0);
            break;
        }
    }

    if ($scope === null) {
        return $groups;
    }

    $currentHeader = 'Actions';
    $currentItems = [];

    $nodes = $xpath->query('.//*[contains(@class,"navhead") or self::h4 or self::h5 or self::div[contains(@class,"card-header")]] | .//a[@href]', $scope);
    if ($nodes === false) {
        return $groups;
    }

    foreach ($nodes as $el) {
        if (!($el instanceof DOMElement)) {
            continue;
        }
        $tag = strtolower($el->tagName);
        if ($tag !== 'a') {
            $hdr = dcc_api_clean_text($el->textContent);
            if ($hdr !== '' && strtolower($hdr) !== 'menu') {
                if (count($currentItems) > 0) {
                    $groups[] = ['header' => $currentHeader, 'items' => $currentItems];
                    $currentItems = [];
                }
                $currentHeader = $hdr;
            }
            continue;
        }

        $href = trim($el->getAttribute('href'));
        if ($href === '' || $href === '#' || str_starts_with(strtolower($href), 'javascript:')) {
            continue;
        }

        $label = dcc_api_clean_text($el->textContent);
        if ($label === '' || isset($seenUrls[$href . '|' . $label])) {
            continue;
        }
        $seenUrls[$href . '|' . $label] = true;

        $hotkey = '';
        $accessKey = trim($el->getAttribute('accesskey'));
        if ($accessKey !== '') {
            $hotkey = strtoupper($accessKey);
        }

        $currentItems[] = [
            'label'  => $label,
            'url'    => $href,
            'hotkey' => $hotkey,
        ];
    }

    if (count($currentItems) > 0) {
        $groups[] = ['header' => $currentHeader, 'items' => $currentItems];
    }

    return $groups;
}

/**
 * @return list<array<string, mixed>>
 */
function dcc_api_extract_char_stats(DOMXPath $xpath): array
{
    $sections = [];
    $containers = $xpath->query('//*[@id="rightSidebar"] | //aside[@aria-label="Secondary"]');
    $scope = ($containers !== false && $containers->length > 0) ? $containers->item(0) : null;
    if ($scope === null) {
        return $sections;
    }

    $currentSection = 'Crawler HUD';
    $currentItems = [];

    $rows = $xpath->query('.//tr | .//*[contains(@class,"charhead")]', $scope);
    if ($rows === false) {
        return $sections;
    }

    foreach ($rows as $row) {
        if (!($row instanceof DOMElement)) {
            continue;
        }
        $class = $row->getAttribute('class');
        if (str_contains($class, 'charhead')) {
            $sec = dcc_api_clean_text($row->textContent);
            if ($sec !== '') {
                if (count($currentItems) > 0) {
                    $sections[] = ['section' => $currentSection, 'items' => $currentItems];
                    $currentItems = [];
                }
                $currentSection = $sec;
            }
            continue;
        }

        $cells = $xpath->query('./td | ./th', $row);
        if ($cells === false) {
            continue;
        }
        if ($cells->length === 1 && $cells->item(0) !== null) {
            $sec = dcc_api_clean_text($cells->item(0)->textContent);
            if ($sec !== '' && strlen($sec) < 48) {
                if (count($currentItems) > 0) {
                    $sections[] = ['section' => $currentSection, 'items' => $currentItems];
                    $currentItems = [];
                }
                $currentSection = $sec;
            }
        } elseif ($cells->length >= 2 && $cells->item(0) !== null && $cells->item(1) !== null) {
            $k = dcc_api_clean_text($cells->item(0)->textContent);
            $v = dcc_api_clean_text($cells->item(1)->textContent);
            if ($k !== '' || $v !== '') {
                $currentItems[] = ['label' => rtrim($k, ':'), 'value' => $v];
            }
        }
    }

    if (count($currentItems) > 0) {
        $sections[] = ['section' => $currentSection, 'items' => $currentItems];
    }

    return $sections;
}

/**
 * @return array<string, mixed>
 */
function dcc_api_extract_mail(DOMXPath $xpath): array
{
    $mail = [
        'unread' => 0,
        'read'   => 0,
        'label'  => 'Syndicate Comms',
        'url'    => 'mail.php',
    ];

    $nodes = $xpath->query('//a[contains(@href,"mail.php")]');
    if ($nodes !== false && $nodes->length > 0 && $nodes->item(0) instanceof DOMElement) {
        $el = $nodes->item(0);
        $text = dcc_api_clean_text($el->textContent);
        $mail['label'] = $text !== '' ? $text : 'Syndicate Comms';
        $mail['url'] = trim($el->getAttribute('href')) ?: 'mail.php';
        if (preg_match('/(\d+)\s*(?:unread|new)/i', $text, $m)) {
            $mail['unread'] = (int) $m[1];
        }
        if (preg_match('/(\d+)\s*(?:archived|old)/i', $text, $m)) {
            $mail['read'] = (int) $m[1];
        }
    }

    return $mail;
}

/**
 * @param list<array<string, mixed>> $charStats
 * @return array<string, mixed>|null
 */
function dcc_api_extract_player(array $charStats): ?array
{
    $session = $GLOBALS['session'] ?? null;
    if (!is_array($session) || empty($session['loggedin']) || !isset($session['user']) || !is_array($session['user'])) {
        return null;
    }

    $u = $session['user'];
    $title = trim((string) (($u['ctitle'] ?? '') !== '' ? $u['ctitle'] : ($u['title'] ?? '')));
    $name = dcc_api_strip_lotgd_codes((string) ($u['name'] ?? ($u['login'] ?? 'Crawler')));
    if ($title !== '' && str_starts_with($name, $title . ' ')) {
        $name = ltrim(substr($name, strlen($title)));
    }

    $dk = (int) ($u['dragonkills'] ?? 0);
    $level = max(1, (int) ($u['level'] ?? 1));
    $maxLevel = 15;
    if (class_exists('\\Lotgd\\Settings') && method_exists('\\Lotgd\\Settings', 'getInstance')) {
        try {
            $maxLevel = (int) \Lotgd\Settings::getInstance()->getSetting('maxlevel', 15);
        } catch (\Throwable $e) {
            $maxLevel = 15;
        }
    }

    $player = [
        'loggedIn'        => true,
        'acctId'          => (int) ($u['acctid'] ?? 0),
        'login'           => (string) ($u['login'] ?? ''),
        'title'           => dcc_api_strip_lotgd_codes($title),
        'name'            => $name,
        'level'           => $level,
        'maxLevel'        => max(1, $maxLevel),
        'floor'           => $dk + 1,
        'dragonKills'     => $dk,
        'hp'              => (int) ($u['hitpoints'] ?? 0),
        'maxHp'           => max(1, (int) ($u['maxhitpoints'] ?? 1)),
        'turns'           => (int) ($u['turns'] ?? 0),
        'gold'            => (int) ($u['gold'] ?? 0),
        'goldInBank'      => (int) ($u['goldinbank'] ?? 0),
        'gems'            => (int) ($u['gems'] ?? 0),
        'experience'      => (int) ($u['experience'] ?? 0),
        'expCurrentLevel' => 0,
        'expNextLevel'    => 100,
        'alive'           => (bool) ($u['alive'] ?? true),
        'location'        => (string) ($u['location'] ?? 'Safe Room'),
        'race'            => (string) ($u['race'] ?? ''),
        'specialty'       => (string) ($u['specialty'] ?? ''),
        'attack'          => (int) ($u['attack'] ?? 0),
        'defense'         => (int) ($u['defense'] ?? 0),
        'isSuperuser'     => ((int) ($u['superuser'] ?? 0)) > 0,
    ];

    if (count($charStats) > 0) {
        $flat = [];
        foreach ($charStats as $sec) {
            foreach ($sec['items'] ?? [] as $it) {
                $flat[strtolower(trim((string) ($it['label'] ?? '')))] = trim((string) ($it['value'] ?? ''));
            }
        }
        if ($player['maxHp'] <= 1) {
            if (isset($flat['hitpoints']) && preg_match('/(\d+)\s*\/\s*(\d+)/', str_replace(',', '', $flat['hitpoints']), $m)) {
                $player['hp'] = (int) $m[1];
                $player['maxHp'] = max(1, (int) $m[2]);
            }
            foreach (['turns', 'stamina', 'forest fights'] as $k) {
                if (isset($flat[$k]) && preg_match('/(\d+)/', str_replace(',', '', $flat[$k]), $m)) {
                    $player['turns'] = (int) $m[1];
                    break;
                }
            }
            foreach (['gold credits', 'gold'] as $k) {
                if (isset($flat[$k]) && preg_match('/(\d+)/', str_replace(',', '', $flat[$k]), $m)) {
                    $player['gold'] = (int) $m[1];
                    break;
                }
            }
            foreach (['sponsor tokens', 'gems'] as $k) {
                if (isset($flat[$k]) && preg_match('/(\d+)/', str_replace(',', '', $flat[$k]), $m)) {
                    $player['gems'] = (int) $m[1];
                    break;
                }
            }
            if (isset($flat['level']) && preg_match('/(\d+)/', $flat['level'], $m)) {
                $player['level'] = max(1, (int) $m[1]);
            }
            if (isset($flat['title']) && $flat['title'] !== '') {
                $player['title'] = $flat['title'];
            }
            if (isset($flat['name']) && $flat['name'] !== '') {
                $player['name'] = $flat['name'];
            }
        }
        if ($player['experience'] <= 0 && isset($flat['experience']) && preg_match('/(\d+)/', str_replace(',', '', $flat['experience']), $m)) {
            $player['experience'] = (int) $m[1];
        }
    }

    $curLvl = (int) $player['level'];
    $player['expCurrentLevel'] = $curLvl <= 1 ? 0 : dcc_api_exp_for_level($curLvl - 1, $dk);
    $player['expNextLevel'] = max(1, dcc_api_exp_for_level($curLvl, $dk));

    return $player;
}

/**
 * @param list<array<string, mixed>> $navGroups
 * @param array<string, mixed>|null  $player
 */
function dcc_api_detect_page_type(string $uri, string $title, array $navGroups, ?array $player): string
{
    $lowerUri = strtolower($uri);
    $lowerTitle = strtolower($title);

    if (str_contains($lowerTitle, 'bad nav') || (str_contains($lowerUri, 'badnav.php') && count($navGroups) === 0)) {
        return 'badnav';
    }
    if ($player === null || str_contains($lowerUri, 'home.php') || str_contains($lowerUri, 'index.php')) {
        return 'login';
    }
    if (str_contains($lowerUri, 'mail.php')) {
        return 'mail';
    }
    if (!($player['alive'] ?? true) || str_contains($lowerUri, 'shades.php') || str_contains($lowerUri, 'graveyard.php')) {
        return 'dead';
    }
    if (str_contains($lowerUri, 'village.php')) {
        return 'village';
    }

    foreach ($navGroups as $g) {
        foreach ($g['items'] ?? [] as $item) {
            $u = strtolower((string) ($item['url'] ?? ''));
            if (str_contains($u, 'op=fight') || str_contains($u, 'op=run') || str_contains($u, 'op=auto')) {
                return 'combat';
            }
        }
    }

    return 'general';
}

function dcc_api_transform_output(string $buffer): string
{
    $code = http_response_code();
    if ($code >= 300 && $code < 400) {
        return $buffer;
    }

    if (str_starts_with($buffer, "\x1f\x8b")) {
        $dec = @gzdecode($buffer);
        if (is_string($dec)) {
            $buffer = $dec;
        }
    }

    $trimmed = ltrim($buffer);
    if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
        return $buffer;
    }

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $cleanBuf = (string) preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $buffer);

    libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $html = '<?xml encoding="UTF-8">' . $cleanBuf;
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $title = 'Dungeon Crawler World: Earth';
    $titleNodes = $xpath->query('//title');
    if ($titleNodes !== false && $titleNodes->length > 0 && $titleNodes->item(0) !== null) {
        $t = dcc_api_clean_text($titleNodes->item(0)->textContent);
        if ($t !== '') {
            $title = $t;
        }
    }

    $mainNodes = $xpath->query('//main | //*[@id="main-content"] | //td[contains(@class,"content")]');
    $mainScope = ($mainNodes !== false && $mainNodes->length > 0)
        ? $mainNodes->item(0)
        : $dom->getElementsByTagName('body')->item(0);

    $paragraphs = [];
    $currentLine = [];
    $forms = [];
    $tables = [];
    $plainFallback = '';
    if ($mainScope !== null) {
        $plainFallback = $mainScope->textContent ?? '';
        $tables = dcc_api_extract_tables($xpath, $mainScope);
        dcc_api_collect_spans($xpath, $mainScope, $paragraphs, $currentLine);
        if (count($currentLine) > 0) {
            $paragraphs[] = $currentLine;
        }
        $forms = dcc_api_extract_forms($xpath, $mainScope);
    }

    if (count($forms) === 0 && $dom->getElementsByTagName('body')->item(0) !== null) {
        $forms = dcc_api_extract_forms($xpath, $dom->getElementsByTagName('body')->item(0));
    }

    $navGroups = dcc_api_extract_nav_groups($xpath);
    $charStats = dcc_api_extract_char_stats($xpath);
    $mail = dcc_api_extract_mail($xpath);
    $player = dcc_api_extract_player($charStats);
    $pageType = dcc_api_detect_page_type($uri, $title, $navGroups, $player);
    $timing = dcc_api_new_day_timing($plainFallback);

    if ($pageType === 'combat') {
        $forms = array_values(array_filter($forms, static function (array $fm): bool {
            foreach ($fm['fields'] ?? [] as $fld) {
                if (in_array($fld['type'] ?? '', ['text', 'password', 'textarea', 'select', 'checkbox', 'number', 'email'], true)) {
                    return true;
                }
            }
            return false;
        }));
    }

    if (count($navGroups) === 0 && $mainScope !== null) {
        $inlineItems = [];
        $links = $xpath->query('.//a[@href]', $mainScope);
        if ($links !== false) {
            foreach ($links as $a) {
                if (!($a instanceof DOMElement)) {
                    continue;
                }
                $href = trim($a->getAttribute('href'));
                $lbl = dcc_api_clean_text($a->textContent);
                if ($href !== '' && $lbl !== '' && !str_starts_with(strtolower($href), 'javascript:')) {
                    $inlineItems[] = ['label' => $lbl, 'url' => $href, 'hotkey' => ''];
                }
            }
        }
        if (count($inlineItems) > 0) {
            $navGroups[] = ['header' => 'Links', 'items' => $inlineItems];
        }
    }

    $payload = [
        'apiVersion'        => 4,
        'ok'                => true,
        'currentUrl'        => $uri,
        'pageType'          => $pageType,
        'title'             => $title,
        'badnav'            => $pageType === 'badnav',
        'recoveryUrl'       => 'badnav.php',
        'nextNewDaySeconds' => $timing['next'],
        'secondsPerGameDay' => $timing['perDay'],
        'player'            => $player,
        'mail'              => $mail,
        'navGroups'         => $navGroups,
        'charStats'         => $charStats,
        'paragraphs'        => $paragraphs,
        'tables'            => $tables,
        'forms'             => $forms,
    ];

    if (!headers_sent()) {
        header_remove('Content-Encoding');
        header_remove('Content-Length');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('X-DCC-Bridge: 4');
    }

    return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
