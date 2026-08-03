<script>
  import { page } from '$app/state';
  import { get, post, navigate } from '$lib/api.js';
  import { requireAdmin } from '$lib/auth.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import PageNav from '$lib/PageNav.svelte';
  import { getIntParam } from '$lib/utils.js';
  import { SIZE_ORDER } from '$lib/constants.js';

  const ps = new PageState();

  let families = $state([]);
  let dirty = $state(false);
  let hasMore = $state(false);

  // URL is source of truth for all navigation state
  const urlQ      = $derived(page.url.searchParams.get('q') ?? '');
  const urlArea   = $derived(page.url.searchParams.get('area') ?? '');
  const urlOffset = $derived(getIntParam(page.url.searchParams, 'offset', 0));
  // Effective offset: search uses 0-based row index, normal uses 1-based thaali number
  const curOffset = $derived(urlQ || urlArea ? urlOffset : (urlOffset || 1));

  // Local input state — committed to URL only on Enter
  let q = $state('');
  let areaFilter = $state('');

  // Keep inputs in sync when URL changes (e.g. browser back/forward)
  $effect(() => { q = urlQ; areaFilter = urlArea; });;

  // Load data whenever URL params change
  $effect(() => {
    if (!requireAdmin()) return;
    fetchFamilies();
  });

  function pageParams(offset) {
    const p = { offset };
    if (urlQ) p.q = urlQ;
    if (urlArea) p.area = urlArea;
    return p;
  }

  async function fetchFamilies() {
    await ps.load(async () => {
      const res = await get('family.php', pageParams(curOffset));
      families = res.data || [];
      ps.msg = res.msg || '';
      dirty = false;
      hasMore = res.other?.hasMore ?? false;
    });
  }

  function buildPageUrl(newOffset) {
    return '/family?' + new URLSearchParams(pageParams(newOffset)).toString();
  }

  function submitSearch() {
    if (q === urlQ && areaFilter === urlArea) return;
    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (areaFilter) params.set('area', areaFilter);
    if (q || areaFilter) params.set('offset', '0');
    const qs = params.toString();
    navigate('/family' + (qs ? '?' + qs : ''));
  }

  function prevPage() {
    navigate(buildPageUrl(Math.max(urlQ || urlArea ? 0 : 1, curOffset - 10)));
  }

  function nextPage() {
    navigate(buildPageUrl(curOffset + 10));
  }

  async function handleSave() {
    await ps.save(async () => {
      const res = await post('family.php', { offset: curOffset }, families);
      families = res.data || [];
      ps.msg = res.msg || 'Saved';
      dirty = false;
    });
  }

  const incomplete = $derived(families.filter((f) => !f.its || !f.phone || !f.poc).length);
</script>

<svelte:head>
  <title>{__APP_NAME__} - Families</title>
</svelte:head>

