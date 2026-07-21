# Create-City-Attachment Skill

Authored `.claude/skills/create-city-attachment/SKILL.md` as a sibling to the existing `create-city-event-card` skill. The motivation was that `_03cd05` (Devil Jonah's Bones) — the cleanest worked example in `faf/` for a city attachment — sits on a meaningfully different base class (`CityAttachment`, not `CityEventCard`), and the patterns differ enough that smashing them into one skill would dilute both.

## What the skill captures

Pulled from these journals:
- `2026-05-03-01-devil-jonahs-bones-03cd05-implementation.md` — the canonical reference.
- `2026-04-26-01-penya-03cd01-implementation.md` — event-ordering inside `handleEvent` (the queue-then-react-to-your-own-event trick).
- `2026-05-10-02-inauguration-day-and-city-event-skill.md` — the parallel skill for event cards, which I used to keep terminology and structure consistent.

Patterns lifted:
- **Forced via `handleEvent`, not via Reaction.** Precedent `_01075` / `_03cd01` / `_03cd05`.
- **`isAttached() && actorId == AttachedToId` gating** for "the equipped character does X."
- **`attachedTo()->ControllerId`** preferred over `$event->playerId` for resilience to controller changes.
- **Steady-state overrides** (`getNumberOfGambleCardsToReveal`) instead of mutating globals — this was a review-driven correction in the `_03cd05` journal and deserves prominent placement.
- **Paired `EventAttachmentEquipped` / `EventAttachmentUnequipped`** for passive grants (`_01198`, `_02047`).
- **`isAttached()` guard around destroy/discard** in actions to handle copied-action edge case (`_01191`).
- **Splitting setup-from-execution** by inserting a new game state + events-state pair before an auto-running state — the `_03cd05` DUEL_GAMBLE_SETUP topology.
- **Variable-name landmine** around `$fromBottom` and `$bOnTop` — flagged because it bit me in `_03cd05` and the comment in the code is the only thing keeping a future session from "fixing" it backwards.
- **Defensive global reset** on the default branch — the lesson from `_03cd05`'s id == 1 explicitly setting `GAMBLE_REVEAL_FROM_BOTTOM = false` rather than leaving it alone.
- **Pre-commit hook subset** that actually applies to attachments — including the gotcha that the `createAttachmentEquippedEvent → getRequiredAttachTargetId` rule concerns the *equipping action*, not the *attachment being equipped*.

## Skill scoping decisions

- **Project-local** under `.claude/skills/`, like the sibling event-card skill. Tightly coupled to this repo's class layout.
- **Pushy description** that names file patterns (`_03cdNN.php`), the base-class condition (`extends CityAttachment`), and natural-language triggers ("the equipped character does X", "wire up the equip wound"). Also explicitly cross-references the sibling skill so the agent picks the right one when the stub `extends CityEventCard`.
- **Single `SKILL.md`**, no external assets. Anything more would be premature — patterns might shift as more `faf` attachments land.

## Open questions / followups

- I didn't include a "destroy this card" attachment-action template in full — pointed at `Action_01187` and `Action_01191` and inlined the key snippet. If a future card needs more glue, expand from there rather than copy-pasting the whole action class.
- The skill doesn't cover `FactionAttachment` (a parallel base class for character-specific attachments like `_02047` Temnota). Different ownership rules. If someone tries to use this skill on a faction attachment, they'll get most of the way there but the city-deck plumbing (`CityCardNumber`, city-deck location lifecycle) won't apply.
- I asserted that `WealthCost` defaults / `OffHand` need not be discussed for typical city attachments. If a card needs the off-hand / two-hand-conflict logic, the skill silently doesn't help. Acceptable starting scope.
- The DUEL_GAMBLE_SETUP topology section is fairly involved — borderline whether it deserves its own dedicated skill ("insert a new state into a core auto-state"). Held off splitting because it's currently a one-instance pattern and the journal entry it's drawn from is small.
