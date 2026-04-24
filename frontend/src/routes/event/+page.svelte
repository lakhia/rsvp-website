<script>
  import { page } from '$app/state';
  import { get, post, navigate } from '$lib/api.js';
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
</script>

<svelte:head>
  <title>{__APP_NAME__} - Events</title>
</svelte:head>

<div class="page-eyebrow mb-1">Events</div>
<h2>Week of {getDisplayDate(startDate)}</h2>

{#if ps.loading}
  <Loading />
{:else}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    {#each events as ev}
      <div class="card p-3">
        <div class="flex items-start justify-between mb-2">
          <div class="page-eyebrow">{getDisplayDate(ev.date)}</div>
          <div class="flex gap-3">
            <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer">
              <input type="checkbox" bind:checked={ev.niyaz} onchange={() => (dirty = true)} class="cursor-pointer" />
              Niyaz
            </label>
            <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer">
              <input type="checkbox" bind:checked={ev.enabled} onchange={() => (dirty = true)} class="cursor-pointer" />
              Enabled
            </label>
          </div>
        </div>
        <input
          type="text"
          bind:value={ev.details}
          oninput={() => (dirty = true)}
          placeholder="Menu details (empty = delete)"
          class="input-inline text-sm"
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