<!-- Page header -->
<div class="page-header">
  <div class="flex items-end justify-between gap-6">
    <div>
      <div class="eyebrow mb-1">Admin · Families</div>
      <h1>
        Families
      </h1>
      <div class="page-subtitle">
        {#if incomplete > 0}
          <b class="warning-count">{incomplete}</b> with missing details ·
        {/if}
        Edit family details
      </div>
    </div>
    <button onclick={() => window.open(__BASE_PATH__ + '/dump.php?table=family')} class="btn-dark">
      Export CSV
    </button>
  </div>
</div>

<!-- Filter bar -->
<div class="flex flex-wrap items-center gap-3 px-8 py-2.5 filter-bar">
  <input
    type="search"
    bind:value={q}
    onkeydown={(e) => e.key === 'Enter' && submitSearch()}
    onblur={submitSearch}
    placeholder="Search name, ITS, email"
    class="filter-input"
  />
  <label class="label-row">
    Area
    <input
      type="text"
      bind:value={areaFilter}
      onkeydown={(e) => e.key === 'Enter' && submitSearch()}
      onblur={submitSearch}
      placeholder="All"
      class="input-sm area-filter"
    />
  </label>
  <div class="flex-1"></div>
  <span class="count-label">{families.length}{hasMore ? '+' : ''} results</span>
</div>

{#if ps.loading}
  <Loading />
{:else}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 py-4">
    {#each families as f}
      {@const isIncomplete = !f.its || !f.phone || !f.poc}
      <div class="card py-3.5 px-4">
        <!-- Top row: number badge + name + incomplete dot -->
        <div class="flex items-start gap-3 mb-2.5">
          <div class="thaali-badge">
            {f.thaali}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex gap-2 name-row">
              <input
                type="text"
                bind:value={f.firstName}
                oninput={() => (dirty = true)}
                placeholder="First"
                class="input-inline flex-1 min-w-0 name-input"
              />
              <input
                type="text"
                bind:value={f.lastName}
                oninput={() => (dirty = true)}
                placeholder="Last"
                class="input-inline flex-1 min-w-0 name-input"
              />
            </div>
            <div class="flex flex-wrap items-center gap-2 meta-row">
              <span class="flex items-baseline gap-1">
                Area
                <input
                  type="text"
                  bind:value={f.area}
                  oninput={() => (dirty = true)}
                  placeholder="—"
                  class="input-inline area-input"
                />
              </span>
              <span class="separator">·</span>
              <button
                onclick={() => { f.resp = f.resp === 'F' ? null : 'F'; dirty = true; }}
                class="tag {f.resp === 'F' ? 'tag-accent' : 'tag-muted'} tag-toggle"
              >Filling Team</button>
            </div>
          </div>
          {#if isIncomplete}
            <span title="Missing ITS, phone, or POC" class="incomplete-dot"></span>
          {/if}
        </div>

        <!-- Field grid + size -->
        <div class="field-section">
          <div class="field-grid">
            <div class="eyebrow">Email</div>
            <input
              type="email"
              bind:value={f.email}
              oninput={() => (dirty = true)}
              placeholder="—"
              class="input-inline input-mono"
            />
            <div class="eyebrow">Phone</div>
            <input
              type="text"
              bind:value={f.phone}
              oninput={() => (dirty = true)}
              placeholder="—"
              class="input-inline input-mono"
            />
            <div class="eyebrow">ITS</div>
            <input
              type="text"
              bind:value={f.its}
              oninput={() => (dirty = true)}
              placeholder="——"
              class="input-inline input-tabular"
            />
            <div class="eyebrow">POC</div>
            <input
              type="text"
              bind:value={f.poc}
              oninput={() => (dirty = true)}
              placeholder="Unassigned"
              class="input-inline"
              style="font-size: 12.5px;"
            />
            <div class="eyebrow">Size</div>
            <div class="flex gap-1">
              {#each SIZE_ORDER as s}
                <button
                  onclick={() => { f.size = s; dirty = true; }}
                  class="round-size-btn {f.size === s ? 'active' : ''}"
                >{s}</button>
              {/each}
            </div>
          </div>
        </div>
      </div>
    {/each}
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
  onPrev={prevPage}
  onNext={nextPage}
  onSave={handleSave}
  {dirty}
  saving={ps.saving}
  prevDisabled={curOffset <= (urlQ || urlArea ? 0 : 1)}
  nextDisabled={(urlQ || urlArea) && !hasMore}
/>

<style>
  .warning-count { color: #b45309; }

  .filter-bar {
    background: var(--bg);
    border-bottom: 1px solid var(--border);
  }

  .area-filter {
    width: 52px;
    text-align: center;
  }

  .thaali-badge {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: var(--subtle);
    border: 1px solid var(--border);
    display: grid;
    place-items: center;
    flex-shrink: 0;
    font-size: 13px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
  }

  .name-row { line-height: 1.2; margin-bottom: 2px; }
  .name-input { font-size: 15px; font-weight: 600; }

  .meta-row {
    font-size: 11px;
    color: var(--muted);
  }

  .area-input {
    width: 28px;
    font-size: 11px;
    font-weight: 600;
    color: var(--text);
  }

  .separator { color: var(--border-strong); }

  .tag-toggle {
    border: none;
    cursor: pointer;
    font-family: inherit;
  }

  .incomplete-dot {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: #d97706;
    flex-shrink: 0;
    margin-top: 6px;
    display: inline-block;
  }

  .field-section {
    padding-top: 10px;
    border-top: 1px dashed var(--border);
  }

  .input-mono {
    font-size: 12.5px;
    font-family: ui-monospace, 'SF Mono', Menlo, monospace;
  }

  .input-tabular {
    font-size: 12.5px;
    font-variant-numeric: tabular-nums;
  }

  .field-grid {
    display: grid;
    grid-template-columns: auto 1fr;
    column-gap: 14px;
    row-gap: 8px;
    align-items: baseline;
  }

  @media (min-width: 560px) and (max-width: 639px) {
    .field-grid {
      grid-template-columns: auto 1fr auto 1fr;
    }
  }

  @media (min-width: 1200px) {
    .field-grid {
      grid-template-columns: auto 1fr auto 1fr;
    }
  }
</style>
