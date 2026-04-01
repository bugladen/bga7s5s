# Legion's Caress (_01021) Audit

## Card Text
> May equip to any non-Leader character at a City location.
> Forced: After the equipped character en gardes • Wound them.

Vodacce FactionAttachment. WealthCost=1. Riposte=0 (dashed), Parry=2, Thrust=3. No stat modifiers. Traits: Poison, Sabotage, Unique. `CanEquipToOpponents = true`.

## Verified Correct — No Bugs Found

### Equip restriction: "any non-Leader character at a City location"
- `CanEquipToOpponents = true` makes the equip flow include opponent characters. The `argsHighDramaEquipActionChoosePerformer` method widens the target pool when any hand attachment has this flag.
- `eventCheck` on `EventAttachmentEquipped` validates two things: character must not have "Leader" trait, and character must be `cardInCity()`. Both throw `BgaUserException` if violated. This is the standard pattern — generic system handles targeting, card-specific restrictions enforced at event level.

### Forced trigger: "After the equipped character en gardes • Wound them"
- `handleEvent` listens for `EventCardEngarded` where `$event->cardId == $this->AttachedToId`. This correctly identifies when the equipped character en gardes.
- Queues `createCharacterBeingWoundedEvent` with 1 wound on the equipped character. "Wound them" without a number = 1 wound.
- "Forced" = automatic, not optional. Being in `handleEvent` (not a reaction) makes it mandatory. Correct.
- Timing: `EventCardEngarded` has `runEventHubAfterCards = true` so card handlers fire before the EventHub processes. But the wound is *queued*, so it executes after the en garde event fully resolves. "After" timing correct.

### Guard conditions
- If unequipped, `AttachedToId = 0` so `$event->cardId == $this->AttachedToId` never matches (cardId > 0). Safe.
- No actions, reactions, maneuvers, techniques, states, or JS needed. Pure passive attachment with equip restriction + forced trigger.
- The Unique trait is handled by DeckValidator (max 1 copy in deck). No runtime equip restriction needed for Unique.

## Note
This is one of the only cards (as of this audit) with `CanEquipToOpponents = true`. It's the only card using this flag.
