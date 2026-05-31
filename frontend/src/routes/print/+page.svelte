<script>
  import { page } from '$app/state';
  import { get, post, navigate } from '$lib/api.js';
  import { getDisplayDate } from '$lib/dates.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import Dialog from '$lib/Dialog.svelte';
  import PageNav from '$lib/PageNav.svelte';
  import { getIntParam, paginateUrl, dateToOffset } from '$lib/utils.js';
  import { SIZE_ORDER, SIZE_WEIGHT } from '$lib/constants.js';

  const ps = new PageState();

  let rows = $state([]);
  let meta = $state({});
  let date = $state('');
  let dirty = $state(false);
  let dateWarning = $state('');
  let confirmingReset = $state(false);

  let sortCol = $state('');
  let filters = $state({
    area: '',
    size: '',
    here: '',
    filled: '',
    norice: '',
  });

  const offset = $derived(getIntParam(page.url.searchParams, 'offset'));
  const dateParam = $derived(page.url.searchParams.get('date') || '');
  const eventIndex = $derived(getIntParam(page.url.searchParams, 'event_index'));

  const effectiveOffset = $derived(dateParam ? dateToOffset(dateParam) : offset);

  $effect(() => {
    loadData(effectiveOffset, eventIndex);
  });

  let warnedDate = $state('');

  function applyResponse(res, defaultMsg = '') {
    rows = res.data || [];
    meta = res.other || {};
    date = res.date || '';
    ps.msg = res.msg || defaultMsg;
  }

  async function loadData(o, ei) {
    dateWarning = '';
    warnedDate = '';
    await ps.load(async () => {
      const res = await get('print.php', { offset: o, event_index: ei });
      // If the requested event_index doesn't exist for this date (e.g. it
      // was deleted) but another event does, redirect to that one instead
      // of getting stuck on "No responses available".
      const events = res.other?.events ?? [];
      if (events.length > 0 && !events.some((ev) => ev.event_index === ei)) {
        switchEvent(events[0].event_index);
        return;
      }
      applyResponse(res);
      dirty = false;
    });
  }

  function switchEvent(ei) {
    const params = new URLSearchParams({ offset: effectiveOffset, event_index: ei });
    navigate('/print?' + params.toString());
  }

  function onCheckboxChange(item) {
    if (!warnedDate) {
      warnedDate = new Date().toLocaleDateString('en-CA');
      if (date !== warnedDate) {
        dateWarning = `You are modifying ${getDisplayDate(date)}.`;
      }
    }
    dirty = true;
    ps.msg = '';
  }

  function markAllFilled() {
    for (const item of filteredRows) {
      item.filled = 1;
    }
    dirty = true;
    ps.msg = '';
  }

  function applyFilters(r, f, skip = null) {
    if (skip !== 'area' && f.area && r.area !== f.area) return false;
    if (skip !== 'size' && f.size && r.size !== f.size) return false;
    if (f.here === 'Y' && !r.here) return false;
    if (f.here === 'N' && r.here) return false;
    if (f.filled === 'Y' && !r.filled) return false;
    if (f.filled === 'N' && r.filled) return false;
    if (f.norice === 'N' && r.norice) return false;
    return true;
  }

  const filteredRows = $derived(rows.filter((r) => applyFilters(r, filters)));

  // Size counts that update with all filters except size itself
  const sizeCountsFiltered = $derived.by(() => {
    const counts = {};
    for (const r of rows) {
      if (!applyFilters(r, filters, 'size')) continue;
      counts[r.size] = (counts[r.size] ?? 0) + 1;
    }
    return counts;
  });

  const areasFiltered = $derived(
    [...new Set(rows.filter((r) => applyFilters(r, filters, 'area')).map((r) => r.area).filter(Boolean))].sort()
  );

  $effect(() => {
    if (filters.area && !areasFiltered.includes(filters.area)) {
      filters.area = '';
    }
  });

  const sortedRows = $derived.by(() => {
    return [...filteredRows].sort((a, b) => {
      const av = a[sortCol] ?? '';
      const bv = b[sortCol] ?? '';
      if (sortCol === 'size') {
        if (meta.niyaz) return (a.count ?? 0) - (b.count ?? 0);
        return SIZE_ORDER.indexOf(av) - SIZE_ORDER.indexOf(bv);
      }
      return av < bv ? -1 : av > bv ? 1 : 0;
    });
  });

  const filledCount = $derived(rows.filter((r) => r.filled).length);
  const weightedTotal = $derived(rows.reduce((s, r) => s + (SIZE_WEIGHT[r.size] ?? 1), 0));
  const weightedFilled = $derived(rows.filter((r) => r.filled).reduce((s, r) => s + (SIZE_WEIGHT[r.size] ?? 1), 0));
  const filledPct = $derived(weightedTotal ? weightedFilled / weightedTotal : 0);
  const RING_RADIUS = 32;
  const ringCircum = 2 * Math.PI * RING_RADIUS;

  const shownCount = $derived(filteredRows.length);

  const niyazSummary = $derived(
    `Adults: ${meta.adults ?? 0}, Kids: ${meta.kids ?? 0}, Thaals: ${Math.round(((meta.adults ?? 0) / 8 + (meta.kids ?? 0) / 10) * 10) / 10}`
  );

  const menuTitle = $derived(
    Object.keys(meta.serving ?? {}).join(' + ') || getDisplayDate(date)
  );

  function toggleSizeFilter(s) {
    filters.size = filters.size === s ? '' : s;
  }

  async function handleSave() {
    const body = rows.map((r) => ({
      thaali: r.thaali,
      here: r.here ? 1 : 0,
      filled: r.filled ? 1 : 0,
    }));
    await ps.save(async () => {
      const res = await post('print.php', { offset: effectiveOffset, event_index: eventIndex }, body);
      applyResponse(res, 'Saved');
      dirty = false;
    });
  }

  function handleReset() {
    for (const r of rows) {
      r.here = 0;
      r.filled = 0;
    }
    dirty = true;
    ps.msg = '';
    warnedDate = '';
    dateWarning = '';
    confirmingReset = false;
  }

  function exportCSV() {
    const params = new URLSearchParams({ offset: effectiveOffset, event_index: eventIndex });
    window.open(__BASE_PATH__ + '/export_rsvps.php?' + params.toString());
  }

  function generateLabels() {
    const params = new URLSearchParams({
      offset: effectiveOffset,
      event_index: eventIndex,
      sort: sortCol,
      filterArea: filters.area,
      filterSize: filters.size,
      filterHere: filters.here,
      filterFilled: filters.filled,
      filterNorice: filters.norice,
    });
    window.open(__BASE_PATH__ + '/generate_labels.php?' + params.toString());
  }

  function paginate(delta) {
    navigate(paginateUrl('/print', dateParam, offset, delta));
  }

  const servingEntries = $derived(Object.entries(meta.serving ?? {}));

