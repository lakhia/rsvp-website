<script>
  import { goto } from '$app/navigation';
  import { page } from '$app/state';
  import { get, post } from '$lib/api.js';
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

<h2>Events from {getDisplayDate(startDate)}</h2>

{#if ps.loading}
  <Loading />
{:else}
  <div class="overflow-x-auto">
    <table>
      <thead>
        <tr>
          <th class="w-[15%]">Date</th>
          <th>Details</th>
          <th class="text-center w-[8%]">Niyaz</th>
          <th class="text-center w-[8%]">Enabled</th>
        </tr>
      </thead>
      <tbody>
        {#each events as ev, i}
          <tr>
            <td class="whitespace-nowrap">
              {getDisplayDate(ev.date)}
            </td>
            <td>
              <input
                type="text"
                bind:value={ev.details}
                oninput={() => (dirty = true)}
                placeholder="Event details (empty = delete)"
                class="input-inline"
              />
            </td>
            <td class="text-center">
              <input
                type="checkbox"
                bind:checked={ev.niyaz}
                onchange={() => (dirty = true)}
                class="cursor-pointer"
              />
            </td>
            <td class="text-center">
              <input
                type="checkbox"
                bind:checked={ev.enabled}
                onchange={() => (dirty = true)}
                class="cursor-pointer"
              />
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
  onPrev={() => goto(`/event?offset=${offset - 7}`)}
  onNext={() => goto(`/event?offset=${offset + 7}`)}
  onSave={handleSave}
  {dirty}
  saving={ps.saving}
/>
