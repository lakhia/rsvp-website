<script>
  import { goto } from '$app/navigation';
  import { page } from '$app/state';
  import { get, post } from '$lib/api.js';
  import { requireAdmin } from '$lib/auth.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import PageNav from '$lib/PageNav.svelte';
  import { getIntParam } from '$lib/utils.js';
  const ps = new PageState();

  let families = $state([]);
  let dirty = $state(false);

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
</script>

<svelte:head>
  <title>{__APP_NAME__} - Families</title>
</svelte:head>

<h2>Families</h2>

{#if ps.loading}
  <Loading />
{:else}
  <div class="overflow-x-auto">
    <table>
      <thead>
        <tr>
          <th class="w-[8%]">Num</th>
          <th class="w-[10%]">Area</th>
          <th class="w-[18%]">Name</th>
          <th class="w-[6%]">Size</th>
          <th class="w-[20%]">Email</th>
          <th class="w-[22%]">Phone / POC</th>
          <th class="w-[8%]">Resp</th>
        </tr>
      </thead>
      <tbody>
        {#each families as f, i}
          <tr>
            <!-- Thaali # + ITS -->
            <td class="align-top">
              <div class="text-xs mb-1">{f.thaali}</div>
              <input
                type="text"
                bind:value={f.its}
                oninput={() => (dirty = true)}
                placeholder="ITS ID"
                class="input-inline"
              />
            </td>
            <!-- Area -->
            <td class="align-top">
              <input
                type="text"
                bind:value={f.area}
                oninput={() => (dirty = true)}
                placeholder="Area"
                class="input-inline"
              />
            </td>
            <!-- Name -->
            <td class="align-top">
              <input
                type="text"
                bind:value={f.firstName}
                oninput={() => (dirty = true)}
                placeholder="First name"
                class="input-inline mb-1"
              />
              <input
                type="text"
                bind:value={f.lastName}
                oninput={() => (dirty = true)}
                placeholder="Last name"
                class="input-inline"
              />
            </td>
            <!-- Size -->
            <td class="align-top">
              <input
                type="text"
                bind:value={f.size}
                oninput={() => (dirty = true)}
                placeholder="MD"
                class="input-inline"
              />
            </td>
            <!-- Email -->
            <td class="align-top">
              <input
                type="email"
                bind:value={f.email}
                oninput={() => (dirty = true)}
                placeholder="Email (empty = delete)"
                class="input-inline"
              />
            </td>
            <!-- Phone / POC -->
            <td class="align-top">
              <input
                type="text"
                bind:value={f.phone}
                oninput={() => (dirty = true)}
                placeholder="Phone"
                class="input-inline mb-1"
              />
              <input
                type="text"
                bind:value={f.poc}
                oninput={() => (dirty = true)}
                placeholder="POC"
                class="input-inline"
              />
            </td>
            <!-- Resp -->
            <td class="align-top">
              <input
                type="text"
                bind:value={f.resp}
                oninput={() => (dirty = true)}
                class="input-inline"
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
  onPrev={() => goto(`/family?offset=${Math.max(1, offset - 10)}`)}
  onNext={() => goto(`/family?offset=${offset + 10}`)}
  onSave={handleSave}
  {dirty}
  saving={ps.saving}
  prevDisabled={offset <= 1}
/>
