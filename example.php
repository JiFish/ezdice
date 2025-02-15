<?php

require 'ezdice.php';

$ezd = new ezdice\EZDice();

$diceStr = 'd20 -1 - 3d4-L 2dF+6 foobar';
$diceStr = '0d10';

// Checking a string contains at least one dice roll
if ($ezd->strContainsDice("$diceStr")) {
    echo("$diceStr contains dice.".PHP_EOL);
};

// Checking a string doesn't contain anything other than dice, modifiers and whitespace
if ($ezd->strIsStrictlyDice("$diceStr") == false) {
    echo("$diceStr is not strictly a dice string. (But it can be rolled anyway.)".PHP_EOL);
};

// Rolling dice
$result = $ezd->roll($diceStr);

echo($result.PHP_EOL);
echo($ezd->getTotal().PHP_EOL);
echo($ezd->getModifier().PHP_EOL);
var_dump($ezd->getDiceStates());
