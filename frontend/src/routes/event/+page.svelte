<script>
  import { page } from '$app/state';
  import { get, post, postText, navigate } from '$lib/api.js';
  import { getDisplayDate } from '$lib/dates.js';
  import { requireAdmin } from '$lib/auth.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import PageNav from '$lib/PageNav.svelte';
  import { getIntParam } from '$lib/utils.js';

  const ps = new PageState();

  let events = $state([]);
  let startDate = $state('');
  let dirty = $state(false);

  const offset = $derived(getIntParam(page.url.searchParams, 'offset'));

  $effect(() => {
    if (!requireAdmin()) return;
    loadData(offset);
  });

  async function loadData(o) {
    await ps.load(async () => {
      const res = await get('event.php', { offset: o });
      events = res.data || [];
      startDate = res.date || '';
      ps.msg = res.msg || '';
      dirty = false;
    });
  }

  async function handleSave() {
    await ps.save(async () => {
      const res = await post('event.php', { offset }, events);
      events = res.data || [];
      startDate = res.date || '';
      ps.msg = res.msg || 'Saved';
      dirty = false;
    });
  }

  const todayStr = new Date().toLocaleDateString('en-CA');
  const enabledCount = $derived(events.filter((e) => e.enabled).length);
  const niyazCount = $derived(events.filter((e) => e.niyaz).length);
  const uniqueDateCount = $derived(new Set(events.map((e) => e.date)).size);

  // Returns true if ev is the last event for its date (show "+" button here)
  const lastForDate = $derived.by(() => {
    const last = new Map();
    for (const ev of events) {
      const cur = last.get(ev.date);
      if (cur === undefined || (ev.event_index ?? 0) > (cur.event_index ?? 0)) {
        last.set(ev.date, ev);
      }
    }
    return new Set([...last.values()].map((ev) => `${ev.date}_${ev.event_index ?? 0}`));
  });

  function addEvent(afterEv) {
    const date = afterEv.date;
    const maxIndex = events
      .filter((ev) => ev.date === date)
      .reduce((max, ev) => Math.max(max, ev.event_index ?? 0), -1);
    const idx = events.indexOf(afterEv);
    events.splice(idx + 1, 0, {
      date,
      event_index: maxIndex + 1,
      details: '',
      enabled: true,
      niyaz: false,
    });
    dirty = true;
  }

  let csvFileInput = $state(null);
  let csvFileName = $state('');

  async function handleImport() {
    const file = csvFileInput?.files?.[0];
    if (!file) return;
    await ps.save(async () => {
      const text = await file.text();
      const res = await postText('dump.php', { table: 'events' }, text);
      ps.msg = res.msg || 'Imported';
      csvFileName = '';
      csvFileInput.value = '';
      await loadData(offset);
    });
  }
</script>

<svelte:head>
  <title>{__APP_NAME__} - Events</title>
</svelte:head>

<!-- Page header -->
<div class="page-header">
  <div class="flex items-end justify-between gap-6">
    <div>
      <div class="eyebrow mb-1">Admin · Events</div>
      <h1>Week of {getDisplayDate(startDate)}</h1>
      <div class="page-subtitle">
        Set the menu for the week.
        <b class="text-content">{enabledCount}</b> of {uniqueDateCount} days enabled ·
        <b class="text-content">{niyazCount}</b> niyaz.
      </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
      <input
        bind:this={csvFileInput}
        type="file"
        accept=".csv"
        class="hidden"
        onchange={() => { csvFileName = csvFileInput?.files?.[0]?.name ?? ''; }}
      />
      <button class="btn-dark" onclick={() => csvFileInput.click()}>
        {csvFileName || 'Upload CSV'}
      </button>
      {#if csvFileName}
        <button class="btn-dark" onclick={handleImport} disabled={ps.saving}>
          Upload
        </button>
      {/if}
    </div>
  </div>
</div>

{#if ps.loading}
  <Loading />
{:else}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 py-5">
    {#each events as ev}
      {@const [dayStr, dateStr] = getDisplayDate(ev.date).split(', ')}
      {@const isLast = lastForDate.has(`${ev.date}_${ev.event_index ?? 0}`)}
      {@const isSecondary = (ev.event_index ?? 0) > 0}
      <div
        class="card py-3.5 px-4 {ev.enabled ? '' : 'card-disabled'} {ev.date === todayStr ? 'card-today' : ''}"
      >
        <!-- Card header -->
        <div class="flex justify-between items-start gap-3 mb-3">
          <div class="flex items-baseline gap-2">
            {#if !isSecondary}
              <span class="eyebrow">{dayStr}</span>
              <span style="font-size: 13px; font-weight: 500; color: var(--text);">{dateStr}</span>
            {:else}
              <span class="eyebrow" style="opacity: 0.4;">{dayStr}</span>
              <span style="font-size: 11px; color: var(--muted);">event {ev.event_index + 1}</span>
            {/if}
          </div>
          <div class="flex gap-1.5 shrink-0">
            <button
              onclick={() => { ev.niyaz = !ev.niyaz; dirty = true; }}
              disabled={!ev.enabled}
              class="final-pill {ev.niyaz ? 'on-accent' : ''}"
            ><span class="dot"></span>Niyaz</button>
            <button
              onclick={() => { ev.enabled = !ev.enabled; dirty = true; }}
              class="final-pill {ev.enabled ? 'on-dark' : ''}"
            ><span class="dot"></span>Enabled</button>
            {#if isLast && (ev.enabled || ev.details)}
              <button
                onclick={() => addEvent(ev)}
                class="final-pill"
                title="Add another event on this day"
              >+</button>
            {/if}
          </div>
        </div>

        <!-- Dish input -->
        <input
          type="text"
          bind:value={ev.details}
          oninput={() => (dirty = true)}
          disabled={!ev.enabled}
          placeholder={ev.enabled ? 'Menu (comma separated)…' : 'Day is off — enable to add dishes'}
          class="input-inline text-sm"
          style="border-bottom-color: {ev.enabled ? 'var(--border)' : 'transparent'};"
        />
      </div>
    {/each}
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
  onPrev={() => navigate(`/event?offset=${offset - 7}`)}
  onNext={() => navigate(`/event?offset=${offset + 7}`)}
  onSave={handleSave}
  {dirty}
  saving={ps.saving}
/>
