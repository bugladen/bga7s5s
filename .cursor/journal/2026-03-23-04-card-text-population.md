# Card Text Population from Spreadsheet

## What was done

Populated `$this->Text` in the constructors of all 200 Core-set card classes from the Google Sheets master directory CSV.

Source: Eddie's local CSV export at `c:\Users\bugbu\Downloads\Eddie's Copy of 7s5s Master Directory Shareable - Copy Master List.csv`

### Process

1. Parsed the CSV, filtered to rows where Set = "Core"
2. Extracted card numbers from CardNumber column (split on `-`, took right side, e.g., `7S5S-89` → `89`)
3. Mapped to class files: card 89 → `_01089.php` (zero-padded to 3 digits, prefixed with `_01`)
4. Processed text: split on `\n`, stripped whitespace, filtered empty lines, wrapped each line in `<p>` tags, joined
5. Escaped for PHP double-quoted strings (`"` → `\"`, `$` → `\$`, `\` → `\\`)
6. Inserted `$this->Text = "..."` after the `$this->Traits = [...]` array and before `$this->resetCard()` in each constructor

### The `_01050` edge case

Unsavory Salve (`_01050`) was the only card class without a `$this->Traits` array — it's a `FactionAttachment` that goes straight from stat modifiers to `$this->resetCard()`. Handled manually by placing `$this->Text` before `$this->resetCard()`.

### Decisions

- **Newlines → `<p>` elements**: Eddie confirmed this since the text will render in a browser. Each non-empty line after splitting on `\n` becomes its own `<p>`.
- **HTML preserved**: The Text values are stored as-is, including any HTML tags. However, no Core cards actually contain HTML tags (`<b>`, `<i>`) — those are only in TAC-set cards.
- **Game-specific markup preserved**: `[BAR]`, `[com]`, `[fin]`, `[inf]`, `•` characters all kept as-is. `[BAR]` becomes its own `<p>[BAR]</p>`.
- **Empty text skipped**: One card (`_01098`, The Cat's Embargo) had empty Text in the CSV. Since the base `Card` class already defaults `$this->Text = ""`, no modification was needed.

### Stats — Core set

- 200 files modified (199 by script + 1 manual)
- 1 skipped (empty text)
- 0 failures

## TAC Set — Second Pass

Same process, with differences:
- Filtered on `Set == "TAC"` instead of `Core`
- CardNumber column is just a plain number (no `7S5S-` prefix), mapped directly
- Class files use `_02` prefix and live in `modules/php/cards/tac/`
- Only first 19 cards are implemented; 42 cards skipped because class files don't exist yet

TAC cards DO contain HTML tags (`<b>`, `<i>`) in their Text values, unlike Core cards. These are preserved as-is inside the `<p>` wrapping.

### Stats — TAC set

- 19 files modified
- 42 skipped (no class file)
- 0 skipped (empty text)
- 0 failures
