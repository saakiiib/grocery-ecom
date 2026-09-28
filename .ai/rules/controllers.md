---
paths:
  - 'app/Http/Controllers/*.php'
---

# Controllers

## Use compact() for frontend view data
Pass view data with compact(), not inline arrays. Build each variable first, then return spa('view.name', compact('var1', 'var2')).
