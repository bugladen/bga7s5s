# Silver Spine (`_03cd21`) — implementation

City Attachment. Stat: `+1 Resolve`. Cost 2.

> **Forced:** Each Day, the first time an opponent's Risk targets the equipped
> character • Cancel the effects.

## Pattern source

User pointed me at `_01186` (Maryam Benu Pleroma). Maryam is a `CityCharacter`
with the same shape: each-Day, first-time, opponent-Risk-targets-her ⇒
cancel-effects + flip a `*_ABILITY_USED` condition that clears at
`EventDuskEndOfDay`. The five event types Maryam intercepts are the same
ones a Risk that targets characters can fire:

- `EventCardMoved` / `EventCardEngaged` — Risks that move/engage characters.
- `EventChallengeIssued` — Risks that issue challenges.
- `EventCharacterBeingWounded` — Risks that wound.
- `EventAttachmentEquipping` — Risks that equip an attachment onto a character
  (e.g. `Action_02008` Fate's Kiss → `_02008_RiskClone`).

The only Maryam-specific quirk for the equipping case is that the canceled
attachment has to be discarded by hand, since the matching
`EventAttachmentEquipped` will never fire. I copied that branch verbatim, with
the same CityAttachment-vs-faction split (`createCardAddedToCityDiscardPileEvent`
vs `createCardDiscardedFromPlayEvent`). See the 2026-04-14-03 journal —
Maryam's coverage of this event type was a later addition specifically because
otherwise the to-be-equipped card sits in limbo.

## Differences from Maryam

1. **Card class is the attachment, not the character.** Identity check is
   `$event-><target> == $this->AttachedToId`, not `$this->Id`. Added an
   `isAttached()` guard up front so the trigger is a no-op while the attachment
   is in the city deck / discard / itself being equipped via a Risk.
2. **"Opponent's Risk" filter.** Maryam's text says "the first time a risk
   targets Maryam," without a controller restriction. Silver Spine says "an
   opponent's Risk." I added `$source->ControllerId != $this->ControllerId` —
   same shape as `Reaction_02048::isFromOpponentRiskThatTargetsCharacters`.
   On a city attachment, `ControllerId` mirrors the equipped character's
   controller, so this naturally means "Risk played by someone other than the
   player who controls the equipped character." Matches the memory note that
   the user is strict about *opposing* vs *different controller* — here the
   card uses "opponent's" which is the looser of the two (no location
   restriction), so the controller check alone is correct.
3. **Condition lives on the attachment, not on the character.** Once-per-Day
   is a property of *Silver Spine* (the artifact), not of the character it
   happens to be equipped to. If the artifact were unequipped and re-equipped
   mid-day to another character, the once-per-day should still be spent —
   storing the condition on the attachment makes that fall out naturally. Also
   simplifies the unequip case: when Silver Spine is removed, the condition
   goes with it.

## Why not a `CardReaction`?

Forced abilities are mandatory and require no player choice — putting them in
`handleEvent` on the card itself is the established pattern (`_01075` Tabard,
`_01186` Maryam, `_03cd01` Penya, `_03cd05` Devil Jonah's Bones). A
`CardReaction` would force me to wire `setUsed`/`isAvailable` for a Forced
that gives the player no decision, and the pre-commit hook would then demand
those exact calls in a pattern that doesn't fit.

## sourceId nullability

`EventAttachmentEquipping::sourceId` is `?int`, the other four are `int`.
`$event->sourceId != 0` works for both — in PHP loose comparison
`null != 0` is `false`, so null-source events get filtered out (no Risk =
nothing to cancel). The same idiom is what Maryam uses, so I kept it for
consistency rather than hand-rolling a `!== null && !== 0` variant.

## UI plumbing — the new annoying part

Maryam/Carmella both got a small image-cropped chip overlay on top of the
character's card image, plus a tippy tooltip. Existing infra:

- `Card::addCondition` / `removeCondition` already set `IsUpdated = true` so
  the conditions array round-trips through `card_serialized`.
- `gamedatas.card.conditions` is populated on page load.
- `Setup`-time chip rendering happens inside `createCharacterCard` —
  attachments don't get this treatment.

So I had to add:

1. `Game::SILVER_SPINE_ABILITY_USED` PHP constant.
2. `this.SILVER_SPINE_ABILITY_USED` JS alias.
3. `notif_silverSpineAbilityUsed` / `notif_silverSpineAbilityRemoved` in
   `Notifications.js`, plus their entries in the notif list (priority 500 to
   match Maryam — these can fire during animation-sensitive flows).
4. **New** chip-render block inside `createAttachmentCard`. There was no prior
   `attachment.conditions.includes(...)` check there — attachments had never
   needed condition chips. Added one block, keyed off
   `this.SILVER_SPINE_ABILITY_USED`.
5. `._7sfs-silver-spine-ability-used-chip` CSS, anchored at `left: 0; top: 0;`
   because attachments are splayed under the character at
   `left: calc(var(--attachment-index) * -15px)` — only ~15px of the leftmost
   strip is visible in the un-splayed view. Anchoring upper-left is the only
   spot that's reliably visible whether splayed or not. Added `z-index: 20` so
   the chip floats above the attachment-stack stacking context.

The user flagged this exact gotcha after my first pass put the chip at
`top: 60px; left: 80px;` (copying Carmella's offset). That position would be
fully occluded under the next attachment in the splay.

## What I deliberately didn't do

- **No new generic attachment-condition framework.** I'm not threading
  every existing condition through `createAttachmentCard`, just adding the
  one block for Silver Spine. If future attachments need other conditions,
  they can add their own block or this can be refactored at that point.
- **No special handling for `EventAttachmentEquipping` where Silver Spine
  itself is the would-be attachment.** `$this->isAttached()` is false in that
  case, so the trigger is correctly skipped — but the practical concern is
  whether a Risk could try to equip Silver Spine onto an opponent's character
  hostilely. Even then, the cancel wouldn't apply (Silver Spine isn't yet
  equipped to anyone), which seems right — its protection only kicks in
  *after* it's attached.
- **No condition-image override.** The chip uses `03cd21.jpg` cropped at
  `-110px -110px`, which is a stab in the dark. If the actual card art
  doesn't have a useful icon at that crop, the user can tune the background
  position later.

## Pre-commit hook

- Extends `CityAttachment` (not an action/reaction class).
- Doesn't call `createAttachmentEquippedEvent` — just `instanceof
  EventAttachmentEquipping` (different string). The hook's grep won't match.
- No `setUsed` / `isAvailable` / `createActionResolvedEvent` requirements
  apply.

So the hook is satisfied without any explicit calls.

## Risks / things to verify in play

- **Page-refresh render.** After the ability has been used, refresh and
  confirm the chip reappears on Silver Spine. Path:
  `gamedatas.cityLocations[...].attachedCards[...].conditions` → `Setup.js`
  attachment loop → `createAttachmentCard` → new chip block.
- **The cancel handles all five event types in practice.** I have no easy way
  to construct a unit test for this — would need to QA against a Risk that
  fires each of: card move, card engage, character wound, challenge issued,
  attachment equipping. The attachment-equipping path is the riskiest because
  of the manual discard-the-canceled-attachment branch — same code that
  Maryam uses (and that the 2026-04-14-04 RiskClone bug was eventually closed
  out on), so it should be safe.
- **Re-equip across day boundary.** If Silver Spine is unequipped mid-day
  while the ability is spent, then re-equipped later that same day, the
  condition is gone (it traveled with the card, but the user-facing chip is
  rendered against the new equip; PHP-side it's still on the card). Net
  effect: the once-per-Day stays spent across the unequip → re-equip cycle,
  which is the rules-correct read.

## Bug carried over from Maryam/Carmella: chip-removal id mismatch

First-pass copy of the Maryam/Carmella `notif_*AbilityRemoved` shape used
`` const id = `${args.cardId}_silver_spine_ability_used`; ``
for `dojo.destroy(id)`. That's a latent bug: the chip was *placed* with
`` `${card.divId}_silver_spine_ability_used` `` (full DOM id, e.g.
`${controllerId}-${cardId}`), so destroy by `args.cardId` silently misses and
the chip persists past dusk.

The Maryam/Carmella implementations have the same mismatch — they happen to
get away with it because the dusk-end-of-day flow tends to redraw the play
area anyway, but my Silver Spine notification fires cleanly without a redraw
and the user noticed.

Fixed by switching the removal id to `card.divId`-based, matching the
placement. **If we touch Maryam/Carmella next, apply the same fix there.**

## Files touched

- `modules/php/Game.php` — `SILVER_SPINE_ABILITY_USED` const.
- `modules/php/cards/faf/_03cd21.php` — full handleEvent implementation.
- `seventhseacityoffivesails.js` — JS alias.
- `modules/js/Notifications.js` — notif list entries + two handlers.
- `modules/js/Utilities.js` — chip render in `createAttachmentCard`.
- `seventhseacityoffivesails.css` — `._7sfs-silver-spine-ability-used-chip`.
