# Temporary DeckValidator gate for 05Overcoat

## Context
User asked for a temporary gate so 05Overcoat cannot be included in a deck. Prior journal was Seadog finish — unrelated.

## What / WHY
Added an early reject in `validateApproachDeck` when `$approachCard == '05Overcoat'`.

WHY approach deck only: Overcoat is a Character (Varvara), so the only legal deck slot is Approach. Faction-deck Character path already requires Brute.

WHY this shape: Mirror the existing temporary-style ban for `05GaryFlipped` in `validateLeader` — plain English string (no `clienttranslate`), id + name in the message, `continue` so we don't instantiate / pile more errors. Comment marks it temporary so a future session knows to delete when playtest issues are cleared — not a printed card rule like `canEnterApproachDeck()`.

## Not done
No regression test added; user asked for a temporary gate only.
