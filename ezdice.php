<?php

namespace ezdice;

/**
 * EZDice, a dice rolling library
 */
class EZDice {
    // Magic dice & modifier matching regex
    private $re = '(?<operator>[\+-])?\s*(?<number>\d+)(?:[dD](?<sides>(?:\d+|%))(?:-(?<drop>[LlHh])(?<dquantity>\d+)?)?)';

    // Stores information on last roll
    private $total = 0;
    private $states = [];
    private $modifier = 0;

    /**
     * Parse **$diceStr** as dice notation, then roll those dice.
     *
     * The parser is very forgiving, ignoring whitespace and anything else it doesn't recognise.
     * It is also case-insensitive. Dice notation is documented in README.md
     *
     * @param string $diceStr the string containing the dice rolls.
     *
     * @return int|false total of all rolls and modifiers, or false if none were found.
     */
    public function roll(string $diceStr)
    {
        // Reset result values
        $this->total = 0;
        $this->states = [];
        $this->modifier = 0;

        // No dice to roll?
        if (is_numeric($diceStr)) {
            $this->total = (int)$diceStr;
            $this->modifier = $this->total;
            return $this->total;
        }

        // Search for dice groups and modifiers
        // The extra "?" at the end of the regex allows it to find modifiers too
        preg_match_all("/{$this->re}?/", $diceStr, $matches, PREG_SET_ORDER, 0);

        // Returning false if no matches found
        if (sizeof($matches) == 0) return false;

        // Process each match
        foreach ($matches as $m) {
            $this->processGroup($m);
        }

        return $this->total;
    }

    /**
     * Parse **$diceStr** and determine if it contains at least one dice roll.
     *
     * @param string $diceStr the string which may contain dice rolls.
     *
     * @return bool true if $diceStr contains at least one dice roll, otherwise false.
     */
    public function strContainsDice(string $diceStr)
    {
        return (preg_match_all("/{$this->re}/", $diceStr) > 0);
    }

    private function addState(int $sides, int $value, bool $dropped = false): void
    {
        $this->states[] = [
            'sides' => $sides,
            'value' => $value,
            'dropped' => $dropped
        ];
    }

    private function processGroup(array $group): void
    {
        // Collect information about group
        $operator = $group['operator'] ?? '+';
        $number = $group['number'];
        $sides = $group['sides'] ?? null;

        // Scaler makes the output postive or negative
        $scaler = ($operator=='-' ? -1 : 1);

        // If sides isn't specified, this is a modifier
        if ($sides === null) {
            $this->total += $number*$scaler;
            $this->modifier += $number*$scaler;
            return;
        }

        // Collect drop information from group
        $drop = (isset($group['drop']) ? strtoupper($group['drop']) : null);

        // 'd%' can be used as shorthand for 'd100'
        $sides = $sides=="%" ? 100 : $sides;

        // Is it is a valid group of dice?
        if ($sides && $number > 0) {
            // Roll Dice
            $results = [];
            for ($c = 0; $c < $number; $c++) {
                $results[] = $this->getRandomNumber($sides);
            }

            // Dropping dice
            if ($drop) {
                $dropQuantity = min($group['dquantity'] ?? 1, $number);
                // Sort low to high
                sort($results, SORT_NUMERIC);
                // Reverse array if dropping lowest
                if ($drop == 'L') {
                    $results = array_reverse($results);
                }
                for ($i=0; $i < $dropQuantity; $i++) {
                    $droppedResult = array_pop($results);
                    $this->addState($sides, $droppedResult, true);
                }
                // Cosmetic re-shuffle of rest of dice
                shuffle($results);
            }

            // Process the rest of the dice
            foreach($results as $result) {
                $this->total += $result*$scaler;
                $this->addState($sides, $result);
            }
        }
    }

    /**
     * Generates the psudo-random number for dice rolls.
     *
     * @param int $max the highest number on the dice. Roll is 1 - $max (inclusive).
     *
     * @return int result of the roll.
     */
    protected function getRandomNumber(int $max): int
    {
        return mt_rand(1,$max);
    }

    /**
     * Get the total of the last roll.
     *
     * @return bool result of test.
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Get the state of the dice after the last roll.
     *
     * @return array array that describes the state of the dice after the last roll.
     */
    public function getDiceStates(): array
    {
        return $this->states;
    }

    /**
     * Get the combined modifiers of the last roll.
     *
     * @return string representing the total of all modifiers in the last roll. If there were no modifiers, or they
     *                cancelled out, an empty string is returned.
     */
    public function getModifier(): string
    {
        if (!$this->modifier) return "";
        return sprintf("%+d",$this->modifier);
    }
}
