<?php

declare(strict_types=1);

/**
 * Module: Borant Stamina Injector & Sponsor Token Exchange Vault (turnbank.php)
 * Reskinned from: Chronomancer Vault (turnbank.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Dungeon_Crawler_Carl_Wiki
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Category:Items
 *
 * Features:
 *   - Drop-in replacement for `turnbank.php` preserving module name `turnbank`.
 *   - Deposit and withdraw Floor Stamina (Turns) into Borant Stamina Injector Vials.
 *   - Enforces a rolling ISO weekly deposit cap (`weekly_deposit_cap`) and total vault cap (`max_vault_capacity`).
 *   - Configurable New Day Auto-Banking (`auto_bank` & `auto_bank_amount`) so Crawlers can
 *     automatically bottle Floor Stamina at the start of each broadcast cycle.
 *   - Two-way Sponsor Token (Gem) <-> Floor Stamina (Turn) synthesizer with daily exchange limits.
 */

function turnbank_getmoduleinfo(): array
{
    return [
        'name' => 'Borant Stamina Injector & Token Vault',
        'version' => '2.0.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Borant Stamina Vault Limits,title',
            'max_vault_capacity' => 'Maximum Floor Stamina (Turns) a Crawler can store in the Vault,int|100',
            'weekly_deposit_cap' => 'Maximum Floor Stamina a Crawler can deposit per week,int|50',
            'deposit_tax_percent' => 'Borant Cryo-Stabilization Tax on deposits (0-50%),range,0,50,5|10',
            'Sponsor Token (Gem) <-> Stamina Exchange,title',
            'turns_per_gem' => 'Floor Stamina granted when spending 1 Sponsor Token (Gem),int|5',
            'turns_cost_per_gem' => 'Floor Stamina required to synthesize 1 Sponsor Token (Gem),int|8',
            'max_exchanges_day' => 'Maximum Token/Stamina exchanges allowed per New Day cycle,int|5',
        ],
        'prefs' => [
            'Crawler Stamina Vault Ledger,title',
            'banked_turns' => 'Floor Stamina currently stored in Injector Vault,int|0',
            'weekly_deposited' => 'Floor Stamina deposited during the current ISO week,int|0',
            'week_key' => 'Current ISO year-week tracker (YYYY-WW),text|',
            'auto_bank' => 'Enable New Day Auto-Banking of Floor Stamina?,bool|0',
            'auto_bank_amount' => 'Number of Floor Stamina points to auto-bank on New Day (1-15),int|5',
            'exchanges_today' => 'Token/Stamina exchanges performed today,int|0',
        ],
    ];
}

function turnbank_install(): bool
{
    module_addhook('village');
    module_addhook('newday');
    module_addhook('charstats');
    return true;
}

function turnbank_uninstall(): bool
{
    return true;
}

/**
 * Ensures the Crawler's weekly deposit counter is synchronized to the current ISO week (`o-\WW`).
 */
function turnbank_sync_week(): string
{
    $currentWeek = date('o-\WW');
    $storedWeek = (string) get_module_pref('week_key', 'turnbank');

    if ($storedWeek !== $currentWeek) {
        set_module_pref('week_key', $currentWeek, 'turnbank');
        set_module_pref('weekly_deposited', 0, 'turnbank');
    }

    return $currentWeek;
}

/**
 * Returns the Crawler's complete vault capacity, weekly cap, and daily exchange telemetry.
 *
 * @return array{
 *   banked: int,
 *   max_vault: int,
 *   vault_space: int,
 *   weekly_used: int,
 *   weekly_cap: int,
 *   weekly_left: int,
 *   max_depositable: int,
 *   tax_pct: int,
 *   auto_bank: bool,
 *   auto_amt: int,
 *   ex_used: int,
 *   ex_max: int,
 *   ex_left: int,
 *   week_label: string
 * }
 */
