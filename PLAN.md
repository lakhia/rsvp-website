# SvelteKit Migration Plan

## Context

The RSVP website frontend is written in AngularJS 1.x, which is end-of-life. The PHP backend (app/*.php) is clean and well-structured and will remain unchanged. The goal is to replace the AngularJS SPA with SvelteKit in SPA mode, page by page, so each step is independently testable without breaking the existing app.

## Architecture Decisions

- **SvelteKit SPA mode**: Use `@sveltejs/adapter-static` with SSR disabled. SvelteKit outputs static files to `build/` served by the existing PHP server — no new server needed.
- **Location**: New `frontend/` directory in repo root. Its build output replaces current `build/index.html` and JS/CSS artifacts.
- **PHP stays untouched**: All `.php` files in `app/` continue to be copied to `build/` via existing build pipeline.
- **Auth**: Read/write the same 4 cookies (`token`, `thaali`, `email`, `adv`) the PHP backend expects.
- **Styling**: Replace Bootstrap 3 with Tailwind CSS.
- **Routes**: Clean SvelteKit routes — do not mirror AngularJS URL patterns.
- **RSVP dirty tracking**: Per-row dirty tracking via `dirty[date] = true` on any field change — never reset on toggle-back. On Save, only dirty rows are sent to the server.
- **Page titles**: Each page sets its own `<title>` via `<svelte:head>`.

## Critical Files

| File | Role |
|------|------|
| `app/js/route.js` | All 8 routes to migrate |
| `app/js/main.js` | `$rootScope.init()` pattern + login/sidebar controllers |
| `app/js/rsvp.js` | Main RSVP controller |
| `app/js/print.js` | Filling team controller (filters, sorting, labels) |
| `app/js/event.js` | Admin event CRUD |
| `app/js/family.js` | Admin family CRUD |
| `app/js/shop.js` | Shopping list (read-only) |
| `app/js/measure.js` | Admin recipes + autocomplete |
| `app/*.html` | 8 templates to rewrite as Svelte components |
| `package.json` | Build scripts to update |

## Route Mapping

| Old AngularJS | New SvelteKit | Page |
|---|---|---|
| `/{offset}/{date}` | `/` (query params `?offset=&date=`) | RSVP home |
| `/login/{out}` | `/login` (logout via button) | Login |
| `/plan/` | `/plan` | Plan (static) |
| `/event/{offset}` | `/event` (`?offset=`) | Event admin |
| `/family/{offset}` | `/family` (`?offset=`) | Family admin |
| `/shop/{offset}` | `/shop` (`?offset=`) | Shopping list |
| `/measure/{offset}` | `/measure` (`?offset=`) | Measure admin |
| `/print/{offset}` | `/print` (`?offset=`) | Print/filling |

---

## Migration Steps

Each step produces a testable artifact. The AngularJS app continues working until Step 10 (output goes to `build-svelte/` during development).

---

### Step 0: SvelteKit Project Setup

- `mkdir frontend && cd frontend && npm create svelte@latest .` (Skeleton, no TypeScript)
- `npm install -D @sveltejs/adapter-static tailwindcss @tailwindcss/vite`
- `svelte.config.js`: adapter-static, `fallback: 'index.html'`, output to `../build-svelte`
- `vite.config.js`: add Tailwind plugin
- Add `"build:svelte": "cd frontend && npm run build"` to root `package.json`

**Test:** `npm run build:svelte` → `build-svelte/index.html` exists.

---

### Step 1: Shared Infrastructure

- `frontend/src/lib/api.js` — fetch wrapper (GET/POST to PHP endpoints, 8s timeout, JSON in/out)
- `frontend/src/lib/auth.js` — `getCookie`, `setCookie`, `clearCookie`, `isAdmin()`, `isLoggedIn()`
- `frontend/src/lib/dates.js` — `getDisplayDate(dateStr)` (date + day-of-week formatting)
- `frontend/src/routes/+layout.svelte` — sidebar nav, auth guard (redirect to `/login` if no cookies), `<svelte:head>` with app name

**Test:** Auth guard: clear cookies → visit `/` → lands on `/login`.

---

### Step 2: Login Page

**Source:** `app/login.html`, `loginController` in `app/js/main.js`

- `frontend/src/routes/login/+page.svelte`
- `<svelte:head><title>Login</title></svelte:head>`
- POST `login.php` with `{email, pass}` → set 4 cookies (60-day expiry) → `goto('/')`
- On error: show message
- Logout button (in sidebar): clear all 4 cookies → `goto('/login')`

**API:** `POST login.php`

**Test:** Bad credentials → error. Good credentials → cookies set, redirected home.

---

### Step 3: Plan Page (static)

**Source:** `app/plan.html`

- `frontend/src/routes/plan/+page.svelte`
- `<svelte:head><title>Plan</title></svelte:head>`
- Static links to `LINK_PLANNING` and `EMAIL_SECRETARY` (deploy.pl substitutes these into the built HTML)
- No API calls

**Test:** Page renders with correct links.

---

### Step 4: RSVP Page (home)

**Source:** `app/rsvp.html`, `app/js/rsvp.js`

- `frontend/src/routes/+page.svelte`
- `<svelte:head><title>RSVP</title></svelte:head>`
- Read `?offset` and `?date` from URL search params
- Fetch `rsvp.php?offset=X&date=Y` on mount; store full response as `events`
- **Per-row dirty tracking**: `let dirty = {}`. On any change to a row, set `dirty[date] = true` — never reset (no toggle-back logic).
- Save button enabled when `Object.keys(dirty).length > 0`
- On Save: POST only the dirty rows (filter `events` by `dirty[date]`) → clear `dirty`, show `msg`
- Size selector: eligible sizes from `other` field (server sends allowed sizes)
- Niyaz events: show adult/kid count inputs
- "Less rice/bread" toggle
- `beforeunload` warning when `dirty`
- Prev/Next buttons: navigate to `?offset=X±7`
- `localStorage` for default `adults` and `kids` counts

**API:** `GET rsvp.php`, `POST rsvp.php`

**Test:**
1. Events for current week shown
2. Toggle any field → Save enables
3. Submit → success, `dirty = false`
4. Prev/Next changes week displayed

---

### Step 5: Print Page

**Source:** `app/print.html`, `app/js/print.js`

- `frontend/src/routes/print/+page.svelte`
- `<svelte:head><title>Print</title></svelte:head>`
- Fetch `print.php?offset=X`; store as `rows` and `meta`
- Checkbox tracking: `here`, `filled` per thaali (simple `dirty = true` on change)
- Client-side filters: area, size, rice, here, filled, name search
- Client-side sort: thaali, area, size, name, here, filled (toggle asc/desc)
- Summary stats computed from filtered rows (not-here, not-filled, total)
- Second line: size breakdown OR niyaz adult/kid split (based on `meta.niyaz`)
- Warn if marking on different date than today
- "Generate Labels" → `window.open('generate_labels.php?date=X&...')` (file download)
- POST `print.php` on Save

**API:** `GET print.php`, `POST print.php`, `GET generate_labels.php`

**Test:** Filter by area → stats update. Sort by size → rows reorder. Generate labels → download.

---

### Step 6: Shop Page

**Source:** `app/shop.html`, `app/js/shop.js`

- `frontend/src/routes/shop/+page.svelte`
- `<svelte:head><title>Shopping List</title></svelte:head>`
- Fetch `shop.php?offset=X` — read-only display
- Render: date → menu → ingredient lines + count metadata
- Prev/Next: `?offset=X±7`

**API:** `GET shop.php`

**Test:** Ingredients grouped by date and menu. Navigation changes week.

---

### Step 7: Event Admin

**Source:** `app/event.html`, `app/js/event.js`

- `frontend/src/routes/event/+page.svelte`
- `<svelte:head><title>Events</title></svelte:head>`
- Redirect to `/` if `!isAdmin()`
- Fetch `event.php?offset=X` → array of 7 days (some with data, some `{date}` placeholder)
- Editable: details (text), niyaz (checkbox), enabled (checkbox)
- `dirty = true` on any change; POST full array on Save
- Empty details = delete event (handled server-side)
- Prev/Next: `?offset=X±7`

**API:** `GET event.php`, `POST event.php`

**Test:** Non-admin redirected. Edit + save → success. Clear details + save → event gone.

---

### Step 8: Family Admin

**Source:** `app/family.html`, `app/js/family.js`

- `frontend/src/routes/family/+page.svelte`
- `<svelte:head><title>Families</title></svelte:head>`
- Redirect if `!isAdmin()`
- Fetch `family.php?offset=X` → 10 records per page (offset = starting thaali number)
- Editable table: thaali, its, firstName, lastName, size, area, email, phone, poc, resp
- `dirty = true` on any change; POST full array on Save
- Empty email = delete record (server-side)
- Prev/Next: `?offset=X±10`

**API:** `GET family.php`, `POST family.php`

**Test:** Edit → save → success. Clear email → save → record deleted.

---

### Step 9: Measure Admin

**Source:** `app/measure.html`, `app/js/measure.js`

- `frontend/src/routes/measure/+page.svelte`
- `<svelte:head><title>Measures</title></svelte:head>`
- Redirect if `!isAdmin()`
- Fetch `measure.php?offset=X&len=10` + `ingred.php` in parallel
- Editable: menu name, ingredients (name + autocomplete, multiplier, unit)
- Autocomplete: keyboard nav (↑/↓, Enter to confirm, Escape to dismiss)
- `dirty = true` on any change; POST on Save
- Prev/Next: `?offset=X±10`

**API:** `GET measure.php`, `POST measure.php`, `GET ingred.php`

**Test:** Type ingredient → suggestions appear. Keyboard nav works. Save → success.

---

### Step 10: Cutover

- Change `frontend/svelte.config.js` output from `../build-svelte` to `../build`
- Update root `package.json`:
  - `npm run build` → `cd frontend && npm run build && cd .. && perl deploy.pl .env`
  - Remove old AngularJS build steps (build:js, build:css, build:templates, build:index)
  - Keep `build:php` (copies `app/*.php` to `build/`)
- Update `docker-entrypoint-dev.sh`: run `cd frontend && npm run dev` instead of old dev pipeline
- Update `deploy.pl`: ensure substitutions target `build/index.html` (SvelteKit output)
- Delete: `app/js/`, `app/lib/angularjs/`, `app/css/`, `app/*.html` (keep `app/*.php`)

**Test:** Full smoke test all pages from built output.

---

## Key Pattern Translations

| AngularJS | Svelte |
|---|---|
| `$scope.changed` | `let dirty = false` |
| `ng-repeat` | `{#each}` |
| `ng-model` | `bind:value` |
| `ng-show/ng-if` | `{#if}` |
| `ng-class` | `class:foo={condition}` |
| `$cookies` | `document.cookie` helpers in `lib/auth.js` |
| `$state.go('login')` | `goto('/login')` |
| `$rootScope.init()` | `onMount` + shared lib functions |
| Bootstrap 3 | Tailwind CSS |

## Verification

After each step:
1. `npm run build:svelte` → no errors
2. Serve `build-svelte/` via PHP: `php -S localhost:8010 -t build-svelte`
3. Step-specific tests above
4. `./vendor/bin/phpunit` → PHP unit tests still pass (unchanged)
