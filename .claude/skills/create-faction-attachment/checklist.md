> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (Stat / Equip Restriction / Passive Grant / Forced / Action / Reaction / Technique / Maneuver). Stat numbers and combat-card stats go on the constructor and are not a "pattern."
2. Confirm: `initializeFaction(<faction>)` is called, `CardNumber` matches the filename's `NNNNN`, `WealthCost` is set, all stat modifiers are set (default 0), all Traits exist in `TraitNames::$TraitsJson`.
3. For equip restrictions, implement BOTH `eventCheck(EventAttachmentEquipping)` AND `canAttachTo(Character)`. Don't pick one — the UI uses `canAttachTo` to grey out invalid targets, and `eventCheck` is the server-side enforcement when the equip event fires.
3a. **"After a \<Trait\> equips this card"** is a **Reaction trigger gate**, not an equip restriction. Do **not** add Pattern A unless text also says "May only equip…". Reference: `_04016`.
3b. For **opponent-equip** (`CanEquipToOpponents = true`): set the flag; remember HD `CHOSEN_PERFORMER` is the **target**. "Opposing" = different controller + same location. If text compares to "your performer," use a same-location ally with greater `Modified*` (`_03066`) — do not assume a second picker exists. After equip, attachment `ControllerId` stays the **equipper**. Watch equip-discount abilities (`_03063`) — "opponent equips" must use `$attachment->ControllerId`, not `$performer->ControllerId`.
4. For passive trait grants, implement BOTH `EventAttachmentEquipped` (add) AND `EventAttachmentUnequipped` (remove). Don't forget the unequip half.
4b. For **while-equipped condition restrictions** (Pattern B''):
    - Lodestone-style ability-scoped Home block: `_03065` — opponent detection via move **`sourceId` ControllerId**, never `initiatingPlayerId`.
    - Shackles-style **"cannot move"** (all destinations): `_03066` / `SHACKLES_CONDITION` — Harpoon-shaped `EventCardMoving` gate + `unstoppable`; **no** swap gate unless text says so; clear on unequip (not DuelEnd).
    - Activate-time checks only on abilities that always do the blocked thing; move-only → skip swap techniques.