function turnbank_get_status(): array
{
    $weekLabel = turnbank_sync_week();

    $banked = max(0, (int) get_module_pref('banked_turns', 'turnbank'));
    $maxVault = max(1, (int) get_module_setting('max_vault_capacity', 'turnbank'));
    $vaultSpace = max(0, $maxVault - $banked);

    $weeklyUsed = max(0, (int) get_module_pref('weekly_deposited', 'turnbank'));
    $weeklyCap = max(1, (int) get_module_setting('weekly_deposit_cap', 'turnbank'));
    $weeklyLeft = max(0, $weeklyCap - $weeklyUsed);

    $maxDepositable = min($vaultSpace, $weeklyLeft);
    $taxPct = max(0, min(50, (int) get_module_setting('deposit_tax_percent', 'turnbank')));

    $autoBank = ((int) get_module_pref('auto_bank', 'turnbank') === 1);
    $autoAmt = max(1, min(15, (int) get_module_pref('auto_bank_amount', 'turnbank')));

    $exUsed = max(0, (int) get_module_pref('exchanges_today', 'turnbank'));
    $exMax = max(1, (int) get_module_setting('max_exchanges_day', 'turnbank'));
    $exLeft = max(0, $exMax - $exUsed);

    return [
        'banked' => $banked,
        'max_vault' => $maxVault,
        'vault_space' => $vaultSpace,
        'weekly_used' => $weeklyUsed,
        'weekly_cap' => $weeklyCap,
        'weekly_left' => $weeklyLeft,
        'max_depositable' => $maxDepositable,
        'tax_pct' => $taxPct,
        'auto_bank' => $autoBank,
        'auto_amt' => $autoAmt,
        'ex_used' => $exUsed,
        'ex_max' => $exMax,
        'ex_left' => $exLeft,
        'week_label' => $weekLabel,
    ];
}

/**
 * Executes a Floor Stamina deposit into the Borant Injector Vault, applying weekly/vault caps and tax.
 *
 * @return array{ok: bool, spent: int, credited: int, tax: int, message: string}
 */
function turnbank_deposit_stamina(int $requestedTurns): array
{
    global $session;

    $status = turnbank_get_status();
    $userTurns = max(0, (int) ($session['user']['turns'] ?? 0));

    if ($requestedTurns <= 0) {
        return ['ok' => false, 'spent' => 0, 'credited' => 0, 'tax' => 0, 'message' => 'Specify at least 1 Floor Stamina point to bottle.'];
    }
    if ($userTurns <= 0) {
        return ['ok' => false, 'spent' => 0, 'credited' => 0, 'tax' => 0, 'message' => 'You have zero Floor Stamina left to deposit!'];
    }
    if ($status['vault_space'] <= 0) {
        return ['ok' => false, 'spent' => 0, 'credited' => 0, 'tax' => 0, 'message' => 'Your Borant Stamina Injector Rack is at maximum capacity!'];
    }
    if ($status['weekly_left'] <= 0) {
        return ['ok' => false, 'spent' => 0, 'credited' => 0, 'tax' => 0, 'message' => 'You have reached your Syndicate weekly Stamina deposit cap!'];
    }

    $actualSpent = min($requestedTurns, $userTurns);
    $tax = ($status['tax_pct'] > 0) ? (int) floor(($actualSpent * $status['tax_pct']) / 100) : 0;
    $netCredited = max(1, $actualSpent - $tax);

    // Clamp if net credited exceeds remaining weekly or vault room
    if ($netCredited > $status['max_depositable']) {
        $netCredited = $status['max_depositable'];
        $actualSpent = ($status['tax_pct'] > 0 && $status['tax_pct'] < 100)
            ? min($userTurns, (int) ceil($netCredited / (1.0 - ($status['tax_pct'] / 100.0))))
            : $netCredited;
        $tax = max(0, $actualSpent - $netCredited);
    }

    $session['user']['turns'] -= $actualSpent;
    set_module_pref('banked_turns', $status['banked'] + $netCredited, 'turnbank');
    set_module_pref('weekly_deposited', $status['weekly_used'] + $netCredited, 'turnbank');

    return [
        'ok' => true,
        'spent' => $actualSpent,
        'credited' => $netCredited,
        'tax' => $tax,
        'message' => sprintf(
            'Extracted `^%d Floor Stamina`2 (`$%d Borant Cryo-Tax`2) and sealed `@+%d Stamina`2 into your Injector Vault!',
            $actualSpent,
            $tax,
            $netCredited
        ),
    ];
}

