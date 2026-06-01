# Throwing Knife (_01157) Audit

## Card Text
> **Technique:** Destroy this card • +1 Thrust.

Neutral FactionAttachment. WealthCost 0. P 2 / T 3, Riposte 0 dashed. Traits: Weapon, Ranged, Knife.

## Component Inventory
- `modules/php/cards/_7s5s/_01157.php` — the card. Implements `IHasTechniques`, uses `TechniqueTrait`.
- `modules/php/cards/_7s5s/techniques/Technique_01157.php` — subclass extending `Technique_DestroyPlusOneThrust`, additionally implements `IRangedAbility`.
- `modules/php/cards/techniques/Technique_DestroyPlusOneThrust.php` — shared parent (also used by `_01155` Improvised Weapon, which is Melee → does NOT subclass for IRangedAbility).

The Throwing Knife is Ranged, hence the dedicated `Technique_01157` subclass exists *only* to (a) flag `IRangedAbility` and (b) fire `RangedAbilityPlayed` events. The destroy + thrust mechanics come from the parent.

## Things That Look Right
- Card's `Knife` trait already exists on `_01022` Stiletto, so no need to register anywhere (and `TraitNames` no longer exists per the 01158 audit — memory `feedback_traitnames_alphabetical` is stale).
- Stats and traits match the published card. WealthCost 0 / 2 Parry / 3 Thrust / dashed Riposte ✓.
- Parent's `EventDuelCalculateTechniqueValues` handler correctly mutates `$event->thrust += 1` *and* queues unequip + discard for the attachment.
- Parent also handles `EventGenerateChallengeThreat` analogously — Throwing Knife participates in challenge threat calculations.
- `Technique` base class sets `ResetOnDuelEnd = true`; since the card is destroyed on activation, this doesn't matter in practice (it can't fire twice).
- No `createAttachmentEquippedEvent` is called → no `getRequiredAttachTargetId` needed (pre-commit hook). ✓
- No `IAbilityThatTargetsCharacters` / `IAbilityThatTargetsCards` collision. ✓
- No Action/Reaction subclass — pre-commit checks for `createActionResolvedEvent` / `setUsed` / `isAvailable` don't apply. ✓

## Issues Found

### 1. (BUG, latent NPE) `Technique_DestroyPlusOneThrust::isAvailableToPlayer` dereferences null
Lines 27–28 of the parent:
```php
$owner = $this->getOwningCharacter($theah);
if ($playerId != $owner->ControllerId)
```
`getOwningCharacter()` returns `null` when the host attachment is in play but **not attached** (e.g., Improvised Weapon in the dueling line before equip). Accessing `->ControllerId` then fatals.

In practice, techniques are only queried for in-play attached weapons, so this is latent — but the codebase clearly considers `getOwningCharacter` nullable (it returns `?Character`), and other techniques null-guard it. One-line fix.

### 2. (dead code) `Technique_01157::isAvailableToPlayer` post-checks `isAttached()` that the parent already implicitly enforces
```php
$owner = $this->getOwningAttachment($theah);
return $owner && $owner->isAttached();
```
Once #1 is fixed, the parent already returns `false` for unattached attachments (via the null character branch). The subclass override becomes a pure passthrough.

There's also a subtler issue: the *current* code calls `parent::isAvailableToPlayer()` first, which NPEs before the `isAttached()` check runs. So this defensive check is **dead** today and only "works" if you imagine the parent NPE going away.

### 3. (style/consistency) `handleEvent` uses `getDuelRoundActor()->Id` instead of `$event->actorId`
```php
$actor = $event->theah->getDuelRoundActor();
$rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $actor->Id);
```
`getDuelRoundActor()` returns `?Character`. `EventDuelCalculateTechniqueValues` already carries `actorId` directly (set when the event was created in `EventFactory::createDuelCalculateTechniqueValuesEvent`). The sibling Ranged technique `Technique_01049` uses `$event->actorId` directly — simpler and avoids the null risk that Breastplate's audit (2026-06-01-01) flagged for `getDuelRoundActor()`.

