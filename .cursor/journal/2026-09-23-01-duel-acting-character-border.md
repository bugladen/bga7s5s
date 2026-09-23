# Duel acting character border

User asked for 2px border + rounded edges on `._7sfs-duel-acting-character`.

## What I did
Separate rule (kept white bg on the combined selector with header rows):
```css
._7sfs-duel-acting-character {
    border: 2px solid black;
    border-radius: 8px;
}
```

Border color: black to match `#duel_table` borders. Radius 8px — common in this stylesheet for non-circle rounding; user didn't specify a radius value.

## Caveat confirmed — fixed
Class is on `<tr>` inside `._7sfs-threat-table`. `border-collapse: collapse` ignores `border-radius` on rows.

Fix: split collapse rules — `#duel_table` stays collapse; `._7sfs-threat-table` uses `separate` + `border-spacing: 0`. Border/radius moved onto the row's `td`s (left cell gets left+radius, right cell gets right+radius, both get top/bottom). Kept gold border (user changed it from black).

## Maneuver/Technique font size

User: reduce Maneuver + Technique column cells by 4pt. Table is 12pt → 8pt on data cols 6–9 only (header stays 12pt). Combat chips keep their own 18pt so only the ability name text really shrinks — which is the point (long names).
