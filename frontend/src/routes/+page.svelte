<script>
  import { goto, beforeNavigate } from '$app/navigation';
  import { page } from '$app/state';
  import { get, post } from '$lib/api.js';
  import { getDisplayDate } from '$lib/dates.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import Dialog from '$lib/Dialog.svelte';
  import PageNav from '$lib/PageNav.svelte';
  import { getIntParam } from '$lib/utils.js';

  const ps = new PageState();

  let events = $state([]);
  let sizes = $state([]); // eligible sizes from server
  let dirty = $state({}); // { [date]: true } — never unset on toggle-back
  let pendingHref = $state(null); // set when navigation is blocked by dirty state

  const offset = $derived(getIntParam(page.url.searchParams, 'offset'));
  const dateParam = $derived(page.url.searchParams.get('date') || '');
  const hasDirty = $derived(Object.keys(dirty).length > 0);

  $effect(() => {
    loadData(offset, dateParam);
  });

  // No reactive reads → runs once on mount, cleans up on unmount
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

  function navigate(delta) {
    goto(`/?offset=${offset + delta}`);
  }
</script>

<svelte:head>
  <title>{__APP_NAME__} - RSVP</title>
</svelte:head>

<h2>
  RSVP for {localStorage.getItem('greet') ?? ''}
</h2>

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
  <div class="overflow-x-auto">
    <table>
      <thead>
        <tr>
          <th class="w-[15%]">Day</th>
          <th class="w-[43%]">Details</th>
          <th class="text-center w-[12%]">No bread<br />/ Rice</th>
          <th class="text-center w-[15%]">RSVP</th>
          <th class="w-[15%]">Size /<br /> Count</th>
        </tr>
      </thead>
      <tbody>
        {#each events as ev, i}
          <tr>
            <!-- Day -->
            <td class="whitespace-nowrap">
              {getDisplayDate(ev.date)}
            </td>

            <!-- Details -->
            <td class="text-gray-600">{ev.details ?? ''}</td>

            <!-- No rice/bread -->
            <td class="text-center">
              {#if ev.enabled && !ev.niyaz}
                <input
                  type="checkbox"
                  bind:checked={ev.lessRice}
                  disabled={ev.readonly || !ev.rsvp}
                  onchange={() => mark(ev)}
                  class="cursor-pointer"
                />
              {/if}
            </td>

            <!-- RSVP button -->
            <td class="text-center">
              {#if ev.enabled}
                <button
                  onclick={() => onRsvpChange(ev)}
                  disabled={ev.readonly}
                  class="w-16 py-0.5 rounded text-sm font-medium transition-colors
									{ev.rsvp ? 'bg-yes hover:bg-yes-dark' : 'bg-no hover:bg-no-dark'}"
                >
                  {ev.rsvp ? 'Yes' : 'No'}
                </button>
              {/if}
            </td>

            <!-- Size / Count -->
            <td>
              {#if ev.enabled && !ev.niyaz}
                <select
                  bind:value={ev.size}
                  disabled={ev.readonly || !ev.rsvp}
                  onchange={() => mark(ev)}
                  class="input-sm"
                >
                  {#each getSizes(ev.size) as s}
                    <option value={s}>{s}</option>
                  {/each}
                </select>
              {:else if ev.enabled && ev.niyaz}
                <div class="flex flex-col gap-1">
                  <label class="flex items-center gap-1 text-xs">
                    <input
                      type="number"
                      bind:value={ev.adults}
                      disabled={ev.readonly || !ev.rsvp}
                      onchange={() => onCountChange(ev)}
                      class="input-sm w-12"
                    />
                    Adults
                  </label>
                  <label class="flex items-center gap-1 text-xs">
                    <input
                      type="number"
                      bind:value={ev.kids}
                      disabled={ev.readonly || !ev.rsvp}
                      onchange={() => onCountChange(ev)}
                      class="input-sm w-12"
                    />
                    Kids
                  </label>
                </div>
              {/if}
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
  onPrev={() => navigate(-7)}
  onNext={() => navigate(7)}
  onSave={handleSave}
  dirty={hasDirty}
  saving={ps.saving}
/>
