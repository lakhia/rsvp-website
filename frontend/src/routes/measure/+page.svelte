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

  let menus = $state([]);
  let allIngreds = $state([]); // full ingredient list for autocomplete
  let dirty = $state(false);
  let dropdown = $state(null);

  const offset = $derived(getIntParam(page.url.searchParams, 'offset'));
  const pageNum = $derived(offset / 10 + 1);

  $effect(() => {
    if (!requireAdmin()) return;
    loadData(offset);
  });

  async function loadData(o) {
    dropdown = null;
    await ps.load(async () => {
      const [menuRes, ingredRes] = await Promise.all([
        get('measure.php', { offset: o, len: 10 }),
        get('ingred.php'),
      ]);
      menus = menuRes.data || [];
      allIngreds = ingredRes.data || [];
      ps.msg = menuRes.msg || '';
      dirty = false;
    });
  }

  async function handleSave() {
    await ps.save(async () => {
      const res = await post('measure.php', { offset, len: 10 }, menus);
      menus = res.data || [];
      ps.msg = res.msg || 'Saved';
      dirty = false;
    });
  }

  function addMenu() {
    menus = [...menus, { id: null, menu: '', rice: false, ingred: [{ name: '' }] }];
    dirty = true;
  }

  // Autocomplete logic
  function getMatches(query) {
    if (!query) return [];
    const q = query.toLowerCase();
    return allIngreds.filter((i) => i.name.toLowerCase().includes(q));
  }

  function onIngredInput(mi, ii, ingred) {
    dirty = true;
    dropdown = {
      menuIdx: mi,
      ingredIdx: ii,
      matches: getMatches(ingred.name),
      highlighted: 0,
    };
    // Auto-add a new blank row when typing in the last ingredient slot
    if (ii === menus[mi].ingred.length - 1 && ingred.name) {
      menus[mi].ingred = [...menus[mi].ingred, { name: '' }];
    }
  }

  function onIngredKeydown(e, mi, ii, ingred) {
    if (!dropdown || dropdown.menuIdx !== mi || dropdown.ingredIdx !== ii)
      return;
    const { matches } = dropdown;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      dropdown.highlighted = Math.min(
        dropdown.highlighted + 1,
        matches.length - 1
      );
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      dropdown.highlighted = Math.max(dropdown.highlighted - 1, 0);
    } else if ((e.key === 'Enter' || e.key === 'Tab') && matches.length > 0) {
      e.preventDefault();
      selectMatch(ingred, matches[dropdown.highlighted]);
    } else if (e.key === 'Escape') {
      dropdown = null;
    }
  }

  function selectMatch(ingred, match) {
    ingred.name = match.name;
    ingred.id = match.id;
    ingred.unit = match.unit;
    dropdown = null;
    dirty = true;
  }

  function onIngredBlur() {
    // Small delay so click on dropdown item fires first
    setTimeout(() => {
      dropdown = null;
    }, 150);
  }
</script>

<svelte:head>
  <title>{__APP_NAME__} - Measures</title>
</svelte:head>

<div class="page-eyebrow mb-1">Measurements · Page {pageNum}</div>
<div class="flex items-center justify-between mb-0">
  <h2>Menu Measurements</h2>
  <button type="button" onclick={addMenu} class="btn-dark">Add Menu</button>
</div>

{#if ps.loading}
  <Loading />
{:else}
  <div class="space-y-5">
    {#each menus as menu, mi}
      <div>
        <!-- Menu section heading -->
        <div class="flex items-center gap-3 mb-2">
          <input
            type="text"
            bind:value={menu.menu}
            oninput={() => (dirty = true)}
            placeholder="Menu name"
            class="input-inline font-medium flex-1"
          />
          <label class="flex items-center gap-1 text-xs text-muted cursor-pointer select-none shrink-0">
            <input
              type="checkbox"
              bind:checked={menu.rice}
              onchange={() => (dirty = true)}
              class="cursor-pointer"
            />
            rice/bread
          </label>
        </div>

        <!-- Ingredient cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          {#each menu.ingred as ingred, ii}
            <div class="card px-3 py-2">
              <div class="grid items-center gap-x-3 [grid-template-columns:56px_36px_1fr]">
                <input
                  type="number"
                  bind:value={ingred.multiplier}
                  oninput={() => (dirty = true)}
                  placeholder="0.0"
                  step="0.01"
                  class="input-inline text-right text-sm"
                />
                <span class="text-xs text-muted">{ingred.unit ?? ''}</span>
                <div class="relative">
                  <input
                    type="text"
                    bind:value={ingred.name}
                    oninput={() => onIngredInput(mi, ii, ingred)}
                    onkeydown={(e) => onIngredKeydown(e, mi, ii, ingred)}
                    onblur={onIngredBlur}
                    placeholder="ingredient"
                    class="input-inline text-sm"
                  />
                  {#if dropdown?.menuIdx === mi && dropdown?.ingredIdx === ii && dropdown.matches.length > 0}
                    <ul class="absolute z-20 left-0 right-0 top-full mt-0.5 bg-surface border border-line rounded shadow-lg max-h-48 overflow-y-auto text-sm">
                      {#each dropdown.matches as match, k}
                        <li>
                          <button
                            type="button"
                            onmousedown={() => selectMatch(ingred, match)}
                            class="w-full text-left px-2 py-1 hover:bg-subtle transition-colors
                              {k === dropdown.highlighted ? 'bg-subtle font-medium' : ''}"
                          >
                            {match.name}
                            <span class="text-xs text-muted ml-1">{match.unit}</span>
                          </button>
                        </li>
                      {/each}
                    </ul>
                  {/if}
                </div>
              </div>
            </div>
          {/each}
        </div>
      </div>
    {/each}
  </div>


{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
  onPrev={() => navigate(`/measure?offset=${Math.max(0, offset - 10)}`)}
  onNext={() => navigate(`/measure?offset=${offset + 10}`)}
  onSave={handleSave}
  {dirty}
  saving={ps.saving}
  prevDisabled={offset === 0}
/>
