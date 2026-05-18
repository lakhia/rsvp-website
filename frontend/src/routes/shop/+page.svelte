<script>
  import { page } from '$app/state';
  import { get, navigate } from '$lib/api.js';
  import { getDisplayDate, parseYMD, fmtWeekday, fmtMonthDay } from '$lib/dates.js';
  import Loading from '$lib/Loading.svelte';
  import { PageState } from '$lib/PageState.svelte.js';
  import Message from '$lib/Message.svelte';
  import PageNav from '$lib/PageNav.svelte';
  import { getIntParam } from '$lib/utils.js';

  const ps = new PageState();

  let data = $state({});
  let startDate = $state('');

  const offset = $derived(getIntParam(page.url.searchParams, 'offset'));

  $effect(() => {
    loadData(offset);
  });

  async function loadData(o) {
    await ps.load(async () => {
      const res = await get('shop.php', { offset: o });
      data = res.data || {};
      startDate = res.date || '';
      ps.msg = res.msg || '';
    });
  }

  const todayStr = new Date().toLocaleDateString('en-CA');

  const days = $derived.by(() => {
    if (!startDate) return [];
    const start = parseYMD(startDate);
    return Array.from({ length: 7 }, (_, i) => {
      const dt = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
      const dateKey = dt.toLocaleDateString('en-CA');
      const dayData = data[dateKey] ?? {};
      return {
        day: fmtWeekday.format(dt),
        dateDisplay: fmtMonthDay.format(dt),
        count: dayData.count ?? null,
        dishes: Object.entries(dayData.ingred ?? {}).map(([name, items]) => ({ name, items })),
        empty: !dayData.count,
        isToday: dateKey === todayStr,
      };
    });
  });

  const totalItems = $derived(data['Total']?.ingred?.[''] ?? []);
  const cookingDays = $derived(days.filter((d) => !d.empty).length);

  async function exportToText() {
    const lines = [`Shopping list — Week of ${getDisplayDate(startDate)}`, ''];
    for (const item of totalItems) lines.push(`- ${item}`);
    try {
      await navigator.clipboard.writeText(lines.join('\n'));
      ps.msg = 'Copied to clipboard!';
    } catch {
      ps.msg = 'Could not copy — try a secure (HTTPS) connection.';
      ps.msgType = 'error';
    }
  }
</script>

<svelte:head>
  <title>{__APP_NAME__} - Shopping</title>
</svelte:head>

<div class="page-header">
  <div class="flex items-end justify-between gap-6 flex-wrap">
    <div>
      <div class="eyebrow mb-1">Shopping</div>
      <h1>Week of {getDisplayDate(startDate)}</h1>
      {#if cookingDays > 0}
        <div class="page-subtitle">{cookingDays} cooking {cookingDays === 1 ? 'day' : 'days'}</div>
      {/if}
    </div>
    {#if totalItems.length}
      <button onclick={exportToText} class="btn-primary">Export to text</button>
    {/if}
  </div>
</div>

{#if ps.loading}
  <Loading />
{:else}
  <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
    {#each days as d}
      <div class="card day-card {d.empty ? 'card-disabled' : ''} {d.isToday ? 'card-today' : ''}">
        <!-- Card header: day + date + stats -->
        <div class="day-card-head">
          <div class="flex items-baseline gap-2">
            <span class="eyebrow">{d.day}</span>
            <span class="day-date">{d.dateDisplay}</span>
            {#if d.empty}
              <span class="no-thaali">no thaali</span>
            {/if}
          </div>
          {#if !d.empty && d.count}
            <div class="day-stats">
              {#each [['count', d.count.count], ['norm.', d.count.normalized], ['rice+br', d.count['rice+bread']]] as [label, val]}
                <div class="stat-cell">
                  <div class="eyebrow">{label}</div>
                  <div class="stat-value">{val ?? 0}</div>
                </div>
              {/each}
            </div>
          {/if}
        </div>

        <!-- Dish list -->
        {#if !d.empty}
          <div class="dishes">
            {#each d.dishes as dish}
              <div class="dish-row">
                <span class="dish-badge">{dish.name}</span>
                <div class="dish-items">
                  {#if dish.items.length === 0}
                    <span class="no-items">no ingredients found</span>
                  {:else}
                    {#each dish.items as item}
                      <div class="dish-item">{item}</div>
                    {/each}
                  {/if}
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    {/each}
  </div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
  onPrev={() => navigate(`/shop?offset=${offset - 7}`)}
  onNext={() => navigate(`/shop?offset=${offset + 7}`)}
  class="no-print"
/>

<style>
  .day-card {
    padding: 14px 16px;
  }

  .day-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
  }

  .day-date {
    font-size: 13px;
    font-weight: 500;
    color: var(--text);
  }

  .no-thaali {
    font-size: 11px;
    font-style: italic;
    color: var(--muted);
    margin-left: 4px;
  }

  .day-stats {
    display: flex;
    align-items: stretch;
    flex-shrink: 0;
  }

  .stat-cell {
    padding: 3px 10px;
    text-align: center;
    border-left: 1px solid var(--border);
  }
  .stat-cell:first-child {
    border-left: none;
    padding-left: 0;
  }

  .stat-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
  }

  .dishes {
    display: grid;
    grid-template-columns: max-content 1fr;
    column-gap: 12px;
    row-gap: 8px;
    align-items: start;
    margin-top: 10px;
  }

  .dish-row {
    display: contents;
  }

  .dish-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 4px;
    background: var(--text);
    color: #fff;
    margin-top: 1px;
    white-space: nowrap;
    text-align: center;
  }

  .dish-items {
    flex: 1;
    font-size: 13px;
    color: var(--text);
    line-height: 1.55;
  }

  .dish-item {
    font-variant-numeric: tabular-nums;
  }

  .no-items {
    font-size: 12px;
    color: var(--muted);
    font-style: italic;
  }
</style>
