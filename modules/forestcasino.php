<?php

declare(strict_types=1);

/**
 * Module: The Desperado Club Casino (forestcasino.php)
 * Reskinned from: The Gilded Mirage (forestcasino.php)
 * Target Engine: Legend of the Green Dragon (NB-Core v2.0.9+ / PHP 8.4 & 8.5 compatible)
 * Repository: https://github.com/machineh3d/lotgd-dcc
 * Lore Reference:
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Desperado_Club
 *   - https://dungeon-crawler-carl.fandom.com/wiki/Casino
 *
 * Features:
 *   - Drop-in replacement for `forestcasino.php` preserving module name `forestcasino`.
 *   - Tito's "100% On The Up-And-Up" Goblin Blackjack (Hit, Stand, Double Down).
 *   - The Nothing Spot & Wheel of Fortune Slots with Progressive Syndicate Jackpot.
 *   - Desperado Pass Tattoo VIP Membership (purchasable with Sponsor Tokens / Gems,
 *     granting +15 percent payouts, higher bet caps, extra daily plays, and daily bar buffs).
 *   - Borant Gambling Addiction Lockout enforced by Astrid & the Sai Samurai Guards.
 *   - Accessible via the Safe Room red glittering door AND random Dungeon Floor encounters.
 */

function forestcasino_getmoduleinfo(): array
{
    return [
        'name' => 'The Desperado Club Casino',
        'version' => '2.0.0',
        'author' => 'Devin Falconer (machineh3d)',
        'category' => 'Dungeon Crawler World',
        'download' => 'https://github.com/machineh3d/lotgd-dcc',
        'settings' => [
            'Desperado Club House Rules,title',
            'jackpot_pool' => 'Current Progressive Syndicate Jackpot (Gold Credits),int|15000',
            'jackpot_seed' => 'Base seed amount after Jackpot is won,int|10000',
            'min_bet' => 'Minimum table wager (Gold Credits),int|50',
            'max_bet_base' => 'Maximum table wager for standard Crawlers,int|1500',
            'max_bet_vip' => 'Maximum table wager for Desperado Pass Tattoo VIPs,int|5000',
            'vip_gem_cost' => 'Sponsor Tokens (Gems) required to ink the Desperado Pass Tattoo,int|25',
            'max_daily_plays' => 'Daily wager limit before Borant Addiction Lockout triggers,int|20',
            'vip_bonus_plays' => 'Extra daily wagers granted by the Desperado Pass Tattoo,int|15',
            'forest_encounter_chance' => 'Percent chance (0-100) to find a Red Glittering Door in the Dungeon,range,0,100,1|10',
        ],
        'prefs' => [
            'Desperado Club Crawler Ledger,title',
            'vip_pass' => 'Has permanent Desperado Pass Tattoo?,bool|0',
            'plays_today' => 'Casino wagers placed today,int|0',
            'addiction_warnings' => 'Lifetime Borant Addiction Lockout ejections,int|0',
            'free_drink_claimed' => 'Claimed free VIP Blitz-Moonshine today?,bool|0',
            'net_profit' => 'Lifetime net Gold Credit profit/loss at the Desperado Club,int|0',
            'bj_state' => 'Active Blackjack hand state (JSON),text|{}',
        ],
    ];
}

function forestcasino_install(): bool
{
    module_addhook('village');
    module_addhook('forest');
    module_addhook('newday');
    module_addhook('charstats');
    module_addhook('bioinfo');
    return true;
}

function forestcasino_uninstall(): bool
{
    return true;
}

/**
 * Helper to calculate the current Crawler's daily wager limit and remaining plays.
 *
 * @return array{used: int, max: int, remaining: int, is_vip: bool, locked: bool}
 */
function forestcasino_get_limit_status(): array
{
    $isVip = ((int) get_module_pref('vip_pass', 'forestcasino') === 1);
    $used = (int) get_module_pref('plays_today', 'forestcasino');
    $baseMax = (int) get_module_setting('max_daily_plays', 'forestcasino');
    $vipBonus = (int) get_module_setting('vip_bonus_plays', 'forestcasino');
    $max = $baseMax + ($isVip ? $vipBonus : 0);
    $remaining = max(0, $max - $used);

    return [
        'used' => $used,
        'max' => $max,
        'remaining' => $remaining,
        'is_vip' => $isVip,
        'locked' => ($remaining <= 0),
    ];
}

