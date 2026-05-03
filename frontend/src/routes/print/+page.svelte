<script>
  import { page } from '$app/state';
  import { get, post, navigate } from '$lib/api.js';
  import { getDisplayDate } from '$lib/dates.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import Dialog from '$lib/Dialog.svelte';
  import PageNav from '$lib/PageNav.svelte';
  import { getIntParam } from '$lib/utils.js';

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
    rice: '',
  });

  const offset = $derived(getIntParam(page.url.searchParams, 'offset'));

  $effect(() => {
    loadData(offset);
  });

  let warnedDate = '';

  function applyResponse(res, defaultMsg = '') {
    rows = res.data || [];
    meta = res.other || {};
    date = res.date || '';
    ps.msg = res.msg || defaultMsg;
  }

  async function loadData(o) {
    dateWarning = '';
    warnedDate = '';
    await ps.load(async () => {
      const res = await get('print.php', { offset: o });
      applyResponse(res);
      dirty = false;
    });
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

  const filteredRows = $derived.by(() => {
    const f = filters;
    return rows.filter((item) => {
      if (f.area && item.area !== f.area) return false;
      if (f.size && item.size !== f.size) return false;
      if (f.here === 'Y' && !item.here) return false;
      if (f.here === 'N' && item.here) return false;
      if (f.filled === 'Y' && !item.filled) return false;
      if (f.filled === 'N' && item.filled) return false;
      const riceVal = item['bread+rice'];
      if (f.rice === 'Y' && riceVal) return false;
      if (f.rice === 'N' && !riceVal) return false;
      return true;
    });
  });

  // Size counts that update with all filters except size itself
  const sizeCountsFiltered = $derived.by(() => {
    const f = filters;
    const counts = {};
    for (const r of rows) {
      if (f.area && r.area !== f.area) continue;
      if (f.here === 'Y' && !r.here) continue;
      if (f.here === 'N' && r.here) continue;
      if (f.filled === 'Y' && !r.filled) continue;
      if (f.filled === 'N' && r.filled) continue;
      counts[r.size] = (counts[r.size] ?? 0) + 1;
    }
    return counts;
  });

  const areas = $derived(
    [...new Set(rows.map((r) => r.area).filter(Boolean))].sort()
  );

  const SIZE_ORDER = ['XL', 'LG', 'MD', 'SM', 'XS'];

  const sizes = $derived(
    [...new Set(rows.map((r) => r.size).filter(Boolean))].sort(
      (a, b) => SIZE_ORDER.indexOf(a) - SIZE_ORDER.indexOf(b)
    )
  );

  const sortedRows = $derived.by(() => {
    return [...filteredRows].sort((a, b) => {
      const av = a[sortCol] ?? '';
      const bv = b[sortCol] ?? '';
      if (sortCol === 'size') {
        return SIZE_ORDER.indexOf(av) - SIZE_ORDER.indexOf(bv);
      }
      return av < bv ? -1 : av > bv ? 1 : 0;
    });
  });

  const filledCount = $derived(rows.filter((r) => r.filled).length);
  const filledPct = $derived(rows.length ? filledCount / rows.length : 0);
  const ringCircum = 2 * Math.PI * 26;

  const shownCount = $derived(filteredRows.length);

  const niyazSummary = $derived(
    `Adults: ${meta.adults ?? 0}, Kids: ${meta.kids ?? 0}`
  );

  const menuTitle = $derived(
    Object.keys(meta.serving ?? {}).join(' + ') || getDisplayDate(date)
  );

  const currentStatus = $derived(
    filters.filled === '' ? 'all' : filters.filled === 'N' ? 'unfilled' : 'filled'
  );
  const currentTiffin = $derived(
    filters.here === '' ? 'All' : filters.here === 'Y' ? 'here' : 'tupperware'
  );

  function setStatus(val) {
    filters.filled = val === 'all' ? '' : val === 'filled' ? 'Y' : 'N';
  }
  function setTiffin(val) {
    filters.here = val === 'All' ? '' : val === 'here' ? 'Y' : 'N';
  }
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
      const res = await post('print.php', { offset }, body);
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

  function generateLabels() {
    const params = new URLSearchParams({
      date,
      sort: sortCol,
      filterArea: filters.area,
      filterSize: filters.size,
      filterHere: filters.here,
      filterFilled: filters.filled,
      filterRice: filters.rice,
    });
    window.open(__BASE_PATH__ + '/generate_labels.php?' + params.toString());
  }

  const servingEntries = $derived(Object.entries(meta.serving ?? {}));
</script>

<svelte:head>
  <title>{__APP_NAME__} - Print</title>
</svelte:head>

<!-- Page header -->
<div
  class="-mx-3 -mt-3 px-7 pt-4 pb-3.5 no-print"
  style="background: var(--surface); border-bottom: 1px solid var(--border);"
>
  <div class="flex items-center gap-4 flex-wrap">
    <!-- Progress ring -->
    {#if !meta.niyaz && rows.length > 0}
      <div style="position: relative; width: 68px; height: 68px; flex-shrink: 0;">
        <svg width="68" height="68">
          <circle cx="34" cy="34" r="26" stroke="var(--border-strong)" stroke-width="4" fill="none"/>
          <circle cx="34" cy="34" r="26" stroke="var(--accent)" stroke-width="4" fill="none"
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

    <!-- Title block -->
    <div style="flex: 1; min-width: 0;">
      <div class="eyebrow" style="margin-bottom: 2px;">Kitchen · {getDisplayDate(date)}</div>
      <h1 style="margin: 0 0 3px; font-size: 20px; font-weight: 600; letter-spacing: -0.015em; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
        {menuTitle}
      </h1>
      {#if !meta.niyaz && rows.length > 0}
        <div style="font-size: 12px; color: var(--muted);">
          <b style="color: var(--text);">{filledCount}</b> filled ·
          {rows.length - filledCount} to go ·
          {rows.length} RSVPs total
        </div>
      {:else if meta.niyaz}
        <div style="font-size: 12px; color: var(--muted);">{niyazSummary}</div>
      {/if}
    </div>

    <!-- Size summary buttons (counts adjust with active filters, click to toggle size filter) -->
    {#if !meta.niyaz && rows.length > 0}
      <div class="flex gap-1.5 flex-wrap">
        {#each SIZE_ORDER.filter((s) => sizeCountsFiltered[s]) as s}
          <button
            onclick={() => toggleSizeFilter(s)}
            style="text-align: center; padding: 6px 11px; border-radius: 8px; border: none; cursor: pointer; transition: background 0.1s;
              background: {filters.size === s ? 'var(--accent)' : 'var(--subtle)'};
              color: {filters.size === s ? 'white' : 'var(--text)'};"
          >
            <div style="font-size: 10px; font-weight: 700; letter-spacing: .08em; opacity: .75;">{s}</div>
            <div style="font-size: 16px; font-weight: 600; line-height: 1; font-variant-numeric: tabular-nums;">{sizeCountsFiltered[s]}</div>
          </button>
        {/each}
      </div>
    {/if}

    <button onclick={generateLabels} class="btn-dark">Print labels →</button>
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
{#if servingEntries.length > 0}
  <table class="w-full border-separate mt-3 mb-3 border no-print" style="border-color: var(--border);">
    <tbody>
      {#each servingEntries as [menu, portions]}
        <tr>
          <td class="font-medium border-r" style="border-color: var(--border);">{menu}</td>
          {#each portions as q}
            <td class="border-r" style="border-color: var(--border);">{q}</td>
          {/each}
        </tr>
      {/each}
    </tbody>
  </table>
{/if}

{#if ps.loading}
  <Loading />
{:else}
  <!-- Filter bar -->
  {#if !meta.niyaz}
    <div class="flex flex-wrap items-center gap-2 py-2.5 px-1 mb-3 no-print" style="border-bottom: 1px solid var(--border);">
      <!-- Status segmented control -->
      <div class="segmented">
        {#each [['all','All'],['unfilled','To fill'],['filled','Filled']] as [key, label]}
          <button class="{currentStatus === key ? 'active' : ''}" onclick={() => setStatus(key)}>{label}</button>
        {/each}
      </div>

      <!-- Tiffin segmented control -->
      <div class="segmented">
        {#each [['All','All'],['here','Tiffin'],['tupperware','Togo']] as [key, label]}
          <button class="{currentTiffin === key ? 'active' : ''}" onclick={() => setTiffin(key)}>{label}</button>
        {/each}
      </div>

      <!-- Sort -->
      <label class="label-row text-xs" style="color: var(--muted);">
        Sort:
        <select bind:value={sortCol} class="input-sm text-xs">
          <option value=""></option>
          <option value="thaali">Thaali</option>
          <option value="area">Area</option>
          <option value="size">Size</option>
          <option value="name">Name</option>
          <option value="here">Here</option>
          <option value="filled">Filled</option>
          <option value="bread+rice">Rice</option>
        </select>
      </label>

      <!-- Area filter -->
      {#if areas.length > 1}
        <label class="label-row text-xs" style="color: var(--muted);">
          Area:
          <select bind:value={filters.area} class="input-sm text-xs">
            <option value="">All</option>
            {#each areas as area}<option value={area}>{area}</option>{/each}
          </select>
        </label>
      {/if}

      <div style="flex: 1;"></div>

      <span style="font-size: 11px; color: var(--muted); white-space: nowrap;">{shownCount} of {rows.length}</span>

      {#if meta.save && shownCount > 0 && currentStatus !== 'filled'}
        <button onclick={markAllFilled} class="btn-primary" style="font-size: 11px; font-weight: 600; padding: 6px 12px;">
          Fill all shown ✓
        </button>
      {/if}
    </div>
  {/if}

  <!-- Rows -->
  {#if meta.niyaz}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
      {#each sortedRows as item}
        <div class="card p-2">
          <div class="flex items-center gap-2">
            <div style="width: 36px; text-align: center; font-size: 18px; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--text); flex-shrink: 0;">{item.thaali}</div>
            <div style="flex: 1; min-width: 0; font-size: 12px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text);">{item.name ?? ''}</div>
          </div>
        </div>
      {/each}
    </div>
  {:else}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-1.5">
      {#each sortedRows as item}
        <div class="filling-row {item.filled ? 'filled' : ''}">
          <div style="width: 28px; text-align: center; flex-shrink: 0;">
            <div style="font-size: 14px; font-weight: 600; line-height: 1; font-variant-numeric: tabular-nums;">{item.thaali}</div>
          </div>

          <span class="sz sz-{item.size}">{item.size ?? ''}</span>

          <div style="flex: 1; min-width: 0; font-size: 12px; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            {item.name ?? ''}
          </div>

          <button
            onclick={() => { item.here = item.here ? 0 : 1; onCheckboxChange(item); }}
            class="final-pill {item.here ? 'on-dark' : ''}"
          ><span class="dot"></span>Tiffin</button>

          <button
            onclick={() => { item.filled = item.filled ? 0 : 1; onCheckboxChange(item); }}
            class="final-pill {item.filled ? 'on-accent' : ''}"
          ><span class="dot"></span>Filled</button>
        </div>
      {/each}
    </div>

    {#if sortedRows.length === 0}
      <div style="text-align: center; padding: 40px; color: var(--muted); font-style: italic; font-size: 13px;">
        Nothing matches these filters.
      </div>
    {/if}
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
  onPrev={() => navigate(`/print?offset=${offset - 1}`)}
  onNext={() => navigate(`/print?offset=${offset + 1}`)}
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