function turnbank_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'village':
            $status = turnbank_get_status();
            tlschema($args['schemas']['marketnav'] ?? 'village');
            addnav($args['marketnav'] ?? 'Safe Room Facilities');
            tlschema();
            addnav(
                ['`@Borant Stamina Vault `0(`^%d`0/`@%d`0)', $status['banked'], $status['max_vault']],
                'runmodule.php?module=turnbank'
            );
            break;

        case 'newday':
            turnbank_sync_week();
            set_module_pref('exchanges_today', 0, 'turnbank');

            $status = turnbank_get_status();
            if ($status['auto_bank'] && $status['max_depositable'] > 0 && (int) ($session['user']['turns'] ?? 0) > 1) {
                // Always leave at least 1 turn on hand when auto-banking
                $availableForAuto = max(0, (int) $session['user']['turns'] - 1);
                $toDeposit = min($status['auto_amt'], $availableForAuto);
                if ($toDeposit > 0) {
                    $res = turnbank_deposit_stamina($toDeposit);
                    if ($res['ok']) {
                        output(
                            "`n`@[BORANT AUTO-CRYO INJECTOR]: `2%s (`^%d/%d Vaulted`2 | `@%d/%d Weekly Cap`2)`0`n",
                            $res['message'],
                            $status['banked'] + $res['credited'],
                            $status['max_vault'],
                            $status['weekly_used'] + $res['credited'],
                            $status['weekly_cap']
                        );
                    }
                }
            }
            break;

        case 'charstats':
            $status = turnbank_get_status();
            addcharstat('Syndicate HUD');
            addcharstat('Stamina Vault', sprintf('`@%d`0/`^%d`0', $status['banked'], $status['max_vault']));
            addcharstat('Weekly Cap', sprintf('`^%d`0/`7%d`0', $status['weekly_used'], $status['weekly_cap']));
            break;
    }

    return $args;
}

