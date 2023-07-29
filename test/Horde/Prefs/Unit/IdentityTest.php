<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 */

/**
 * Test the Identity object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 */
class Horde_Prefs_Unit_IdentityTest extends Horde_Test_Case
{
    private $identity;

    /**
     */
    public function setUp(): void
    {
        $this->identity = new Horde_Prefs_Identity(array(
            'prefs' => new Horde_Prefs(
                'foo', new Horde_Prefs_Stub_Storage('foo')
            ),
            'user' => 'foo'
        ));
    }

    /**
     */
    public function testIdentityAdd()
    {
        $this->assertEquals(
            0,
            $this->identity->add(array())
        );
    }

    /**
     */
    public function testIdentityGet()
    {
        $this->identity->add(array());

        $this->assertIsArray($this->identity->get(0));
        $this->assertNull($this->identity->get(1));
    }

    /**
     */
    public function testIdentityDelete()
    {
        $this->identity->add(array());

        $this->assertEquals(
            array(),
            $this->identity->delete(0)
        );

        $this->assertNull($this->identity->get(0));
    }

    /**
     */
    public function testArrayAccessExists()
    {
        $this->identity->add(array());

        $this->assertTrue(isset($this->identity[0]));
        $this->assertFalse(isset($this->identity[1]));
    }

    /**
     */
    public function testArrayAccessGet()
    {
        $this->identity->add(array());

        $this->assertIsArray($this->identity[0]);
        $this->assertNull($this->identity[1]);
    }

    /**
     */
    public function testArrayAccessUnset()
    {
        $this->identity->add(array());

        $this->assertIsArray($this->identity[0]);

        unset($this->identity[0]);

        $this->assertNull($this->identity[0]);
    }

    /**
     */
    public function testCountable()
    {
        $this->assertEquals(0, count($this->identity));
        $this->identity->add(array());
        $this->assertEquals(1, count($this->identity));
    }

    /**
     */
    public function testIterator()
    {
        $this->identity->add(array());
        $this->assertEquals(1, count(iterator_to_array($this->identity)));
    }

}
