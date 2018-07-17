<?php

declare(strict_types=1);

namespace Test;

use \Ancarda\Functional\Operation;
use \PHPUnit\Framework\TestCase;
use \LogicException;

final class OperationTest extends TestCase
{
    public function testReturnInputWithNoCommands()
    {
        $o = new Operation;
        $this->assertEquals('Hello', $o->input('Hello')->realize());

        $o = new Operation;
        $this->assertNull($o->realize());
    }

    public function testFilter()
    {
        $o = new Operation;
        $this->assertEquals(
            [2],
            array_values($o->input([1, 2, 3])->filter(function ($v) {
                return $v == 2;
            })->realize())
        );
    }

    public function testModify()
    {
        $o = new Operation;
        $this->assertEquals(
            [1, 2, 3],
            $o->input(["a", "bb", "ccc"])->modify(function ($v) {
                return strlen($v);
            })->realize()
        );

        $o = new Operation;
        $this->assertEquals(
            [1, 2, 3],
            $o->input(["a", "bb", "ccc"])->modify('strlen')->realize()
        );
    }

    public function testDeduplicate()
    {
        $o = new Operation;
        $this->assertEquals(
            [1, 2],
            array_values($o->input([1, 1, 2])->deduplicate()->realize())
        );
    }

    public function testLength()
    {
        $o = new Operation;
        $this->assertEquals(3, $o->input(range(1, 3))->length()->realize());

        $o = new Operation;
        $this->assertEquals(3, $o->input('123')->length()->realize());

        $co = $this->getMockBuilder('Countable')->setMethods(['count'])->getMock();
        $co->method('count')->will($this->returnValue(15));
        $o = new Operation;
        $this->assertEquals(15, $o = $o->input($co)->length()->realize());

        $o = (new Operation)->input(false)->length();
        $this->expectException(LogicException::class);
        $o->realize();
    }

    public function testAppend()
    {
        $o = new Operation;
        $this->assertEquals([1, 2, 8, 9], $o->input([1, 2])->append([8, 9])->realize());

        $o = new Operation;
        $this->assertEquals('1289', $o->input('12')->append('89')->realize());

        $o = (new Operation)->input(1);
        $this->expectException(LogicException::class);
        $o->append(2);
    }

    public function testPrepend()
    {
        $o = new Operation;
        $this->assertEquals([8, 9, 1, 2], $o->input([1, 2])->prepend([8, 9])->realize());

        $o = new Operation;
        $this->assertEquals('8912', $o->input('12')->prepend('89')->realize());

        $o = (new Operation)->input(1);
        $this->expectException(LogicException::class);
        $o->prepend(2);
    }

    public function testSort()
    {
        $o = new Operation;
        $this->assertEquals([1, 2, 3], $o->input([3, 1, 2])->sort()->realize());
    }

    public function testFlatten()
    {
        $o = new Operation;
        $this->assertEquals([1, 2, 3, 4], $o->input([[1, 2], [3, 4]])->flatten()->realize());

        $o = new Operation;
        $this->assertEquals([1, 2, 3, 4], $o->input([1, 2, 3, [4]])->flatten()->realize());
    }

    public function testShuffle()
    {
        $o = new Operation;
        $this->assertNotEquals(range(1, 50), $o->input(range(1, 50))->shuffle()->realize());
    }

    public function testReverse()
    {
        $o = new Operation;
        $this->assertEquals([3, 2, 1], $o->input([1, 2, 3])->reverse()->realize());

        $o = new Operation;
        $this->assertEquals('cba', $o->input('abc')->reverse()->realize());

        $o = (new Operation)->input(1)->reverse();
        $this->expectException(LogicException::class);
        $o->realize();
    }

    public function testRealizeAcceptsInput()
    {
        $o = new Operation;
        $o = $o->input([1, 2, 3])->reverse();
        $this->assertSame([6, 5, 4], $o->realize([4, 5, 6]));
    }
}
