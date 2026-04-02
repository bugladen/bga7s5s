# Cirilo (01009) Recruit Cost Display Bug

## The Bug

When using Cirilo's City Action to recruit a mercenary, the "pay for mercenary" state prompt showed the mercenary's original printed cost (e.g., 4) instead of Cirilo's flat cost of 1. The backend correctly accepted 1 Wealth of payment, so gameplay worked — just the UI was wrong.

## Root Cause

Two places needed to reflect Cirilo's cost override:

1. **PHP `argsHighDramaRecruitActionPayForMercenary()`** — returned `$recruit->WealthCost` (the printed cost) without checking for `CIRILO_RECRUIT_TYPE`. This `cost` arg feeds into the state description template `#{cost}`.

2. **JS `OnEnteringState.js` pay state handler** — computed the status bar cost as `args.args.cost - args.args.discount` without checking for Cirilo. The card overlay code (lines 152-153) already had the Cirilo check but the status bar display (line 132) did not.

The backend validation in `FrameworkActionsTrait.php` already correctly overrides cost to 1 for Cirilo, which is why the action was accepted with 1 card despite the prompt saying 4.

## The Fix

- **ArgumentsTrait.php**: Added check — when `recruitType == CIRILO_RECRUIT_TYPE`, set `$cost = 1` before returning args.
- **OnEnteringState.js**: Added check — when `recruitType == CIRILO_RECRUIT_TYPE`, set `displayCost = 1` for the status bar chip, mirroring the existing check already present for the card overlay cost.
