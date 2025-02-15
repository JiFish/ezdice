<?php

namespace ezdice;

/**
 * EZDice, a dice rolling library
 */
class EZDice {
    // Magic dice & modifier matching regex
    private const REGEX_DICE = '/(?<operator>[\+-]?)\s*(?:(?:(?<number>\d+)*[dD](?<type>(?:\d+|[%fF]))(?:-(?<drop>[LlHh])(?<dropAmount>\d+)?)?)|(?<mod>[0-9]\d*))/';
    private const REGEX_DICE_SINGLE = '/(?<number>\d+)*[dD](?<type>(?:[1-9]\d*|[%fF]))/';

    private array $customDice = [
        'F' => [
            'type' => 'dF',
            'side_count' => 3,
            'sides' => [
                ['value' => -1, 'name' => '-'],
                ['value' => 0, 'name' => ''],
                ['value' => 1, 'name' => '+'],
            ]
        ],
        '%' => [
            'type' => 'd%',
            'side_count' => 100,
        ],
        'C' => [
            'type' => 'Coin',
            'side_count' => 2,
            'sides' => [
                ['value' => 1, 'name' => 'Heads'],
                ['value' => 0, 'name' => 'Tails'],
            ]
        ],
    ];

    // Stores information on last roll
    private $total = 0;
    private $states = [];
    private $modifier = 0;
    private $diceGroupNumber = 0;
    private $diceModsNumber = 0;

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
     * Get the number of dice groups in the last roll.
     *
     * @return int the number of dice groups in the last roll.
     */
    public function getDiceGroupNumber(): int
    {
        return $this->diceGroupNumber;
    }

