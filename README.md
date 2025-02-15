# EZDice

A really simple PHP library for parsing dice notation and rolling. By Joseph Fowler aka JiFish.

Works in PHP 7+.

## Why use EZDice?

EZDice was written because I wasn't happy with similar libraries. EZDice has the following advantages over other offerings:

- No Bloat. EZDice is one simple class with no dependencies.
- Provides the state of each die after the roll, not just the total.
- Forgiving parser designed for humans.
- Option to easily replace the RNG.
- MIT licensed, ensuring you can use it freely in any project.

## Usage

Here's a basic example

```
require 'ezdice.php';

$ezd = new ezdice\EZDice();
echo($ezd->roll('1d20+2d4'));
echo(' Rolls:');
foreach($ezd->getDiceStates() as $die) {
    echo(' ['.$die['value'].'] ');
}
```

Example Output:
```
23 Rolls: [17] [4] [2]
```

## Installing with Composer

You don't have to use composer to include EZDice in your project, but you can:

`composer require jifish/ezdice`

## Methods

### roll($diceStr)

Parse **$diceStr** as dice notation, then roll those dice. Returns *(int)* total of all rolls and modifiers, or *false* if none were found.

The parser is very forgiving, ignoring whitespace and anything else it doesn't recognize. It is also case-insensitive. Dice notation is briefly documented below.

### getTotal()

Returns *(int)* that is the total of the last roll.

### getDiceStates()

Returns an *(array)* of dice that describes the state of the dice after the last roll. Each die is also an *(array)* with the following keys:

- **sides** - *(int)* the number of sides the die has
- **value** - *(int)* the value the die rolled
- **dropped** - *(bool)* *true* if this dice was dropped, otherwise *false*. Dropped dice aren't counted towards the total.
- **negative** - *(bool)* *true* if this dice was subtracted from the total, *false* if it was added.
- **type** - *(str)* string with the type of dice this is. e.g. "d6" or "dF"
- **group** - *(int)* the group number this die belongs to, starting from 1 for the first group found.

### getModifier()

Returns a *(string)* representing the total of all modifiers in the last roll. If there were no modifiers, or they cancelled out, an empty string is returned. You can cast this to an *(int)* if needed.

e.g. if you rolled `1d8+10+1d4-2` this method would return `+8`.

### getDiceGroupNumber()

Returns an *(int)* containing the total number of dice groups in the last roll. e.g. if you rolled `10d4-1d6`, this method would return `2`.

### getDiceModsNumber()

Returns an *(int)* containing the total number of modifiers in the last roll. e.g. if you rolled `10+1d6+5-1`, this method would return `3`.

### strContainsDice($diceStr)

Parse **$diceStr** and returns true if it contains at least one dice roll, otherwise returns false. Useful for verifying user input. Modifiers without dice don't count.

### strIsStrictlyDice($diceStr, $allowWhitespace = true, $mustContainDice = true)

Parse **$diceStr** and determine if it only contains dice and modifiers. Whitespace is allowed unless **$allowWhitespace** is set to false, but strings containing only whitespace will never pass. Set **$mustContainDice** to false to allow plain modifiers without dice (e.g. `-4`) to pass. Returns true if **$diceStr** passes, otherwise false.

The **roll** function ignores stuff it can't parse, so this function may be useful if you need to verify a string doesn't contain additional junk.

### rollStrict($diceStr, $allowWhitespace = true, $mustContainDice = true)

Convenience function that returns false if **$diceStr** is not strictly dice (as above), but otherwise works the same as **roll()**.

## Dice Notation

- Dice notation is in the form (number of dice)**d**(dice sides). e.g. `2d10`.
- Number of dice can be omitted to get one die of that type, e.g. `d12` 
- Additional dice can be chained with **+** and **-** operators. e.g. `2d10+1d6`.
- Modifiers can also be given. e.g. `2d10-5`
- d% can be used as a shorthand for a percentile dice. `1d%` and `1d100` are equivalent.
- Append a roll with -L to drop the lowest dice in that group, or -H to drop the highest. Dropped dice are excluded from the total. e.g. `2d20-L` will roll 2 twenty sided dice and drop the lowest. You can also specify a number of dice to drop e.g. `6d6-H3` will drop the highest 3 rolls.
- dF can be used to roll a Fudge/FATE dice. Fudge dice have 3 outcomes: -1, 0, and +1.
- Whitespace, and anything else not recognized as a dice or a modifier, is treated like a **+** operator. e.g. `foo10 1d4bar1d4  5` is equivalent to `5+1d4+1d4+10`, or simply `2d4+15`. If this is an issue, use **rollStrict**.
- Groups of zero dice, or dice with zero sides e.g. `0d0` are invalid and won't be parsed. 

## Replacing the random number generator

By default *mt_rand()* is used as the RNG, which should be fine for most applications. If you want to change this, you can extend the class and override the method **getRandomNumber($max)**, which should return an int equal to or between 1 to $max.

```
class WeightedDice extends ezdice\EZDice {
    protected function getRandomNumber($max) {
        if (mt_rand(0,1)) return $max;
        return mt_rand(1,$max);
    }
}
```

## Legalese

Released under the MIT license. Copyright (c) 2021 - 2025 Joseph Fowler.
