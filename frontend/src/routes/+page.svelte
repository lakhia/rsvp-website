<script>
  import { goto, beforeNavigate } from '$app/navigation';
  import { page } from '$app/state';
  import { get, post, navigate } from '$lib/api.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import Dialog from '$lib/Dialog.svelte';
  import PageNav from '$lib/PageNav.svelte';
  import { getIntParam, paginateUrl, dateToOffset } from '$lib/utils.js';

  const ps = new PageState();

  let events = $state([]);
  let sizes = $state([]);
  let dirty = $state({});
  let pendingHref = $state(null);
  let weekSize = $state('');

  const offset = $derived(getIntParam(page.url.searchParams, 'offset'));
  const dateParam = $derived(page.url.searchParams.get('date') || '');
  const hasDirty = $derived(Object.keys(dirty).length > 0);
  const todayStr = new Date().toLocaleDateString('en-CA');
  const effectiveOffset = $derived(dateParam ? dateToOffset(dateParam) : offset);

  $effect(() => {
    loadData(effectiveOffset);
  });

  $effect(() => {
    window.addEventListener('beforeunload', warnIfDirty);
    return () => window.removeEventListener('beforeunload', warnIfDirty);
  });

  beforeNavigate(({ cancel, to }) => {
    if (hasDirty) {
      cancel();
      pendingHref = to?.url?.href ?? '/';
    }
  });

  function warnIfDirty(e) {
    if (hasDirty) e.preventDefault();
  }

  async function loadData(o) {
    await ps.load(async () => {
      const res = await get('rsvp.php', { offset: o });
      events = res.data || [];
      sizes = res.other || [];
      ps.msg = res.msg || '';
      dirty = {};
      if (sizes.length && (!weekSize || !sizes.includes(weekSize))) {
        weekSize = sizes[0];
      }
    });
  }

  function mark(event) {
    dirty[event.date] = true;
    ps.msg = '';
  }

  function getSizes(currentSize) {
    return sizes.includes(currentSize) ? sizes : [...sizes, currentSize];
  }

  function onRsvpChange(ev) {
    ev.rsvp = ev.rsvp ? 0 : 1;
    if (ev.niyaz) {
      if (ev.rsvp) {
        ev.adults = parseInt(localStorage.getItem('adults')) || 0;
        ev.kids = parseInt(localStorage.getItem('kids')) || 0;
      } else {
        ev.adults = ev.kids = null;
      }
    }
    mark(ev);
  }

  function onCountChange(ev) {
    localStorage.setItem('adults', ev.adults ?? 0);
    localStorage.setItem('kids', ev.kids ?? 0);
    mark(ev);
  }

  function applyToWeek() {
    if (!weekSize) return;
    for (const ev of events) {
      if (ev.enabled && !ev.readonly && !ev.niyaz) {
        ev.rsvp = 1;
        ev.size = weekSize;
        dirty[ev.date] = true;
      }
    }
    ps.msg = '';
  }

  async function handleSave() {
    const body = {};
    for (const ev of events) {
      if (dirty[ev.date]) {
        const row = {
          rsvp: ev.rsvp ? 1 : 0,
          size: ev.size,
          lessRice: ev.lessRice ? 1 : 0,
        };
        if (ev.niyaz) {
          row.adults = ev.adults ?? 0;
          row.kids = ev.kids ?? 0;
        }
        body[ev.date] = row;
      }
    }
    await ps.save(async () => {
      const res = await post('rsvp.php', { offset: effectiveOffset }, body);
      events = res.data || [];
      sizes = res.other || [];
      ps.msg = res.msg || 'Saved';
      dirty = {};
    });
  }

  function paginate(delta) {
    navigate(paginateUrl('/', dateParam, offset, delta));
  }

  function formatCardDate(dateStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return {
      day: days[dt.getDay()].toUpperCase(),
      monthDay: `${months[dt.getMonth()]} ${dt.getDate()}`,
      isToday: dateStr === todayStr,
    };
  }

  function getWeekNum(dateStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    return Math.ceil(((dt - new Date(y, 0, 1)) / 86400000 + 1) / 7);
  }

  const name = localStorage.getItem('greet') ?? '';
  const thaali = localStorage.getItem('thaali') ?? '';
  const weekNum = $derived(events.length ? getWeekNum(events[0].date) : '');
</script>

<svelte:head>
  <title>{__APP_NAME__} - RSVP</title>
</svelte:head>