### 4. (style/consistency) Challenge branch reaches through `getOwningCharacter` for the actor
```php
$owner = $this->getOwningAttachment($event->theah);
$character = $this->getOwningCharacter($event->theah);
$rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $character->Id);
```
Same `$event->actorId` is available on `EventGenerateChallengeThreat`. The character with the technique IS the actor for the challenge step that uses this technique. Sibling `Technique_01049` uses `$event->actorId`.

### 5. (style/consistency) `getOwningAttachment` vs `getOwningCard`
The duel branch uses `getOwningCard`; the challenge branch uses `getOwningAttachment`. Both only read `ControllerId` / `Id`, which Card provides. Pick one — `getOwningCard` matches the parent class and the duel branch.

### 6. (observation) Parent's `isAvailableToPlayer` doesn't gate by Engaged / Used
That's correct here — the cost is "Destroy this card," not "Engage," and the card is destroyed on activation anyway. Compared with `Technique_01049` which gates on `! $owner->Engaged` for its engage-cost technique. No bug.

### 7. (observation) Card text is fully expressed by parent + `IRangedAbility` interface
There is no Throwing-Knife–specific game effect beyond "Destroy + 1 Thrust + Ranged." So `Technique_01157` correctly contributes only the Ranged flagging and the RangedAbilityPlayed event. Clean separation.

### 8. (observation) Card text "+1 Thrust" is straightforward — applies during duel calculation; matches parent behavior.
The parent also adds "+1 Threat" on challenge — Throwing Knife can be used during challenges too (challenges run techniques the same way duels do). This matches the published card rules: techniques fire in duels and challenges.

## Risk Assessment
- **#1** — latent NPE; cheap one-line fix; benefits both _01155 and _01157.
- **#2** — dead code; removable.
- **#3, #4, #5** — code-clarity / consistency; no behavioral bugs.
- **#6, #7, #8** — observations only.

No gameplay bug. Text matches implementation faithfully.

## Fixes Applied (this session)

User asked to apply fixes. Applied all five (#1–#5).

- **#1 (parent NPE):** Added a `if (! $owner) return false;` guard in `Technique_DestroyPlusOneThrust::isAvailableToPlayer` before the `ControllerId` check. This also fixes the same latent NPE for `_01155` Improvised Weapon (which uses the parent technique directly).
- **#2 (dead override):** Removed `Technique_01157::isAvailableToPlayer`. Parent now correctly returns false when the attachment isn't attached, so the override added no value.
- **#3 (duel actor):** Replaced `$event->theah->getDuelRoundActor()->Id` with `$event->actorId` for the `EventDuelCalculateTechniqueValues` branch.
- **#4 (challenge actor):** Replaced `getOwningCharacter`-derived ID with `$event->actorId` for the `EventGenerateChallengeThreat` branch. Removed the unused `getOwningCharacter` call.
- **#5 (Owning getter consistency):** Changed `getOwningAttachment` to `getOwningCard` in the challenge branch. Removed the now-unused `Theah` import (it was only used for the `Theah` parameter in the deleted `isAvailableToPlayer` override).

Also removed unused imports (`Theah`, `EventFactory` is still used). The class is now noticeably leaner.

## WHY for future-me

- The parent class `Technique_DestroyPlusOneThrust` carries *all* destroy/+1-thrust logic. Subclassing it for Throwing Knife exists for one reason: **to gain `IRangedAbility`** and fire `RangedAbilityPlayed` events. Anything beyond that in the subclass is bloat. Don't re-inflate it.
- `$event->actorId` is the canonical source of "who is using this technique right now" for both `EventDuelCalculateTechniqueValues` and `EventGenerateChallengeThreat`. **Always prefer it** over `getDuelRoundActor()` (NPE-prone if `IN_DUEL` state is inconsistent) or `getOwningCharacter` (chain hop that adds nothing).
- `Technique_DestroyPlusOneThrust` is shared with `_01155` Improvised Weapon. Changes to its `isAvailableToPlayer` must keep `_01155` working: a Melee attachment that is attached, controlled by the user, available technique — must still return true. The null guard only changes behavior for the unattached case, so _01155's normal flow is unaffected.
- The "Knife" trait existed on Stiletto before this card, so no `TraitNames` registration is needed (and `TraitNames` no longer exists anyway — see 01158 audit).
