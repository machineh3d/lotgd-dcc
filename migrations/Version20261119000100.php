<?php

declare(strict_types=1);

namespace Lotgd\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Lotgd\MySQL\Database;

/**
 * Dungeon Crawler Carl Total Conversion — Automated NB-Core Doctrine Migration.
 *
 * Automatically executed by NB-Core's `installer.php` (and 2.x SchemaUpgrade)
 * via Lotgd\Doctrine\MigrationRunner. Applies all four DCC SQL packs
 * (total conversion, mounts, taunts/deaths, shop dialogue), respects any
 * configured table prefix, and sets `defaultskin = 'dcc_hud_minimal'`.
 *
 * Repository: https://github.com/machineh3d/lotgd-dcc
 */
final class Version20261119000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Apply Dungeon Crawler Carl Total Conversion SQL packs, nav overrides, and defaultskin';
    }

    public function up(Schema $schema): void
    {
        Database::setDoctrineConnection($this->connection);

        $settingsTable = Database::prefix('settings');
        $weaponsTable = Database::prefix('weapons');

        if (! Database::tableExists($settingsTable) || ! Database::tableExists($weaponsTable)) {
            return;
        }

        // Locate SQL packs (baked Docker path first, then repo-relative fallback)
        $sqlDir = null;
        foreach (['/opt/lotgd-dcc/sql', dirname(__DIR__) . '/sql'] as $candidate) {
            if (is_dir($candidate)) {
                $sqlDir = $candidate;
                break;
            }
        }

        if ($sqlDir === null) {
            return;
        }

        $prefix = (string) Database::prefix('');
        $sqlFiles = [
            'lotgd_dcc_total_conversion.sql',
            'lotgd_dcc_mounts.sql',
            'lotgd_dcc_taunts_deaths.sql',
            'lotgd_dcc_shop_dialogue.sql',
        ];

        foreach ($sqlFiles as $file) {
            $path = $sqlDir . '/' . $file;
            if (! is_file($path)) {
                continue;
            }

            $rawSql = (string) file_get_contents($path);
            foreach ($this->splitSqlStatements($rawSql) as $statement) {
                $trimmed = trim($statement);
                if ($trimmed === '') {
                    continue;
                }

                // Skip transaction & session directives; Doctrine manages its own transaction
                if (preg_match('/^(SET\s+NAMES|START\s+TRANSACTION|BEGIN|COMMIT)\b/i', $trimmed) === 1) {
                    continue;
                }

                // Apply table prefix if configured
                if ($prefix !== '') {
                    $trimmed = (string) preg_replace(
                        '/`(settings|settings_extended|translations|weapons|armor|masters|creatures|mounts|taunts|deathmessages|titles)`/',
                        '`' . $prefix . '$1`',
                        $trimmed
                    );
                }

                $this->addSql($trimmed);
            }
        }

        // Ensure defaultskin and translation engine are active out of the box
        $this->addSql(
            "INSERT INTO `{$settingsTable}` (`setting`, `value`) VALUES "
            . "('defaultskin', 'dcc_hud_minimal'), "
            . "('enabletranslation', '1'), "
            . "('defaultlanguage', 'en') "
            . "ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
        );

        // Automatically deactivate conflicting stock medieval race & event modules
        $modulesTable = Database::prefix('modules');
        if (Database::tableExists($modulesTable)) {
            $this->addSql(
                "UPDATE `{$modulesTable}` SET `active` = 0 "
                . "WHERE `modulename` IN ('raceelf', 'racedwarf', 'racetroll', 'racehuman', 'oldman', 'statue', 'fairy', 'specialtydarkarts', 'specialtymysticpower', 'specialtythiefskills')"
            );
        }
    }

    public function down(Schema $schema): void
    {
        Database::setDoctrineConnection($this->connection);
        $settingsTable = Database::prefix('settings');
        if (Database::tableExists($settingsTable)) {
            $this->addSql(
                "INSERT INTO `{$settingsTable}` (`setting`, `value`) VALUES ('defaultskin', 'aurora') "
                . "ON DUPLICATE KEY UPDATE `value` = 'aurora'"
            );
        }
    }

    /**
     * Splits a multi-statement SQL file on semicolons outside single-quoted strings and comments.
     *
     * @return list<string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inSingleQuote = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

            // Skip `-- ...` single-line comments when outside quotes
            if (! $inSingleQuote && $ch === '-' && $next === '-') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if ($ch === "'") {
                if ($inSingleQuote && $next === "'") {
                    // Escaped SQL single quote ('')
                    $buffer .= "''";
                    $i++;
                    continue;
                }
                if ($inSingleQuote && $i > 0 && $sql[$i - 1] === '\\') {
                    // Backslash-escaped single quote
                    $buffer .= $ch;
                    continue;
                }
                $inSingleQuote = ! $inSingleQuote;
                $buffer .= $ch;
                continue;
            }

            if (! $inSingleQuote && $ch === ';') {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}