# Accept Challenge — include Technique threat in display

## Why
Accept status bar showed challenge-stat only ("excluding Technique"). Technique is already chosen/resolved before Accept; real threat math lives on `EventGenerateChallengeThreat` which fires *after* Accept. Players deciding Accept/Refuse/Intervene deserve the full number.

## Approach
`EventGenerateChallengeThreat::$preview` + `Theah::previewChallengeThreat()`.

WHY preview flag over a Technique API: reuses existing +Thrust / Lethal / conditional math (01011 Red Hands, 01196 Combat/Influence, 04033 UseThrust, 05Dabney Choice) without duplicating per technique. WHY not fire the real event: handlers queue destroy/choosers/ranged and touch globals.

## Side-effect gates (preview)
- DestroyPlusOneThrust / 01157 — threat yes, destroy/ranged no
- 04017 — +1 threat yes, discard/ranged no
- 01049 — Lethal yes, ranged no
- 01063Swap — actorId yes (threat math), CHOSEN_PERFORMER/conditions/swapped event no
- 02026a/b, 01090 — chooser/reveal already CHALLENGE_ACCEPTED-gated; also `!preview`

## Also included (bonus)
Scheme/risk mods on the same event (02061 +1 defender, etc.) — same dry-run, more accurate total. Actor-only mods don't change the defender chip.

## Files
- EventGenerateChallengeThreat.php — `$preview`
- Theah.php — `previewChallengeThreat`, `applyAbsentActorBaseChallengeThreat` (locker challenger shared with EventHub)
- EventHub — call shared locker helper
- ArgumentsTrait — use preview for `defenderThreat`
- states.inc.php — drop "(excluding Technique)"
- Technique side-effect gates listed above

## Not done
Lethal chip in status bar — flag is computed but UI still only shows the number.
