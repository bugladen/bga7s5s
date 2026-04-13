# Henri Michelet (01065) Audit

## Card Text
- **Passive:** Your other Musketeers at Henri's location cannot be moved by an opponent's abilities.
- **Reaction:** When Henri issues a challenge, engage his equipped Weapon • Target character cannot intervene.

## Passive — Musketeer Movement Protection
Correct. `eventCheck` on `EventCardMoved` blocks moves when:
- Card being moved is a Character, not Henri himself
- Card is at Henri's location (`$event->fromLocation == $this->Location`)
- Same controller (your Musketeers)
- Has "Musketeer" trait
- Initiated by opponent (`$event->initiatingPlayerId != $this->ControllerId`)

Throws `BgaUserException` to prevent the move. All conditions correct.

## Reaction — Engage Weapon + Prevent Intervening
### What was correct
- Trigger: `EventChallengeIssued` when Henri is the challenger and has an unengaged Weapon ✓
- Gate: Only fires if there are opposing characters at the location who aren't the defender ✓
- Player chooses which character to prevent from intervening ✓
- `eventCheck` on `EventCharacterIntervened` blocks the prevented character ✓
- Reset on `EventDuskEndOfDay` ✓
- Marked as `Used` after activation ✓

### Bug found & fixed
The card text says "engage his equipped Weapon" as a cost, but `performReaction` never engaged the weapon.

### Future-proofed for Offhand keyword
The upcoming expansion introduces the Offhand keyword, allowing a character to equip a 2nd weapon. This means Henri could have two unengaged weapons when the reaction fires, requiring the player to choose which one to engage.

Approach: composite buttons encoding both choices. `getReactionButtonProperties` now generates one button per Character×Weapon combination. When Henri has only one unengaged weapon, the label stays simple ("Prevent X from Intervening"). When he has multiple, the label expands ("Engage [Weapon], Prevent [Character]"). The reactionId format is `prevent-{charId}-weapon-{weaponId}`, parsed with preg_match in `performReaction`.

WHY composite buttons instead of a two-step reaction: avoids needing extra state transitions. In practice the combinatorics stay small (2 weapons × 1-3 characters = 2-6 buttons). Follows the single-step pattern already used by Reaction_01040.

## Files Changed
- `modules/php/cards/_7s5s/reactions/Reaction_01065.php` — Added `use Attachment`, composite Character/Weapon buttons, weapon engagement in `performReaction`
