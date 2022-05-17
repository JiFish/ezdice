<?php

namespace ezdice;

class EZDice {
    // Magic dice & modifier matching regex
    private $re = '(?<operator>[\+-])?\s*(?<number>\d+)(?:[dD](?<sides>(?:\d+|%))(?:-(?<variant>[LlHh]))?)?';

    // Stores information on last roll
    private $total = 0;
    private $states = [];
    private $modifier = 0;

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
        preg_match_all("/{$this->re}/", $diceStr, $matches, PREG_SET_ORDER, 0);

        // Returning false if no matches found
        if (sizeof($matches) == 0) return false;

        // Process each match
        foreach ($matches as $m) {
            $this->processGroup($m);
        }

        return $this->total;
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

        // Collect variant information from group
        $variant = (isset($group['variant']) ? strtoupper($group['variant']) : null);

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
            if ($variant && $number > 1) {
                // Sort low to high
                sort($results, SORT_NUMERIC);
                // Reverse array if dropping highest
                if ($variant == 'H') {
                    $results = array_reverse($results);
                }
                $droppedResult = array_pop($results);
                $this->addState($sides, $droppedResult, true);
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

    protected function getRandomNumber(int $max): int
    {
        return mt_rand(1,$max);
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getDiceStates(): array
    {
        return $this->states;
    }

    public function getModifier(): string
    {
        if (!$this->modifier) return "";
        return sprintf("%+d",$this->modifier);
    }
}
