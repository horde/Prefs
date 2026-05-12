<?php

/**
 * Copyright 2011-2026 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Horde_Prefs_Storage_Sql::class)]
#[CoversClass(Horde_Prefs::class)]
class SqlStorageTestBase extends TestCase
{
    protected static $db;

    protected static $migrator;

    protected static $reason;

    protected static $prefs;

    public function testCreatePreferences()
    {
        // Pre-insert the preference definition to simulate config/prefs.php
        // The pref must exist before it can be modified
        self::$db->insert(
            'INSERT INTO horde_prefs (pref_uid, pref_scope, pref_name, pref_value) VALUES (?, ?, ?, ?)',
            ['joe', 'test', 'a', new Horde_Db_Value_Binary('default')]
        );

        $p = new Horde_Prefs(
            'test',
            self::$prefs
        );
        $p['a'] = 'c';
        $p->store();
        $this->assertEquals(
            'c',
            $this->_readValue(
                self::$db->selectValue(
                    'SELECT pref_value FROM horde_prefs WHERE pref_uid = ? AND pref_scope = ? AND pref_name = ?',
                    ['joe', 'test', 'a']
                )
            )
        );
    }

    public function testModifyPreferences()
    {
        $p = new Horde_Prefs(
            'horde',
            self::$prefs
        );
        $p['theme'] = "bar\0bie";
        $p->store();
        $this->assertEquals(
            "bar\0bie",
            $this->_readValue(
                self::$db->selectValue(
                    'SELECT pref_value FROM horde_prefs WHERE pref_uid = ? AND pref_scope = ? AND pref_name = ?',
                    ['joe', 'horde', 'theme']
                )
            )
        );
    }

    public static function setUpBeforeClass(): void
    {
        $logger = new Horde_Log_Logger(new Horde_Log_Handler_Cli());
        //self::$db->setLogger($logger);
        $dir = __DIR__ . '/../../migration/Horde/Prefs';
        if (!is_dir($dir)) {
            error_reporting(E_ALL & ~E_DEPRECATED);
            $dir = PEAR_Config::singleton()
                ->get('data_dir', null, 'pear.horde.org')
                . '/Horde_Prefs/migration';
            error_reporting(E_ALL | E_STRICT);
        }
        self::$migrator = new Horde_Db_Migration_Migrator(
            self::$db,
            null,//$logger,
            ['migrationsPath' => $dir,
                'schemaTableName' => 'horde_prefs_schema_info']
        );
        self::$migrator->up();
        self::$db->insert(
            'INSERT INTO horde_prefs (pref_uid, pref_scope, pref_name, pref_value) VALUES (?, ?, ?, ?)',
            ['joe', 'horde', 'theme', new Horde_Db_Value_Binary('silver')]
        );

        self::$prefs = new Horde_Prefs_Storage_Sql('joe', ['db' => self::$db]);
    }

    public static function tearDownAfterClass(): void
    {
        self::$prefs = null;
        if (self::$db) {
            self::$db->delete('DELETE FROM horde_prefs');
        }
        if (self::$migrator) {
            self::$migrator->down();
            self::$migrator = null;
        }
        if (self::$db) {
            self::$db->disconnect();
            self::$db = null;
        }
    }

    public function setUp(): void
    {
        if (!self::$db) {
            $this->markTestSkipped(self::$reason);
        }
    }

    protected function _readValue($value)
    {
        $columns = self::$db->columns('horde_prefs');
        return $columns['pref_value']->binaryToString($value);
    }
}
