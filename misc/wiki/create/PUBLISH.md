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
cd wiki-tmp
git add Implementing-A-Character-Card.md Character-Guide-*.md Implementing-A-Leader-Card.md Leader-Guide-*.md Implementing-A-Scheme-Card.md Scheme-Guide-*.md
git commit -m "Update Character, Leader, and Scheme create guides."
git push origin master
```

`Home.md` already links [[Implementing a Character Card]], [[Implementing a Leader Card]], and [[Implementing a Scheme Card]] — leave it alone unless those entry points change.
