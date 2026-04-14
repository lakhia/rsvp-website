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

<h2>Menu Measurements, page {pageNum}</h2>

{#if ps.loading}
  <Loading />
{:else}
  <div class="overflow-x-auto">
    <table>
      <thead>
        <tr>
          <th class="w-[25%]">Menu</th>
          <th>Ingredients per thaali</th>
        </tr>
      </thead>
      <tbody>
        {#each menus as menu, mi}
          <tr class="align-top">
            <td>{menu.menu}</td>
            <td>
              <!-- ingredient grid: 3 cols mobile, 6 cols (2×3) on large screens -->
              <div
                class="grid items-center gap-x-3 gap-y-1 [grid-template-columns:60px_50px_1fr] lg:[grid-template-columns:60px_50px_1fr_60px_50px_1fr]"
              >
                {#each menu.ingred as ingred, ii}
                  <!-- Amount -->
                  <input
                    type="number"
                    bind:value={ingred.multiplier}
                    oninput={() => (dirty = true)}
                    placeholder="0.0"
                    step="0.01"
                    class="input-inline text-right"
                  />
                  <!-- Unit -->
                  <span class="text-sm">{ingred.unit ?? ''}</span>
                  <!-- Name + autocomplete dropdown -->
                  <div class="relative">
                    <input
                      type="text"
                      bind:value={ingred.name}
                      oninput={() => onIngredInput(mi, ii, ingred)}
                      onkeydown={(e) => onIngredKeydown(e, mi, ii, ingred)}
                      onblur={onIngredBlur}
                      placeholder="ingredient"
                      class="input-inline"
                    />
                    {#if dropdown?.menuIdx === mi && dropdown?.ingredIdx === ii && dropdown.matches.length > 0}
                      <ul
                        class="absolute z-20 left-0 right-0 top-full mt-0.5 bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-y-auto text-sm"
                      >
                        {#each dropdown.matches as match, k}
                          <li>
                            <button
                              type="button"
                              onmousedown={() => selectMatch(ingred, match)}
                              class="w-full text-left hover:bg-gray-200 transition-colors
															{k === dropdown.highlighted ? 'bg-gray-200 font-medium' : ''}"
                            >
                              {match.name}
                              <span class="text-xs ml-1">{match.unit}</span>
                            </button>
                          </li>
                        {/each}
                      </ul>
                    {/if}
                  </div>
                {/each}
              </div>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
  onPrev={() => goto(`/measure?offset=${Math.max(0, offset - 10)}`)}
  onNext={() => goto(`/measure?offset=${offset + 10}`)}
  onSave={handleSave}
  {dirty}
  saving={ps.saving}
  prevDisabled={offset === 0}
/>
