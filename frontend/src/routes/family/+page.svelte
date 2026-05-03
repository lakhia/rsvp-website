<script>
  import { page } from '$app/state';
  import { get, post, navigate } from '$lib/api.js';
  import { requireAdmin } from '$lib/auth.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import PageNav from '$lib/PageNav.svelte';
  import { getIntParam } from '$lib/utils.js';

  const ps = new PageState();

  let families = $state([]);
  let dirty = $state(false);
  let q = $state('');
  let areaFilter = $state('');

  // Family uses thaali number as offset (1-indexed), not week offset
  const offset = $derived(getIntParam(page.url.searchParams, 'offset', 1));

  $effect(() => {
    if (!requireAdmin()) return;
    loadData(offset);
  });

  async function loadData(o) {
    await ps.load(async () => {
      const res = await get('family.php', { offset: o });
      families = res.data || [];
      ps.msg = res.msg || '';
      dirty = false;
      q = '';
      areaFilter = '';
    });
  }

  async function handleSave() {
    await ps.save(async () => {
      const res = await post('family.php', { offset }, families);
      families = res.data || [];
      ps.msg = res.msg || 'Saved';
      dirty = false;
    });
  }

  const incomplete = $derived(families.filter((f) => !f.its || !f.phone || !f.poc).length);

  const visible = $derived(
    families.filter((f) => {
      if (areaFilter && f.area?.toLowerCase() !== areaFilter.toLowerCase()) return false;
      if (!q) return true;
      const hay = `${f.firstName} ${f.lastName} ${f.email} ${f.its} ${f.poc}`.toLowerCase();
      return hay.includes(q.toLowerCase());
    })
  );
</script>

<svelte:head>
  <title>{__APP_NAME__} - Families</title>
</svelte:head>

<!-- Page header -->
<div class="page-header px-8 pt-5 pb-4">
  <div class="flex items-end justify-between gap-6">
    <div>
      <div class="page-eyebrow mb-1">Admin · Families</div>
      <h1>
        Families
        <span class="heading-count">· {families.length}</span>
      </h1>
      <div class="page-subtitle mt-1">
        {#if incomplete > 0}
          <b class="warning-count">{incomplete}</b> with missing details ·
        {/if}
        Edit family details
      </div>
    </div>
    <button onclick={() => window.open(__BASE_PATH__ + '/dump.php?table=family')} class="btn-secondary">
      Export CSV
    </button>
  </div>
</div>

<!-- Filter bar -->
<div class="flex flex-wrap items-center gap-3 px-8 py-2.5 filter-bar">
  <input
    type="search"
    bind:value={q}
    placeholder="Search name, ITS, email…"
    class="filter-input"
  />
  <label class="label-row text-xs text-muted">
    Area
    <input
      type="text"
      bind:value={areaFilter}
      placeholder="All"
      class="input-sm area-filter"
    />
  </label>
  <div class="flex-1"></div>
  <span class="count-label">{visible.length} of {families.length}</span>
</div>

{#if ps.loading}
  <Loading />
{:else}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 px-8 py-4">
    {#each visible as f}
      {@const isIncomplete = !f.its || !f.phone || !f.poc}
      <div class="card family-card">
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
              {#if f.resp}
                <span class="separator">·</span>
                <span class="tag tag-muted">
                  {f.resp === 'F' ? 'Filling Team' : ''}
                </span>
              {/if}
            </div>
          </div>
          {#if isIncomplete}
            <span title="Missing ITS, phone, or POC" class="incomplete-dot"></span>
          {/if}
        </div>

        <!-- Field grid + size -->
        <div class="field-section">
          <div class="field-grid">
            <div class="field-label">Email</div>
            <input
              type="email"
              bind:value={f.email}
              oninput={() => (dirty = true)}
              placeholder="—"
              class="input-inline input-mono"
            />
            <div class="field-label">Phone</div>
            <input
              type="text"
              bind:value={f.phone}
              oninput={() => (dirty = true)}
              placeholder="—"
              class="input-inline input-mono"
            />
            <div class="field-label">ITS</div>
            <input
              type="text"
              bind:value={f.its}
              oninput={() => (dirty = true)}
              placeholder="——"
              class="input-inline input-tabular"
            />
            <div class="field-label">POC</div>
            <input
              type="text"
              bind:value={f.poc}
              oninput={() => (dirty = true)}
              placeholder="Unassigned"
              class="input-inline input-data"
            />
          </div>
          <div class="flex items-center gap-2 mt-2.5">
            <div class="field-label">Size</div>
            <div class="flex gap-1">
              {#each ['XS', 'SM', 'MD', 'LG', 'XL'] as s}
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
  onPrev={() => navigate(`/family?offset=${Math.max(1, offset - 10)}`)}
  onNext={() => navigate(`/family?offset=${offset + 10}`)}
  onSave={handleSave}
  {dirty}
  saving={ps.saving}
  prevDisabled={offset <= 1}
/>

<style>
  .heading-count {
    color: var(--muted);
    font-weight: 400;
    font-size: 18px;
    margin-left: 6px;
  }

  .warning-count { color: #b45309; }

  .filter-bar {
    background: var(--bg);
    border-bottom: 1px solid var(--border);
  }

  .area-filter {
    width: 52px;
    text-align: center;
  }

  .family-card { padding: 14px 16px; }

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
    text-transform: uppercase;
  }

  .separator { color: var(--border-strong); }

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

  .input-data { font-size: 12.5px; }

  .field-label {
    font-size: 10px;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--muted);
    font-weight: 600;
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
