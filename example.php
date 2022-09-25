<?php

require 'ezdice.php';

$ezd = new ezdice\EZDice();

$diceStr = '1d20+2d4-L+6';

// Validating a dice string
if ($ezd->strContainsDice("$diceStr") {
    echo "$diceStr contains dice!";
};

// Rolling dice
echo($ezd->roll($diceStr).PHP_EOL);
print_r($ezd->getDiceStates());
echo($ezd->getModifier());
