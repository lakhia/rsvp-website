# RSVP Website — Frontend

Svelte 5 / SvelteKit single-page application for the jamaat RSVP and thaali management system.

## Stack

- **Svelte 5** with runes (`$state`, `$derived`, `$effect`)
- **SvelteKit** with `adapter-static` — SSR disabled, client-side SPA
- **Tailwind CSS v4**
- **Vite 8**

## Development

Development is managed from the **repo root**, not this directory. See the root `CLAUDE.md` for full setup instructions.

```bash
# From repo root
bun install
bun run dev        # Vite at http://localhost:5173, PHP at http://localhost:8010
```

Vite proxies `.php` requests to the PHP backend on port 8010 automatically.

To format frontend code:

```bash
cd frontend
bun run format     # Prettier + prettier-plugin-svelte
```

## Routes

| Route | Description |
|-------|-------------|
| `/` | Home / RSVP submission page |
| `/login` | Authentication |
| `/event` | Event management (admin) |
| `/family` | Family/thaali record management (admin) |
| `/plan` | Meal planning view |
| `/measure` | Measurement tracking |
| `/ingred` | Ingredient management |
| `/shop` | Shopping list |
| `/print` | Printable views |

## Project Structure

```
src/
  routes/          # SvelteKit file-based routes
  lib/
    api.js         # get/post helpers (attaches offset/date params, handles auth errors)
    auth.js        # Auth state from localStorage (token, thaali, email)
    PageState.svelte.js  # Loading/saving/error state class used across pages
    constants.js   # Shared constants
    dates.js       # Date utilities
    utils.js       # General utilities
    Dialog.svelte  # Modal dialog component
    Icon.svelte    # Icon component
    Loading.svelte # Loading spinner
    Message.svelte # Status/error message display
    PageNav.svelte # Pagination navigation
    Tooltip.svelte # Tooltip component
```

## Build

```bash
# From repo root
bun run build        # Vite build + copies PHP files to build/
bun run build:prod   # Single minified build/index.html (all JS/CSS inlined)
```
