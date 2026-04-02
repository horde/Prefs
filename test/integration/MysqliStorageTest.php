<?php

/**
 * Prepare the test setup.
 */

namespace Horde\Prefs\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use Horde_Db_Adapter_Mysqli;
use Horde_Prefs_Storage_Sql;
use Horde_Prefs;
use SqlStorageTestBase;

require_once __DIR__ . '/../Unnamespaced/SqlStorageTestBase.php';

/**
 * Copyright 2011-2026 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[CoversClass(Horde_Prefs_Storage_Sql::class)]
#[CoversClass(Horde_Prefs::class)]
class MysqliStorageTest extends SqlStorageTestBase
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }

        // Check for config file
        $configFile = __DIR__ . '/../../conf.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (!empty($config['prefs']['sql']['mysqli'])) {
                self::$db = new Horde_Db_Adapter_Mysqli($config['prefs']['sql']['mysqli']);
                parent::setUpBeforeClass();
                return;
            }
        }

        self::$reason = 'No mysqli configuration';
    }
}
