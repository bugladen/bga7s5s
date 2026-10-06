# Borets (01129) rest-of-duel ban — same locker gap as Mireli

## Follow-up to 2026-10-06-02 / -03
User asked to apply the 01135 global pattern to Borets.

## Gap
`$IsActive` eventCheck on the Risk blocked ManeuverActivated / TechniqueActivated.
Miyato lockers the Risk at EndOfRound; clone stripped at NewRound → ban dies early
even though text is "for the rest of the duel".

## Fix
- `Game::BORETS_MANEUVER_TECHNIQUE_LOCK` = `{sourceInjectCode, maneuverId}`
- Arm on `EventResolveManeuver`
- Assert in `Theah::eventCheck` (before card walk) — eventCheck never hits locker cards
- Clear: cancel (by maneuverId), EventDuelEnd, `stDuelEnd` safety-net
- Removed instance `eventCheck` (global is sole authority — avoids depending on card in world)

WHY Theah not EventHub: this is a gate on activate, not a calc apply. eventCheck is Theah.

Note: after Borets resolves, Techniques are banned — so Miyato usually cannot *copy*
Borets itself. Lock still needed if the Risk leaves play any other way, or if a
clone/original stays armed past locker without the global.
