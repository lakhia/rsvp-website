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

<h2>Shopping from {getDisplayDate(startDate)}</h2>

{#if ps.loading}
    <Loading />
{:else}

<div class="overflow-x-auto">
    <table>
        <thead>
            <tr>
                <th class="w-[15%]">Date</th>
                <th class="w-[65%]">Ingredients</th>
                <th class="w-[20%]">Counts</th>
            </tr>
        </thead>
        <tbody>
            {#each entries as [date, value], i}
                <tr class="align-top">
                    <td class="whitespace-nowrap">
                        {date === "Total" ? "Total" : getDisplayDate(date)}
                    </td>
                    <td>
                        <div class="sm:columns-2 gap-x-6">
                            {#each Object.entries(value.ingred ?? {}) as [menu, ingreds]}
                                <div class="mb-2 break-inside-avoid">
                                    {#if menu}<span class="badge">{menu}</span>{/if}
                                    <div class="ml-2">
                                        {#each ingreds as q}
                                            <div>{q}</div>
                                        {/each}
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </td>
                    <td>
                        {#each Object.entries(value.count ?? {}) as [k, v]}
                            <div>
                                <span>{k}:</span>
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
