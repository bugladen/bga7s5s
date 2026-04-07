# Maneuver Stray Log Message Fix

## The Bug
When Improvised Weapon (01155) was played as a combat card and its +1 Parry maneuver was activated, a stray log message appeared containing only the card name — no descriptive text around it.

## Root Cause
`Maneuver_PlusOneParry.php` (the generic +1 Parry maneuver used by Improvised Weapon) added the owning card's inject code directly to the `explanations` array:

```php
$event->explanations[] = $owner->getInjectCode();
```

In `EventHub.php`, the `EventDuelCalculateManeuverValues` handler iterates over explanations and sends each one as a separate log "message" notification:

```php
foreach ($event->explanations as $explanation) {
    $theah->game->notify->all("message", $theah->game->translate($explanation));
}
```

Since the explanation was just the raw inject code (e.g., `[123:Card:Improvised Weapon(01155.jpg)]`), it rendered in the game log as nothing but the bolded card name.

Every other maneuver that adds explanations wraps the inject code in descriptive text via `sprintf`, e.g., `"%s adds 2 Parry."`. This generic maneuver was the only one that skipped the descriptive wrapper.

## Fix
Replaced the bare explanations with proper descriptive text following the convention used by every other maneuver (e.g., `"%s adds 1 Parry."`). Initially removed them entirely, but Eddie pointed out they're more useful with a proper explanation than with none at all — they help players understand where each stat modifier comes from when multiple modifiers stack.

## Second Instance: Maneuver_01084
Same pattern. Line 76 had `$event->explanations[] = $owner->Name;` — even worse, it used `$owner->Name` directly (not even inject code), so the log showed just the plain card name text with no formatting or tooltip. Fixed the same way with `"%s adds 1 Riposte."`.

## Files Changed
- `modules/php/cards/maneuvers/Maneuver_PlusOneParry.php` — replaced bare `$owner->getInjectCode()` explanation with `sprintf("%s adds 1 Parry.", $owner->getInjectCode())`.
- `modules/php/cards/_7s5s/maneuvers/Maneuver_01084.php` — replaced bare `$owner->Name` explanation with `sprintf("%s adds 1 Riposte.", $owner->getInjectCode())`.
