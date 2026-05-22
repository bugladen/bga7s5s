# Maxime (01069) — taking wounds from Porté Travel (01085) Sorcerer Action

## The bug

User report: when Maxime de Lafayette (01069) is chosen as the performer for
Action_01085 (Porté Travel's "Sorcerer Action: Wound your performer • Move
target character..."), he takes the wound. He shouldn't — his Text says:

> Maxime ignores wounds from Sorceries and Sorcerer abilities he performs.

## Root cause

`_01069::handleEvent` gated the entire ignore-wounds branch on the source card
being Maxime himself or one of his attachments:

```php
if ($source?->Id == $this->Id || in_array($event->sourceId, $this->Attachments))
{
    // ... inner check for ISorcererAbility / Sorcery trait
}
```

But the wound event from `Action_01085` is constructed with the Porté Travel
*card* (01085) as the source:

```php
EventFactory::createCharacterBeingWoundedEvent($performer->Id, $porteTravel->Id, 1, ...);
```

Porté Travel is not Maxime and is not an attachment on Maxime, so the outer
guard skipped the entire branch and the wound applied.

The previous audit (2026-04-10-16) had narrowed the source check from
"controller == Maxime's controller" to "Maxime or his attachment" on the
reasoning that "Sorcery risk maneuvers in duels wound the adversary (not the
performer), so attachment coverage is sufficient." That overlooked Sorcery
**Sorcerer Actions** (not duel maneuvers) where the source is the Sorcery card
itself and the wound is directed at the chosen performer.

## Fix

Rewrote `handleEvent` to evaluate two independent conditions:

1. **Source has Sorcery trait** → ignore. A Sorcery's wound effect targets the
   chosen sorcerer (its performer). If Maxime is the wound target, he's the
   performer of that Sorcery. Covers both `_01085`'s "When played" forced
   ability and `Action_01085`'s Sorcerer Action.
2. **Source's ability is ISorcererAbility AND Maxime is `CHOSEN_PERFORMER`** →
   ignore. Catches Sorcerer abilities hosted on non-Sorcery cards (character
   own abilities, attachments, etc.) where Maxime has been selected as the
   performer.

Why two conditions instead of one unified check: the Sorcery-trait wound path
doesn't reliably set `CHOSEN_PERFORMER` (`_01085::actFromCardWithId` chooses
the sorcerer ad-hoc and queues the wound without going through the standard
SorcererAbility tracking — no `createSorcererAbilityStartEvent` call), so we
can't rely on the global there. The Sorcery-trait check is sound because every
Sorcery wound effect in the codebase targets its performer (verified: 01076,
01085 forced, Action_01085 all wound performer; no Sorcery wounds side
targets).

Also added the missing `use Bga\Games\SeventhSeaCityOfFiveSails\Game;` import.

## Code shape change

Restructured the original "if not Wounded { parent } if Wounded { ... }"
pattern into early-return blocks. Same semantics, easier to read; eliminates
the dead `if ($event instanceof EventCharacterWounded)` second check after
the first one already returned.

## What I'd flag

- The `CHOSEN_PERFORMER` global is the source of truth for ISorcererAbility's
  "performer", but Sorcery card "When played" effects bypass it. If a future
  Sorcery uses `CHOSEN_PERFORMER` instead of an ad-hoc selection, the second
  condition would cover it and the first becomes belt-and-suspenders. For now
  both branches earn their keep.
- The previous audit's reasoning ("attachment coverage is sufficient") was a
  good-faith but incomplete read of "he performs." Worth remembering: "Maxime
  performs X" includes being the chosen performer of *any* Sorcery card or
  Sorcerer-tagged ability, not just abilities sourced from cards he owns.
