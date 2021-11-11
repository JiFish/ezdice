<?php

require 'ezdice.php';

$ezd = new ezdice\EZDice();
echo($ezd->roll('1d20+2d4').PHP_EOL);
foreach($ezd->getDiceStates() as $die) {
    print ' ['.$die['value'].'] ';
}