function turnbank_run(): void
{
    global $session;

    page_header('Borant Stamina Injector & Sponsor Token Exchange Vault');

    $op = (string) httpget('op');
    $status = turnbank_get_status();
    $turnsPerGem = max(1, (int) get_module_setting('turns_per_gem', 'turnbank'));
    $turnsCostPerGem = max(2, (int) get_module_setting('turns_cost_per_gem', 'turnbank'));

    // ========================================================================
    // ACTION HANDLERS (DEPOSIT, WITHDRAW, AUTO-BANK, GEM/TURN EXCHANGE)
    // ========================================================================
    if ($op === 'deposit') {
        $amt = (int) httppost('amount');
        if ($amt <= 0) {
            $amt = (int) httpget('amount');
        }
        $res = turnbank_deposit_stamina($amt);
        if ($res['ok']) {
            output("`@[CRYO-INJECTOR SEALED]: `2%s`0`n`n", $res['message']);
        } else {
            output("`\$[BORANT VAULT ERROR]: `4%s`0`n`n", $res['message']);
        }
        $status = turnbank_get_status();
    } elseif ($op === 'withdraw') {
        $amt = (int) httppost('amount');
        if ($amt <= 0) {
            $amt = (int) httpget('amount');
        }
        if ($amt <= 0) {
            output("`\$[BORANT VAULT ERROR]: `4Specify at least 1 Stamina Injector unit to withdraw.`0`n`n");
        } elseif ($status['banked'] <= 0) {
            output("`\$[BORANT VAULT ERROR]: `4Your Stamina Injector rack is completely empty!`0`n`n");
        } else {
            $withdraw = min($amt, $status['banked']);
            set_module_pref('banked_turns', $status['banked'] - $withdraw, 'turnbank');
            $session['user']['turns'] += $withdraw;
            output("`@[BLITZ-STAMINA INJECTED!]: `2You slam a cryo-vial into your neck! `@+%d Floor Stamina`2 surges into your bloodstream!`0`n`n", $withdraw);
            $status = turnbank_get_status();
        }
    } elseif ($op === 'toggle_auto') {
        $newState = $status['auto_bank'] ? 0 : 1;
        set_module_pref('auto_bank', $newState, 'turnbank');
        output(
            "`@[AUTO-CRYO CONFIG UPDATED]: `2New Day Auto-Banking is now %s.`0`n`n",
            $newState === 1 ? '`@ENABLED`2' : '`\$DISABLED`2'
        );
        $status = turnbank_get_status();
    } elseif ($op === 'set_auto_amt') {
        $newAmt = max(1, min(15, (int) httppost('auto_amount')));
        set_module_pref('auto_bank_amount', $newAmt, 'turnbank');
        output("`@[AUTO-CRYO CONFIG UPDATED]: `2New Day Auto-Banking target set to `^%d Floor Stamina`2 per cycle.`0`n`n", $newAmt);
        $status = turnbank_get_status();
    } elseif ($op === 'gem_to_turns') {
        if ($status['ex_left'] <= 0) {
            output("`\$[SYNDICATE EXCHANGE LOCKED]: `4Orren shakes his head. \"You have hit your daily limit of `^%d Token/Stamina exchanges`4. Wait for the next broadcast cycle.\"`0`n`n", $status['ex_max']);
        } elseif ((int) ($session['user']['gems'] ?? 0) < 1) {
            output("`\$[INSUFFICIENT SPONSOR TOKENS]: `4You need at least `51 Sponsor Token`4 to purchase a Blitz-Stick Stamina Pack!`0`n`n");
        } else {
            $session['user']['gems'] -= 1;
            $session['user']['turns'] += $turnsPerGem;
            set_module_pref('exchanges_today', $status['ex_used'] + 1, 'turnbank');
            output("`5[SPONSOR STAMINA PACK DELIVERED]: `2You feed `51 Sponsor Token`2 into the pneumatic tube and inject `@+%d Floor Stamina`2 immediately!`0`n`n", $turnsPerGem);
            $status = turnbank_get_status();
        }
    } elseif ($op === 'turns_to_gem') {
        if ($status['ex_left'] <= 0) {
            output("`\$[SYNDICATE EXCHANGE LOCKED]: `4Orren shakes his head. \"You have hit your daily limit of `^%d Token/Stamina exchanges`4. Wait for the next broadcast cycle.\"`0`n`n", $status['ex_max']);
        } elseif ((int) ($session['user']['turns'] ?? 0) < $turnsCostPerGem) {
            output("`\$[INSUFFICIENT FLOOR STAMINA]: `4Synthesizing a Sponsor Token requires `^%d active Floor Stamina`4 on hand!`0`n`n", $turnsCostPerGem);
        } else {
            $session['user']['turns'] -= $turnsCostPerGem;
            $session['user']['gems'] += 1;
            set_module_pref('exchanges_today', $status['ex_used'] + 1, 'turnbank');
            output("`5[SPONSOR TOKEN SYNTHESIZED]: `2You burn `^%d Floor Stamina`2 on the live treadmill feed for your subscribers, earning `5+1 Sponsor Token`2!`0`n`n", $turnsCostPerGem);
            $status = turnbank_get_status();
        }
    }

    // ========================================================================
    // HUD HEADER & TELEMETRY DASHBOARD
    // ========================================================================
    $vaultPct = min(100, (int) round(($status['banked'] / $status['max_vault']) * 100));
    $weekPct = min(100, (int) round(($status['weekly_used'] / $status['weekly_cap']) * 100));

    rawoutput('
    <div style="background:linear-gradient(135deg,#09151e 0%,#0f293a 60%,#07131c 100%);border:2px solid #00f0ff;border-radius:8px;padding:14px 18px;margin-bottom:14px;color:#f0f6fc;font-family:Consolas,monospace;box-shadow:0 0 16px rgba(0,240,255,0.28);">
        <div style="font-size:1.25em;font-weight:900;color:#00f0ff;letter-spacing:1.2px;">&#9889; BORANT STAMINA CRYO-VAULT &amp; SPONSOR EXCHANGE &#9889;</div>
        <div style="font-size:0.9em;color:#8b949e;margin-bottom:10px;">Supervised by Syndicate Liaison Orren &amp; Game Guide Mordecai — ISO Cycle: <strong style="color:#ffd700;">' . htmlspecialchars($status['week_label'], ENT_QUOTES, 'UTF-8') . '</strong></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;margin-top:8px;">
            <div style="background:#0d1117;border:1px solid #30363d;border-radius:6px;padding:10px;">
                <div style="color:#8b949e;font-size:0.82em;">CRYO-VAULTED STAMINA</div>
                <div style="font-size:1.2em;font-weight:bold;color:#3fb950;">' . $status['banked'] . ' / ' . $status['max_vault'] . '</div>
                <div style="background:#21262d;height:6px;border-radius:3px;margin-top:6px;overflow:hidden;">
                    <div style="background:#3fb950;width:' . $vaultPct . '%;height:100%;"></div>
                </div>
            </div>
            <div style="background:#0d1117;border:1px solid #30363d;border-radius:6px;padding:10px;">
                <div style="color:#8b949e;font-size:0.82em;">WEEKLY DEPOSIT CAP</div>
                <div style="font-size:1.2em;font-weight:bold;color:#ffd700;">' . $status['weekly_used'] . ' / ' . $status['weekly_cap'] . '</div>
                <div style="background:#21262d;height:6px;border-radius:3px;margin-top:6px;overflow:hidden;">
                    <div style="background:#ffd700;width:' . $weekPct . '%;height:100%;"></div>
                </div>
            </div>
            <div style="background:#0d1117;border:1px solid #30363d;border-radius:6px;padding:10px;">
                <div style="color:#8b949e;font-size:0.82em;">NEW DAY AUTO-BANKING</div>
                <div style="font-size:1.1em;font-weight:bold;color:' . ($status['auto_bank'] ? '#00f0ff' : '#f85149') . ';">' . ($status['auto_bank'] ? 'ACTIVE (' . $status['auto_amt'] . '/day)' : 'DISABLED') . '</div>
                <div style="color:#8b949e;font-size:0.8em;margin-top:4px;">Borant Cryo-Tax: ' . $status['tax_pct'] . '%</div>
            </div>
            <div style="background:#0d1117;border:1px solid #30363d;border-radius:6px;padding:10px;">
                <div style="color:#8b949e;font-size:0.82em;">TOKEN EXCHANGES LEFT</div>
                <div style="font-size:1.2em;font-weight:bold;color:#ff2a85;">' . $status['ex_left'] . ' / ' . $status['ex_max'] . '</div>
                <div style="color:#8b949e;font-size:0.8em;margin-top:4px;">1 Token = +' . $turnsPerGem . ' Stamina</div>
            </div>
        </div>
    </div>
    ');

    output("`3Inside the Safe Room's medical bay, **Mordecai** leans against a rack of glowing liquid-nitrogen **Blitz-Stick Stamina Injectors**, while Syndicate Liaison **Orren** monitors your weekly Borant storage compliance.`0`n`n");

    output("`7• **Active Floor Stamina on Hand:**`0 `@%d`0`n", (int) ($session['user']['turns'] ?? 0));
    output("`7• **Sponsor Tokens (Gems) on Hand:**`0 `5%d`0`n", (int) ($session['user']['gems'] ?? 0));
    output("`7• **Remaining Deposit Allowance This Week:**`0 `^%d Floor Stamina`0`n`n", $status['max_depositable']);

    // Custom Deposit / Withdraw / Auto-Bank Forms
    rawoutput('<div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:14px;">');

    // Deposit Form
    rawoutput('<form action="runmodule.php?module=turnbank&op=deposit" method="POST" style="background:#161b22;border:1px solid #30363d;border-radius:6px;padding:10px 14px;color:#c9d1d9;">');
    addnav('', 'runmodule.php?module=turnbank&op=deposit');
    rawoutput('<strong>Bottle Floor Stamina:</strong><br>');
    rawoutput('<input type="number" name="amount" min="1" max="' . max(1, (int) ($session['user']['turns'] ?? 1)) . '" value="5" style="width:70px;margin-top:6px;padding:3px;"> ');
    rawoutput('<input type="submit" class="button" value="Deposit (' . $status['tax_pct'] . '% Tax)">');
    rawoutput('</form>');

    // Withdraw Form
    rawoutput('<form action="runmodule.php?module=turnbank&op=withdraw" method="POST" style="background:#161b22;border:1px solid #30363d;border-radius:6px;padding:10px 14px;color:#c9d1d9;">');
    addnav('', 'runmodule.php?module=turnbank&op=withdraw');
    rawoutput('<strong>Inject Vaulted Stamina:</strong><br>');
    rawoutput('<input type="number" name="amount" min="1" max="' . max(1, $status['banked']) . '" value="' . min(5, max(1, $status['banked'])) . '" style="width:70px;margin-top:6px;padding:3px;"> ');
    rawoutput('<input type="submit" class="button" value="Withdraw">');
    rawoutput('</form>');

    // Auto-Bank Target Form
    rawoutput('<form action="runmodule.php?module=turnbank&op=set_auto_amt" method="POST" style="background:#161b22;border:1px solid #30363d;border-radius:6px;padding:10px 14px;color:#c9d1d9;">');
    addnav('', 'runmodule.php?module=turnbank&op=set_auto_amt');
    rawoutput('<strong>New Day Auto-Bank Target:</strong><br>');
    rawoutput('<input type="number" name="auto_amount" min="1" max="15" value="' . $status['auto_amt'] . '" style="width:60px;margin-top:6px;padding:3px;"> ');
    rawoutput('<input type="submit" class="button" value="Save Target">');
    rawoutput('</form>');

    rawoutput('</div>');

    // Quick Navigation Links
    addnav('Stamina Injector Rack');
    if ($status['max_depositable'] > 0 && (int) ($session['user']['turns'] ?? 0) >= 5) {
        addnav('Deposit 5 Stamina', 'runmodule.php?module=turnbank&op=deposit&amount=5');
    }
    if ($status['max_depositable'] > 0 && (int) ($session['user']['turns'] ?? 0) >= 10) {
        addnav('Deposit 10 Stamina', 'runmodule.php?module=turnbank&op=deposit&amount=10');
    }
    if ($status['banked'] >= 5) {
        addnav('Withdraw 5 Stamina', 'runmodule.php?module=turnbank&op=withdraw&amount=5');
    }
    if ($status['banked'] >= 10) {
        addnav('Withdraw 10 Stamina', 'runmodule.php?module=turnbank&op=withdraw&amount=10');
    }
    if ($status['banked'] > 0) {
        addnav(['Withdraw ALL (%d)', $status['banked']], 'runmodule.php?module=turnbank&op=withdraw&amount=' . $status['banked']);
    }

    addnav('New Day Auto-Cryo');
    addnav(
        $status['auto_bank'] ? '`$Disable Auto-Banking`0' : '`@Enable Auto-Banking`0',
        'runmodule.php?module=turnbank&op=toggle_auto'
    );

    addnav('Sponsor Token Synthesizer');
    if ($status['ex_left'] > 0) {
        addnav(
            ['`5Spend 1 Token -> +%d Stamina`0', $turnsPerGem],
            'runmodule.php?module=turnbank&op=gem_to_turns'
        );
        addnav(
            ['`^Burn %d Stamina -> +1 Token`0', $turnsCostPerGem],
            'runmodule.php?module=turnbank&op=turns_to_gem'
        );
    }

    addnav('Return');
    addnav('Return to Safe Room', 'village.php');

    page_footer();
}