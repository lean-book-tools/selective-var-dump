<?php

declare(strict_types=1);

namespace SelectiveVarDump\Test\SelectiveVarDumpTest;

abstract class AbstractParentClassWithPrivateProperty
{
    // protected will also be a property of the child class, but shouldn't be dumped twice
    protected string $baz = 'baz';

    private string $foo = 'foo';

    private string $bar = 'bar';
}
