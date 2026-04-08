<script>
    import { goto } from "$app/navigation";
    import { page } from "$app/state";
    import { get, post } from "$lib/api.js";
    import { getDisplayDate } from "$lib/dates.js";
    import { isAdmin } from "$lib/auth.js";
    import Loading from "$lib/Loading.svelte";
    import { PageState } from "$lib/PageState.svelte.js";
    import Message from "$lib/Message.svelte";

    const ps = new PageState();

    let events = $state([]);
    let startDate = $state("");
    let dirty = $state(false);

    const offset = $derived(parseInt(page.url.searchParams.get("offset")) || 0);

    $effect(() => {
        if (!isAdmin()) { goto("/"); return; }
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

<h3 class="text-lg font-semibold text-gray-700 mb-4">
    Events from {getDisplayDate(startDate)}
</h3>

{#if ps.loading}
    <Loading />
{:else}
<div class="overflow-x-auto">
    <table class="w-full min-w-120 text-sm border-collapse">
        <thead>
            <tr
                class="bg-gray-200 text-gray-700 text-xs uppercase tracking-wide text-left"
            >
                <th class="px-3 py-2 font-medium w-[15%]">Date</th>
                <th class="px-3 py-2 font-medium">Details</th>
                <th class="px-3 py-2 font-medium text-center w-[8%]">Niyaz</th>
                <th class="px-3 py-2 font-medium text-center w-[8%]">Enabled</th
                >
            </tr>
        </thead>
        <tbody>
            {#each events as ev, i}
                <tr
                    class="border-t border-gray-200 {i % 2 === 1
                        ? 'bg-gray-50'
                        : ''}"
                >
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

<div class="mt-4 flex justify-center gap-4">
    <button
        onclick={() => goto(`/event?offset=${offset - 7}`)}
        class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors"
    >
        &laquo; Prev
    </button>
    <button
        onclick={handleSave}
        disabled={!dirty || ps.saving}
        class="px-4 py-1.5 text-sm rounded text-white transition-colors bg-brand hover:bg-brand-dark disabled:opacity-40"
    >
        {ps.saving ? "Saving…" : "Save"}
    </button>
    <button
        onclick={() => goto(`/event?offset=${offset + 7}`)}
        class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors"
    >
        Next &raquo;
    </button>
</div>
