# Menu Data — Food Ordering Website

This file defines all menu items, categories, sizes, add-ons, and notes fields.
Claude Code should use this to seed the `categories`, `menu_items`, and any `addons` / `options` tables.

---

## How to read this file

Each category section contains:
- **Items** — the individual dishes
- **Base price** — price for Normal size
- **Sizes** — Normal / Large with their prices (where applicable)
- **Egg add-on** — available on marked categories: Fried egg or Half-boiled egg, +10 THB
- **Spiciness note** — an optional free-text note field on checkout ("mild / medium / extra spicy / no spice")

---

## Category 1 — Spicy Basil Stir-Fry (Pad Kra Pao)

**Items:**
- Spicy Fried Pork with Basil Leaves
- Spicy Fried Chicken with Basil Leaves
- Spicy Fried Meat with Basil Leaves
- Spicy Fried Seafood with Basil Leaves

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** Yes — Fried egg or Half-boiled egg, +10 THB

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 2 — Omelet with Rice (Khao Kai Jeow)

**Items:**
- Omelet Pork with Rice
- Omelet Chicken with Rice
- Omelet Vegetable with Rice
- Omelet Shrimp with Rice

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 40          |

**Egg add-on:** No (dish is already egg-based)

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 3 — Crispy Rice Chicken (Khao Man Gai Thod)

**Items:**
- Crispy Rice Chicken

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** Yes — Fried egg or Half-boiled egg, +10 THB

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 4 — Crispy Chicken with Kale (Gai Thod Kana)

**Items:**
- Crispy Chicken with Kale

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** Yes — Fried egg or Half-boiled egg, +10 THB

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 5 — Garlic & Pepper Stir-Fry

**Items:**
- Stir-Fried Pork with Garlic and Peppercorns
- Crispy Fried Calamari
- Stir-Fried Shrimp with Garlic

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** Yes — Fried egg or Half-boiled egg, +10 THB

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 6 — Spicy Fried Noodles (Pad Kee Mao)

**Items:**
- Spicy Fried Noodles (plain)
- Spicy Fried Noodles with Seafood
- Spicy Fried Noodles with Pork

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** No

**Spiciness note:** No (spice level is part of the dish style)

---

## Category 7 — Stir-Fried Chilli (Pad Prik)

**Items:**
- Stir-Fried Chilli with Beef
- Stir-Fried Chilli with Chicken
- Stir-Fried Chilli with Pork

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** Yes — Fried egg or Half-boiled egg, +10 THB

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 8 — Tom Yum Fried Rice (Khao Pad Tom Yum)

**Items:**
- Tom Yum Fried Rice with Seafood
- Tom Yum Fried Rice with Pork
- Tom Yum Fried Rice with Chicken
- Tom Yum Fried Rice with Beef

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** Yes — Fried egg or Half-boiled egg, +10 THB

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 9 — Suki (Thai Sukiyaki)

**Items:**
- Suki (plain / nam)
- Suki with Seafood
- Suki with Pork
- Suki with Beef
- Suki with Chicken

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** No

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 10 — Chilli Salt Stir-Fry (Pad Prik Gleua)

**Items:**
- Fried Chilli Salt Chicken
- Fried Chilli Salt Beef

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** Yes — Fried egg or Half-boiled egg, +10 THB

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 11 — Fried Noodles (Pad Mee / Sen Yai)

**Items:**
- Fried Noodles with Pork
- Fried Noodles with Meat
- Fried Noodles with Chicken
- Fried Noodles with Seafood

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 40          |

**Egg add-on:** No

**Spiciness note:** No

---

## Category 12 — Thai Fried Noodles (Pad Thai)

**Items:**
- Pad Thai with Pork
- Pad Thai with Chicken
- Pad Thai with Meat
- Pad Thai with Seafood

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 40          |
| Large  | 50          |

**Egg add-on:** No

**Spiciness note:** No

---

## Category 13 — Fried Rice (Khao Pad)

**Items:**
- Fried Rice with Pork
- Fried Rice with Chicken
- Fried Rice with Seafood
- Fried Rice with Meat
- Fried Rice with Vegetables
- Fried Rice with Mixed Meat

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 50          |

**Egg add-on:** Yes — Fried egg or Half-boiled egg, +10 THB

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 14 — Noodle Soup (Kuay Teow)

**Items:**
- Pork Noodles
- Meat Noodles

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 40          |

**Egg add-on:** No

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 15 — Tom Yum Noodle Soup

**Items:**
- Tom Yum Pork / Meat Noodles
- Pork / Meat Seafood Noodles

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 40          |

**Egg add-on:** No

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Category 16 — Fried Noodles with Soup (Mee Nam)

**Items:**
- Fried Noodles with Pork and Soup
- Fried Noodles with Meat and Soup
- Fried Noodles with Chicken and Soup
- Fried Noodles with Seafood and Soup

**Sizes:**
| Size   | Price (THB) |
| ------ | ----------- |
| Normal | 30          |
| Large  | 40          |

**Egg add-on:** No

**Spiciness note:** Optional free-text field — "How spicy would you like it?"

---

## Add-on and Option Rules (for Claude Code)

Implement these as reusable option groups attached to menu items:

### Option Group A — Size
Applied to: all items
| Option | Price Modifier (THB) |
| ------ | -------------------- |
| Normal | +0 (base price)      |
| Large  | +20 (most items) or +10 (noodle/soup categories 11, 14, 15, 16) |

> Note: For Pad Thai (Category 12), Large price is 50 THB (not 40 like other noodle categories).

### Option Group B — Egg Add-on
Applied to: Categories 1, 3, 4, 5, 7, 8, 10, 13
| Option       | Price Modifier (THB) |
| ------------ | -------------------- |
| No egg       | +0 (default)         |
| Fried egg    | +10                  |
| Half-boiled egg | +10               |

### Option Group C — Spiciness Note
Applied to: Categories 1, 2, 3, 4, 5, 7, 8, 9, 10, 13, 14, 15, 16
Type: Optional free-text input
Placeholder: "Spiciness level? (e.g. mild, medium, extra spicy, no spice)"
Required: No

---

## Price Summary

| Scenario | Price (THB) |
| --- | --- |
| Normal size, no egg | 30 |
| Large size, most categories | 50 |
| Large size, noodle/soup categories | 40 |
| Pad Thai Normal | 40 |
| Pad Thai Large | 50 |
| Add fried or half-boiled egg | +10 |
| Maximum single item total | 60 (Large + egg) |