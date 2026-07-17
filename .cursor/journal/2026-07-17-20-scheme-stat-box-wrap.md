# In-city scheme stat boxes detach on flex-wrap

## Bug
When a scheme is in the city (Leshiye of the Wood / Path to Poluchatel / etc.) and `._7sfs-city-my-cards` / `._7sfs-city-other-cards` wraps to a new row because horizontal space is tight, the initiative/panache stat boxes stay on the old row while the scheme art moves.

## WHY
`jstpl_card_scheme` puts the stat boxes as **siblings** of `${id}_image`, not children (unlike character cards, which nest stats inside `._7sfs-card`).

- Stats: `position: absolute; top: 63px / 87px`
- In-city image: `._7sfs-scheme-in-city { position: relative }` so the art participates in flex wrap
- Container: `._7sfs-scheme-container-in-city` previously had **only** `margin-left` — no positioning context

Absolute stats therefore resolved against `._7sfs-city-location` (`position: relative`), not the scheme wrapper. Flex moves the wrapper; stats stay put.

Home schemes were fine: both art and stats resolve against `._7sfs-home-container`, and there's only one scheme at a fixed anchor.

## Fix
Add `position: relative` to `._7sfs-scheme-container-in-city`. Minimal, home layout untouched.

## Alternative considered
Nest stats inside `${id}_image` like characters. Cleaner long-term, but would change containing block for home schemes (tops currently calibrated against home-container with scheme at `top: 35px`). Not needed for this bug.

## Unfinished
Studio/browser verify with Leshiye at a crowded city location on a narrow viewport.
