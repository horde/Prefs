<?php

/**
 * Prepare the test setup.
 */

namespace Horde\Prefs\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use Horde_Db_Adapter_Oci8;
use Horde_Prefs_Storage_Sql;
use Horde_Prefs;
use SqlStorageTestBase;

require_once __DIR__ . '/../Unnamespaced/SqlStorageTestBase.php';

/**
 * Copyright 2014-2026 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[CoversClass(Horde_Prefs_Storage_Sql::class)]
#[CoversClass(Horde_Prefs::class)]
class Oci8StorageTest extends SqlStorageTestBase
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('oci8')) {
            self::$reason = 'No oci8 extension';
            return;
        }

        // Check for config file
        $configFile = __DIR__ . '/../../conf.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (!empty($config['prefs']['sql']['oci8'])) {
                self::$db = new Horde_Db_Adapter_Oci8($config['prefs']['sql']['oci8']);
                parent::setUpBeforeClass();
                return;
            }
        }

        self::$reason = 'No oci8 configuration';
    }

    public function testLargePreferences()
    {
        $p = new Horde_Prefs(
            'test',
            self::$prefs
        );
        $value = str_repeat('x', 4001);
        $p['a'] = $value;
        $p->store();
        $this->assertEquals(
            $value,
            $this->_readValue(
                self::$db->selectValue(
                    'SELECT pref_value FROM horde_prefs WHERE pref_uid = ? AND pref_scope = ? AND pref_name = ?',
                    ['joe', 'test', 'a']
                )
            )
        );
    }
}
