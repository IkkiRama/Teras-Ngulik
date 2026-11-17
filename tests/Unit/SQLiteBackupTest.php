<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SQLiteBackupTest extends TestCase
{
    public function test_backup_can_be_opened_and_restores_rows(): void
    {
        $source = new \SQLite3(':memory:');
        $source->exec('CREATE TABLE proof (id INTEGER PRIMARY KEY, value TEXT)');
        $source->exec("INSERT INTO proof (value) VALUES ('backup-restore')");
        $target = new \SQLite3(':memory:');
        $this->assertTrue($source->backup($target));
        $this->assertSame('backup-restore', $target->querySingle('SELECT value FROM proof WHERE id=1'));
        $target->close();
        $source->close();
    }
}
