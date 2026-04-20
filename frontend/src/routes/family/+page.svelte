<script>
  import { page } from '$app/state';
  import { get, post, navigate } from '$lib/api.js';
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
  <div class="space-y-3">
    {#each families as f, i}
      <div class="border border-gray-200 rounded p-3 text-sm {i % 2 === 1 ? 'bg-gray-50' : ''}">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2">
          <div>
            <div class="text-xs text-gray-500 mb-0.5">Thaali #{f.thaali} / ITS</div>
            <input
              type="text"
              bind:value={f.its}
              oninput={() => (dirty = true)}
              placeholder="ITS ID"
              class="input-inline"
            />
          </div>
          <div>
            <div class="text-xs text-gray-500 mb-0.5">Area</div>
            <input
              type="text"
              bind:value={f.area}
              oninput={() => (dirty = true)}
              placeholder="Area"
              class="input-inline"
            />
          </div>
          <div>
            <div class="text-xs text-gray-500 mb-0.5">First Name</div>
            <input
              type="text"
              bind:value={f.firstName}
              oninput={() => (dirty = true)}
              placeholder="First name"
              class="input-inline"
            />
          </div>
          <div>
            <div class="text-xs text-gray-500 mb-0.5">Last Name</div>
            <input
              type="text"
              bind:value={f.lastName}
              oninput={() => (dirty = true)}
              placeholder="Last name"
              class="input-inline"
            />
          </div>
          <div class="col-span-2">
            <div class="text-xs text-gray-500 mb-0.5">Email</div>
            <input
              type="email"
              bind:value={f.email}
              oninput={() => (dirty = true)}
              placeholder="Email (empty = delete)"
              class="input-inline"
            />
          </div>
          <div>
            <div class="text-xs text-gray-500 mb-0.5">Phone</div>
            <input
              type="text"
              bind:value={f.phone}
              oninput={() => (dirty = true)}
              placeholder="Phone"
              class="input-inline"
            />
          </div>
          <div class="sm:col-span-2">
            <div class="text-xs text-gray-500 mb-0.5">POC</div>
            <input
              type="text"
              bind:value={f.poc}
              oninput={() => (dirty = true)}
              placeholder="POC"
              class="input-inline"
            />
          </div>
          <div>
            <div class="text-xs text-gray-500 mb-0.5">Size</div>
            <input
              type="text"
              bind:value={f.size}
              oninput={() => (dirty = true)}
              placeholder="MD"
              class="input-inline"
            />
          </div>
          <div>
            <div class="text-xs text-gray-500 mb-0.5">Resp</div>
            <input
              type="text"
              bind:value={f.resp}
              oninput={() => (dirty = true)}
              class="input-inline"
            />
          </div>
        </div>
      </div>
    {/each}
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
  onPrev={() => navigate(`/family?offset=${Math.max(1, offset - 10)}`)}
  onNext={() => navigate(`/family?offset=${offset + 10}`)}
  onSave={handleSave}
  {dirty}
  saving={ps.saving}
  prevDisabled={offset <= 1}
/>
