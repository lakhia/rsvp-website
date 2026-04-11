<script>
    import { goto } from "$app/navigation";
    import { page } from "$app/state";
    import { get, post } from "$lib/api.js";
    import { getDisplayDate } from "$lib/dates.js";
    import { requireAdmin } from "$lib/auth.js";
    import Loading from "$lib/Loading.svelte";
    import { PageState } from "$lib/PageState.svelte.js";
    import Message from "$lib/Message.svelte";
    import PageNav from "$lib/PageNav.svelte";
    import { getIntParam } from "$lib/utils.js";
    import { tableHeadClass, pageHeadingClass } from "$lib/styles.js";

    const ps = new PageState();

    let events = $state([]);
    let startDate = $state("");
    let dirty = $state(false);

    const offset = $derived(getIntParam(page.url.searchParams, "offset"));

    $effect(() => {
        if (!requireAdmin()) return;
        loadData(offset);
    });

    async function loadData(o) {
        await ps.load(async () => {
            const res = await get("event.php", { offset: o });
            events    = res.data || [];
            startDate = res.date || "";
            ps.msg    = res.msg  || "";
            dirty     = false;
        });
    }

    async function handleSave() {
        await ps.save(async () => {
            const res = await post("event.php", { offset }, events);
            events    = res.data || [];
            startDate = res.date || "";
            ps.msg    = res.msg  || "Saved";
            dirty     = false;
        });
    }
</script>

<svelte:head>
    <title>{__APP_NAME__} - Events</title>
</svelte:head>

<h3 class={pageHeadingClass}>
    Events from {getDisplayDate(startDate)}
</h3>

{#if ps.loading}
    <Loading />
{:else}
<div class="overflow-x-auto">
    <table class="w-full min-w-120 text-sm border-collapse">
        <thead>
            <tr class={tableHeadClass}>
                <th class="px-3 py-2 font-medium w-[15%]">Date</th>
                <th class="px-3 py-2 font-medium">Details</th>
                <th class="px-3 py-2 font-medium text-center w-[8%]">Niyaz</th>
                <th class="px-3 py-2 font-medium text-center w-[8%]">Enabled</th
                >
            </tr>
        </thead>
        <tbody>
            {#each events as ev, i}
                <tr class="border-t border-gray-200 even:bg-gray-50">
                    <td class="px-3 py-2 text-gray-700 whitespace-nowrap">
                        {getDisplayDate(ev.date)}
                    </td>
                    <td class="px-3 py-2">
                        <input
                            type="text"
                            bind:value={ev.details}
                            oninput={() => (dirty = true)}
                            placeholder="Event details (empty = delete)"
                            class="w-full bg-transparent border-b border-transparent hover:border-gray-300 focus:border-brand focus:outline-none text-gray-700 placeholder-gray-300"
                        />
                    </td>
                    <td class="px-3 py-2 text-center">
                        <input
                            type="checkbox"
                            bind:checked={ev.niyaz}
                            onchange={() => (dirty = true)}
                            class="cursor-pointer"
                        />
                    </td>
                    <td class="px-3 py-2 text-center">
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
    dirty={dirty}
    saving={ps.saving}
/>
