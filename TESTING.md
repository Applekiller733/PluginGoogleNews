# Testing & Pre-Submission Guide

This guide takes the plugin from source to a state you can confidently submit to
the WordPress.org directory. It has three phases:

1. **Build** — compile the source so WordPress can run it.
2. **Runtime testing** — run it inside a real WordPress via wp-env and click through it.
3. **Compliance checks** — run the same automated scans the review team uses.

Work through them in order. Do not skip the build: the plugin will not run until
`src/` is compiled into `build/`.

---

## Prerequisites (install once)

- **Node.js** (LTS). Check: `node -v`
- **Docker Desktop**, installed and *running*. Check: `docker info` (should not error).
- **Composer** (for the PHP checks). Check: `composer --version`

---

## Phase 1 — Build

From the plugin folder:

```bash
npm install        # fetch build tooling (first time only)
npm run build      # compile src/ -> build/
```

This transpiles the JSX, compiles the SCSS, generates `build/index.asset.php`,
and copies `block.json` and `render.php` into `build/`. If this step errors,
it is almost always a JSX/JS syntax problem — fix it before going further, since
nothing else can work until the build succeeds.

For active development, run `npm run start` instead — it rebuilds automatically
whenever you save a source file. Leave it running in its own terminal tab.

---

## Phase 2 — Runtime testing with wp-env

### Start the environment

Make sure Docker Desktop is running, then:

```bash
npm run env:start
```

The first run downloads WordPress and sets up the containers, so give it a few
minutes. When it finishes, you have a working WordPress at:

- Site:  http://localhost:8888
- Admin: http://localhost:8888/wp-admin  (user: `admin`, password: `password`)

Because `.wp-env.json` mounts this folder as a plugin, it is already installed.
Activate it under **Plugins**, then create a new page to test the block.

### The manual test checklist

Work through these in the editor and on the published page. Each targets a
specific behavior built into the plugin.

**Basic rendering**
- [ ] Add the "News Follow Buttons" block. It appears in the inserter.
- [ ] Enable each button, give each a valid URL (see below), and publish.
- [ ] On the published page, all three buttons render and link correctly.

**Valid test URLs** (these satisfy the prefix validation)
- News:      `https://news.google.com/publications/TEST123`
- Discover:  `https://profile.google.com/cp/TEST123`
- Preferred: `https://www.google.com/preferences/source?q=example.com`

**URL validation (the feature you added last)**
- [ ] Enter an off-pattern URL (e.g. `https://example.com`) in a button field.
      An inline warning appears in the editor naming the required prefix.
- [ ] Publish with that bad URL. As a logged-in editor, viewing the page shows
      the amber admin-only notice; the bad button does not render.
- [ ] Open the same page in a private/incognito window (logged out). The notice
      is **gone** and the bad button is simply absent — a visitor sees neither.
- [ ] A valid button on the same block still renders normally.

**Styling controls**
- [ ] Change a button's background/text/border colors, font size, weight, and
      border style. The editor preview updates live.
- [ ] Publish and confirm the front end matches the preview.
- [ ] Try an extreme value (font size at max, thick border). It stays sane.

**Responsiveness**
- [ ] Narrow the browser below ~480px (or use device emulation). Buttons stack
      full-width instead of sitting in a row.

**Placement & block features**
- [ ] Set alignment (left/center/right) and confirm it applies.
- [ ] Toggle "open in new tab" and verify the link's target on the front end.
- [ ] Move the block around the page; it behaves like any core block.

**Debugging**
If something misbehaves, watch the PHP debug log (WP_DEBUG is on):

```bash
npm run env:cli -- eval 'error_log("test");'   # sanity check CLI works
```

Or tail the log directly:

```bash
npx wp-env logs
```

### Stop / reset

```bash
npm run env:stop     # stop containers (state is kept)
npm run env:clean    # wipe and start fresh if the install gets messy
```

---

## Phase 3 — Compliance checks (run before every submission)

These are the checks that decide whether the directory accepts your plugin.
Running them yourself is the single best way to avoid a rejection.

### 3a. Plugin Check (PCP) — the official scanner

This is WordPress's own pre-submission plugin. Install it into your wp-env site:

```bash
npm run env:cli -- plugin install plugin-check --activate
```

Then either:
- In wp-admin, go to **Tools -> Plugin Check**, select this plugin, and run it; or
- From the CLI:

```bash
npm run env:cli -- plugin check news-follow-buttons
```

Fix every **Error**. Review **Warnings** — some are advisory, but most are worth
resolving. Common things it flags: unescaped output, missing text domain,
readme.txt format issues, disallowed function calls.

### 3b. PHPCS with WordPress Coding Standards

This checks your PHP against the directory's sanitization/escaping rules.

```bash
composer install     # first time only, fetches the sniffers
composer run lint     # scan (config is in .phpcs.xml.dist)
composer run lint:fix # auto-fix what can be fixed
```

Aim for a clean run. Where an intentional exception exists, it is annotated in
the code with a `phpcs:ignore` comment explaining why.

### 3c. JS/CSS linting

```bash
npm run lint:js
npm run lint:css
```

---

## Final pre-submission checklist

- [ ] `npm run build` succeeds with no errors.
- [ ] All manual runtime tests pass (logged in AND logged out).
- [ ] Plugin Check reports zero errors.
- [ ] `composer run lint` is clean.
- [ ] `npm run lint:js` and `npm run lint:css` are clean.
- [ ] Author, URI, and contributor fields are filled in (no placeholders).
- [ ] Verify the Discover and preferred-source URL prefixes against real Google
      URLs (they were supplied, not independently confirmed).
- [ ] Build the final artifact: `npm run plugin-zip`.

The zip from `plugin-zip` — built from compiled output by the official tool — is
what you upload, not the source zip.
