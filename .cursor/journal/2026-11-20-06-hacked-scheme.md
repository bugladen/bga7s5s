# Stolen Schematics (_05Hacked) finish

## Context
Prototype CAD Scheme. User: finish via create-scheme skill; no clienttranslate.

## Art vs scaffold
- Traits art: **Discovery / Stealth** (scaffold had Dar Matushki / Command — wrong).
- Initiative 0 / Panache 0 — art literally shows 0; skill "non-zero Initiative" warning does not apply.
- CardNumber 24 kept (CAD sequence); footer CAD-C is set designation.

## Text → patterns
1. **"When this scheme is revealed, if you control an Academic, this scheme becomes a copy of an opponent's scheme in play or in The Locker."** → Pattern B When-Revealed + player pick. Copy = Gary same-Id class swap so EventResolveScheme / Actions / Reactions of the source class actually run.
2. **"If you do not control an Academic, add a Renown to any location."** → Pattern A resolve (only when still `_05Hacked`). Academic-with-no-targets: notify, no Renown (Renown is the else branch only).

## WHY decisions

### Class swap (Gary) not RiskClone shell
Shell keeping `_05Hacked` cannot host source `actFromCard*` / transition keys (`"01071"` etc.). Same-Id instantiate of source type is the only way resolve sub-states route correctly.

### card_type stays `05Hacked`
Unlike Gary (B-side IS the card), we keep column `05Hacked` so `getCardIdByType` for the real source scheme stays unique. Serialize carries the copy class (getCardObjectFromDb unserializes class).

### Dusk revert via EventHub + condition
After swap, handlers live on the source class. Stamp `STOLEN_SCHEMATICS_COPY`; EventHub CardSentToLocker (hub-before-cards) reverts to `_05Hacked` after the locker move so next-day scheme pick is still Stolen Schematics.

### When-Revealed map (not resolve) for the pick
Copy must finish before any EventResolveScheme so the copied class resolves. First When-Revealed player-pick in the codebase — wire `"05Hacked"` on both When-Revealed EVENTS maps; pick state returns to main When-Revealed EVENTS (240).

### Panache / Initiative from source + home visual
Leader already absorbed Stolen Schematics' modifier (0) on EventSchemeCardRevealed. Assign `$copy->Initiative` / `$copy->PanacheModifier` from the live source; apply `source.PanacheModifier - shell` to Leader.

Home visual: `characterFlipped` character path only patches combat/finesse/influence/resolve — scheme chips are initiative/panache. Client now **destroys + recreateSchemeCard** at `{playerId}-scheme-anchor` when `card.type === 'Scheme'`, and syncs `gamedatas.homeCards`.

## Shipped
- `_05Hacked` + When-Revealed pick state `2405024` + Renown resolve `2605024` / `"05Hacked_2"`.
- EventHub dusk revert; Game/`FakeGame` `STOLEN_SCHEMATICS_COPY`.
- cad.js triple; create-scheme skill (shape table, pattern-b, checklist 61, references).
- `Card_05Hacked_Test` — 10 pass.

## Unfinished / watch
- Multi When-Revealed choose-order path returns to main EVENTS (240) after pick — should still reach CHECK_REMAINING; not deeply tested.
- Reusing `characterFlipped` notif for scheme face swap — works for image/cardProperties; not a dedicated scheme notif.
- Panache delta when copying a non-zero PanacheModifier scheme — unit-tested only via becomeCopyOf structure; no explicit Leader panache assert.
