<?php

/**
 * Copyright 2014-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2026 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 */

namespace Horde\Prefs\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Horde_Prefs_Identity;
use Horde_Prefs;
use Horde_Prefs_Exception;
use Horde_Prefs_Stub_Storage;
use Horde_Mail_Rfc822_Address;

/**
 * Test the Identity object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2026 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 */
#[CoversClass(Horde_Prefs_Identity::class)]
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

    private function createPrefsWithValues(array $values): Horde_Prefs
    {
        $prefs = new Horde_Prefs('horde', new Horde_Prefs_Stub_Storage('testuser'));
        $prefs->retrieve();
        $scope = $prefs->getScopeObject();
        foreach ($values as $key => $val) {
            $scope->set($key, $val);
        }
        return $prefs;
    }

    private function createIdentityWithPrefs(array $prefValues): Horde_Prefs_Identity
    {
        $prefs = $this->createPrefsWithValues($prefValues);
        $identity = new Horde_Prefs_Identity([
            'prefs' => $prefs,
            'user' => 'testuser',
        ]);
        $identity->init();
        return $identity;
    }

    // =========================================================================
    // Existing tests
    // =========================================================================

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
        // Initialize with one identity first
        $this->identity->add([]);
        $this->assertEquals(1, count($this->identity));
        $this->identity->add([]);
        $this->assertEquals(2, count($this->identity));
    }

    /**
     */
    public function testIterator()
    {
        $this->identity->add([]);
        $this->assertEquals(1, count(iterator_to_array($this->identity)));
    }

    // =========================================================================
    // Constructor tests — unserialize handling
    // =========================================================================

    public function testConstructorWithValidIdentities(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test User', 'from_addr' => 'test@example.com'],
            ]),
            'default_identity' => '0',
            'from_addr' => 'test@example.com',
            'fullname' => 'Test User',
            'id' => 'Default',
        ]);

        $this->assertCount(1, $identity);
        $this->assertEquals('test@example.com', $identity->getValue('from_addr', 0));
        $this->assertEquals('Test User', $identity->getValue('fullname', 0));
    }

    public function testConstructorWithMultipleIdentities(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Personal', 'fullname' => 'John Doe', 'from_addr' => 'john@example.com'],
                ['id' => 'Work', 'fullname' => 'J. Doe', 'from_addr' => 'jdoe@company.com'],
            ]),
            'default_identity' => '0',
            'from_addr' => 'john@example.com',
            'fullname' => 'John Doe',
            'id' => 'Personal',
        ]);

        $this->assertCount(2, $identity);
        $this->assertEquals('john@example.com', $identity->getValue('from_addr', 0));
        $this->assertEquals('jdoe@company.com', $identity->getValue('from_addr', 1));
    }

    public function testConstructorWithEmptySerializedArray(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => 'a:0:{}',
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'User',
            'id' => 'Default',
        ]);

        // init() should have built a default identity
        $this->assertCount(1, $identity);
        $this->assertEquals('user@example.com', $identity->getValue('from_addr', 0));
    }

    public function testConstructorWithCorruptData(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => 'NOT_VALID_SERIALIZED_DATA',
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'User',
            'id' => 'Default',
        ]);

        // Should not throw, init() builds default
        $this->assertCount(1, $identity);
        $this->assertEquals('user@example.com', $identity->getValue('from_addr', 0));
    }

    public function testConstructorWithEmptyString(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => '',
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'User',
            'id' => 'Default',
        ]);

        $this->assertCount(1, $identity);
    }

    public function testConstructorWithSerializedFalse(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => 'b:0;',
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'User',
            'id' => 'Default',
        ]);

        // serialize(false) = 'b:0;' — unserializes to false, not an array
        $this->assertCount(1, $identity);
    }

    public function testConstructorWithSerializedString(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize('not an array'),
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'User',
            'id' => 'Default',
        ]);

        $this->assertCount(1, $identity);
    }

    // =========================================================================
    // verify() tests
    // =========================================================================

    public function testVerifyWithValidStringAddress(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => 'valid@example.com'],
            ]),
            'default_identity' => '0',
            'from_addr' => 'valid@example.com',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $identity->verify(0);
        $this->assertEquals('valid@example.com', $identity->getValue('from_addr', 0));
    }

    public function testVerifyWithEmptyAddress(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => ''],
            ]),
            'default_identity' => '0',
            'from_addr' => '',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        // Empty string is accepted by parseAddressList — verify does not throw
        $identity->verify(0);
        $this->assertEquals('', $identity->getValue('from_addr', 0));
    }

    public function testVerifyWithFlatArrayAddress(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => ['user@example.com']],
            ]),
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $identity->verify(0);
        $this->assertEquals('user@example.com', $identity->getValue('from_addr', 0));
    }

    public function testVerifyWithNestedArrayAddress(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => [['user@example.com']]],
            ]),
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $identity->verify(0);
        $this->assertEquals('user@example.com', $identity->getValue('from_addr', 0));
    }

    public function testVerifyWithNullAddress(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => null],
            ]),
            'default_identity' => '0',
            'from_addr' => '',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        // Null is coerced to empty string — verify does not throw (no TypeError)
        $identity->verify(0);
        $this->assertEquals('', $identity->getValue('from_addr', 0));
    }

    public function testVerifyWithIntegerAddress(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => 12345],
            ]),
            'default_identity' => '0',
            'from_addr' => '12345',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        // Integer coerced to empty string by defensive code — no TypeError thrown
        $identity->verify(0);
        $this->assertEquals('', $identity->getValue('from_addr', 0));
    }

    public function testVerifySetsUnnamedId(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => '', 'fullname' => 'Test', 'from_addr' => 'test@example.com'],
            ]),
            'default_identity' => '0',
            'from_addr' => 'test@example.com',
            'fullname' => 'Test',
            'id' => '',
        ]);

        $identity->verify(0);
        $this->assertNotEmpty($identity->getValue('id', 0));
    }

    public function testVerifyWithMultipleAddressesInArray(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => ['first@example.com', 'second@example.com']],
            ]),
            'default_identity' => '0',
            'from_addr' => 'first@example.com',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        // Should take the first address from the array
        $identity->verify(0);
        $this->assertEquals('first@example.com', $identity->getValue('from_addr', 0));
    }

    // =========================================================================
    // getFromAddress() tests
    // =========================================================================

    public function testGetFromAddressValidString(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => 'user@example.com'],
            ]),
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $addr = $identity->getFromAddress(0);
        $this->assertInstanceOf(Horde_Mail_Rfc822_Address::class, $addr);
        $this->assertEquals('user@example.com', $addr->bare_address);
    }

    public function testGetFromAddressEmptyFallsBackToUser(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => ''],
            ]),
            'default_identity' => '0',
            'from_addr' => '',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $addr = $identity->getFromAddress(0);
        $this->assertInstanceOf(Horde_Mail_Rfc822_Address::class, $addr);
        // Falls back to user 'testuser'
        $this->assertEquals('testuser', $addr->mailbox);
    }

    public function testGetFromAddressFlatArray(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => ['user@example.com']],
            ]),
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $addr = $identity->getFromAddress(0);
        $this->assertInstanceOf(Horde_Mail_Rfc822_Address::class, $addr);
        $this->assertEquals('user@example.com', $addr->bare_address);
    }

    public function testGetFromAddressNestedArray(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => [['user@example.com']]],
            ]),
            'default_identity' => '0',
            'from_addr' => 'user@example.com',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $addr = $identity->getFromAddress(0);
        $this->assertInstanceOf(Horde_Mail_Rfc822_Address::class, $addr);
        $this->assertEquals('user@example.com', $addr->bare_address);
    }

    public function testGetFromAddressNull(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => null],
            ]),
            'default_identity' => '0',
            'from_addr' => '',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $addr = $identity->getFromAddress(0);
        $this->assertInstanceOf(Horde_Mail_Rfc822_Address::class, $addr);
        // Falls back to user 'testuser'
        $this->assertEquals('testuser', $addr->mailbox);
    }

    public function testGetFromAddressInteger(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => 12345],
            ]),
            'default_identity' => '0',
            'from_addr' => '12345',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $addr = $identity->getFromAddress(0);
        $this->assertInstanceOf(Horde_Mail_Rfc822_Address::class, $addr);
        // Integer is not a string — coerced to empty, falls back to user
        $this->assertEquals('testuser', $addr->mailbox);
    }

    public function testGetFromAddressDeeplyNestedArray(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => [[['deep@example.com']]]],
            ]),
            'default_identity' => '0',
            'from_addr' => 'deep@example.com',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $addr = $identity->getFromAddress(0);
        $this->assertInstanceOf(Horde_Mail_Rfc822_Address::class, $addr);
        $this->assertEquals('deep@example.com', $addr->bare_address);
    }

    public function testGetFromAddressEmptyArrayFallsBackToUser(): void
    {
        $identity = $this->createIdentityWithPrefs([
            'identities' => serialize([
                ['id' => 'Default', 'fullname' => 'Test', 'from_addr' => []],
            ]),
            'default_identity' => '0',
            'from_addr' => '',
            'fullname' => 'Test',
            'id' => 'Default',
        ]);

        $addr = $identity->getFromAddress(0);
        $this->assertInstanceOf(Horde_Mail_Rfc822_Address::class, $addr);
        $this->assertEquals('testuser', $addr->mailbox);
    }
}