    /**
     * Get the number of modifiers in the last roll.
     * 
     * @return int the number of modifiers in the last roll.
     */
    public function getDiceModsNumber(): int
    {
        return $this->diceModsNumber;
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
        return sprintf("%+d", $this->modifier);
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
     * Parse **$diceStr** as dice notation, then roll those dice.
     *
     * The parser is very forgiving, ignoring whitespace and anything else it doesn't recognise.
     * It is also case-insensitive. Dice notation is documented in README.md
     *
     * @param string $diceStr the string containing the dice rolls.
     *
     * @return int|false total of all rolls and modifiers, or false if none were found.
     */
    public function roll(string $diceStr): int|false
    {
        $this->resetResultValues();

        // No dice to roll?
        if (is_numeric($diceStr)) {
            $this->total = (int)$diceStr;
            $this->modifier = $this->total;
            $this->diceModsNumber = 1;
            return $this->total;
        }

        // Search for dice groups and modifiers
        preg_match_all(self::REGEX_DICE, $diceStr, $matches, PREG_SET_ORDER, 0);

        // Returning false if no matches found
        if (sizeof($matches) == 0) return false;

        // Process each match
        foreach ($matches as $m) {
            $this->processGroup($m);
        }

        // No dice were rolled and no modifiers were found
        if ($this->diceModsNumber == 0 && $this->diceGroupNumber == 0) {
            return false;
        }

        return $this->total;
    }

    /** Convenience function that ensures **$diceStr** is strictly valid before rolling it.
     * 
     * @param string $diceStr the string containing the dice rolls.
     * @param bool $allowWhitespace whether the string can contain whitespace. Default is true.
     * @param bool $mustContainDice whether the string must contain at least 1 die. Default is true.
     *
     * @return int|false total of all rolls and modifiers, or false if none were found, or the string was invalid.
     */
    public function rollStrict(string $diceStr, bool $allowWhitespace = true, bool $mustContainDice = true): int|false
    {
        if (!$this->strIsStrictlyDice($diceStr, $allowWhitespace, $mustContainDice)) {
            $this->resetResultValues();
            return false;
        }
        return $this->roll($diceStr);
    }

    /**
     * Parse **$diceStr** and determine if it contains at least one dice roll.
     *
     * @param string $diceStr the string which may contain dice rolls.
     *
     * @return bool true if $diceStr contains at least one dice roll, otherwise false.
     */
    public function strContainsDice(string $diceStr): bool
    {
        preg_match_all(self::REGEX_DICE_SINGLE, $diceStr, $matches, PREG_SET_ORDER, 0);
        foreach ($matches as $m) {
            // Check for valid dice notation
            if ((!is_numeric($m['type']) || (int)$m['type'] > 0) && ($m['number'] === "" || (int)$m['number'] > 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse **$diceStr** and determine if it only contains dice and modifiers.
     * Whitespace is allowed by default, but strings containing only whitespace will always return false.
     *
     * @param string $diceStr the string which may contain dice rolls.
     * @param bool $allowWhitespace whether the string can contain whitespace. Default is true.
     * @param bool $mustContainDice whether the string must contain at least 1 die. Default is true.
     *
     * @return bool true if $diceStr contains dice, modifiers or whitespace, otherwise false.
     */
    public function strIsStrictlyDice(string $diceStr, bool $allowWhitespace = true, bool $mustContainDice = true): bool
    {
        // Remove whitespace
        $diceStr = preg_replace("/\s+/", "", $diceStr, -1, $count);
        if ($diceStr == "") return false;
        if (!$allowWhitespace && $count > 0) return false;

        // Check for invalid dice groups, get get dice count
        $diceCount = $this->countAndValidateDiceGroups($diceStr);
        if ($diceCount === false || ($mustContainDice && $diceCount == 0)) {
            return false;
        }

        // Remove anything that's a dice or modifier, if there's anything left then it's not strictly dice
        $diceStr = preg_replace(self::REGEX_DICE, "", $diceStr);
        return $diceStr == "";
    }

    private function addState(array $diceDefinition, int $value, bool $isNegative, bool $dropped = false): void
    {
        $state = [
            'sides' => $diceDefinition['side_count'],
            'type' => $diceDefinition['type'],
            'dropped' => $dropped,
            'negative' => $isNegative,
            'group' => $this->diceGroupNumber,
        ];
        if (isset($diceDefinition['sides'][$value])) {
            $state['value'] = $diceDefinition['sides'][$value]['value'];
            $state['name'] = $diceDefinition['sides'][$value]['name'];
        } else {
            $state['value'] = $value;
            $state['name'] = $value;
        }
        $this->states[] = $state;
    }

    private function countAndValidateDiceGroups(string $diceStr): int|false
    {
        // Search for dice groups and modifiers
        preg_match_all(self::REGEX_DICE_SINGLE, $diceStr, $matches, PREG_SET_ORDER, 0);

        $groupCount = 0;
        // Process each match
        foreach ($matches as $m) {
            // Check for valid dice notation
            if ((!is_numeric($m['type']) || $m['type'] > 0) && ($m['number'] === "" || $m['number'] > 0)) {
                $groupCount++;
            } else {
                return false;
            }
        }

        return $groupCount;
    }

    private function processGroup(array $group): void
    {
        // Scaler makes the output positive or negative
        $isNegative = ($group['operator'] == '-');
        $scaler = ($isNegative ? -1 : 1);

        // Modifiers (not dice)
        if (isset($group['mod'])) {
            $this->total += $group['mod']*$scaler;
            $this->modifier += $group['mod']*$scaler;
            $this->diceModsNumber++;
            return;
        }

        // Check for zero sized groups, or zero sided dice
        if ($group['number'] == 0 || $group['type'] == 0) {
            return;
        }

        $this->diceGroupNumber++;

        // Collect information about dice
        $number = $group['number'] ? $group['number'] : 1;
        $type = strtoupper($group['type']);
        $diceDefinition = $this->customDice[$type] ?? [
            'type' => "d$type",
            'side_count' => (int)$type,
        ];

        // Collect drop information
        $drop = (isset($group['drop']) ? strtoupper($group['drop']) : null);

        $results = [];
        for ($i = 0; $i < $number; $i++) {
            $results[] = $this->getRandomNumber($diceDefinition['side_count']);
        }

        // TODO: Dice dropping isn't taking custom dice values into account
        // Dropping dice
        if ($drop) {
            // Dropping low, so sort descending
            if ($drop == 'L') {
                rsort($results, SORT_NUMERIC);
            } else { // Dropping high, so sort ascending
                sort($results, SORT_NUMERIC);
            }
            $dropQuantity = min($group['dropAmount'] ?? 1, $number);
            for ($i=0; $i < $dropQuantity; $i++) {
                $droppedResult = array_pop($results);
                $this->addState($diceDefinition, $droppedResult, $isNegative, true);
            }
            // Cosmetic re-shuffle of rest of dice
            shuffle($results);
        }

        // Process the rest of the dice
        foreach($results as $result) {
            $this->total += $result*$scaler;
            $this->addState($diceDefinition, $result, $isNegative);
        }
    }

    private function resetResultValues(): void
    {
        $this->total = 0;
        $this->states = [];
        $this->modifier = 0;
        $this->diceGroupNumber = 0;
        $this->diceModsNumber = 0;
    }

    /**
     * Generates the pseudo-random number for dice rolls.
     *
     * @param int $max the highest number on the dice. Roll is 1 - $max (inclusive).
     *
     * @return int result of the roll.
     */
    protected function getRandomNumber(int $max): int
    {
        return mt_rand(1, $max);
    }
}
