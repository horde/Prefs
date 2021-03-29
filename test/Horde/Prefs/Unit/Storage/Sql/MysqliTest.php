<?php
/**
 * Prepare the test setup.
 */
namespace Horde\Prefs\Unit\Storage\Sql;
use Horde_Prefs_Test_Sql_Base;

require_once __DIR__ . '/Base.php';

/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Prefs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class MysqliTest extends Base
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('mysqli')) {
            self::$reason = 'No mysqli extension';
            return;
        }
        $config = self::getConfig('PREFS_SQL_MYSQLI_TEST_CONFIG',
                                  __DIR__ . '/../../..');
        if ($config && !empty($config['prefs']['sql']['mysqli'])) {
            self::$db = new Horde_Db_Adapter_Mysqli($config['prefs']['sql']['mysqli']);
            parent::setUpBeforeClass();
        } else {
            self::$reason = 'No mysqli configuration';
        }
    }
}
