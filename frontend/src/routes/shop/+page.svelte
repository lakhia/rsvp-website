<script>
    import { goto } from "$app/navigation";
    import { page } from "$app/state";
    import { get } from "$lib/api.js";
    import { getDisplayDate } from "$lib/dates.js";
    import Loading from "$lib/Loading.svelte";
    import { PageState } from "$lib/PageState.svelte.js";
    import Message from "$lib/Message.svelte";
    import PageNav from "$lib/PageNav.svelte";
    import { getIntParam } from "$lib/utils.js";
    import { tableHeadClass, pageHeadingClass } from "$lib/styles.js";

    const ps = new PageState();

    let data = $state({});
    let startDate = $state("");

    const offset = $derived(getIntParam(page.url.searchParams, "offset"));

    $effect(() => { loadData(offset); });

    async function loadData(o) {
        await ps.load(async () => {
            const res = await get("shop.php", { offset: o });
            data      = res.data || {};
            startDate = res.date || "";
            ps.msg    = res.msg  || "";
        });
    }

    const entries = $derived(Object.entries(data));
</script>

<svelte:head>
    <title>{__APP_NAME__} - Shopping</title>
</svelte:head>

<h3 class={pageHeadingClass}>
    Shopping from {getDisplayDate(startDate)}
</h3>

{#if ps.loading}
    <Loading />
{:else}

{#if entries.length === 0 && !ps.msg}
    <p class="text-sm text-gray-500">No events this week.</p>
{/if}

<div class="overflow-x-auto">
    <table class="w-full min-w-120 text-sm border-collapse">
        <thead>
            <tr class={tableHeadClass}>
                <th class="px-3 py-2 font-medium w-[15%]">Date</th>
                <th class="px-3 py-2 font-medium w-[65%]">Ingredients</th>
                <th class="px-3 py-2 font-medium w-[20%]">Counts</th>
            </tr>
        </thead>
        <tbody>
            {#each entries as [date, value], i}
                <tr class="border-t border-gray-200 align-top even:bg-gray-50">
                    <td class="px-3 py-2 text-gray-700 whitespace-nowrap">
                        {date === "Total" ? "Total" : getDisplayDate(date)}
                    </td>
                    <td class="px-3 py-2">
                        <div class="sm:columns-2 gap-x-6">
                            {#each Object.entries(value.ingred ?? {}) as [menu, ingreds]}
                                <div class="mb-2 break-inside-avoid">
                                    <span class="font-medium text-gray-700">{menu}</span>
                                    <div class="ml-2 text-gray-600">
                                        {#each ingreds as q}
                                            <div>{q}</div>
                                        {/each}
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </td>
                    <td class="px-3 py-2 text-gray-600">
                        {#each Object.entries(value.count ?? {}) as [k, v]}
                            <div>
                                <span class="text-gray-500">{k}:</span>
                                {v}
                            </div>
                        {/each}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<PageNav
    onPrev={() => goto(`/shop?offset=${offset - 7}`)}
    onNext={() => goto(`/shop?offset=${offset + 7}`)}
    class="no-print"
/>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
    }
</style>
