<?php
/**
 * Test the file based preferences storage backend.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/**
 * Test the file based preferences storage backend.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Prefs_Unit_Storage_FileTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissingDirectory()
    {
        $b = new Horde_Prefs_Storage_File('nobody');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidDirectory()
    {
        $b = new Horde_Prefs_Storage_File('nobody', array('directory' => __DIR__ . '/DOES_NOT_EXIST'));
    }

    public function testConstruction()
    {
        $b = new Horde_Prefs_Storage_File('nobody', array('directory' => Horde_Util::createTempDir()));
    }
}
