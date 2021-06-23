<?php

declare(strict_types=1);

namespace SelectiveVarDump\Test;

use PHPUnit\Framework\TestCase;
use SelectiveVarDump\Test\SelectiveVarDumpTest\ChildClass;
use SelectiveVarDump\Test\SelectiveVarDumpTest\DummyClass;
use SelectiveVarDump\Test\SelectiveVarDumpTest\Many\SubNamespace\Parts\SomeClass;
use SelectiveVarDump\VarDumperConfig;
use SelectiveVarDump\SelectiveVarDumper;

final class SelectiveVarDumperTest extends TestCase
{
    public function testItDumpsAnArray(): void
    {
        self::assertSame(
            'array(0)',
            (new SelectiveVarDumper(new VarDumperConfig()))->dump([])
        );
    }

    public function testItDumpsAnObjectInsideAnArray(): void
    {
        self::assertSame(
            <<<'CODE_SAMPLE'
array(1) {
  [0] => object(stdClass)
}
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig()))->dump(
                [(object) []]
            )
        );
    }

    public function testItDumpsObjectProperties(): void
    {
        self::assertSame(
            <<<'CODE_SAMPLE'
array(1) {
  [0] => object(stdClass) {
    ["foo"] => "bar"
  }
}
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig()))->dump(
                [(object) [
                    'foo' => 'bar',
                ]]
            )
        );
    }

    public function testItDumpsObjectPropertiesThatAreNotPublic(): void
    {
        self::assertSame(
            <<<'CODE_SAMPLE'
array(1) {
  [0] => object(SelectiveVarDump\Test\SelectiveVarDumpTest\DummyClass) {
    ["bar"] => "baz"
    ["foo"] => "bar"
  }
}
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig()))->dump(
                [new DummyClass()]
            )
        );
    }

    public function testItAlsoDumpsPrivatePropertiesOfParentClasses(): void
    {
        self::assertSame(
            <<<'CODE_SAMPLE'
object(SelectiveVarDump\Test\SelectiveVarDumpTest\ChildClass) {
  ["baz"] => "baz"
  ["foo"] => "foo"
}
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig([
                'foo',
                'baz',
            ])))->dump(new ChildClass())
        );
    }

    public function testItDoesNotDumpPropertiesThatShouldBeSkipped(): void
    {
        self::assertSame(
            <<<'CODE_SAMPLE'
array(1) {
  [0] => object(stdClass) {
    ["foo"] => "bar"
  }
}
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig([], [
                'bar',
            ])))->dump(
                [(object) [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ]]
            )
        );
    }

    public function testItIncludesOnlyPropertiesWithCertainNames(): void
    {
        self::assertSame(
            <<<'CODE_SAMPLE'
array(1) {
  [0] => object(stdClass) {
    ["foo"] => "bar"
  }
}
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig([
                'foo',
            ])))->dump(
                [(object) [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ]]
            )
        );
    }

    public function testItCanDumpNull(): void
    {
        self::assertSame(
            <<<'CODE_SAMPLE'
array(1) {
  [0] => null
}
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig()))->dump(
                [null]
            )
        );
    }

    public function testItCanDumpBooleans(): void
    {
        self::assertSame(
            <<<'CODE_SAMPLE'
array(2) {
  [0] => true
  [1] => false
}
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig()))->dump(
                [true, false]
            )
        );
    }

    public function testItCanSkipObjectsOfCertainTypes(): void
    {
        self::assertSame(
            <<<'CODE_SAMPLE'
array(1) {
  [0] => object(stdClass)
}
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig([], [], [
                DummyClass::class,
            ])))->dump([new DummyClass(), (object) []])
        );
    }

    public function testItAbbreviatesLongClassNames(): void
    {
        $object = new SomeClass();
        self::assertSame(
            <<<'CODE_SAMPLE'
object(SelectiveVarDump\...\SomeClass)
CODE_SAMPLE
,
            (new SelectiveVarDumper(new VarDumperConfig()))->dump(
                $object
            )
        );
    }
}
