# Publishing create guides to the GitHub wiki

These folders are the **source of truth** for the GitHub wiki pages. Filenames and
`[[display text|Page Name]]` links are already wiki-ready — copy them as-is.

## Repo

`https://github.com/bugladen/bga7s5s.wiki.git`

## Copy map

| Local folder | Files | Wiki destination |
|---|---|---|
| `misc/wiki/create/character/` | `Implementing-A-Character-Card.md`, `Character-Guide-*.md` | wiki repo root |
| `misc/wiki/create/leader/` | `Implementing-A-Leader-Card.md`, `Leader-Guide-*.md` | wiki repo root |
| `misc/wiki/create/scheme/` | `Implementing-A-Scheme-Card.md`, `Scheme-Guide-*.md` | wiki repo root |
| `misc/wiki/create/citycharacter/` | `Implementing-A-CityCharacter-Card.md`, `CityCharacter-Guide-*.md` | wiki repo root |
| `misc/wiki/create/factionattachment/` | `Implementing-A-FactionAttachment-Card.md`, `FactionAttachment-Guide-*.md` | wiki repo root |
| `misc/wiki/create/cityattachment/` | `Implementing-A-CityAttachment-Card.md`, `CityAttachment-Guide-*.md` | wiki repo root |
| `misc/wiki/create/risk/` | `Implementing-A-Risk-Card.md`, `Risk-Guide-*.md` | wiki repo root |

Do **not** rename files on copy. Do **not** convert links.

## Link rules (GitHub / Gollum)

- Correct: `[[01 — Passives|Character Guide Passives]]` — display text **then** page name
- Wrong: `[[Character Guide Passives|01 — Passives]]` — links to a nonexistent page
- Inside markdown tables, prefer `[Passives](Character-Guide-Passives)` — a `|` inside `[[…|…]]` splits table columns
- Page names use spaces (`Character Guide Passives`); filenames use hyphens (`Character-Guide-Passives.md`)

## Quick publish

```powershell
git clone https://github.com/bugladen/bga7s5s.wiki.git wiki-tmp
Copy-Item misc/wiki/create/character/*.md wiki-tmp/
Copy-Item misc/wiki/create/leader/*.md wiki-tmp/
Copy-Item misc/wiki/create/scheme/*.md wiki-tmp/
Copy-Item misc/wiki/create/citycharacter/*.md wiki-tmp/
Copy-Item misc/wiki/create/factionattachment/*.md wiki-tmp/
Copy-Item misc/wiki/create/cityattachment/*.md wiki-tmp/
Copy-Item misc/wiki/create/risk/*.md wiki-tmp/
cd wiki-tmp
git add Implementing-A-Character-Card.md Character-Guide-*.md Implementing-A-Leader-Card.md Leader-Guide-*.md Implementing-A-Scheme-Card.md Scheme-Guide-*.md Implementing-A-CityCharacter-Card.md CityCharacter-Guide-*.md Implementing-A-FactionAttachment-Card.md FactionAttachment-Guide-*.md Implementing-A-CityAttachment-Card.md CityAttachment-Guide-*.md Implementing-A-Risk-Card.md Risk-Guide-*.md
git commit -m "Update Character, Leader, Scheme, CityCharacter, FactionAttachment, CityAttachment, and Risk create guides."
git push origin master
```

`Home.md` should link [[Implementing a Character Card]], [[Implementing a Leader Card]], [[Implementing a Scheme Card]], [[Implementing a CityCharacter Card]], [[Implementing a FactionAttachment Card]], [[Implementing a CityAttachment Card]], and [[Implementing a Risk Card]]. Update Home when you add a new entry-point guide.
