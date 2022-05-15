<?php

require 'ezdice.php';

$ezd = new ezdice\EZDice();
echo($ezd->roll('1d20+2d4-L+6').PHP_EOL);
print_r($ezd->getDiceStates());
echo($ezd->getModifier());
