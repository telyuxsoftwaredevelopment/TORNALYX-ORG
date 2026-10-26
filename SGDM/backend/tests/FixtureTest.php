<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FixtureTest extends TestCase {
    public function testRoundRobinEvenHasEveryUniquePair(): void {
        $rondas = Fixture::roundRobin(range(1, 6));
        self::assertCount(5, $rondas);
        $pares = array_merge(...$rondas);
        self::assertCount(15, $pares);
        $canonicos = array_map(static fn(array $p): string => min($p) . ':' . max($p), $pares);
        self::assertCount(15, array_unique($canonicos));
        foreach ($pares as [$a, $b]) self::assertNotSame($a, $b);
    }

    public function testRoundRobinOddCreatesExpectedPairs(): void {
        self::assertCount(10, array_merge(...Fixture::roundRobin([1, 2, 3, 4, 5])));
    }

    public function testEliminationLeavesByeForOddCount(): void {
        self::assertSame([[1, 2], [3, 4]], Fixture::eliminacion([1, 2, 3, 4, 5]));
    }

    public function testSwissAvoidsRematchWhenAlternativeExists(): void {
        self::assertSame([[1, 3], [2, 4]], Fixture::suizo([1, 2, 3, 4], [1 => [2 => true]]));
    }

    public function testEliminationRoundNames(): void {
        self::assertSame('Semifinal', Fixture::nombreRonda('eliminacion_directa', 1, 4));
    }
}
