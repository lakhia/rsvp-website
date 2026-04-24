<script>
  import { get, post } from '$lib/api.js';
  import { requireAdmin } from '$lib/auth.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';

  const ps = new PageState();

  let ingreds = $state([]);
  let dirty = $state(false);

  $effect(() => {
    if (!requireAdmin()) return;
    loadData();
  });

  async function loadData() {
    await ps.load(async () => {
      const res = await get('ingred.php');
      ingreds = res.data || [];
      ps.msg = '';
      dirty = false;
    });
  }

  async function handleSave() {
    await ps.save(async () => {
      const res = await post('ingred.php', {}, ingreds);
      ingreds = res.data || [];
      ps.msg = res.msg || 'Saved';
      dirty = false;
    });
  }

  function addIngred() {
    ingreds = [...ingreds, { id: null, name: '', unit: '' }];
    dirty = true;
  }
</script>

<svelte:head>
  <title>{__APP_NAME__} - Ingredients</title>
</svelte:head>

<h2>Ingredients</h2>

{#if ps.loading}
  <Loading />
{:else}
  <div class="grid gap-3 [grid-template-columns:repeat(auto-fill,minmax(180px,1fr))]">
    {#each ingreds as ingred}
      <div class="card p-2 flex flex-col gap-1">
        <input
          type="text"
          bind:value={ingred.name}
          oninput={() => (dirty = true)}
          placeholder="Name"
          class="input-inline font-medium"
        />
        <input
          type="text"
          bind:value={ingred.unit}
          oninput={() => (dirty = true)}
          placeholder="unit (e.g. cups)"
          class="input-inline text-sm text-gray-500"
        />
      </div>
    {/each}

    <!-- "Add" card -->
    <button
      type="button"
      onclick={addIngred}
      class="border-2 border-dashed border-gray-300 rounded p-2 text-gray-400 hover:border-gray-400 hover:text-gray-600 transition-colors min-h-[64px] flex items-center justify-center text-sm"
    >
      + Add Ingredient
    </button>
  </div>

  <div class="mt-4">
    <button
      type="button"
      onclick={handleSave}
      disabled={!dirty || ps.saving}
      class="btn-primary min-w-22"
    >
      {ps.saving ? 'Saving…' : 'Save'}
    </button>
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />
