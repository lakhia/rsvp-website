<script>
  import { goto, beforeNavigate } from '$app/navigation';
  import { page } from '$app/state';
  import { get, post, navigate } from '$lib/api.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import Dialog from '$lib/Dialog.svelte';
  import { getIntParam } from '$lib/utils.js';

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

  $effect(() => {
    loadData(offset, dateParam);
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

  async function loadData(o, d) {
    await ps.load(async () => {
      const res = await get('rsvp.php', { offset: o, date: d });
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
      const res = await post('rsvp.php', { offset }, body);
      events = res.data || [];
      sizes = res.other || [];
      ps.msg = res.msg || 'Saved';
      dirty = {};
    });
  }

  function paginate(delta) {
    if (dateParam) {
      const d = new Date(dateParam + 'T00:00:00');
      d.setDate(d.getDate() + delta * 7);
      navigate(`/?date=${d.toISOString().split('T')[0]}`);
    } else {
      navigate(`/?offset=${offset + delta}`);
    }
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

<!-- Header -->
<div class="mb-5">
  {#if weekNum}
    <div class="page-eyebrow mb-1">
      Thaali · Week {weekNum}{thaali ? ` · #${thaali}` : ''}
    </div>
  {/if}
  <h2 class="mb-2">RSVP for {name}</h2>

  {#if sizes.length}
    <div class="flex flex-wrap items-center justify-end gap-2">
      <span class="text-xs text-gray-500">Size</span>
      <div class="flex gap-1">
        {#each sizes as s}
          <button
            onclick={() => (weekSize = s)}
            class="size-pill {weekSize === s ? 'bg-gray-700 text-white border-gray-700' : ''}"
          >{s}</button>
        {/each}
      </div>
      <button onclick={applyToWeek} class="btn-primary text-xs">
        Apply to week →
      </button>
    </div>
  {/if}
</div>

{#if pendingHref}
  <Dialog
    message="You have unsaved changes. Discard them and leave?"
    confirmLabel="Discard"
    cancelLabel="Stay"
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
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    {#each events as ev}
      {@const cd = formatCardDate(ev.date)}
      <div
        class="card p-3
          {cd.isToday ? 'border-2 border-gray-600' : ''}
          {dirty[ev.date] ? 'bg-blue-50' : ''}"
      >
        <!-- Card header: day label + RSVP button -->
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-gray-400">{cd.day}</span>
            <span class="text-sm text-gray-600">{cd.monthDay}</span>
            {#if cd.isToday}
              <span class="text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded font-medium">TODAY</span>
            {/if}
          </div>
          {#if ev.enabled}
            <button
              onclick={() => onRsvpChange(ev)}
              disabled={ev.readonly}
              class="flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium transition-colors
                {ev.rsvp
                  ? 'bg-yes text-white hover:bg-yes-dark'
                  : 'bg-no text-white hover:bg-no-dark'}"
            >
              <span class="w-2 h-2 rounded-full inline-block bg-white"></span>
              {ev.rsvp ? 'Yes' : 'No'}
            </button>
          {/if}
        </div>

        <!-- Menu text -->
        <div class="text-sm font-medium text-gray-800 mb-3 leading-snug">
          {ev.details ?? ''}
        </div>

        <!-- Footer: no bread/rice + size badges, or niyaz counts -->
        {#if ev.enabled && !ev.niyaz}
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-1.5 text-xs text-gray-400 cursor-pointer select-none">
              <input
                type="checkbox"
                bind:checked={ev.lessRice}
                disabled={ev.readonly || !ev.rsvp}
                onchange={() => mark(ev)}
                class="cursor-pointer"
              />
              no bread / rice
            </label>
            <div class="flex gap-1">
              {#each getSizes(ev.size) as s}
                <button
                  onclick={() => { ev.size = s; mark(ev); }}
                  disabled={ev.readonly || !ev.rsvp}
                  class="size-pill {ev.size === s ? 'bg-gray-700 text-white border-gray-700' : ''}"
                >{s}</button>
              {/each}
            </div>
          </div>
        {:else if ev.enabled && ev.niyaz && ev.rsvp}
          <div class="flex gap-4 text-sm">
            <div class="flex items-center gap-1">
              <button
                onclick={() => { ev.adults = Math.max(0, (ev.adults ?? 0) - 1); onCountChange(ev); }}
                disabled={ev.readonly}
                class="w-6 h-6 rounded border border-gray-300 flex items-center justify-center hover:bg-gray-100"
              >−</button>
              <span class="w-6 text-center font-medium">{ev.adults ?? 0}</span>
              <button
                onclick={() => { ev.adults = (ev.adults ?? 0) + 1; onCountChange(ev); }}
                disabled={ev.readonly}
                class="w-6 h-6 rounded border border-gray-300 flex items-center justify-center hover:bg-gray-100"
              >+</button>
              <span class="text-xs text-gray-400 ml-1">adults</span>
            </div>
            <div class="flex items-center gap-1">
              <button
                onclick={() => { ev.kids = Math.max(0, (ev.kids ?? 0) - 1); onCountChange(ev); }}
                disabled={ev.readonly}
                class="w-6 h-6 rounded border border-gray-300 flex items-center justify-center hover:bg-gray-100"
              >−</button>
              <span class="w-6 text-center font-medium">{ev.kids ?? 0}</span>
              <button
                onclick={() => { ev.kids = (ev.kids ?? 0) + 1; onCountChange(ev); }}
                disabled={ev.readonly}
                class="w-6 h-6 rounded border border-gray-300 flex items-center justify-center hover:bg-gray-100"
              >+</button>
              <span class="text-xs text-gray-400 ml-1">kids</span>
            </div>
          </div>
        {/if}
      </div>
    {/each}
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<div class="mt-4 flex justify-between items-center">
  <button onclick={() => paginate(-7)} class="btn-secondary">‹ prev</button>
  {#if hasDirty}
    <button onclick={handleSave} disabled={ps.saving} class="btn-primary min-w-22">
      {ps.saving ? 'Saving…' : 'Save'}
    </button>
  {/if}
  <button onclick={() => paginate(7)} class="btn-secondary">next ›</button>
</div>
