# Improvising + Patricia Pull (01106 / 01172 / 05Serenissima)

## Report
User: Patricia (05Serenissima) should be able to Improvising-steal Pull from an opponent's discard, and skip the "wound unless Strega" cost because she can count as Strega.

## WHY it failed
`Action_01106` probes opponent-discard Risks with `isAvailableToPlayer(..., overrideInHandCheck=true)` while the discarded card still has the **opponent's** `ControllerId`.

Patricia's `hasTrait` fiction (Uwe shape) only invents traits when `$queryCard->ControllerId == $this->ControllerId`. Pull's Sorcerer gate passes `$owner` (the discarded Pull) → fiction fails → Improvising never offers Pull when Patricia is the only "Sorcerer".

After a successful steal the RiskClone already gets `ControllerId = thief`, so the Strega wound skip on `Action_01172::resolveTargetedCharacter` would have worked — availability was the blocker.

## Fix
`Action_01106::withDiscardRiskControlledBy` — temporarily set discarded Risk `ControllerId` to the Improvising player around availability probes in `isAvailableToPlayer` and `getArgsFromAction` step 2; restore in `finally`.

Not changing Patricia's hasTrait gate (would widen fiction to opponent-owned queryCards). Not changing Pull.

## Tests
`Action_01106_Test`: Patricia + Pull availability; args lists Pull; Improvised Pull resolve skips wound for Patricia; ControllerId restored after probe.

## Same hole, not fixed this pass
`Action_04cd01b` (Penya sink → play from opponent discard) has the identical probe. Same ControllerId swap would unlock Patricia there too.
