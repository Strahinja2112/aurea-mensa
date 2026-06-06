# Aurea Mensa – Refined Color System (Image-Matched)

This palette is extracted from the provided background image and adjusted for UI consistency. The goal is to preserve the **dark stone, muted teal, and near-black atmosphere** of the visual.

---

# 1. Core Background Palette

| Name | Hex | Usage |
|------|-----|------|
| Void Black | `#101010` | Main app background |
| Deep Graphite | `#202020` | Primary surfaces |
| Dark Slate | `#102020` | Section layering |
| Muted Steel | `#303030` | Borders, subtle separation |
| Soft Charcoal | `#203030` | Elevated cards / glass base |

---

# 2. Text Colors (Matched Contrast)

| Name | Hex | Usage |
|------|-----|------|
| Warm Fog | `#E6E6E6` | Primary text |
| Ash Gray | `#C8C8C8` | Secondary text |
| Dim Smoke | `#9A9A9A` | Disabled / metadata |

---

# 3. Accent System (Minimal, Controlled)

The image does not contain strong warm accents, so accents should remain **cool and restrained**.

| Name | Hex | Usage |
|------|-----|------|
| Cold Teal Accent | `#4A6A6A` | Primary interactive accent |
| Muted Cyan Shadow | `#2E4A4A` | Hover / depth |
| Steel Highlight | `#6A7A7A` | Subtle emphasis |

---

# 4. UI Usage Rules

## Background Layering
- Base: `#101010`
- Panels: `#202020`
- Cards: `#203030`
- Borders: `#303030` (very low opacity preferred)

---

## Glassmorphism (Adjusted for Dark Stone Theme)

- Background: rgba(32, 32, 32, 0.55)
- Border: rgba(200, 200, 200, 0.06)
- Blur: 14px–20px
- Shadow: soft, wide, very low opacity black

---

## Buttons

### Primary
- Background: `#4A6A6A`
- Text: `#E6E6E6`
- Hover: slightly brighter cyan-teal

### Secondary
- Border: `#303030`
- Background: transparent / glass
- Text: `#C8C8C8`

### Destructive
- Use muted red only if necessary (keep desaturated)

---

## Links & Interactions
- Default: `#4A6A6A`
- Hover: `#6A7A7A`
- Active: subtle brightness increase only (no saturation jump)

---

# 5. Design Principle

This system is intentionally:
- **Cold and neutral (not warm or gold-toned)**
- **Low saturation across all layers**
- **Focused on depth through contrast, not color variety**
- **Built to let the food photography and glass UI stand out**

The background image defines the atmosphere — the UI should feel like it belongs inside it, not on top of it.