/**
 * Increments the daily wager counter and triggers the System AI / Astrid lockout warning if maxed.
 */
function forestcasino_record_wager(int $netGoldDelta): void
{
    $used = (int) get_module_pref('plays_today', 'forestcasino') + 1;
    set_module_pref('plays_today', $used, 'forestcasino');

    $net = (int) get_module_pref('net_profit', 'forestcasino') + $netGoldDelta;
    set_module_pref('net_profit', $net, 'forestcasino');

    $status = forestcasino_get_limit_status();
    if ($status['locked']) {
        $warnings = (int) get_module_pref('addiction_warnings', 'forestcasino') + 1;
        set_module_pref('addiction_warnings', $warnings, 'forestcasino');
        output("`n`\$[BORANT SHOWRUNNER COMPLIANCE LOCKOUT]: `4Assistant Manager Astrid snaps her fingers. Two seven-foot-tall steampunk rhinoceros `^Sai Guards`4 step in front of the table!`n");
        output("`4\"That's enough degenerate stalling for one broadcast cycle, Crawler. Viewers didn't tune in to watch you pull levers all day. Get back into the corridors and bleed for the cameras!\"`0`n");
    }
}

/**
 * Builds and shuffles a standard 52-card Blackjack shoe.
 *
 * @return array<int, array{rank: string, suit: string, value: int}>
 */
function forestcasino_build_deck(): array
{
    $suits = ['♠', '♥', '♦', '♣'];
    $ranks = [
        '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6,
        '7' => 7, '8' => 8, '9' => 9, '10' => 10,
        'J' => 10, 'Q' => 10, 'K' => 10, 'A' => 11,
    ];
    $deck = [];
    foreach ($suits as $suit) {
        foreach ($ranks as $rank => $val) {
            $deck[] = ['rank' => (string) $rank, 'suit' => $suit, 'value' => $val];
        }
    }
    shuffle($deck);
    return $deck;
}

/**
 * Calculates the best Blackjack total for a hand, softening Aces from 11 to 1 as needed.
 *
 * @param array<int, array{rank: string, suit: string, value: int}> $hand
 */
function forestcasino_hand_total(array $hand): int
{
    $total = 0;
    $aces = 0;
    foreach ($hand as $card) {
        $total += (int) ($card['value'] ?? 0);
        if (($card['rank'] ?? '') === 'A') {
            $aces++;
        }
    }
    while ($total > 21 && $aces > 0) {
        $total -= 10;
        $aces--;
    }
    return $total;
}

/**
 * Formats a Blackjack hand for output.
 *
 * @param array<int, array{rank: string, suit: string, value: int}> $hand
 */
function forestcasino_format_hand(array $hand, bool $hideSecondCard = false): string
{
    $parts = [];
    foreach ($hand as $idx => $card) {
        if ($hideSecondCard && $idx === 1) {
            $parts[] = '`7[?? Hidden Card ??]`0';
            continue;
        }
        $suit = (string) ($card['suit'] ?? '♠');
        $rank = (string) ($card['rank'] ?? '?');
        $color = ($suit === '♥' || $suit === '♦') ? '`$' : '`&';
        $parts[] = sprintf('%s[%s%s]`0', $color, $rank, $suit);
    }
    return implode(' ', $parts);
}

