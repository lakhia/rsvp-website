<script>
    import { goto } from "$app/navigation";
    import { page } from "$app/state";
    import { get, post } from "$lib/api.js";
    import { isAdmin } from "$lib/auth.js";
    import Loading from "$lib/Loading.svelte";
    import { PageState } from "$lib/PageState.svelte.js";

    const ps = new PageState();

    let families = $state([]);
    let dirty = $state(false);

    // Family uses thaali number as offset (1-indexed), not week offset
    const offset = $derived(parseInt(page.url.searchParams.get("offset")) || 1);

    $effect(() => {
        if (!isAdmin()) { goto("/"); return; }
        loadData(offset);
    });

    async function loadData(o) {
        await ps.load(async () => {
            const res = await get("family.php", { offset: o });
            families = res.data || [];
            ps.msg   = res.msg  || "";
            dirty    = false;
        });
    }

    async function handleSave() {
        await ps.save(async () => {
            const res = await post("family.php", { offset }, families);
            families = res.data || [];
            ps.msg   = res.msg  || "Saved";
            dirty    = false;
        });
    }

    const inputClass =
        "w-full bg-transparent border-b border-transparent hover:border-gray-300 focus:border-brand focus:outline-none text-gray-700 placeholder-gray-300 text-sm";
</script>

<svelte:head>
    <title>{__APP_NAME__} - Families</title>
</svelte:head>

<h3 class="text-lg font-semibold text-gray-700 mb-4">Families</h3>

{#if ps.loading}
    <Loading />
{:else}
<div class="overflow-x-auto">
    <table class="w-full min-w-200 text-sm border-collapse">
        <thead>
            <tr
                class="bg-gray-200 text-gray-700 text-xs uppercase tracking-wide text-left"
            >
                <th class="px-2 py-2 font-medium w-[8%]">Num</th>
                <th class="px-2 py-2 font-medium w-[10%]">Area</th>
                <th class="px-2 py-2 font-medium w-[18%]">Name</th>
                <th class="px-2 py-2 font-medium w-[6%]">Size</th>
                <th class="px-2 py-2 font-medium w-[20%]">Email</th>
                <th class="px-2 py-2 font-medium w-[22%]">Phone / POC</th>
                <th class="px-2 py-2 font-medium w-[8%]">Resp</th>
            </tr>
        </thead>
        <tbody>
            {#each families as f, i}
                <tr
                    class="border-t border-gray-200 {i % 2 === 1
                        ? 'bg-gray-50'
                        : ''}"
                >
                    <!-- Thaali # + ITS -->
                    <td class="px-2 py-2 align-top">
                        <div class="text-gray-500 text-xs mb-1">{f.thaali}</div>
                        <input
                            type="text"
                            bind:value={f.its}
                            oninput={() => (dirty = true)}
                            placeholder="ITS ID"
                            class={inputClass}
                        />
                    </td>
                    <!-- Area -->
                    <td class="px-2 py-2 align-top">
                        <input
                            type="text"
                            bind:value={f.area}
                            oninput={() => (dirty = true)}
                            placeholder="Area"
                            class={inputClass}
                        />
                    </td>
                    <!-- Name -->
                    <td class="px-2 py-2 align-top">
                        <input
                            type="text"
                            bind:value={f.firstName}
                            oninput={() => (dirty = true)}
                            placeholder="First name"
                            class="{inputClass} mb-1"
                        />
                        <input
                            type="text"
                            bind:value={f.lastName}
                            oninput={() => (dirty = true)}
                            placeholder="Last name"
                            class={inputClass}
                        />
                    </td>
                    <!-- Size -->
                    <td class="px-2 py-2 align-top">
                        <input
                            type="text"
                            bind:value={f.size}
                            oninput={() => (dirty = true)}
                            placeholder="MD"
                            class={inputClass}
                        />
                    </td>
                    <!-- Email -->
                    <td class="px-2 py-2 align-top">
                        <input
                            type="email"
                            bind:value={f.email}
                            oninput={() => (dirty = true)}
                            placeholder="Email (empty = delete)"
                            class={inputClass}
                        />
                    </td>
                    <!-- Phone / POC -->
                    <td class="px-2 py-2 align-top">
                        <input
                            type="text"
                            bind:value={f.phone}
                            oninput={() => (dirty = true)}
                            placeholder="Phone"
                            class="{inputClass} mb-1"
                        />
                        <input
                            type="text"
                            bind:value={f.poc}
                            oninput={() => (dirty = true)}
                            placeholder="POC"
                            class={inputClass}
                        />
                    </td>
                    <!-- Resp -->
                    <td class="px-2 py-2 align-top">
                        <input
                            type="text"
                            bind:value={f.resp}
                            oninput={() => (dirty = true)}
                            class={inputClass}
                        />
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
{/if}

{#if ps.msg}
    <p class="mt-3 text-center text-sm text-red-600">{ps.msg}</p>
{/if}

<div class="mt-4 flex justify-center gap-4">
    <button
        onclick={() => goto(`/family?offset=${Math.max(1, offset - 10)}`)}
        disabled={offset <= 1}
        class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors disabled:opacity-40"
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
        onclick={() => goto(`/family?offset=${offset + 10}`)}
        class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors"
    >
        Next &raquo;
    </button>
</div>
