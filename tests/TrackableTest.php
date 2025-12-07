<?php

namespace Remyb98\ObjectTracker\Tests;

use PHPUnit\Framework\TestCase;
use Remyb98\ObjectTracker\Attribute\Track;
use Remyb98\ObjectTracker\Trait\Trackable;


/**
 * Objets de test
 */
class DummyRole
{
    public string $label;

    public function __construct(string $label)
    {
        $this->label = $label;
    }
}

class DummyNoDisplay
{
    public int $x = 42;
}

class DummyToString
{
    public function __toString(): string
    {
        return "TOSTRING_VALUE";
    }
}

class DummyObject
{
    use Trackable;

    #[Track]
    private string $name;

    #[Track(alias: 'years')]
    private int $age;

    #[Track(display: 'label')]
    private DummyRole $role;

    #[Track(display: 'not_existing')]
    private ?DummyNoDisplay $noDisplay;

    #[Track]
    private ?int $nullable = null;

    #[Track]
    private ?DummyToString $toStringObject = null;

    private string $notTracked = 'xxx';

    public function __construct(string $name, int $age, DummyRole $role)
    {
        $this->name = $name;
        $this->age = $age;
        $this->role = $role;
        $this->noDisplay = null;
    }

    public function setName(string $name) { $this->name = $name; }
    public function setAge(int $age) { $this->age = $age; }
    public function setRole(DummyRole $role) { $this->role = $role; }
    public function setNoDisplay(?DummyNoDisplay $v) { $this->noDisplay = $v; }
    public function setNullable($v) { $this->nullable = $v; }
    public function setToStringObject(?DummyToString $v) { $this->toStringObject = $v; }
}



class TrackableTest extends TestCase
{
    private function getPrivate(object $object, string $property)
    {
        $ref = new \ReflectionClass($object);
        $p = $ref->getProperty($property);
        return $p->getValue($object);
    }

    public function testSnapshotStoresInitialValues(): void
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $original = $this->getPrivate($obj, 'originalData');

        $this->assertArrayHasKey('name', $original);
        $this->assertArrayHasKey('years', $original);
        $this->assertArrayHasKey('role', $original);
    }

    public function testGetChangesReturnsOnlyModifiedProperties()
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $obj->setName("Bob");

        $changes = $obj->getChanges();

        $this->assertEquals([
            'name' => [
                'before' => 'Alice',
                'after'  => 'Bob',
            ]
        ], $changes);
    }

    public function testCommitUpdatesSnapshot()
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $obj->setName("Bob");
        $obj->commit();

        $this->assertEmpty($obj->getChanges());
    }

    public function testAliasIsUsedCorrectly()
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $data = $this->getPrivate($obj, 'originalData');

        $this->assertArrayHasKey('years', $data);
    }

    public function testDisplayValueOnObject(): void
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $obj->setRole(new DummyRole("SuperAdmin"));

        $changes = $obj->getChanges();

        $this->assertEquals([
            'before' => 'Admin',
            'after'  => 'SuperAdmin',
        ], $changes['role']);
    }



    /**
     * Objet : display inexistant → fallback __toString → fallback [Class]
     */
    public function testDisplayPropertyMissingFallsBackCorrectly(): void
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $obj->setNoDisplay(new DummyNoDisplay());
        $obj->setName("Bob");

        $changes = $obj->getChanges();
        $value = $changes['noDisplay'];

        // Before = "[DummyNoDisplay]"
        $this->assertEquals(null, $value['before']);
        $this->assertEquals("[Remyb98\ObjectTracker\Tests\DummyNoDisplay]", $value['after']);
    }

    /**
     * Objet avec __toString et pas de display → utilise __toString()
     */
    public function testObjectWithToStringIsUsed()
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $obj->setToStringObject(new DummyToString());

        $changes = $obj->getChanges();

        $this->assertEquals(null, $changes['toStringObject']['before']);
        $this->assertEquals("TOSTRING_VALUE", $changes['toStringObject']['after']);
    }

    /**
     * Valeur scalaire et null
     */
    public function testNullValueHandled()
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $obj->setNullable(123);

        $changes = $obj->getChanges();

        $this->assertEquals([
            'before' => null,
            'after'  => 123,
        ], $changes['nullable']);
    }

    /**
     * Propriété sans attribut Track → ignorée
     */
    public function testUntrackedPropertyIsIgnored()
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $p = new \ReflectionClass($obj);
        $prop = $p->getProperty('notTracked');

        $this->assertNull(
            $prop->getAttributes(Track::class)[0] ?? null
        );
    }

    /**
     * Aucun changement → résultat vide
     */
    public function testNoChangesReturnsEmptyArray()
    {
        $obj = new DummyObject("Alice", 30, new DummyRole("Admin"));
        $obj->snapshot();

        $this->assertSame([], $obj->getChanges());
    }
}
