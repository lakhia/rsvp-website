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

  // Filtering
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

  const areas = $derived(
    [...new Set(rows.map((r) => r.area).filter(Boolean))].sort()
  );

  const sizes = $derived(
    [...new Set(rows.map((r) => r.size).filter(Boolean))].sort(
      (a, b) => SIZE_ORDER.indexOf(a) - SIZE_ORDER.indexOf(b)
    )
  );

  const SIZE_ORDER = ['XL', 'LG', 'MD', 'SM', 'XS'];

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

  const sizeCounts = $derived.by(() => {
    const counts = {};
    for (const r of rows) if (!r.filled) counts[r.size] = (counts[r.size] ?? 0) + 1;
    return counts;
  });

  const shownCount = $derived(filteredRows.length);

  const niyazSummary = $derived(
    `Adults: ${meta.adults ?? 0}, Kids: ${meta.kids ?? 0}`
  );

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
    });
    window.open(__BASE_PATH__ + '/generate_labels.php?' + params.toString());
  }

  const servingEntries = $derived(Object.entries(meta.serving ?? {}));
</script>

<svelte:head>
  <title>{__APP_NAME__} - Print</title>
</svelte:head>

<!-- Header -->
<div class="flex flex-wrap items-start justify-between gap-3 mb-3 no-print">
  <div class="flex-1 min-w-0">
    <h2 class="mb-1">Filling for {getDisplayDate(date)}</h2>
  </div>

  <div class="flex flex-col items-end gap-2 shrink-0">
    <!-- Size counts -->
    {#if !meta.niyaz && rows.length > 0}
      <div class="flex gap-2 items-center text-xs">
        {#each SIZE_ORDER.filter((s) => sizeCounts[s]) as s}
          <span class="badge">{s}</span>
          <span class="font-medium text-gray-600">{sizeCounts[s]}</span>
        {/each}
      </div>
    {/if}
    <!-- Sort + Generate labels -->
    <div class="flex items-center gap-2">
      <label class="label-row text-xs">
        Sort:
        <select bind:value={sortCol} class="input-sm text-xs">
          <option value=""></option>
          <option value="thaali">Thaali</option>
          <option value="area">Area</option>
          <option value="size">Size</option>
          <option value="name">Name</option>
          {#if !meta.niyaz}
            <option value="here">Here</option>
            <option value="filled">Filled</option>
            <option value="bread+rice">Rice / Bread</option>
          {/if}
        </select>
      </label>
      <button onclick={generateLabels} class="btn-primary text-xs">
        Generate labels
      </button>
    </div>
  </div>
</div>

{#if !meta.niyaz && rows.length > 0}
  <div class="mt-2 w-full">
    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
      <span class="font-medium">{filledCount} filled · {((filledCount / rows.length) * 100).toFixed(0)}%</span>
      <span>{rows.length - filledCount} remaining · {rows.length} total RSVPs</span>
    </div>
    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
      <div
        class="h-full bg-brand rounded-full transition-all"
        style="width: {((filledCount / rows.length) * 100).toFixed(1)}%"
      ></div>
    </div>
  </div><br>
{:else if meta.niyaz}
  <div class="text-sm text-gray-500 mt-1">{niyazSummary}</div>
{/if}

{#if dateWarning}
  <Dialog
    message={dateWarning}
    cancelLabel="OK"
    onCancel={() => (dateWarning = '')}
  />
{/if}

<!-- Serving guidance -->
{#if servingEntries.length > 0}
  <table class="w-full border-separate mb-3 border border-gray-200 no-print">
    <tbody>
      {#each servingEntries as [menu, portions]}
        <tr>
          <td class="font-medium border-r border-gray-200">{menu}</td>
          {#each portions as q}
            <td class="border-r border-gray-200">{q}</td>
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
  <div class="flex flex-wrap items-center gap-2 mb-3 no-print">
    {#if !meta.niyaz}
      <!-- Tiffin / Togo segmented control -->
      <div class="segmented text-xs">
        {#each [['', 'All'], ['Y', 'Tiffin'], ['N', 'Togo']] as [val, label]}
          <button
            onclick={() => (filters.here = val)}
            class="px-3 py-1 transition-colors
              {filters.here === val
                ? 'bg-gray-700 text-white'
                : 'text-gray-600 hover:bg-gray-100'}"
          >{label}</button>
        {/each}
      </div>
      <!-- Unfilled only toggle -->
      <button
        onclick={() => (filters.filled = filters.filled === 'N' ? '' : 'N')}
        class="px-3 py-1 text-xs rounded border transition-colors
          {filters.filled === 'N'
            ? 'bg-gray-700 text-white border-gray-700'
            : 'border-gray-300 text-gray-600 hover:border-gray-500'}"
      >
        Unfilled only
      </button>
    {/if}
    <label class="label-row text-xs">
      Area:
      <select bind:value={filters.area} class="input-sm text-xs">
        <option value="">All</option>
        {#each areas as area}<option value={area}>{area}</option>{/each}
      </select>
    </label>
    <label class="label-row text-xs">
      Size:
      <select bind:value={filters.size} class="input-sm text-xs">
        <option value="">All</option>
        {#each sizes as s}<option value={s}>{s}</option>{/each}
      </select>
    </label>
    <span class="text-xs text-gray-400">{shownCount} shown</span>
    {#if !meta.niyaz && meta.save && shownCount > 0}
      <button onclick={markAllFilled} class="btn-secondary text-xs ml-auto">
        Mark all filled
      </button>
    {/if}
  </div>

  <!-- Card grid -->
  <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
    {#each sortedRows as item}
      <div
        class="card p-2 {item.filled ? 'bg-teal-50 border-teal-300' : ''}"
      >
        <div class="flex items-start gap-2">
          <!-- Thaali number + area -->
          <div class="shrink-0 w-9 text-center">
            <div class="text-lg font-bold leading-tight text-gray-800">
              {item.thaali}
            </div>
            <div class="text-xs text-gray-400">
              {item.area ?? ''}
            </div>
          </div>

          <!-- Size badge + name -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-1 mb-0.5">
              <span class="badge shrink-0">{item.size ?? ''}</span>
              <span class="text-xs font-medium truncate text-gray-700">
                {item.name ?? ''}
              </span>
            </div>
            {#if item['bread+rice'] && !meta.niyaz}
              <div class="text-xs text-gray-400">
                {item['bread+rice']}
              </div>
            {/if}
          </div>

          <!-- ✓ / F buttons -->
          {#if !meta.niyaz}
            <div class="flex gap-1 shrink-0">
              <button
                onclick={() => { item.here = item.here ? 0 : 1; onCheckboxChange(item); }}
                class="action-btn {item.here ? 'bg-brand border-brand text-white' : ''}"
              >T</button>
              <button
                onclick={() => { item.filled = item.filled ? 0 : 1; onCheckboxChange(item); }}
                class="action-btn {item.filled ? 'bg-brand border-brand text-white' : ''}"
              >F</button>
            </div>
          {/if}
        </div>
      </div>
    {/each}
  </div>
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
