# 10 — Finish checklist

← [[09 — Wiring|FactionAttachment Guide Wiring States And JavaScript]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[11 — Examples|FactionAttachment Guide Example FactionAttachments To Copy]]

Use this before you call a FactionAttachment "done." Check only the rows that apply to what you built.

## Card class

- [ ] `extends FactionAttachment` (not `CityAttachment`, not `Character`)
- [ ] `initializeFaction(...)` is called (including `'Neutral'` when printed Neutral)
- [ ] `WealthCost` set
- [ ] `CardNumber` matches the filename (not `CityCardNumber`)
- [ ] Stat modifiers set (default `0` is fine)
- [ ] **`Riposte` always set** (even `0`) — pre-commit
- [ ] `Parry` / `Thrust` / dashed flags match the card
- [ ] Traits exist in `TraitNames::$TraitsJson`
- [ ] `OffHand` / `CanEquipToOpponents` only when printed
- [ ] `resetCard()` after fields; Actions/Reactions/Techniques/Maneuvers after that
- [ ] Any `handleEvent` / `eventCheck` override calls `parent::` first

## Classification

- [ ] Every printed clause maps to exactly one pattern
- [ ] "City" abilities still use FactionAttachment + `cardInCity` (no `AttachmentCityAction`)
- [ ] Sorcerer vs trait-prefix keywords parsed literally
- [ ] Gambling is `IN_DUEL` + `DUEL_GAMBLED`, not a trait
- [ ] While-equipped vs remainder-of-duel clear timing chosen deliberately

## Equip restrictions

- [ ] Both `canAttachTo` **and** `eventCheck(EventAttachmentEquipping)`
- [ ] `\Bga\GameFramework\UserException` (not old `BgaUserException`)
- [ ] Opponent-equip sets `CanEquipToOpponents = true` when required
- [ ] "Less Stat than performer" resolved via same-location ally (`_03066` shape) if needed

## Passives / Forced

- [ ] Trait grants: equip **and** unequip halves
- [ ] While-equipped conditions: stamp / clear / `Character::eventCheck`
- [ ] Opponent Home-block uses move `sourceId` ControllerId
- [ ] Forced end-of-HD destroy: `EventHighDramaPhaseEnd` → unequip → discard

## Actions

- [ ] `extends AttachmentAction`
- [ ] City Actions gate `cardInCity`
- [ ] No `setUsed` / `announceAction` / `resetPlayerPassCount`
- [ ] `createActionResolvedEvent()` present
- [ ] Immediate effects have no invented GameState
- [ ] Picker transitions use attachment `sourceId`
- [ ] Sink = unequip → removeFromPlay → faction-deck bottom (`OwnerId`, `false`)

## Reactions

- [ ] `extends AttachmentReaction`
- [ ] Literals: `setUsed`, `isAvailable`, `ownerIsAttached`
- [ ] Equipped gate (or documented exception)
- [ ] Multi-stage: deferred `setUsed` until finalize
- [ ] Cross-player via reaction transitions (no phase-locked HD state)
- [ ] Cancel: Activated + HIGH_PRIORITY; prefer `_03044` id gates
- [ ] `ISorcererAbility` only if printed Sorcerer — then Start + Played events

## Techniques / Maneuvers

- [ ] Card class `IHas*` + trait + array registration
- [ ] `*Canceled` handled or commented
- [ ] Attachment-hosted `sourceId` = attachment
- [ ] Gambling gates complete
- [ ] Reveal uses chooseList ack when printed Reveal
- [ ] Remainder-of-duel conditions clear on DuelEnd / cancel

## States + JS

- [ ] Constant + State class + `states.inc.php` entry
- [ ] Enter / Update / Leave JS for each new state
- [ ] Home selectable in JS when offered
- [ ] Condition Started/Ended notifs if new condition
- [ ] Multiplayer states in `ZombieTrait`

## Sanity

- [ ] `php -l` on every touched PHP file
- [ ] Mentally run the pre-commit table in `CLAUDE.md`
- [ ] Mirrored a real reference attachment instead of inventing structure
- [ ] Journal entry written with the **why** of non-obvious choices (project convention)

## Next

Pick a mirror card → [[11 — Examples|FactionAttachment Guide Example FactionAttachments To Copy]]
