<?php

require 'ezdice.php';

use ezdice\EZDice;

const TEST_RUNS = 1000;

function assertGreaterThanOrEqual($expected, $actual) {
    if ($actual < $expected) {
        throw new Exception("Failed asserting that $actual is greater than or equal to $expected.");
    }
}

function assertLessThanOrEqual($expected, $actual) {
    if ($actual > $expected) {
        throw new Exception("Failed asserting that $actual is less than or equal to $expected.");
    }
}

function assertIsInt($actual) {
    if (!is_int($actual)) {
        throw new Exception("Failed asserting that $actual is of type int.");
    }
}

function assertIsArray($actual) {
    if (!is_array($actual)) {
        throw new Exception("Failed asserting that $actual is of type array.");
    }
}

function assertArrayHasKey($key, $array) {
    if (!array_key_exists($key, $array)) {
        throw new Exception("Failed asserting that array has key $key.");
    }
}

function assertEquals($expected, $actual) {
    if ($expected !== $actual) {
        throw new Exception("Failed asserting that $actual equals $expected.");
    }
}

function assertTrue($actual) {
    if ($actual !== true) {
        if (is_bool($actual))
            $actual = var_export($actual, true);
        throw new Exception("Failed asserting that $actual is true.");
    }
}

function assertFalse($actual) {
    if ($actual !== false) {
        if (is_bool($actual))
            $actual = var_export($actual, true);
        throw new Exception("Failed asserting that $actual is false.");
    }
}

function testRollSingleDie() {
    $ezd = new EZDice();
    $result = $ezd->roll('1d6');
    assertGreaterThanOrEqual(1, $result);
    assertLessThanOrEqual(6, $result);
}

function testRollMultipleDice() {
    $ezd = new EZDice();
    $result = $ezd->roll('2d6');
    assertGreaterThanOrEqual(2, $result);
    assertLessThanOrEqual(12, $result);
}

function testRollWithModifier() {
    $ezd = new EZDice();
    $result = $ezd->roll('1d6+2');
    assertGreaterThanOrEqual(3, $result);
    assertLessThanOrEqual(8, $result);
}

function testGetTotal() {
    $ezd = new EZDice();
    $ezd->roll('1d6');
    assertIsInt($ezd->getTotal());
}

function testInvalidDice() {
    $ezd = new EZDice();
    assertFalse($ezd->roll('0d10'));
    assertFalse($ezd->roll('10d0'));
    assertFalse($ezd->roll('0d0'));
}

function testGetDiceStates() {
    $ezd = new EZDice();
    $ezd->roll('1d6');
    $states = $ezd->getDiceStates();
    assertIsArray($states);
    foreach ($states as $state) {
        assertArrayHasKey('sides', $state);
        assertArrayHasKey('value', $state);
        assertArrayHasKey('dropped', $state);
        assertArrayHasKey('negative', $state);
        assertGreaterThanOrEqual(1, $state['value']);
        assertLessThanOrEqual(6, $state['value']);
        assertFalse($state['dropped']);
        assertFalse($state['negative']);
    }
}

function testGetDiceStatesWithGroups() {
    $ezd = new EZDice();
    $ezd->roll('1d6+2dF+1d4');
    $states = $ezd->getDiceStates();
    assertIsArray($states);
    $groupCounts = [];
    foreach ($states as $state) {
        assertArrayHasKey('sides', $state);
        assertArrayHasKey('value', $state);
        assertArrayHasKey('dropped', $state);
        assertArrayHasKey('negative', $state);
        assertArrayHasKey('group', $state);
        assertGreaterThanOrEqual(-1, $state['value']);
        assertLessThanOrEqual(6, $state['value']);
        assertFalse($state['dropped']);
        assertFalse($state['negative']);
        if (!isset($groupCounts[$state['group']])) {
            $groupCounts[$state['group']] = 0;
        }
        $groupCounts[$state['group']]++;
    }
    assertEquals(3, count($groupCounts)); // 3 groups: 1d6, 2dF, 1d4
}

function testGetModifier() {
    $ezd = new EZDice();
    $ezd->roll('1d6+2');
    assertEquals('+2', $ezd->getModifier());
    $ezd->roll('5 1d6-10');
    assertEquals('-5', $ezd->getModifier());
    $ezd->roll('1d6+1d1');
    assertEquals('', $ezd->getModifier());
}

function testStrContainsDice() {
    $ezd = new EZDice();
    assertTrue($ezd->strContainsDice('1d6'));
    assertFalse($ezd->strContainsDice('no dice here'));
    assertFalse($ezd->strContainsDice('0d6-6d0+0d0'));
    assertTrue($ezd->strContainsDice('d6'));
    assertTrue($ezd->strContainsDice('d%+dF'));
}

