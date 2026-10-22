# Frau Eleanor (_05Sniper)

Test card. User: finish from Text, use create-character, and do not add translate functions (front or back) on this card. Strings stay plain. Translators later.

## Clauses

1. While using your abilities, you may consider Eleanor equipped with a Ranged Weapon.
2. Reaction: challenge issued at this location or an adjacent one → wound the challenger or the challenged.
3. Technique: Gain Lethal.

## Fiction, not a fake attachment

"Equipped with a Ranged Weapon" was three inline attachment loops (Long Shot action `Action_01055`, maneuver `Maneuver_01055`, Vantage Point `Reaction_04020`). They never consulted the character. A FakeAttachment would show up for unequip, engage, destroy, and opponent checks. Katain `Technique_02011` must still require a real unengaged gun — you cannot engage a sentence.

Added `Character::countsAsEquippedWithRangedWeapon($theah, $actingPlayerId)`. Default is a real Weapon+Ranged attachment (counts for any checker). Eleanor overrides: after the real-gun check, return true only when the acting player is her controller, she is in play (city or Home), not discarded/locker, and not Fate's Silence'd.

WHY acting player: the text says "while using **your** abilities." Opponent checks still see only a real gun. "May" is that permission for "if equipped" gates. It is not a choice UI — every current gate is a benefit, and a choice would need every caller to prompt. Silence blanks the fiction; a real flintlock still counts (parent returns first).

Did not route Katain through the hook. Engaging needs a card id.

## Reaction

`EventChallengeIssued` (Odette 03027b — issued, not accepted). Not a City Reaction, so Home is legal (`cardInPlay`).

Range: same location, or `getAdjacentCityLocations($owner->Location, false)`. WHY false: other "adjacent location" reactions exclude Home from a city. A challenge at Home is in range only when she is also at Home. From Home, the list is the outer cities, so she still sees those.

Buttons: Wound challenger / Wound challenged / Pass. Pass does not `setUsed`. Once per day. Stash both ids — the event is gone when buttons render. No `IAbilityThatTargetsCharacters` (text says "wound", not "target"). Not Sorcerer.

## Technique

`Technique_05Sniper extends Technique_GainLethal` so both pipelines stay (challenge `adversaryThreatIsLethal`, duel `createGainLethalEvent`). Name overwritten to a plain string after the generic constructor's `clienttranslate`. Duel availability requires she is the round actor. No state, no JS.

## Traits

Höpken added to `TraitNames` (dictionary order, ö as o, after Honor).

## Skill

User asked to fold this into create-character. Rows in the shape table, Pattern A "Consider equipped with a Ranged Weapon", Pattern D "Challenge issued here or adjacent", Pattern E Gain Lethal subclass note, references, checklist 117–119. The Katain non-hook is the part a later session will "fix" if it isn't written down.
