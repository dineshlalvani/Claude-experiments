# Euryka iOS — UX observations

Notes from the five screenshots (Home/My Day, Mood, Location, Sketch, Camera).

## Cross-cutting

- **Three competing nav surfaces.** The dial wheel (bottom-left), the giant center mode button, and the 5-tab bar all fight for the same band of screen. Pick one canonical layout — running all three forces tiny hit targets and an unclear mental model.
- **Theme inconsistency.** Mode color flips (orange / purple / teal / orange / purple) but the tab bar stays the same blue everywhere, breaking the otherwise strong per-mode identity.
- **"Evening, Dinesh" repeats** on every detail screen top-right. Saves no space, adds no info — keep it on Home only.
- **Search as a 6th detached pill** off the tab bar reads as an afterthought. Either fold it into a tab or hoist it into the header.
- **Dial icons are tiny and unlabeled** — well under Apple HIG's 44pt minimum, and with no text it's hard to learn what each does.

## Home (My Day)

- ~30% of the screen above the greeting is empty before any content lands.
- The card stacks three rows where two are empty states ("Nothing else today", "Nothing to triage"). Collapse to one "All clear today" line, or hide entirely when empty.
- Address truncates to "Water…" — show city only, or a friendly label ("Home").

## Mood

- 22 emojis × 4 columns ≈ 5.5 rows; the grid is dense and unstructured.
- Cluster by valence (positive / neutral / negative) or use a 2D valence×arousal picker — both reduce cognitive load.
- "Pick a mood above to begin" sits below an enabled-looking text input; the input should look disabled until a mood is picked.

## Location

- Shows raw `52.24681, -7.09638` even though the Home card already reverse-geocodes the same area to "Namaskar, 38 Summerville Ave, Water…". Reuse that.
- The "Why this place?" optional field is good — keep it but move primary focus to a confirm action.

## Sketch

- Tools scattered to four corners (expand top-left, undo/redo top-right, color picker bottom-right). Consolidate into one floating toolbar.
- No visible save/export action — currently relies on the dial change to commit?

## Camera

- Zoom strip (0.5/1/2/5/10) overlaps the subject inside the photo frame. Move above the shutter, or add a backdrop blur.
- 10× zoom feels excessive for the likely use cases; 0.5/1/2/3 is probably enough.

## Quick wins (cheap, high impact)

1. Theme the tab bar to the active mode color.
2. Drop "Evening, Dinesh" from detail screens.
3. Reverse-geocode the Location pin.
4. Collapse the empty Home card.
5. Move the camera zoom strip out of the photo frame.

## Bigger questions

- **Dial vs. tab bar — which is canonical?** If the dial is the primary mode-switcher, the tab bar is redundant. If tabs are primary, the dial duplicates them.
- **Modes vs. tabs — what's the relationship?** Modes (My Day / Mood / Location / Sketch / Camera) feel like *capture inputs*. Tabs (Euryka / Chat / Vault / Profile) feel like *spaces*. That distinction isn't communicated, so the two sit awkwardly side-by-side.
