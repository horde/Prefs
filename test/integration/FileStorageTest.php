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

namespace Horde\Prefs\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde_Prefs_Storage_File;
use Horde_Util;

/**
 * Test the file based preferences storage backend.
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
#[CoversClass(Horde_Prefs_Storage_File::class)]
class FileStorageTest extends TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissingDirectory()
    {
        $this->expectException('InvalidArgumentException');
        $b = new Horde_Prefs_Storage_File('nobody');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidDirectory()
    {
        $this->expectException('InvalidArgumentException');
        $b = new Horde_Prefs_Storage_File('nobody', ['directory' => __DIR__ . '/DOES_NOT_EXIST']);
    }

    public function testConstruction()
    {
        $b = new Horde_Prefs_Storage_File('nobody', ['directory' => Horde_Util::createTempDir()]);
        $this->markTestSkipped();
    }
}
