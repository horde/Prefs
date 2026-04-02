<?php

/**
 * Copyright 2014-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 */

namespace Horde\Prefs\Unit;

use PHPUnit\Framework\TestCase;
use Horde_Prefs_Identity;
use Horde_Prefs;
use Horde_Prefs_Stub_Storage;

/**
 * Test the Identity object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 * @coversNothing
 */
class IdentityTest extends TestCase
{
    private $identity;

    /**
     */
    public function setUp(): void
    {
        $this->identity = new Horde_Prefs_Identity([
            'prefs' => new Horde_Prefs(
                'foo',
                new Horde_Prefs_Stub_Storage('foo')
            ),
            'user' => 'foo',
        ]);
    }

    /**
     */
    public function testIdentityAdd()
    {
        $this->assertEquals(
            0,
            $this->identity->add([])
        );
    }

    /**
     */
    public function testIdentityGet()
    {
        $this->identity->add([]);

        $this->assertIsArray($this->identity->get(0));
        $this->assertNull($this->identity->get(1));
    }

    /**
     */
    public function testIdentityDelete()
    {
        $this->identity->add([]);

        $this->assertEquals(
            [],
            $this->identity->delete(0)
        );

        $this->assertNull($this->identity->get(0));
    }

    /**
     */
    public function testArrayAccessExists()
    {
        $this->identity->add([]);

        $this->assertTrue(isset($this->identity[0]));
        $this->assertFalse(isset($this->identity[1]));
    }

    /**
     */
    public function testArrayAccessGet()
    {
        $this->identity->add([]);

        $this->assertIsArray($this->identity[0]);
        $this->assertNull($this->identity[1]);
    }

    /**
     */
    public function testArrayAccessUnset()
    {
        $this->identity->add([]);

        $this->assertIsArray($this->identity[0]);

        unset($this->identity[0]);

        $this->assertNull($this->identity[0]);
    }

    /**
     */
    public function testCountable()
    {
        $this->assertEquals(0, count($this->identity));
        $this->identity->add([]);
        $this->assertEquals(1, count($this->identity));
    }

    /**
     */
    public function testIterator()
    {
        $this->identity->add([]);
        $this->assertEquals(1, count(iterator_to_array($this->identity)));
    }

}
