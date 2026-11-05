# CAD Valeri + Diplomatic Envoy soft-lock

## Report
Valeri City Action move + Combat challenge vs En Garde Diplomat at Forum while
Diplomatic Envoy (_04024) is revealed. Lands on `highDramaChallengeActionActivateTechnique`.
Cannot Back (VALERI_CHALLENGE_TYPE lock after move). Pass/technique → `stIssueChallenge`
→ Envoy `eventCheck` UserException. Soft-lock.

## Cause
`shouldIssueChallenge` only checked co-location (Soline flee). Envoy ban fires later at
ChallengeIssued. Valeri always has Technique_05DabneyUS01 → always opens ActivateTechnique
with Back blocked. Same class as Soline skip, different legality axis.

## Fix
- `Theah::challengeCanBeIssued` — dry-run ChallengeIssued eventCheck; optional temp CHALLENGE_STAT.
- `Action_05DabneyUS01::shouldIssueChallenge` + availability/args/`isValidTarget` Combat filter.
- Andriana `shouldIssueChallenge` same legality (Patricia/Envoy soft-lock class).
- `stTechniqueAvailable` distinct notify when co-located but banned.
- ActivateTechnique Pass/technique abort + `challengeSkipped` → NEXT_PLAYER (unstick live tables).

WHY filter at pick too: don't spend the move on a target that was never legal. WHY Pass abort
anyway: tables already stuck past TECHNIQUE_AVAILABLE; Back stays locked by design.

## Tests
Action_05DabneyUS01 Envoy cases; StatesTrait Valeri skip; FrameworkActions Pass abort;
Theah_Capabilities challengeCanBeIssued + stat restore. 102 pass on those suites.

## Gotchas while testing
- `Game::STAT_*` are strings — `challengeCanBeIssued` third arg is `string|int|null`.
- TestTheah stubs `eventCheck` (no card walk). Dry-run must call extracted
  `eventCheckCards` or Envoy never fires in harness.
- `skipChallengeBeforeTechnique` / abort helper need `protected` — FrameworkActionsTrait
  on FrameworkActionsGame (child) cannot see StatesTraitGame privates.