function testStrIsStrictlyDice() {
    $ezd = new EZDice();
    assertTrue($ezd->strIsStrictlyDice('d6'));
    assertTrue($ezd->strIsStrictlyDice('  1d6+2'));
    assertFalse($ezd->strIsStrictlyDice('1d6+2 and some text'));
    assertFalse($ezd->strIsStrictlyDice('0d6-6d0+0d0'));
    assertTrue($ezd->strIsStrictlyDice('1d6 + 2', true));
    assertFalse($ezd->strIsStrictlyDice('1d6+ 2', false));
    assertTrue($ezd->strIsStrictlyDice('1d6 + 2', true, true));
    assertFalse($ezd->strIsStrictlyDice('  +2  ', true, true));
    assertTrue($ezd->strIsStrictlyDice('  +2  ', true, false));
    assertFalse($ezd->strIsStrictlyDice('   '));
}

function testRollEmptyString() {
    $ezd = new EZDice();
    $result = $ezd->roll('');
    assertFalse($result);
    $states = $ezd->getDiceStates();
    assertIsArray($states);
    assertEquals(0, count($states));
}

function testRollPercentileDie() {
    $ezd = new EZDice();
    $result = $ezd->roll('1d%');
    assertGreaterThanOrEqual(1, $result);
    assertLessThanOrEqual(100, $result);
}

function testDropLowestDie() {
    $ezd = new EZDice();
    $result = $ezd->roll('2d6-L');
    assertGreaterThanOrEqual(1, $result);
    assertLessThanOrEqual(6, $result);
    $states = $ezd->getDiceStates();
    assertEquals(2, count($states));
    $droppedDie = min($states[0]['value'], $states[1]['value']);
    assertTrue($states[0]['dropped'] || $states[1]['dropped']);
    assertEquals($droppedDie, $states[0]['dropped'] ? $states[0]['value'] : $states[1]['value']);
}

function testDropHighestDie() {
    $ezd = new EZDice();
    $result = $ezd->roll('2d6-H');
    assertGreaterThanOrEqual(1, $result);
    assertLessThanOrEqual(6, $result);
    $states = $ezd->getDiceStates();
    assertEquals(2, count($states));
    $droppedDie = max($states[0]['value'], $states[1]['value']);
    assertTrue($states[0]['dropped'] || $states[1]['dropped']);
    assertEquals($droppedDie, $states[0]['dropped'] ? $states[0]['value'] : $states[1]['value']);
}

function testDropMultipleLowestDice() {
    $ezd = new EZDice();
    $result = $ezd->roll('4d6-L2');
    assertGreaterThanOrEqual(2, $result);
    assertLessThanOrEqual(12, $result);
    $states = $ezd->getDiceStates();
    assertEquals(4, count($states));
    $values = array_column($states, 'value');
    sort($values);
    $droppedValues = array_slice($values, 0, 2);
    $droppedCount = 0;
    foreach ($states as $state) {
        if ($state['dropped']) {
            assertTrue(in_array($state['value'], $droppedValues));
            $droppedCount++;
        }
    }
    assertEquals(2, $droppedCount);
}

function testDropMultipleHighestDice() {
    $ezd = new EZDice();
    $result = $ezd->roll('4d6-H2');
    assertGreaterThanOrEqual(2, $result);
    assertLessThanOrEqual(12, $result);
    $states = $ezd->getDiceStates();
    assertEquals(4, count($states));
    $values = array_column($states, 'value');
    rsort($values);
    $droppedValues = array_slice($values, 0, 2);
    $droppedCount = 0;
    foreach ($states as $state) {
        if ($state['dropped']) {
            assertTrue(in_array($state['value'], $droppedValues));
            $droppedCount++;
        }
    }
    assertEquals(2, $droppedCount);
}

// Run tests
$startTime = microtime(true);
for ($i = 0; $i < TEST_RUNS; $i++) {
    testRollSingleDie();
    testRollMultipleDice();
    testRollWithModifier();
    testGetTotal();
    testGetDiceStates();
    testGetDiceStatesWithGroups();
    testRollPercentileDie();
    testDropLowestDie();
    testDropHighestDie();
    testDropMultipleLowestDice();
    testDropMultipleHighestDice();
}
$endTime = microtime(true);

// Run non-random tests once
testInvalidDice();
testGetModifier();
testStrContainsDice();
testStrIsStrictlyDice();
testRollEmptyString();

echo "All tests passed.\n";
echo "Time taken: " . ($endTime - $startTime) . " seconds.\n";
?>
