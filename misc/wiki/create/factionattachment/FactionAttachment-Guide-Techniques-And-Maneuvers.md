# 08 — Techniques and Maneuvers

← [[07 — Reactions|FactionAttachment Guide Reactions]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[09 — Wiring states and JavaScript|FactionAttachment Guide Wiring States And JavaScript]]

When the printed text has **Technique** or **Maneuver**, create a class under `techniques/` or `maneuvers/` and register it on the attachment.

## Wire-up on the card class

FactionAttachment does **not** include Technique support by default. On the attachment:

```php
class _NNNNN extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        // …
        $this->resetCard();
        $this->Techniques = [new Technique_NNNNN()];
    }
}
```

Same idea for Maneuvers with `IHasManeuvers` + `ManeuverTrait`.

## Pre-commit

| Class | Required |
|---|---|
| `extends Technique` | Handle `EventTechniqueCanceled` **or** comment `// EventTechniqueCanceled handler not needed` |
| `extends Maneuver` | Same for `EventManeuverCanceled` |

## Attachment-hosted transitions — `sourceId`

When a Technique on an **attachment** opens a player state, pass the **attachment** id as `sourceId`:

```php
EventFactory::createTransitionEvent(
    $playerId,
    $this->getOwningCard($theah)->Id,  // attachment, NOT character
    'NNNNN',
    $this->Id
);
```

**Why:** `actFromCardWithId` / `argsForState` hydrate the source card and call `getTechniqueById` on it. If `sourceId` is the character, the technique is invisible and the act fails.

Mirror: `Technique_03043`, `Technique_02006`.

## Gambling Technique / Maneuver

**Gambling** is not a trait. Always gate:

1. `Game::IN_DUEL`
2. `Game::DUEL_GAMBLED`
3. Actor is the equipped character

Optional printed conditions (greater Finesse than adversary, adversary wounded, …) go in the same availability check.

Mirror: El Gato's Mask `Technique_03043`, Harpoon `Technique_03064`, Aja `Technique_03002` (character-hosted twin for Gambling gates).

## Engage this card (on a Technique)

Gate `!$attachment->Engaged` and queue `createCardEngagedEvent` on the **attachment** id when the Technique resolves. Mirror `Technique_03064` / `Technique_01049`.

## Remainder-of-duel conditions

When text says the adversary **has −N [Stat], cannot be swapped, and cannot move for the remainder of the duel**:

1. Stamp a `Game::*_CONDITION` on the affected character (do not rely on a Technique `$Active` bool alone).
2. Apply / reverse stat mods via `createCharacter*ModifiedEvent`.
3. Clear on `EventDuelEnd` **and** `*Canceled` (skip if character is already in discard/locker).
4. Enforce moves in `Character::eventCheck` (respect `unstoppable`).
5. Enforce swaps in `Theah::swapParticipantsInDuel` **before** duel rows mutate — `*Swapped` events are too late.
6. Add activate-time checks on Techniques/Maneuvers that choose now and move/swap later, so the player gets a clear error instead of a stuck picker.

**This is not the same as while-equipped locks** (Lodestone / Shackles) — those clear on **unequip**. See [[05|FactionAttachment Guide Passives And Forced]].

Mirror: Harpoon `_03064` (+ Soline `_01089` for condition JS / Finesse mod shape).

## Reveal to all players

Log inject codes alone are **not** enough when text says **Reveal**. Players must see cards in the shared `chooseList` and acknowledge.

Typical pipeline (El Gato's Mask `_03043`):

1. Persist revealed ids on the Technique + `$owner->IsUpdated = true`.
2. Notify reveals.
3. Multiplayer acknowledge state (`stMultiPlayerInitSansInitiatingPlayer`).
4. Game state branches via `stateFromTechnique`.
5. Only **after** ack: discard / wound / etc.

Register the multi state in `ZombieTrait`. See [[09|FactionAttachment Guide Wiring States And JavaScript]].

## Simple Technique (no picker)

Unsavory Salve `Technique_01050` (−1 Thrust + wound) resolves on the Technique events without a custom HD GameState — prefer that when text needs no choice.

## Checklist for this pattern

- [ ] Card class has the correct `IHas*` + trait + array registration after `resetCard`
- [ ] `*Canceled` handled or commented
- [ ] Attachment-hosted transitions use attachment `sourceId`
- [ ] Gambling gates `IN_DUEL` + `DUEL_GAMBLED` + actor
- [ ] Remainder-of-duel ≠ while-equipped clear timing
- [ ] Reveals use chooseList ack, not log-only

## Next

When you added picker / ack states → [[09 — Wiring|FactionAttachment Guide Wiring States And JavaScript]]  
Otherwise → [[10 — Checklist|FactionAttachment Guide Finish Checklist]]
