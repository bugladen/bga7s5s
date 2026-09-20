# 09 — Finish checklist

← [[08 — Wiring|CityAttachment Guide Wiring States And JavaScript]] · [[Index|Implementing a CityAttachment Card]] · Next: [[10 — Examples|CityAttachment Guide Example CityAttachments To Copy]]

Use this before you call a CityAttachment "done." Check only the rows that apply to what you built.

## Card class

- [ ] `extends CityAttachment` (not `FactionAttachment`, not `CityEventCard`, not `Character`)
- [ ] **No** `initializeFaction(...)` call
- [ ] `WealthCost` set
- [ ] `CityCardNumber` set; `CardNumber` matches folder convention (`0` for newer city cards, or image id for base-game siblings)
- [ ] Stat modifiers set (default `0` is fine)
- [ ] Traits exist in `TraitNames::$TraitsJson`
- [ ] `resetCard()` after fields; Actions/Reactions after that
- [ ] Any normal `handleEvent` override calls `parent::` first (Pattern G cancel-early branches documented)

## Classification

- [ ] Every printed clause maps to exactly one pattern
- [ ] "City" abilities still use CityAttachment + `cardInCity` (no `AttachmentCityAction`)
- [ ] Forced vs Reaction distinguished correctly
- [ ] Steady-state bonuses use `get*` overrides, not global mutation

## Passives / Forced

- [ ] Trait grants: equip **and** unequip halves
- [ ] Equipped-character Forced gates `isAttached()`
- [ ] Pattern G: all needed Risk-target events, dusk clear, chip plumbing if used, manual discard on canceled equipping
- [ ] Equip restrictions dual-gated when printed

## Actions

- [ ] `extends AttachmentAction`
- [ ] City Actions gate `cardInCity`
- [ ] No `setUsed` / `announceAction` / `resetPlayerPassCount`
- [ ] `createActionResolvedEvent()` present
- [ ] Destroy = unequip + **city** discard with `isAttached()` guard
- [ ] Picker transitions use attachment `sourceId`

## Reactions

- [ ] `extends AttachmentReaction`
- [ ] Literals: `setUsed`, `isAvailable`
- [ ] Equipped gate via `ownerIsAttached`
- [ ] Multi-stage: deferred `setUsed` until finalize

## Steady-state / custom states

- [ ] `get*` override for lasting play-area properties
- [ ] Setup + SetupEvents inserted; old transitions rerouted
- [ ] New events registered in Events / EventHub / EventFactory
- [ ] Globals cleared + default branch resets explicitly

## States + JS

- [ ] Constant + State class + `states.inc.php` entry
- [ ] Enter / Update / Leave JS for each new interactive state
- [ ] Home selectable in JS when offered
- [ ] Condition / chip notifs if new once-per-Day condition

## Sanity

- [ ] `php -l` on every touched PHP file
- [ ] Mentally run the pre-commit table in `CLAUDE.md` (note: **Riposte is not required** on CityAttachment)
- [ ] Mirrored a real reference CityAttachment instead of inventing structure
- [ ] Journal entry written with the **why** of non-obvious choices (project convention)

## Next

Pick a mirror card → [[10 — Examples|CityAttachment Guide Example CityAttachments To Copy]]
