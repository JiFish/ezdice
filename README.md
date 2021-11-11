# EZDice

A really simple PHP library for parsing dice notation and rolling.

Works in PHP 7+.

## Why use EZDice?

EZDice was written because I wasn't happy with similar libraries. EZDice has the following advantages over other offerings:

- No Bloat. EZDice is one simple class with no dependences.
- Provides each die roll, not just the total.
- Forgiving parser designed for humans, not machines.
- Option to easily replace the RNG.
- MIT licence, ensuring you can use it freely in any project.

## Usage

Here's a basic example

```
require 'ezdice.php';

$ezd = new ezdice\EZDice();
echo($ezd->roll('1d20+2d4').PHP_EOL);
foreach($ezd->getDiceStates() as $die) {
    print ' ['.$die['value'].'] ';
}
```

Output:
```
23
Rolls: 17, 4, 2,
```

## Installing with Composer

Composer is not required, but you can add ezdice to your project with it:

`composer require jifish/ezdice`

### Methods

#### roll($diceStr)

Parse **$diceStr** as dice notation then roll those dice. Returns *(int)* total of all rolls, or *false* if no dice are found.

The parser is very forgiving, ignoring whitespace and anything else it doesn't recognise. Dice notation is briefly documented below.

#### getTotal()

Returns *(int)* that is the total of the last roll.

#### getDiceStates()

Returns an *(array)* of *(array)*s that describes the state of the dice after the last roll. Each die has the following format:

- **sides** - *(int)* the number of sides the die has
- **value** - *(int)* the value the die rolled
- **dropped** - *(bool)* *true* if this dice was dropped, otherwise *false*. Dropped dice aren't counted towards the total.

#### getModifier()

Returns a *(string)* representing the total of all modifiers. If there were no modifiers, or they cancelled out, a empty string is returned. You can cast this to an *(int)* if needed.

e.g. for the string `1d8+10+1d4-2` this method would return `+8`.

## Dice Notation

- Dice notation is in the form (number of dice)D(dice sides). e.g. `2d10`.
- Additional dice can be chained with + and - operators. e.g. `2d10+1d6`.
- Modifiers can also be specified. e.g. `2d10-5`
- d% can be used as a shorthand for a percentile dice. `1d%` and `1d100` are equivalent.
- Append a roll with -L to drop the lowest dice in that group, or -H to drop the highest. Dropped dice are excluded from the total. e.g. `2d20-L` will roll 2 twenty sided dice and drop the lowest.
- No notation is currently provided for fudge dice. You can use `1d3-2` instead.

## Replacing the RNG

By default *mt_rand()* is used as the RNG, which should be fine for most applications. If you want to change this, for example to normalise dice rolls, you can extend the class and override the method **getRandomNumber($max)**

```
class WeightedDice extends ezdice\EZDice {
    protected function getRandomNumber($max) {
        if (mt_rand(0,1)) return $max;
        return mt_rand(1,$max);
    }
}
```

## Legalese

Released under the MIT licence. Copyright Joseph Fowler.
