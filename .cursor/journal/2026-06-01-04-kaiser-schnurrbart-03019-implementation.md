# Kaiser Schnurrbart (_03019) Implementation

Kaiser Schnurrbart ("His Shaggy Majesty") faction attachment, Eisen, 2 cost. Animal/Hound/Tracker/Unique.

**Naming note:** I initially read the Title field ("His Shaggy Majesty") as the Name and called the card "Eisen's Relic" (the original placeholder text in the stub). The Name was corrected by the user to **Kaiser Schnurrbart**; "His Shaggy Majesty" is the Title (flavor subtitle). Watch the Name vs. Title distinction on faf stubs — the Name field is what shows in card lists/UIs.

## What it does

- Passive: Equipped character gains Hunter.
- City Reaction: After an opposing character moves to an adjacent City location, engage this card • Move the equipped character to their new location and engage that character.

## Decisions

### "Opposing" means came-from-our-location (not just enemy)

Per `feedback_opposing_definition`, "opposing" requires same-location + different-controller. The trigger fires on `EventCardMoved` AFTER the move resolves, so the moved character is no longer at our location. I interpret the rule as: the character was opposing **before** the move — i.e. `fromLocation == owningCharacter->Location`. Same pattern Horatio's `Reaction_01066` uses. Without this gate the reaction would fire on any opponent move that happens to end adjacent to us, which is broader than the printed text.

### "engage that character" = the opposing character

Ambiguous antecedent in "Move the equipped character to their new location and engage that character." Possible referents are the equipped character (grammatical proximity) or the opposing character (the "their" right before).

Went with engaging the **opposing** character because:
- "their new location" already establishes the opposing character as the active pronoun referent in the sentence
- Thematically a Hunter pinning their prey reads cleaner than the dog handler exhausting themselves mid-chase
- The cost (engage attachment) → effect ratio is already balanced; engaging the equipped character on top of moving them would make the reaction strictly worse than a baseline move

Worth flagging if a playtester/designer disagrees — easy revert is swap `$opposing->Id` → `$owningCharacter->Id` in the second `createCardEngagedEvent`.

### No IAbilityThatTargetsCharacters

The opposing character is a trigger reference, and the equipped character is a fixed referent (whoever's wearing the attachment). Neither involves player choice over a character target. Mirrors `Reaction_01066`'s lack of the interface.

## Implementation shape

`_03019.php`:
- `implements IHasReactions`, `use ReactionTrait`
- `handleEvent` adds/removes "Hunter" trait on `EventAttachmentEquipped`/`Unequipped` — same shape as `_01198`, `_02016`, `_02047`

`Reaction_03019.php`:
- Extends `AttachmentReaction`
- `handleEvent(EventCardMoved)` filters: owner attached + not engaged + in city, character is a Character + different controller + fromLocation == owner location + toLocation in city + toLocation adjacent to owner location
- `performReaction('hunt')` queues three events in order: engage attachment, move equipped character (engage=false so the move itself doesn't engage them), engage opposing character

WHY engage=false on the move: the move's auto-engage is for moving-as-action; this is a Reaction-driven move, and we explicitly handle which character gets engaged separately. Matches Reaction_01066 / Reaction_01037 pattern.

## Open questions

- Should the "Hunter" trait grant fire when the attachment changes hands via some weird mechanic? Current pattern adds on equip / removes on unequip, which mirrors every other "equipped character gains X" — assuming that's correct.
- Hunter is already in TraitNames.php (line 99) so no TraitNames edit needed.
