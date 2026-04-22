# Andriana Dondolo (_02001) Bugfixes

## What happened

Eddie asked me to audit the _02001 card (Andriana Dondolo) against its card text and fix any issues. Found 4 bugs across `Action_02001.php` and `Reaction_02001.php`.

## Fixes applied

### 1. Variable shadowing bug in Action_02001 line 126

```php
// BEFORE (broken): lambda $card shadows outer $card, always true
$hand = array_filter($hand, fn($card) => $card->Id == $card->Id);

// AFTER: renamed param to $handCard, compare against $id
$hand = array_filter($hand, fn($handCard) => $handCard->Id == $id);
```

WHY the shadowing happened: The outer scope had `$card = $game->theah->getCardById($id)`, and the lambda reused the same name `$card`. PHP closures don't capture outer variables by default (you need `use`), but the lambda parameter itself shadows the intent entirely — it always compared a card's ID to its own ID.

### 2. Missing Sorcery trait validation in Action_02001

The server-side handler for state 02001 (choosing a Sorcery card to discard) never validated that the submitted card actually has the "Sorcery" trait. The UI filters client-side but a modified client could bypass. Added a `hasTrait("Sorcery")` check that throws if the card isn't Sorcery.

### 3. Missing Leader check in Action_02001::isValidTargetForAbility

`isAvailableToPlayer` and `getArgsFromAction` both filter out Leader characters, but `isValidTargetForAbility` (the server-side validation when a target is submitted) was missing the check. Added `$character->hasTrait("Leader")` guard.

### 4. Location check in Reaction_02001::isValidTargetForAbility — REVERTED

Initially removed the location check thinking it wasn't in the card text. Eddie corrected me: in 7th Sea rules, the word "opposing" means "owned by an opponent AND at the same location." So the location check is correct and was restored. Important rule to remember for future card audits.

## Pattern observation

This is the same pattern seen in `2026-03-08-03-tac-isValidTargetForAbility.md` — `isValidTargetForAbility` being incomplete relative to the filters in `isAvailableToPlayer`/`getArgsFromAction`. These three places all need to agree on the targeting rules, but they're written independently. Worth keeping in mind when auditing other cards.
