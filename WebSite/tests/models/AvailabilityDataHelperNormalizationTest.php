<?php

declare(strict_types=1);

namespace tests\models;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use app\models\AvailabilityDataHelper;

class AvailabilityDataHelperNormalizationTest extends TestCase
{
    private AvailabilityDataHelper $helper;

    protected function setUp(): void
    {
        /** @var AvailabilityDataHelper $helper */
        $helper = (new ReflectionClass(AvailabilityDataHelper::class))
            ->newInstanceWithoutConstructor();

        $this->helper = $helper;
    }

    private function invokeNormalize(mixed $json): array
    {
        $method = (new ReflectionClass($this->helper))->getMethod('normalizeAvailabilities');
        $method->setAccessible(true);
        return $method->invoke($this->helper, $json);
    }

    private function invokeSanitize(mixed $slots): array
    {
        $method = (new ReflectionClass($this->helper))->getMethod('sanitizeSlots');
        $method->setAccessible(true);
        return $method->invoke($this->helper, $slots);
    }

    // --- normalizeAvailabilities ---

    public function testNormalizeAcceptsIndexedListOfSevenDays(): void
    {
        $data = [
            ['morning' => 'on'],
            ['afternoon' => 'on'],
            [],
            [],
            [],
            [],
            ['evening' => 'on'],
        ];

        $result = $this->invokeNormalize($data);

        $this->assertCount(7, $result);
        $this->assertSame(['morning' => 'on'], $result[0]);
        $this->assertSame(['afternoon' => 'on'], $result[1]);
        $this->assertSame(['evening' => 'on'], $result[6]);
    }

    public function testNormalizeAcceptsAssociativeObjectWithStringDayKeys(): void
    {
        $data = [
            '0' => ['morning' => 'on'],
            '5' => ['evening' => 'on'],
        ];

        $result = $this->invokeNormalize($data);

        $this->assertCount(7, $result);
        $this->assertSame(['morning' => 'on'], $result[0]);
        $this->assertSame(['evening' => 'on'], $result[5]);
        $this->assertSame([], $result[1]);
    }

    public function testNormalizeIgnoresDayKeysOutOfRange(): void
    {
        $data = [
            '7'  => ['morning' => 'on'],
            '-1' => ['evening' => 'on'],
            '3'  => ['afternoon' => 'on'],
        ];

        $result = $this->invokeNormalize($data);

        $this->assertCount(7, $result);
        $this->assertSame(['afternoon' => 'on'], $result[3]);
        foreach ([0, 1, 2, 4, 5, 6] as $day) {
            $this->assertSame([], $result[$day]);
        }
    }

    public function testNormalizeAcceptsJsonEncodedString(): void
    {
        $json = json_encode([
            '0' => ['morning' => 'on'],
            '1' => ['afternoon' => 'on'],
        ]);

        $result = $this->invokeNormalize($json);

        $this->assertSame(['morning' => 'on'], $result[0]);
        $this->assertSame(['afternoon' => 'on'], $result[1]);
    }

    public function testNormalizeReturnsEmptyDaysForInvalidJsonString(): void
    {
        $result = $this->invokeNormalize('{not valid json');

        $this->assertCount(7, $result);
        foreach ($result as $day) {
            $this->assertSame([], $day);
        }
    }

    public function testNormalizeReturnsEmptyDaysForNonArrayInput(): void
    {
        $result = $this->invokeNormalize(42);

        $this->assertCount(7, $result);
        foreach ($result as $day) {
            $this->assertSame([], $day);
        }
    }

    // --- sanitizeSlots ---

    public function testSanitizeSlotsReturnsEmptyArrayForNonArrayInput(): void
    {
        $this->assertSame([], $this->invokeSanitize('on'));
        $this->assertSame([], $this->invokeSanitize(null));
    }

    public function testSanitizeSlotsKeepsOnlyStringValues(): void
    {
        $slots = [
            'morning'   => 'on',
            'afternoon' => 1,
            'evening'   => 'on',
            'extra'     => ['nested' => 'array'],
        ];

        $result = $this->invokeSanitize($slots);

        $this->assertSame(['morning' => 'on', 'evening' => 'on'], $result);
    }

    public function testSanitizeSlotsCastsIntegerKeysToStrings(): void
    {
        $slots = [0 => 'on', 1 => 'off'];

        $result = $this->invokeSanitize($slots);

        $this->assertSame(['0' => 'on', '1' => 'off'], $result);
    }
}