<!-- Page header -->
<div class="page-header mb-5">
  <div class="flex items-end justify-between gap-6 flex-wrap">
    <div>
      {#if weekNum}
        <div class="eyebrow mb-1">RSVP · Week {weekNum}{thaali ? ` · #${thaali}` : ''}</div>
      {/if}
      <h1>RSVP for {name}</h1>
    </div>

    {#if sizes.length}
      <div class="flex flex-col gap-1.5 items-end">
        <div class="flex items-center gap-2">
          <div class="flex gap-1">
            {#each sizes as s}
              <button
                onclick={() => (weekSize = s)}
                class="round-size-btn {weekSize === s ? 'active' : ''}"
              >{s}</button>
            {/each}
          </div>
          <button onclick={applyToWeek} class="btn-primary" style="white-space: nowrap;">
            Apply to week →
          </button>
        </div>
      </div>
    {/if}
  </div>
</div>

{#if pendingHref}
  <Dialog
    message="You have unsaved changes. Discard them and leave?"
    confirmLabel="Discard"
    cancelLabel="Stay"
    danger={true}
    onConfirm={() => {
      dirty = {};
      goto(pendingHref);
      pendingHref = null;
    }}
    onCancel={() => (pendingHref = null)}
  />
{/if}

{#if ps.loading}
  <Loading />
{:else}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
    {#each events as ev}
      {@const cd = formatCardDate(ev.date)}
      <div class="card p-3.5 {cd.isToday ? 'card-today' : ''}">

        <!-- Card header row -->
        <div class="flex items-start justify-between mb-2">
          <div class="flex items-baseline gap-2 flex-wrap">
            <span class="eyebrow">{cd.day}</span>
            <span style="font-size: 13px; font-weight: 500;">{cd.monthDay}</span>
            {#if cd.isToday}
              <span class="tag tag-accent">TODAY</span>
            {/if}
          </div>
          {#if ev.enabled}
            <button
              onclick={() => onRsvpChange(ev)}
              disabled={ev.readonly}
              class="yes-no-pill {ev.rsvp ? 'yes' : 'no'} shrink-0 ml-2"
            >
              <span class="dot"></span>
              {ev.rsvp ? 'Yes' : 'No'}
            </button>
          {/if}
        </div>

        <!-- Dish list -->
        <div class="card-text">{ev.details ?? ''}</div>

        <!-- Card footer: size / count (only when RSVP'd yes) -->
        {#if ev.enabled && ev.rsvp}
          <div class="card-footer">
            {#if ev.niyaz}
              <!-- Count stepper for niyaz events -->
              <div class="flex gap-4">
                <div class="flex items-center gap-1.5">
                  <button
                    onclick={() => { ev.adults = Math.max(0, (ev.adults ?? 0) - 1); onCountChange(ev); }}
                    disabled={ev.readonly}
                    class="stepper-btn"
                  >−</button>
                  <span class="stepper-count">{ev.adults ?? 0}</span>
                  <button
                    onclick={() => { ev.adults = (ev.adults ?? 0) + 1; onCountChange(ev); }}
                    disabled={ev.readonly}
                    class="stepper-btn"
                  >+</button>
                  <span class="text-xs text-muted">adults</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <button
                    onclick={() => { ev.kids = Math.max(0, (ev.kids ?? 0) - 1); onCountChange(ev); }}
                    disabled={ev.readonly}
                    class="stepper-btn"
                  >−</button>
                  <span class="stepper-count">{ev.kids ?? 0}</span>
                  <button
                    onclick={() => { ev.kids = (ev.kids ?? 0) + 1; onCountChange(ev); }}
                    disabled={ev.readonly}
                    class="stepper-btn"
                  >+</button>
                  <span class="text-xs text-muted">kids</span>
                </div>
              </div>
            {:else}
              <!-- Less rice + size selector -->
              <label class="flex items-center gap-1.5 text-xs text-muted cursor-pointer select-none">
                <input
                  type="checkbox"
                  bind:checked={ev.lessRice}
                  disabled={ev.readonly || !ev.rsvp}
                  onchange={() => mark(ev)}
                  class="cursor-pointer"
                />
                no rice / bread
              </label>
              <div class="flex gap-1">
                {#each getSizes(ev.size) as s}
                  <button
                    onclick={() => { ev.size = s; mark(ev); }}
                    disabled={ev.readonly}
                    class="round-size-btn {ev.size === s ? 'active' : ''}"
                  >{s}</button>
                {/each}
              </div>
            {/if}
          </div>
        {/if}
      </div>
    {/each}
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
  onPrev={() => paginate(-7)}
  onNext={() => paginate(7)}
  onSave={hasDirty ? handleSave : null}
  dirty={hasDirty}
  saving={ps.saving}
/>

<style>
  .stepper-count {
    min-width: 16px;
    text-align: center;
    font-weight: 600;
    font-size: 13px;
    font-variant-numeric: tabular-nums;
  }
</style>