4c. **Forced destroy at end of High Drama:** `EventHighDramaPhaseEnd` + `isAttached()` → unequip → discard (`asEffect=true`). Mirror `_01025_Burden` trigger + `_01153` destroy. Unequip clears B'' conditions.
4d. For **duel-scoped conditional** bonuses (Pattern B''' — `_04006`):
    - Do **not** set constructor `*Modifier` for conditional "during a duel / while …" text.
    - Stat half: applied flag + `createCharacter*ModifiedEvent`; recompute on DuelStarted / Wounded / Healed / Swapped / Equipped; clear on DuelEnd / Unequipped.
    - Wound/heal: apply `characterHandled` delta (Benci) so stale `Wounds` does not miss the flip.
    - Unequip undo uses `$event->characterId` — EventHub clears `AttachedToId` before card `handleEvent`.
    - Gamble +1: `getNumberOfGambleCardsToReveal` with the **same** condition gate (no flag). If text prints both +Stat and +reveal, implement **both** — Finesse also caps gambles-left separately from reveal count.
    - Always-on "when equipped character gambles" (no while-condition): Gallegos / `_04017` — no duel-wound gate.
    - No Action/Reaction/Technique file unless a keyword is also printed. No Game condition/JS unless a tooltip is requested.
    - Passive gamble paragraph + separate `<b>Technique:</b>` → still a **normal** Technique unless the keyword says Gambling (`_04017`).
5. **Parse keyword(s) literally** before picking interfaces:
   - "Sorcerer …" → `implements ISorcererAbility` + emit Start/Played events in the Action/Reaction class.
   - "Strega …" / "Mercenary …" / "Diplomat …" / etc. → performer-trait gate (`hasTrait("Strega")` on the equipped character or chosen performer). NOT a Sorcerer ability.
   - **"Gambling Technique/Maneuver"** → `Game::DUEL_GAMBLED` + actor identity gate. NOT a trait gate.
   - Both Sorcerer and trait gates can stack ("Sorcerer Strega Reaction" is both).
6. For Reactions with **engage cost**, gate the trigger on `! $owner->Engaged` AND `$this->isAvailable()`, then queue `createCardEngagedEvent` on the attachment in `performReaction`. The dusk reset handles both `Engaged` and `Used`. **If a later stage still needs a reaction transition, do not `setUsed` on Engage** — `runEvents` skips reaction transitions when `!isAvailable()`. Defer to finalize (`03007`, `03044`).
7. **Cross-player reactions** (opponent must do part of the resolve): use multi-stage `$stage` + `createReactionTransitionEvent($opponentId, ...)`. Do NOT create a dedicated sub-state — reactions can fire from any phase and a sub-state is only reachable from its phase's `*_EVENTS` transitions. During duel cancel interrupts, set **`HIGH_PRIORITY` on every** reaction transition in the chain (default `REACTION_PRIORITY` is later than MEDIUM Resolve).
8. For multi-step pools ("sink two cards"), structure `advanceToNext*` to return `false` when the pool is exhausted, and have the caller finalize early. "If able" is implicit in the rules text.
9. **Log auto-applied branches.** When an edge case skips the player's choice (no cards to sink, no Leader to wound, empty hand on "unless discard", etc.), notify *before* the consequent effect so players understand why the choice wasn't offered.
10. Capture event-time context onto the reaction/technique; clear it when the flow finishes / on cancel. Use `$owner->IsUpdated = true` to persist. For **cancel Maneuver/Technique** reactions, prefer **public** `$TechniqueId` / `$ManeuverId` / `$stage` / restore ids (mirror `01047` / `03044`) — nested private fields have been fragile across multi-stage playerReaction requests.
11. **Typed parameters** on every function/method signature. No bare `$foo`. Add `use ...\cards\Card;` (etc.) imports as needed.
12. Pre-commit hook checks on every file you touched:
    - **AttachmentReaction subclass:** `$this->setUsed(`, `$this->isAvailable(`, `$this->ownerIsAttached(`.
    - **AttachmentAction subclass:** `createActionResolvedEvent()` called. NO `setUsed`/`announceAction`/`resetPlayerPassCount`.
    - **`implements ISorcererAbility`:** both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()`.
    - **Maneuver/Technique:** handle the corresponding `*Canceled` event (or add the literal comment).
    - No class implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards`.
13. For **attachment-hosted Techniques** that transition into player states: `sourceId` = `getOwningCard()->Id` (attachment), not the equipped character.
14. When text **reveals** cards: add a multiplayer `chooseList` acknowledge state (Constanzo / Lorenzo / `_03043`). Do not rely on log messages alone. Resolve discard/wound/etc. **after** ack. Register the multi state in `ZombieTrait`.
15. **"Cancel unless they discard" Maneuver/Technique:** cancel-first on Engage (`delete*Events` + clear `CHOSEN_*`, no `*Canceled` yet); discard re-queues Resolve/Calculate; Accept Cancel fires `*Canceled`. Listen on `*Activated`, not `EventResolve*`. Use correct adversary id gates (character vs player). See `_03044`.
16. **Choose-location AttachmentAction:** `sourceId` = attachment; HD GameState + `"NNNNN"` on `HIGH_DRAMA_PLAYER_TURN_EVENTS`; pay engage (if any) with the move on location resolve; exclude current location when this card itself satisfies the destination trait; Home in PHP **and** JS (`makeHomeEndcapMarkerSelectable`) when text is not City-only. See `_03055`.
16b. **Immediate-resolve / "Sink this card" AttachmentAction:** no GameState when there is no picker. Sink equipped attachment = unequip → removeFromPlay → `createCardAddedToFactionDeckEvent(OwnerId, id, false)`. Then effects; move with `engage=false`. Mirror `Technique_02055` / `Action_03065`.
17. **Remainder-of-duel condition** ("-N [Stat], cannot swap/move for the remainder of the duel"):
    - Add `Game::*_CONDITION` string; stamp on the affected character (not a Technique `$Active` bool alone).
    - Stat mod via `createCharacter*ModifiedEvent` + `updateCardObjectInDb` after `addCondition` / `removeCondition`.
    - Clear on `EventDuelEnd` **and** `*Canceled`; skip restore if character is in discard/locker.
    - **Cannot move:** `Character::eventCheck` on `EventCardMoving` (respect `unstoppable`) **plus** activate-time `eventCheck` on deferred EndOfRound movers (`EventTechniqueActivated` / `EventManeuverActivated`) — check the character who would actually move (actor vs adversary).
    - **Cannot swap:** gate in `Theah::swapParticipantsInDuel` *before* mutating duel rows; do **not** rely on `EventChallengerSwapped` / `EventDefenderSwapped` (too late). Add activate-time checks on swap techniques that wound-before-picker (`Technique_03013`).
    - Intervene ≠ swap. JS: constant + Started/Ended notifs (Soline tooltip shape). See `_03064`.
    - Distinct from Pattern B'' while-equipped conditions (`_03065` Lodestone, `_03066` Shackles) — those clear on unequip, not DuelEnd. Shackles is move-only (no swap gate).
18. **Self-equip heal Reaction** (`EventAttachmentEquipped` for `$event->attachmentId == $owner->Id`): gate host traits (OR) + `Wounds > 0`; transition `sourceId` = attachment; `createCharacterBeingHealedEvent`. Not Pattern A. Reference: `Reaction_04016`.
19. **Gambling Technique deferred EndOfRound threat:** public `$IsActive` on Resolve; fire `createThreatModifiedEvent(1, 1)` on EndOfRound only when `actorId == owningCharacter->Id`; clear on fire / `EventTechniqueCanceled` / `EventDuelEnd` (do not clear on every EndOfRound). No GameState. Distinct from remainder-of-duel `Game::*_CONDITION` (Harpoon). Reference: `Technique_04016`.
20. **Resolve-time "If your participant is a Trait…"** inside Technique text: check traits on `EventResolveTechnique` **after** costs; do **not** gate `isAvailableToPlayer` on those traits. Unconditional halves (engage, +Thrust) still apply for other hosts. Reference: `Technique_04017`.
21. **Adversary discards a card** (Technique): Maya hand picker — `createTechniqueTransitionEvent` to adversary when hand non-empty; empty → notify + skip; attachment `sourceId`; `createCardDiscardedFromHandEvent(..., asEffect=true)`. Wire bas/faf GameState + expansion JS + `EventHandlers.js`. Distinct from reveal-then-discard (`_03043`) and cancel-unless-discard (`_03044`). Reference: `Technique_04017` / `01093` / `03039`.
21b. **Pressure fails instead** (difference ≤1): `EventLocationPressured` (`success` + `difference <= 1`); optional `"at this location"` → `$event->location == owningCharacter->Location`; engage cost → fail in `performReaction` (not Objection wealth-pay / `ICancelReaction` / `EventRiskReactionTriggered`). Capture pressured fields; `HIGH_PRIORITY` offer; `deletePressureResultEvents` + `createLocationPressureResultEvent(..., false)`. Gate out own pressures (`playerId == ControllerId`) unless Rules say otherwise. Reference: `Reaction_04026` (attachment) / `Reaction_01027` (Risk math sibling).
21c. **Engage + +N Parry** (normal Technique): same availability/Resolve engage as engage + Thrust (`Technique_04017`); Calculate `parry += N`. No GameState. Reference: `Technique_04026`.
22. Lint touched PHP files (`php -l`) before committing. **Do not** rewrite line endings after Write — leaves `\r\r\n` on this Windows repo. If a file already has doubled CRs (`0D 0D 0A`), fix with `\r\r\n` → `\r\n` only; do not convert the repo to LF.
23. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>-implementation.md` covering the WHY: which existing patterns you mirrored, what alternatives you considered, anything that looks weird (defensive null checks, dual-gate equip restrictions, the order of Sorcerer Start/Played around effects). Read related faf journals first — they encode hard-won knowledge about edge cases.
