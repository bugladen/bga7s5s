> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern E — Techniques and Maneuvers

These follow the standard Character patterns — nothing CityCharacter-specific:

- **Technique** — `IHasTechniques` is already on `Character` via `TechniqueTrait`. Add the technique class under `cards/<expansion>/techniques/` and push it into `$this->Techniques` in the constructor.
- **Maneuver** — Implement `IHasManeuvers`, `use ManeuverTrait`, push into `$this->Maneuvers`.
