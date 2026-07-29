# notif_characterRecruited side-of-city slide

## What

`notif_characterRecruited` now `slideAndAttach`s the existing card DOM to the post-recruit target, then destroy+recreate — same pattern as `notif_cardMoved` / `notif_schemeMovedToCity`.

## Why this approach

Recruiting sets `controllerId` from 0 → recruiting player. For the recruiting client, `getTargetElementForLocation` switches from the right endcap to `*-my-cards-endcap` on the left of the city-image. That's the whole point of the animation Eddie asked for: card crosses the city card.

Alternatives considered:
- FLIP via `animateCardFromElement` after create: works, but we'd invent a phantom "from" rect or keep a ghost. `slideAndAttach` already moves the live element to the dest endcap.
- Keep destroy+instant recreate (old): teleports; no visual continuity.

WHY not delete from `cardProperties` anymore: old code did `delete this.cardProperties[args.characterId]` before recreate. `createCharacterCard` re-adds anyway; `cardMoved` never deletes. Dropping the delete avoids a transient hole and matches the established move pattern.

## Notes

- Notif duration already 1000ms — enough for slideAndAttach.
- Opponent/spectator views: both old and new targets are the right endcap, so the slide is a short reposition (plus color/wealth-cost refresh on recreate). Still fine.
- `alignCityImages` from the May journal is gone from the codebase; not reintroduced here.
