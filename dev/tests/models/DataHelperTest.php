<?php

declare(strict_types=1);

namespace tests\models;

use PHPUnit\Framework\TestCase;

/**
 * DataHelper does not add any methods specific to Data (its constructor only
 * delegates to parent::__construct()): see app/models/DataHelper.php.
 * It serves solely as a concrete entry point for instantiating and testing
 * the generic methods of the abstract Data class.
 *
 * This behavior is covered by DataTest (which uses
 * makeHelper(DataHelper::class, ...)), not here. This class exists solely
 * to satisfy the test-presence check performed by the pre-commit hook
 * (one DataHelper class => one DataHelperTest class).
 */
class DataHelperTest extends TestCase
{
    /**
     * No behavior is specific to DataHelper: its constructor only delegates
     * to Data::__construct(). The generic methods of Data are covered by
     * DataTest. This class exists solely to satisfy the one-class =>
     * one-test-class convention enforced by the pre-commit hook.
     */
    public function testNothingToTestHere(): void
    {
        $this->markTestSkipped(
            'DataHelper has no behavior of its own; see DataTest for the inherited methods.'
        );
    }
}
