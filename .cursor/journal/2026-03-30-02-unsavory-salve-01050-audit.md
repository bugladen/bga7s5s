# Unsavory Salve (_01050) Audit

## Card Text
- Equip restriction: "May only equip to your character that has a Weapon. (If they lose their Weapon, destroy this card.)"
- Technique: "-1 [Thrust] • Wound the adversary. (Must have minimum of 1 Thrust)"

## Files Reviewed
- `modules/php/cards/_7s5s/_01050.php` — main card class
- `modules/php/cards/_7s5s/techniques/Technique_01050.php` — technique implementation
- No Action file exists (card has no action, only technique + passive)

## Verdict: No bugs found

### Equip Restriction
`eventCheck` correctly blocks equipping to a character without a weapon via `EventAttachmentEquipped` + `hasWeaponEquipped()`. "Your character" ownership enforced at framework level through `FactionAttachment::$CanEquipToOpponents = false` (checked in `FrameworkActionsTrait`).

### Weapon Loss → Self-Destruct
`handleEvent` on `EventAttachmentUnequipped`:
- `isAttached()` guard prevents triggering when the Salve itself has already been unequipped
- `hasWeaponEquipped()` correctly checks whether the owning character still has a weapon
- Event timing is correct: `runEventHubAfterCards = false` (default) means the weapon is already removed from the character's `Attachments` array when the Salve's handler fires

### Technique
- `isAvailableToPlayer`: checks IN_DUEL and `getCurrentRoundThrust() >= 1`
- `handleEvent` on `EventDuelCalculateTechniqueValues`: subtracts 1 thrust, wounds adversary via `getDuelRoundOpponent()` for 1 wound
- `ResetOnDuelEnd = true` by default — technique can be used once per duel

### Edge Case Noted (not a bug in this card)
If weapon removal causes character death (resolve drops below wounds), `unEquipAllAttachments` queues unequip+discard for all remaining attachments including the Salve, AND the Salve's own handler also queues its own unequip+discard. This double-queue is harmless in practice (second operations are no-ops on already-processed state) and is a general framework pattern, not specific to this card.
