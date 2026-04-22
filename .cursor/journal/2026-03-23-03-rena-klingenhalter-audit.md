# Rena Klingenhalter (_01040) Audit

## What I found

Four bugs, all related to the intervention/weapon mechanic being incomplete. The +1 Combat logic was correct.

### The big one: "even while engaged" was entirely missing

Rena's card says she can intervene while engaged if she has a Weapon. But she wasn't special-cased anywhere in the intervention pipeline — `ArgumentsTrait` filtered her out of the UI when engaged, `interventionCheck` rejected her, and `actHighDramaChallengeActionIntervene` unconditionally engaged her.

This follows the same pattern as Carmella (_01178) and Mourad (_02003), but Rena's bypass is conditional on having a Weapon equipped. So I couldn't just add her to the existing instanceof check — needed a separate `else if` with the weapon condition.

Added `hasWeaponEquipped(Theah $theah)` helper to `_01040.php` since the check appears in five different places across the codebase. It takes Theah as a parameter because `$this->Attachments` only stores IDs — you need `$theah->getAttachmentById()` to check traits.

### The engagement flow was subtly wrong

The old reaction code did: engage Rena (from intervention) → reaction fires → en-garde Rena + engage weapon. This works when Rena starts un-engaged, but if she's already engaged (which the card explicitly allows!), the en-garde would strip her pre-existing engagement.

WHY the new approach: I restructured so `actHighDramaChallengeActionIntervene` skips Rena's engagement entirely when she has a weapon (sets `$engageRequired = false`), deferring the decision to the reaction. Accept = engage weapon only. Decline = engage Rena. This way Rena's pre-existing engagement state is never touched.

This follows the Odette/Musketeer pattern already in `actHighDramaChallengeActionIntervene` where `$engageRequired` is set to false under certain conditions.

### Weapon filtering was missing from the reaction

Both `handleEvent` (trigger) and `getReactionButtonProperties` (UI) operated on ALL attachments instead of just Weapons. An edge case: Rena with only an Armor attachment would trigger the reaction and show "Engage: [armor name]" as an option to replace her engagement.

## Pattern note

The "intervene while engaged" bypass pattern now has three characters:
- _01178 (Carmella): conditional on `!AbilityUsed`
- _02003 (Mourad): unconditional (handled via reaction)
- _01040 (Rena): conditional on `hasEngardeWeaponEquipped()`

All three use special-case instanceof checks in `ArgumentsTrait` and/or `interventionCheck`. The journal from the Mourad audit (2026-03-16-03) suggested a `canInterveneWhileEngaged()` method if more characters get this ability. With three now, it might be worth considering, but each has different conditions so the method would need Theah context. Not worth refactoring today.

### Follow-up: engaged weapon check

Eddie caught that `hasWeaponEquipped()` didn't check `$attachment->Engaged`. If all weapons were engaged, Rena could still bypass the engagement restriction and "engage" an already-engaged weapon for zero cost. Added `hasEngardeWeaponEquipped()` that requires `!$attachment->Engaged`. All bypass checks and the reaction trigger/buttons now use this method. The original `hasWeaponEquipped()` is kept for the +1 Combat logic, which correctly doesn't care about engagement status — it just tracks whether any weapon is equipped at all.

## Re-implementation on main (session 2)

The original audit was done on a branch that had `Theah::interventionCheck()`. Main doesn't have that method — on main the intervention checks are done inline in two places:

1. `ArgumentsTrait::argsHighDramaChallengeActionAcceptChallenge()` — builds the list of who can intervene (UI side)
2. `FrameworkActionsTrait::actHighDramaChallengeActionIntervene()` — validates the intervention action (server side)

Both already had `_01178` (Carmella) special cases. I added `_01040` (Rena) `else if` branches alongside them, using `hasEngardeWeaponEquipped()` as the condition.

WHY the bypass pattern is structured as separate `else if` blocks instead of a combined condition: each character that can intervene while engaged has different conditions (Carmella's is `!AbilityUsed`, Rena's is weapon-based). Combining them into one expression would be fragile and hard to extend.

The `Character.php` base class already had `hasWeaponEquipped()` and `hasEngardeWeaponEquipped()` on main (must have been merged separately or added during another session). So `_01040.php` didn't need any changes — the helpers are inherited.

The `Reaction_01040.php` fixes were straightforward re-implementations of the branch work:
- Weapon-only filtering in button list (+ engagement check)
- `hasEngardeWeaponEquipped()` trigger instead of `count(Attachments) > 0`
- Removed en-garde event; accept = engage weapon only, decline = engage Rena
