# Indomitable Will — second copy death leaves location controlled

## Bug (Eddie)

Two Indomitable Wills in deck. Play #1, character moves away (condition ends). Later play #2 on another character; that character dies in combat. Location stays controlled.

## Root cause

Lasting IW state (`IsActive` / `ControllingCharacterId` / `ControlledLocation`) lived only on the **played Risk's Action**. Cleanup listeners were gated on `$this->IsActive` on that Action instance.

With two copies (and/or reshuffle into the faction deck, which `buildCity()` never loads), the Action that still has `IsActive` can be out of the event loop when the stamped character dies. Destroy recreates the character (wiping the condition as a side effect of recreate) but **never queues location uncontrolled** or restores `CanBeClaimed` / `CanBecomeUncontrolled`.

Same class of failure as Deal with the Devil: correlator must live on the character when the card holding the Action may not be in `$theah->cards`.

## Fix

1. `Action_01130::endEffect()` — idempotent; condition is the lock; clears every in-world + faction-deck `Action_01130` tracking that character; restores flags; queues uncontrol only if location still has a Controller.
2. `Character::handleEvent` — on destroy / city move-away / discard-from-play / dusk, if `INDOMITABLE_WILL_CONDITION`, call `endEffect`. Character is always in the event loop at destroy time (before EventHub recreate).
3. Existing Action listeners kept; they call the same path. Double-fire is safe (idempotent).
4. `Theah::getWorldCards()` — so endEffect can clear Action state on discarded copies.

## WHY not only fix IsActive persistence

Could chase why the second copy's Action wasn't IsActive at death — fragile with N copies and deck reshuffles. Character-condition correlator matches how Harpoon / Soline / Deal with the Devil already work.