const sizeSummary = $derived.by(() => {
    const togo = {};
    const tiffins = {};
    const total = {};
    for (const r of rows) {
      const s = r.size;
      if (!s) continue;
      total[s] = (total[s] ?? 0) + 1;
      if (r.here) {
        tiffins[s] = (tiffins[s] ?? 0) + 1;
      } else {
        togo[s] = (togo[s] ?? 0) + 1;
      }
    }
    return { togo, tiffins, total };
  });
</script>

<svelte:head>
  <title>{__APP_NAME__} - Print</title>
</svelte:head>

<!-- Floating progress ring -->
{#if !meta.niyaz && rows.length > 0}
  <div class="progress-ring no-print" style="position: fixed; bottom: 1rem; right: 1rem; z-index: 50;">
    <svg viewBox="0 0 68 68" width="100%" height="100%">
      <circle cx="34" cy="34" r={RING_RADIUS} stroke="var(--border-strong)" stroke-width="4" fill="none"/>
      <circle cx="34" cy="34" r={RING_RADIUS} stroke="var(--accent)" stroke-width="4" fill="none"
        stroke-dasharray="{ringCircum}"
        stroke-dashoffset="{ringCircum * (1 - filledPct)}"
        transform="rotate(-90 34 34)"
        stroke-linecap="round"/>
    </svg>
    <div style="position: absolute; inset: 0; display: grid; place-items: center; font-size: 14px; font-weight: 600; font-variant-numeric: tabular-nums;">
      {Math.round(filledPct * 100)}%
    </div>
  </div>
{/if}

<!-- Event tabs (shown when multiple events share the same date) -->
{#if (meta.events ?? []).length > 1}
  <div class="flex gap-1.5 mb-4 no-print">
    {#each meta.events as ev}
      <button
        onclick={() => switchEvent(ev.event_index)}
        class="final-pill {ev.event_index === meta.event_index ? 'on-dark' : ''}"
      >
        {ev.details || `Event ${ev.event_index + 1}`}
      </button>
    {/each}
  </div>
{/if}

<!-- Page header -->
<div class="page-header no-print">
  <div class="flex items-center gap-4 flex-wrap">
    <!-- Left group: title -->
    <div style="flex: 1; min-width: 0;">
      <div class="eyebrow mb-0.5">{getDisplayDate(date)}</div>
      <h1>{menuTitle}</h1>
      {#if !meta.niyaz && rows.length > 0}
        <div class="page-subtitle">
          <b class="text-content">{filledCount}</b> filled ·
          {rows.length - filledCount} left ·
          {rows.length} RSVPs total
        </div>
      {:else if meta.niyaz}
        <div class="page-subtitle">{niyazSummary}</div>
      {/if}
    </div>

    <!-- Right group: size buttons + print labels wrap together and fill row when alone -->
    <div style="flex: 1; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
      {#if !meta.niyaz && rows.length > 0}
        <div class="flex gap-1.5 flex-wrap">
          {#each SIZE_ORDER.filter((s) => sizeCountsFiltered[s]) as s}
            <button
              onclick={() => toggleSizeFilter(s)}
              class="size-stat-btn {filters.size === s ? 'active' : ''}"
            >
              <div class="size-stat-label">{s}</div>
              <div class="size-stat-count">{sizeCountsFiltered[s]}</div>
            </button>
          {/each}
        </div>
      {/if}
      <button onclick={exportCSV} class="btn-secondary">Export</button>
      <button onclick={generateLabels} class="btn-dark">Print labels</button>
    </div>
  </div>
</div>

{#if dateWarning}
  <Dialog
    message={dateWarning}
    cancelLabel="OK"
    onCancel={() => (dateWarning = '')}
  />
{/if}

<!-- Serving guidance -->
{#if !meta.niyaz && servingEntries.length > 0}
  {@const usedSizes = SIZE_ORDER.filter((s) => sizeSummary.total[s] > 0)}
  <div style="overflow-x: auto;">
    <table class="mt-3 mb-3 no-print text-xs" style="border: 1px solid var(--border);">
      <thead>
        <tr>
          <th class="serving-cell font-medium text-muted"></th>
          {#each usedSizes as s}
            <th class="serving-cell font-semibold">{s}</th>
          {/each}
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="serving-cell text-muted">Tiffins</td>
          {#each usedSizes as s}
            <td class="serving-cell">{sizeSummary.tiffins[s] ?? 0}</td>
          {/each}
        </tr>
        <tr>
          <td class="serving-cell text-muted">Togo</td>
          {#each usedSizes as s}
            <td class="serving-cell">{sizeSummary.togo[s] ?? 0}</td>
          {/each}
        </tr>
        <tr style="border-bottom: 2px solid var(--border);">
          <td class="serving-cell font-medium">Total</td>
          {#each usedSizes as s}
            <td class="serving-cell font-medium">{sizeSummary.total[s] ?? 0}</td>
          {/each}
        </tr>
        {#each servingEntries as [menu, portions]}
          <tr>
            <td class="serving-cell font-medium">{menu}</td>
            {#each usedSizes as s}
              {@const match = portions.find((p) => p.startsWith(s + ':'))}
              <td class="serving-cell">{match ? match.slice(s.length + 2) : ''}</td>
            {/each}
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
{/if}

{#if ps.loading}
  <Loading />
{:else}
  <!-- Filter bar -->
  {#if rows.length > 0}
    <div class="flex flex-wrap items-center gap-2 py-2.5 px-1 mb-3 no-print" style="border-bottom: 1px solid var(--border);">
      {#if !meta.niyaz}
        <!-- Unfilled toggle -->
        <div class="segmented">
          <button class="{filters.filled === 'N' ? 'active' : ''}" onclick={() => { filters.filled = filters.filled === 'N' ? '' : 'N'; }}>Unfilled</button>
        </div>

        <!-- With Rice toggle -->
        <div class="segmented">
          <button class="{filters.norice === 'N' ? 'active' : ''}" onclick={() => { filters.norice = filters.norice === 'N' ? '' : 'N'; }}>With Rice</button>
        </div>

        <!-- Tiffin segmented control -->
        <div class="segmented">
          {#each [['','All'],['Y','Tiffin'],['N','Togo']] as [key, label]}
            <button class="{filters.here === key ? 'active' : ''}" onclick={() => filters.here = key}>{label}</button>
          {/each}
        </div>
      {/if}

      <!-- Sort -->
      <label class="label-row">
        Sort:
        <select bind:value={sortCol} class="input-sm text-xs">
          <option value=""></option>
          <option value="thaali">Thaali</option>
          <option value="area">Area</option>
          <option value="name">Name</option>
          <option value="size">{meta.niyaz ? 'Count' : 'Size'}</option>
          {#if !meta.niyaz}
            <option value="here">Tiffin</option>
            <option value="filled">Filled</option>
            <option value="norice">No Rice</option>
          {/if}
        </select>
      </label>

      <!-- Area filter -->
      <label class="label-row">
        Area:
        <select bind:value={filters.area} class="input-sm text-xs">
          <option value="">All</option>
          {#each areasFiltered as area}<option value={area}>{area}</option>{/each}
        </select>
      </label>

      <div style="flex: 1;"></div>

      <span class="count-label">{shownCount} of {rows.length}</span>

      {#if !meta.niyaz && meta.save && shownCount > 0}
        <button onclick={markAllFilled} class="btn-primary" style="font-size: 11px; font-weight: 600; padding: 6px 12px;">
          Fill all shown
        </button>
      {/if}
    </div>
  {/if}

  <!-- Rows -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
    {#each sortedRows as item}
      <div class="filling-row {item.filled ? 'filled' : ''}">
        <!-- Row 1: thaali | name -->
        <div style="text-align: center;">{item.thaali}</div>
        <div style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{item.name ?? ''}</div>
        <!-- Row 2: area | adult/child count (niyaz) or size+buttons -->
        <div style="text-align: center;">{item.area ?? ''}</div>
        <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
          {#if meta.niyaz}
            <span class="text-sm">{item.size}</span>
          {:else}
            <span class="sz sz-{item.size}">{item.size ?? ''}</span>
            {#if item.norice}
              <span class="tag tag-accent">no rice</span>
            {/if}
            <button
              onclick={() => { item.here = item.here ? 0 : 1; onCheckboxChange(item); }}
              disabled={!meta.save}
              class="final-pill {item.here ? 'on-dark' : ''}"
              style="margin-left: auto;"
            ><span class="dot"></span>Tiffin</button>
            <button
              onclick={() => { item.filled = item.filled ? 0 : 1; onCheckboxChange(item); }}
              disabled={!meta.save}
              class="final-pill {item.filled ? 'on-accent' : ''}"
            ><span class="dot"></span>Filled</button>
          {/if}
        </div>
      </div>
    {/each}
  </div>

  {#if sortedRows.length === 0}
    <div class="empty-state">Nothing matches these filters.</div>
  {/if}
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

{#if confirmingReset}
  <Dialog
    message="Clear all here and filled checkboxes?"
    confirmLabel="Reset"
    cancelLabel="Cancel"
    danger={true}
    onConfirm={handleReset}
    onCancel={() => (confirmingReset = false)}
  />
{/if}

<PageNav
  onPrev={() => paginate(-1)}
  onNext={() => paginate(1)}
  onSave={meta.save ? handleSave : null}
  {dirty}
  saving={ps.saving}
  class="no-print"
>
  {#if meta.save}
    <button onclick={() => (confirmingReset = true)} class="btn-secondary">
      Reset
    </button>
  {/if}
</PageNav>

<style>
  .progress-ring {
    width: 68px;
    height: 68px;
  }
  @media (max-width: 480px) {
    .progress-ring {
      width: 44px;
      height: 44px;
    }
    .progress-ring div {
      font-size: 11px;
    }
  }
</style>
