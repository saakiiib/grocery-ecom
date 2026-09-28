---
paths:
  - 'resources/views/frontend/**'
  - 'resources/views/frontend/*.blade.php'
---

# Theme

## Internal anchors require @spa
Every internal anchor must use the package directive (<a @spa href="...">), which compiles to data-spa for the engine. Never write raw data-spa. External links (http, mailto:, tel:) and # anchors must NOT have it, and file downloads must stay plain.

## Keep page scripts SPA re-runnable
Page scripts re-execute on every SPA navigation in one shared global scope: never use top-level const/let (use var), never rely on DOMContentLoaded (run init directly at the end of the script). Inline onclick handlers must stay global functions.
