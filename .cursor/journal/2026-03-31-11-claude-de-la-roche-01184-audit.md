# Claude de la Roche (_01184) Audit

## Card Text
> Negotiable (You may parley when paying for this card.)
> During pressures, Claude gains +1[inf].
> City Reaction: When Claude's location is pressured • Count only the performer and en garde characters.

## No Bugs Found

All three aspects of the card are correctly implemented:

- **Negotiable**: `$this->Negotiable = true` set in constructor. CityCharacter base class exposes it in `getPropertyArray()` for framework/JS handling.
- **+1[inf] during pressures**: `getInfluencePressureValue` adds +1 to `ModifiedInfluence`. Only called when pressure stat is `STAT_INFLUENCE`, which is correct since the bonus is specifically `[inf]`.
- **City Reaction**: Reaction_01184 triggers on `EventPressureOccuring` when Claude is controlled, in the city, and at the pressured location. Sets `CLAUDE_PRESSURE_TYPE` binary flag. UtilitiesTrait.php pressure calc filters characters to only the performer and en garde (`!Engaged`) characters. Resets at dusk via base `CardReaction`.

Minor note: `handleEvent` has a redundant `$claude->ControllerId` truthiness check after already passing `$claude->isControlled()`. Not harmful.