function forestcasino_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'village':
            tlschema($args['schemas']['tavernnav'] ?? 'village');
            addnav($args['tavernnav'] ?? 'Red Glittering Door');
            tlschema();
            addnav('`4The Desperado Club Casino`0', 'runmodule.php?module=forestcasino');
            break;

        case 'forest':
            $status = forestcasino_get_limit_status();
            $chance = (int) get_module_setting('forest_encounter_chance', 'forestcasino');
            if (!$status['locked'] && $chance > 0 && e_rand(1, 100) <= $chance) {
                addnav('Transfer Station Dive Bar');
                addnav('`4Enter Red Glittering Door (Desperado Club)`0', 'runmodule.php?module=forestcasino&from=forest');
            }
            break;

        case 'newday':
            set_module_pref('plays_today', 0, 'forestcasino');
            set_module_pref('free_drink_claimed', 0, 'forestcasino');
            set_module_pref('bj_state', '{}', 'forestcasino');

            if ((int) get_module_pref('vip_pass', 'forestcasino') === 1) {
                $rebate = e_rand(100, 250) * max(1, (int) ($session['user']['level'] ?? 1));
                $session['user']['gold'] += $rebate;
                output(
                    "`n`4[DESPERADO CLUB VIP]: `2Your `\$Desperado Pass Tattoo`2 tingles warmly! Clarabelle wired your daily high-roller dividend of `^%d Gold Credits`2 and reset your casino wager allowance!`0`n",
                    $rebate
                );
            }
            break;

        case 'charstats':
            $status = forestcasino_get_limit_status();
            $badge = $status['is_vip'] ? '`4[Dagger Tattoo]`0' : '`7[Guest Pass]`0';
            addcharstat('Desperado Club');
            addcharstat('Club Status', $badge);
            addcharstat('Wagers Left', sprintf('`^%d`0/`@%d`0', $status['remaining'], $status['max']));
            break;

        case 'bioinfo':
            $targetId = (int) ($args['acctid'] ?? 0);
            if ($targetId > 0) {
                $isVip = ((int) get_module_pref('vip_pass', 'forestcasino', $targetId) === 1);
                $ejected = (int) get_module_pref('addiction_warnings', 'forestcasino', $targetId);
                if ($isVip || $ejected > 0) {
                    output(
                        "`n`4Desperado Club Standing:`0 %s (`7Cut off by Sai Guards: %d times`0)`n",
                        $isVip ? '`$Permanent Dagger Tattoo Holder`0' : '`7Public Pub Patron`0',
                        $ejected
                    );
                }
            }
            break;
    }

    return $args;
}

