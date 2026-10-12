# Halloween deck-picker banner

## Context
Continuation of CITY_HALLOWEEN seasonal UX. User wants deck-picker `banner_description` to open with festival copy when flag=1.

## What / Why
In `OnUpdateActionButtons.js` `pickDecks` path, build `banner_description` then optionally prepend:

> Samhein, Vaticine Holy Days, Festival of Flames, Halloween - whatever you want to call it, it's being celebrated in the City of Five Sails!

WHY prepend not replace: keep the existing "explore starters / custom deck / Confirm Deck" instructions intact — Halloween is flavor on top.

WHY gate on `window.CITY_HALLOWEEN === 1`: same client-only flag as towers / map tint; flip once and all seasonal bits go away together.

WHY wrap Halloween string in `_()`: other banner strings are translated; keep i18n consistent.

Spelling is "Samhein" as user wrote (not Samhain).

## Update: banner box styling
User styled the Halloween blurb as an orange-on-dark box (inline styles). Swapped `background-color: black` → `rgba(45, 45, 45, 0.9)` to match `body._7sfs-halloween` map tint — same seasonal grey, not pure black.
