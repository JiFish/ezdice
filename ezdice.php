<?php

namespace ezdice;

class EZDice {
    private $total = 0;
    private $states = [];
    private $modifier = 0;
    
    public function roll($diceStr)
    {
        // Reset result values
        $this->total = 0;
        $this->states = [];
        $this->modifier = 0;
        
        // Strip all whitespace
        $diceStr = preg_replace('/\s+/', '', $diceStr);
        
        // Search for dice groups and modifiers
        $re = '/(?<operator>[\+-])?(?<number>\d+)(?:[dD](?<sides>(?:\d+|%)))?(?:-(?<variant>[LlHh]))?/m';
        preg_match_all($re, $diceStr, $matches, PREG_SET_ORDER, 0);
        
        // Returning false if no matches found
        if (sizeof($matches) == 0) return false;
        
        // Process each match
        foreach ($matches as $m) {
            $this->processGroup($m);
        }
        
        return $this->total;
    }
    
    private function addState($sides, $value, $dropped = false) {
        $this->states[] = [
            'sides' => $sides,
            'value' => $value,
            'dropped' => $dropped
        ];
    }
    
    private function processGroup($m) {
        // Scaler to make the output postive or negative
        $scaler = ($m['operator']=='-'?-1:1);
        
        // If no sides, this is a modifier
        if(!isset($m['sides'])) {
            $value = $m['number'];
            $this->modifier += $value*$scaler;
        }
        // Otherwise, it is a group of dice
        elseif ($m['sides'] > 0) {
            // Always roll at least 1 dice
            $number = max($m['number'],1);
            // 'd%' can be used as shorthand for 'd100'
            $sides = $m['sides']=="%"?100:(int)$m['sides'];
            
            // Roll Dice
            $results = [];
            for ($c = 0; $c < $number; $c++) {
                $results[] = $this->getRandomNumber($sides);
            }
            
            // Dropping dice
            if (isset($m['variant']) && $number > 1) {
                // Sort low to high
                sort($results, SORT_NUMERIC);
                // Reverse array if dropping highest
                if (strtoupper($m['variant']) == 'H') {
                    $results = array_reverse($results);
                }
                $dropped = array_pop($results);
                $this->addState($sides, $dropped, true);
                // Re-shuffle rest of dice
                shuffle($results);
            }
            
            // Process the rest of the dice
            $value = 0;
            foreach($results as $r) {
                $value += $r;
                $this->addState($sides, $r);
            }
        }
        
        // Update total
        $this->total += $value*$scaler;
    }
    
    protected function getRandomNumber($max) {
        return mt_rand(1,$max);
    }
    
    public function getTotal() {
        return $this->total;
    }
    
    public function getDiceStates() {
        return $this->states;
    }
    
    public function getModifier() {
        if (!$this->modifier) return ""; 
        return sprintf("%+d",$this->modifier);
    }
}