function forestcasino_run(): void
{
    global $session;

    page_header('The Desperado Club Casino - "So Fun It Hurts"');

    $op = (string) httpget('op');
    $from = (string) httpget('from');
    $returnUrl = ($from === 'forest') ? 'forest.php' : 'village.php';
    $returnLabel = ($from === 'forest') ? 'Exit Door to Dungeon Corridors' : 'Exit Door to Bopca Safe Room';

    $status = forestcasino_get_limit_status();
    $jackpot = (int) get_module_setting('jackpot_pool', 'forestcasino');
    $minBet = (int) get_module_setting('min_bet', 'forestcasino');
    $maxBet = $status['is_vip']
        ? (int) get_module_setting('max_bet_vip', 'forestcasino')
        : (int) get_module_setting('max_bet_base', 'forestcasino');
    $vipCost = (int) get_module_setting('vip_gem_cost', 'forestcasino');

    // Club Header Banner
    rawoutput('
    <div style="background:linear-gradient(135deg,#1a050d 0%,#2d0916 55%,#12040a 100%);border:2px solid #ff1e56;border-radius:8px;padding:14px 18px;margin-bottom:14px;color:#f8f9fa;font-family:Consolas,monospace;box-shadow:0 0 16px rgba(255,30,86,0.35);">
        <div style="font-size:1.3em;font-weight:900;color:#ff1e56;letter-spacing:1.5px;">&#128481; THE DESPERADO CLUB CASINO &#128481;</div>
        <div style="font-size:0.92em;color:#ffac41;font-style:italic;margin-bottom:8px;">"So fun it hurts." — Guarded by Clarabelle, Astrid &amp; the Sai Samurai</div>
        <div style="display:flex;flex-wrap:wrap;gap:16px;font-size:0.92em;border-top:1px solid rgba(255,30,86,0.3);padding-top:8px;">
            <span><strong style="color:#00f0ff;">Progressive Syndicate Jackpot:</strong> <span style="color:#ffd700;font-weight:bold;">' . number_format($jackpot) . ' Gold Credits</span></span>
            <span><strong style="color:#00f0ff;">Membership:</strong> ' . ($status['is_vip'] ? '<span style="color:#ff1e56;font-weight:bold;">Desperado Pass Tattoo (+15 percent Payouts)</span>' : '<span style="color:#adb5bd;">Standard Pass</span>') . '</span>
            <span><strong style="color:#00f0ff;">Borant Wager Allowance:</strong> <span style="color:#2ea043;font-weight:bold;">' . $status['remaining'] . ' / ' . $status['max'] . '</span></span>
        </div>
    </div>
    ');

    // ========================================================================
    // VIP PASS TATTOO PURCHASE & BAR PERKS
    // ========================================================================
    if ($op === 'buy_vip') {
        if ($status['is_vip']) {
            output("`4Clarabelle squints at your wrist. `2\"You already have the `\$Desperado Dagger Tattoo`2, Crawler. Get inside.\"`0`n`n");
        } elseif ((int) ($session['user']['gems'] ?? 0) < $vipCost) {
            output("`4Clarabelle crosses her massive arms in front of the red glittering door. `2\"The permanent `\$Desperado Pass Tattoo`2 costs `5%d Sponsor Tokens`2. Come back when your sponsors aren't broke.\"`0`n`n", $vipCost);
        } else {
            $session['user']['gems'] -= $vipCost;
            set_module_pref('vip_pass', 1, 'forestcasino');
            $status = forestcasino_get_limit_status();
            $maxBet = (int) get_module_setting('max_bet_vip', 'forestcasino');
            output("`4Clarabelle stamps a glowing magical needle against your wrist! `\$NEW TATTOO ACQUIRED: Desperado Club Dagger Pass!`0`n");
            output("`2• Your maximum wager cap is now `^%d Gold Credits`2!`n", $maxBet);
            output("`2• All winning Blackjack hands and Slot spins receive a `@+15 percent VIP Payout Boost`2!`n");
            output("`2• Your daily Borant wager limit before lockout increased to `@%d plays/day`2!`0`n`n", $status['max']);
        }
        $op = '';
    } elseif ($op === 'vip_drink') {
        if (!$status['is_vip']) {
            output("`4The crocodilian bartender shakes his head. `2\"VIP shelf is strictly for `\$Desperado Pass Tattoo`2 holders.\"`0`n`n");
        } elseif ((int) get_module_pref('free_drink_claimed', 'forestcasino') === 1) {
            output("`4The bartender taps the sign. `2\"One complimentary Liquid Blitz-Moonshine cocktail per broadcast cycle, friend. Doctor Bones is watching the tab.\"`0`n`n");
        } else {
            set_module_pref('free_drink_claimed', 1, 'forestcasino');
            $session['user']['turns'] += 2;
            apply_buff('desperado_vip_cocktail', [
                'name' => '`4Desperado Club "Material Girl" Blitz-Cocktail`0',
                'rounds' => 25,
                'wearoff' => 'The bass from Doctor Bones\'s turntable stops ringing in your ears.',
                'atkmod' => 1.25,
                'defmod' => 1.15,
                'roundmsg' => '`4Your Desperado VIP cocktail fuels a reckless nightclub haymaker!`0',
                'schema' => 'module-forestcasino',
            ]);
            output("`4You slam a flaming glass of `\$Desperado Blitz-Moonshine`4 while Skeleton DJ **Doctor Bones** drops a remix! You gain `@+2 Floor Stamina`4 and a `@25-round combat buff`4!`0`n`n");
        }
        $op = '';
    }

    // ========================================================================
    // BORANT ADDICTION LOCKOUT CHECK
    // ========================================================================
    if ($status['locked'] && in_array($op, ['slots', 'spin', 'blackjack', 'bj_start', 'bj_hit', 'bj_stand', 'bj_double'], true)) {
        output("`\$[BORANT COMPLIANCE LOCKOUT ACTIVE]:`0`n");
        output("`4Assistant Manager **Astrid** and two armored **Sai Rhinoceros Samurai** block your path to Croupier Tito's pit.`n");
        output("`2\"You have hit your daily limit of `^%d wagers`2, Crawler. Per Borant Corporation anti-stalling regulations, the casino floor is closed to you until the next New Day broadcast cycle!\"`0`n`n", $status['max']);
        $op = '';
    }

    // ========================================================================
    // GAME 1: THE NOTHING SPOT & WHEEL OF FORTUNE SLOTS
    // ========================================================================
    if ($op === 'slots' || $op === 'spin') {
        output("`b`4=== THE NOTHING SPOT & WHEEL OF FORTUNE SLOTS ===`0`b`n");
        output("`3A row of humming Syndicate slot terminals sits dangerously close to an unstable tear into **The Nothing**. Every spin feeds `^15 percent of your wager`3 into the **Progressive Syndicate Jackpot** (`^%s Gold Credits`3)!`0`n`n", number_format($jackpot));

        if ($op === 'spin') {
            $bet = (int) httppost('bet');
            if ($bet <= 0) {
                $bet = (int) httpget('bet');
            }
            $bet = max($minBet, min($maxBet, $bet));

            if ((int) ($session['user']['gold'] ?? 0) < $bet) {
                output("`\$You pat your pockets—you don't have `^%d Gold Credits`\$ to feed the slot terminal!`0`n`n", $bet);
            } else {
                $session['user']['gold'] -= $bet;

                // Contribute 15% of wager to Progressive Jackpot
                $contrib = max(1, (int) round($bet * 0.15));
                $jackpot += $contrib;
                set_module_setting('jackpot_pool', $jackpot, 'forestcasino');

                $symbols = [
                    'dagger'  => '`4[🗡️ Desperado Dagger]`0',
                    'mongo'   => '`@[🦖 Mongo]`0',
                    'hoblob'  => '`^[💣 Jug O\' Boom]`0',
                    'tiara'   => '`5[👑 Donut\'s Tiara]`0',
                    'crystal' => '`#[💎 Soul Crystal]`0',
                    'minge'   => '`\$[👹 Demon Minge]`0',
                ];
                $pool = ['dagger', 'dagger', 'dagger', 'mongo', 'mongo', 'mongo', 'hoblob', 'hoblob', 'tiara', 'tiara', 'crystal', 'minge'];

                $r1 = $pool[e_rand(0, count($pool) - 1)];
                $r2 = $pool[e_rand(0, count($pool) - 1)];
                $r3 = $pool[e_rand(0, count($pool) - 1)];

                output("`c`b`7--- REELS SPINNING ---`0`b`n");
                output("%s  |  %s  |  %s`c`n", $symbols[$r1], $symbols[$r2], $symbols[$r3]);

                $vipMult = $status['is_vip'] ? 1.15 : 1.0;
                $payout = 0;

                if ($r1 === 'crystal' && $r2 === 'crystal' && $r3 === 'crystal') {
                    // PROGRESSIVE SYNDICATE JACKPOT!
                    $payout = $jackpot + (int) round($bet * 10 * $vipMult);
                    $seed = (int) get_module_setting('jackpot_seed', 'forestcasino');
                    set_module_setting('jackpot_pool', $seed, 'forestcasino');
                    $jackpot = $seed;

                    $session['user']['gold'] += $payout;
                    output("`c`b`5SYNDICATE PROGRESSIVE JACKPOT!!`0`b`c`n");
                    output("`2Three unstable `#Soul Crystals`2 align! The machine erupts in gold sparks, paying out the entire `^%s Gold Credit Jackpot`2!`0`n", number_format($payout));
                    addnews(
                        '`4DESPERADO CLUB NEWS:`0 `@%s`2 hit three `#Soul Crystals`2 on The Nothing Spot slots and won the `^%s Gold Credit`2 Progressive Jackpot!',
                        (string) ($session['user']['name'] ?? 'A Crawler'), number_format($payout)
                    );
                } elseif ($r1 === 'minge' && $r2 === 'minge' && $r3 === 'minge') {
                    // NOTHING BREACH! Samantha accidentally releases Minge!
                    $hpLoss = max(1, (int) round($session['user']['hitpoints'] * 0.15));
                    $session['user']['hitpoints'] = max(1, (int) $session['user']['hitpoints'] - $hpLoss);
                    output("`\$[BREACH IN THE NOTHING!]: `4Samantha rolls past the slot bank yelling about her mother, accidentally releasing the feral demon **Minge** from the Nothing! Vorpal suicide bombers detonate across the casino ceiling, singeing you for `\$%d HP`4 while Carl throws an alarm ball blaring Madonna's *Material Girl*!`0`n", $hpLoss);
                } elseif ($r1 === $r2 && $r2 === $r3) {
                    $multipliers = [
                        'dagger' => 3.0,
                        'mongo'  => 4.5,
                        'hoblob' => 6.0,
                        'tiara'  => 8.5,
                    ];
                    $mult = $multipliers[$r1] ?? 3.0;
                    $payout = (int) round($bet * $mult * $vipMult);
                    $session['user']['gold'] += $payout;
                    output("`@[TRIPLE MATCH!]: `2Three matching %s symbols lock in! You win `^%s Gold Credits`2%s!`0`n", $symbols[$r1], number_format($payout), $status['is_vip'] ? ' (`4includes +15 percent VIP Tattoo Bonus`2)' : '');
                } elseif ($r1 === $r2 || $r2 === $r3 || $r1 === $r3) {
                    // Check if double Minge ruined the pair
                    $mingeCount = ($r1 === 'minge' ? 1 : 0) + ($r2 === 'minge' ? 1 : 0) + ($r3 === 'minge' ? 1 : 0);
                    if ($mingeCount >= 2) {
                        output("`\$Two Demon Minge symbols glare at you from the Nothing portal, eating your wager!`0`n");
                    } else {
                        $payout = (int) round($bet * 1.6 * $vipMult);
                        $session['user']['gold'] += $payout;
                        output("`^[PAIR MATCH!]: `2Two matching symbols lock in! Tito slides you `^%s Gold Credits`2%s!`0`n", number_format($payout), $status['is_vip'] ? ' (`4+15 percent VIP Bonus`2)' : '');
                    }
                } else {
                    output("`7The reels clatter to a halt with no match. Your wager feeds the Syndicate Jackpot.`0`n");
                }

                forestcasino_record_wager($payout - $bet);
                $status = forestcasino_get_limit_status();
                output("`n");
            }
        }

        if (!$status['locked']) {
            addnav('Quick Slot Wagers');
            addnav(sprintf('Spin (%d Gold)', $minBet), "runmodule.php?module=forestcasino&op=spin&bet={$minBet}&from={$from}");
            $midBet = min($maxBet, max($minBet * 5, 250));
            addnav(sprintf('Spin (%d Gold)', $midBet), "runmodule.php?module=forestcasino&op=spin&bet={$midBet}&from={$from}");
            addnav(sprintf('Max Spin (%d Gold)', $maxBet), "runmodule.php?module=forestcasino&op=spin&bet={$maxBet}&from={$from}");
        }
    }

    // ========================================================================
    // GAME 2: TITO'S "100% ON THE UP-AND-UP" GOBLIN BLACKJACK
    // ========================================================================
    elseif (in_array($op, ['blackjack', 'bj_start', 'bj_hit', 'bj_stand', 'bj_double'], true)) {
        output("`b`4=== TITO'S \"100 percent ON THE UP-AND-UP\" GOBLIN BLACKJACK ===`0`b`n");
        output("`3Croupier **Tito** adjusts his visor and taps the felt. `2\"No tricks, Crawler—this shoe is 100 percent on the up-and-up, certified by Borant Gaming Commission!\"`0`n`n");

        $rawState = (string) get_module_pref('bj_state', 'forestcasino');
        $state = json_decode($rawState, true);
        if (!is_array($state)) {
            $state = [];
        }

        if ($op === 'bj_start') {
            $bet = (int) httpget('bet');
            $bet = max($minBet, min($maxBet, $bet));

            if ((int) ($session['user']['gold'] ?? 0) < $bet) {
                output("`\$Tito shakes his head. \"Cash on the felt first, Crawler. You don't have `^%d Gold Credits`\$.\"`0`n`n", $bet);
                $state = [];
            } else {
                $session['user']['gold'] -= $bet;
                $deck = forestcasino_build_deck();
                $playerHand = [array_pop($deck), array_pop($deck)];
                $dealerHand = [array_pop($deck), array_pop($deck)];

                $state = [
                    'active' => true,
                    'bet' => $bet,
                    'deck' => $deck,
                    'player' => $playerHand,
                    'dealer' => $dealerHand,
                ];

                // Check natural 21
                if (forestcasino_hand_total($playerHand) === 21) {
                    $op = 'bj_stand';
                }
            }
        }

        if (!empty($state['active']) && $op === 'bj_hit') {
            $state['player'][] = array_pop($state['deck']);
            if (forestcasino_hand_total($state['player']) >= 21) {
                $op = 'bj_stand';
            }
        } elseif (!empty($state['active']) && $op === 'bj_double') {
            $extraBet = (int) ($state['bet'] ?? 0);
            if ((int) ($session['user']['gold'] ?? 0) < $extraBet) {
                output("`\$You don't have enough Gold Credits to Double Down! Drawing a normal hit instead.`0`n");
            } else {
                $session['user']['gold'] -= $extraBet;
                $state['bet'] += $extraBet;
                output("`^You slam an additional `^%d Gold Credits`^ onto the felt to DOUBLE DOWN!`0`n", $extraBet);
            }
            $state['player'][] = array_pop($state['deck']);
            $op = 'bj_stand';
        }

        if (!empty($state['active']) && $op === 'bj_stand') {
            $pTotal = forestcasino_hand_total($state['player']);
            while ($pTotal <= 21 && forestcasino_hand_total($state['dealer']) < 17 && !empty($state['deck'])) {
                $state['dealer'][] = array_pop($state['deck']);
            }
            $dTotal = forestcasino_hand_total($state['dealer']);
            $bet = (int) ($state['bet'] ?? 0);
            $vipMult = $status['is_vip'] ? 1.15 : 1.0;

            output("`7• **Croupier Tito's Hand** (`^%d`7):`0 %s`n", $dTotal, forestcasino_format_hand($state['dealer'], false));
            output("`7• **Your Crawler Hand** (`@%d`7):`0 %s`n`n", $pTotal, forestcasino_format_hand($state['player'], false));

            $payout = 0;
            $isNatural = ($pTotal === 21 && count($state['player']) === 2);

            if ($pTotal > 21) {
                output("`\$[BUST!]: `4You went over 21! Tito rakes your `^%d Gold Credits`4 into the Desperado drop box.`0`n`n", $bet);
            } elseif ($isNatural && $dTotal !== 21) {
                $payout = (int) round($bet * 2.5 * $vipMult);
                $session['user']['gold'] += $payout;
                output("`@[NATURAL DESPERADO BLACKJACK!]: `2You hit 21 on the deal! Tito pays out `^%s Gold Credits`2%s!`0`n`n", number_format($payout), $status['is_vip'] ? ' (`4+15 percent VIP Tattoo Bonus`2)' : '');
            } elseif ($dTotal > 21 || $pTotal > $dTotal) {
                $payout = (int) round($bet * 2.0 * $vipMult);
                $session['user']['gold'] += $payout;
                output("`@[WINNER!]: `2You beat Tito's hand! You collect `^%s Gold Credits`2%s!`0`n`n", number_format($payout), $status['is_vip'] ? ' (`4+15 percent VIP Tattoo Bonus`2)' : '');
            } elseif ($pTotal === $dTotal) {
                $payout = $bet;
                $session['user']['gold'] += $payout;
                output("`^[PUSH!]: `3You and Tito tied at `^%d`3. Your `^%d Gold Credit`3 wager is returned.`0`n`n", $pTotal, $bet);
            } else {
                output("`\$[HOUSE WINS]: `4Tito hits `^%d`4 against your `^%d`4. \"100 percent on the up-and-up, Crawler!\"`0`n`n", $dTotal, $pTotal);
            }

            $state = [];
            set_module_pref('bj_state', '{}', 'forestcasino');
            forestcasino_record_wager($payout - $bet);
            $status = forestcasino_get_limit_status();
        }

        if (!empty($state['active'])) {
            set_module_pref('bj_state', json_encode($state), 'forestcasino');
            $pTotal = forestcasino_hand_total($state['player']);
            output("`7• **Croupier Tito's Hand:**`0 %s`n", forestcasino_format_hand($state['dealer'], true));
            output("`7• **Your Crawler Hand** (`@%d`7):`0 %s`n", $pTotal, forestcasino_format_hand($state['player'], false));
            output("`7• **Current Wager on Felt:**`0 `^%d Gold Credits`0`n`n", (int) $state['bet']);

            addnav('Blackjack Actions');
            addnav('Hit (Draw Card)', "runmodule.php?module=forestcasino&op=bj_hit&from={$from}");
            addnav('Stand (Hold Total)', "runmodule.php?module=forestcasino&op=bj_stand&from={$from}");
            if (count($state['player']) === 2 && (int) ($session['user']['gold'] ?? 0) >= (int) $state['bet']) {
                addnav('Double Down (2x Bet)', "runmodule.php?module=forestcasino&op=bj_double&from={$from}");
            }
        } elseif (!$status['locked']) {
            addnav('Deal Blackjack Hand');
            addnav(sprintf('Deal (%d Gold)', $minBet), "runmodule.php?module=forestcasino&op=bj_start&bet={$minBet}&from={$from}");
            $midBet = min($maxBet, max($minBet * 5, 250));
            addnav(sprintf('Deal (%d Gold)', $midBet), "runmodule.php?module=forestcasino&op=bj_start&bet={$midBet}&from={$from}");
            addnav(sprintf('High-Roller Deal (%d Gold)', $maxBet), "runmodule.php?module=forestcasino&op=bj_start&bet={$maxBet}&from={$from}");
        }
    }

    // ========================================================================
    // MAIN DESPERADO CLUB FLOOR LOBBY
    // ========================================================================
    else {
        $netProfit = (int) get_module_pref('net_profit', 'forestcasino');
        $profitStr = ($netProfit >= 0) ? sprintf('`@+%s Gold Credits`0', number_format($netProfit)) : sprintf('`\$%s Gold Credits`0', number_format($netProfit));

        output("`3You step through the **red glittering door** past the hulking bouncer **Clarabelle** and enter the pocket-dimension nightclub known across the World Dungeon as **The Desperado Club**. Above the main floor, skeleton DJ **Doctor Bones** blasts bass across all three descending levels while armored **Sai Rhinoceros Samurai** patrol the perimeter.`0`n`n");

        output("`b`^Your Desperado Club Dossier:`0`b`n");
        output("`7• Gold Credits on Hand:`0 `^%s`0`n", number_format((int) ($session['user']['gold'] ?? 0)));
        output("`7• Sponsor Tokens (Gems):`0 `5%d`0`n", (int) ($session['user']['gems'] ?? 0));
        output("`7• Lifetime Club Net Ledger:`0 %s`n", $profitStr);
        output("`7• Table Betting Limits:`0 `^%s – %s Gold Credits`0`n`n", number_format($minBet), number_format($maxBet));
    }

    // Standard Club Navigation
    addnav('Desperado Casino Pit');
    addnav('The Nothing Spot Slots', "runmodule.php?module=forestcasino&op=slots&from={$from}");
    addnav('Tito\'s Goblin Blackjack', "runmodule.php?module=forestcasino&op=blackjack&from={$from}");
    addnav('Main Club Floor', "runmodule.php?module=forestcasino&from={$from}");

    addnav('Clarabelle & VIP Lounge');
    if (!$status['is_vip']) {
        addnav(
            sprintf('Ink Desperado Pass Tattoo (%d Tokens)', $vipCost),
            "runmodule.php?module=forestcasino&op=buy_vip&from={$from}"
        );
    } else {
        if ((int) get_module_pref('free_drink_claimed', 'forestcasino') === 0) {
            addnav('`4Claim Free VIP Blitz-Cocktail`0', "runmodule.php?module=forestcasino&op=vip_drink&from={$from}");
        }
    }

    addnav('Exit Pocket Dimension');
    addnav($returnLabel, $returnUrl);

    page_footer();
}