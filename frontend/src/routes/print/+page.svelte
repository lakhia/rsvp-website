<script>
    import { goto } from "$app/navigation";
    import { page } from "$app/state";
    import { get, post } from "$lib/api.js";
    import { getDisplayDate } from "$lib/dates.js";
    import Loading from "$lib/Loading.svelte";
    import { PageState } from "$lib/PageState.svelte.js";
    import Message from "$lib/Message.svelte";
    import Dialog from "$lib/Dialog.svelte";

    const ps = new PageState();

    let rows = $state([]);
    let meta = $state({}); // { save, niyaz, adults, kids, serving }
    let date = $state("");
    let dirty = $state(false);
    let dateWarning = $state("");
    let confirmingReset = $state(false);

    let sortCol = $state("thaali");
    let filters = $state({ area: "", size: "", here: "", filled: "", rice: "", name: "" });

    const offset = $derived(parseInt(page.url.searchParams.get("offset")) || 0);

    $effect(() => { loadData(offset); });

    let warnedDate = "";

    async function loadData(o) {
        dateWarning = "";
        warnedDate = "";
        await ps.load(async () => {
            const res = await get("print.php", { offset: o });
            rows  = res.data  || [];
            meta  = res.other || {};
            date  = res.date  || "";
            ps.msg = res.msg  || "";
            dirty = false;
        });
    }

    function onCheckboxChange(item) {
        if (!warnedDate) {
            warnedDate = new Date().toLocaleDateString("en-CA");
            if (date !== warnedDate) {
                dateWarning = `You are modifying ${getDisplayDate(date)}, not today.`;
            }
        }
        dirty = true;
        ps.msg = "";
    }

    // Filtering
    const filteredRows = $derived.by(() => {
        const f = filters;
        return rows.filter((item) => {
            if (f.name && !item.name.includes(f.name)) return false;
            if (f.area && item.area !== f.area) return false;
            if (f.size && item.size?.toUpperCase() !== f.size.toUpperCase()) return false;
            if (f.here === "Y" && !item.here) return false;
            if (f.here === "N" && item.here) return false;
            if (f.filled === "Y" && !item.filled) return false;
            if (f.filled === "N" && item.filled) return false;
            if (f.rice) {
                const riceVal = item["bread+rice"];
                if (f.rice.startsWith("N") && !riceVal) return false;
                if (f.rice.startsWith("Y") && riceVal) return false;
            }
            return true;
        });
    });

    const areas = $derived(
        [...new Set(rows.map(r => r.area).filter(Boolean))].sort()
    );

    const sortedRows = $derived.by(() => {
        return [...filteredRows].sort((a, b) => {
            const av = a[sortCol] ?? "";
            const bv = b[sortCol] ?? "";
            return av < bv ? -1 : av > bv ? 1 : 0;
        });
    });

    const firstLine = $derived.by(() => {
        if (!meta.niyaz) {
            const notHere   = filteredRows.filter((r) => !r.here).length;
            const notFilled = filteredRows.filter((r) => !r.filled).length;
            return `Not here: ${notHere}, not filled: ${notFilled}, total: ${filteredRows.length}`;
        }
        return `Adults: ${meta.adults ?? 0}, Kids: ${meta.kids ?? 0}`;
    });

    const secondLine = $derived.by(() => {
        if (!meta.niyaz) {
            const counts = { XS: 0, SM: 0, MD: 0, LG: 0, XL: 0 };
            for (const r of filteredRows) if (r.size in counts) counts[r.size]++;
            return Object.entries(counts).map(([k, v]) => `${k}: ${v}`).join(", ");
        }
        const thaals = ((meta.adults ?? 0) / 8 + (meta.kids ?? 0) / 10).toFixed(1);
        return `Thaals: ${thaals}`;
    });

    async function handleSave() {
        const body = rows.map((r) => ({ thaali: r.thaali, here: r.here ? 1 : 0, filled: r.filled ? 1 : 0 }));
        await ps.save(async () => {
            const res = await post("print.php", { offset }, body);
            rows   = res.data  || [];
            meta   = res.other || {};
            date   = res.date  || "";
            ps.msg = res.msg   || "Saved";
            dirty  = false;
        });
    }

    function handleReset() {
        for (const r of rows) { r.here = 0; r.filled = 0; }
        dirty = true;
        ps.msg = "";
        warnedDate = "";
        dateWarning = "";
        confirmingReset = false;
    }

    function generateLabels() {
        const params = new URLSearchParams({ date, sort: sortCol, filterArea: filters.area, filterSize: filters.size, filterHere: filters.here });
        window.open("generate_labels.php?" + params.toString());
    }

    function navigate(delta) { goto(`/print?offset=${offset + delta}`); }

    const servingEntries = $derived(Object.entries(meta.serving ?? {}));
</script>

<svelte:head>
    <title>{__APP_NAME__} - Print</title>
</svelte:head>

<!-- Header bar -->
<div class="flex flex-wrap items-start justify-between gap-3 mb-3 no-print">
    <div class="flex items-center gap-3">
        <h3 class="text-lg font-semibold text-gray-700">{getDisplayDate(date)}</h3>
        <button onclick={generateLabels} class="px-3 py-1 text-xs border border-gray-300 rounded hover:bg-gray-100 transition-colors">
            Generate Labels
        </button>
        <label class="flex items-center gap-1 text-sm text-gray-600">
            Sort:
            <select bind:value={sortCol} class="border border-gray-300 rounded px-1 py-0.5 text-sm">
                <option value="thaali">Thaali</option>
                <option value="area">Area</option>
                <option value="size">Size</option>
                <option value="name">Name</option>
                {#if !meta.niyaz}
                    <option value="here">Here</option>
                    <option value="filled">Filled</option>
                    <option value="bread+rice">Rice / Bread</option>
                {/if}
            </select>
        </label>
    </div>
    <div class="text-right text-sm text-gray-600 leading-relaxed">
        <div>{firstLine}</div>
        <div>{secondLine}</div>
    </div>
</div>

{#if dateWarning}
    <Dialog message={dateWarning} cancelLabel="OK" onCancel={() => dateWarning = ""} />
{/if}

<!-- Serving guidance -->
{#if servingEntries.length > 0}
    <table class="text-xs text-gray-600 mb-3 border border-gray-200 rounded">
        <tbody>
            {#each servingEntries as [menu, portions]}
                <tr>
                    <td class="px-2 py-1 font-medium border-r border-gray-200">{menu}</td>
                    {#each portions as q}
                        <td class="px-2 py-1 border-r border-gray-100">{q}</td>
                    {/each}
                </tr>
            {/each}
        </tbody>
    </table>
{/if}

{#if ps.loading}
    <Loading />
{:else}
<!-- Main table -->
<div class="overflow-x-auto">
    <table class="w-full min-w-[520px] text-sm border-collapse">
        <thead>
            <!-- Label row -->
            <tr class="bg-gray-200 text-gray-700 text-left text-xs uppercase tracking-wide">
                <th class="px-2 py-2 w-16 text-right">#</th>
                <th class="px-2 py-2 w-24">Group</th>
                {#if !meta.niyaz}<th class="px-2 py-2 w-24">Rice/Bread</th>{/if}
                <th class="px-2 py-2 w-16">Size</th>
                {#if !meta.niyaz}
                    <th class="px-2 py-2 w-16">Here</th>
                    <th class="px-2 py-2 w-16">Filled</th>
                {/if}
                <th class="px-2 py-2">Name</th>
            </tr>
            <!-- Filter row -->
            <tr class="bg-gray-100 border-b border-gray-300">
                <td class="px-2 py-1"></td>
                <td class="px-2 py-1">
                    <select bind:value={filters.area} class="w-full text-xs bg-transparent focus:outline-none">
                        <option value="">All</option>
                        {#each areas as area}
                            <option value={area}>{area}</option>
                        {/each}
                    </select>
                </td>
                {#if !meta.niyaz}
                    <td class="px-2 py-1">
                        <select bind:value={filters.rice} class="w-full text-xs bg-transparent focus:outline-none">
                            <option value="">All</option>
                            <option value="Y">No rice</option>
                            <option value="N">With rice</option>
                        </select>
                    </td>
                {/if}
                <td class="px-2 py-1">
                    <select bind:value={filters.size} class="w-full text-xs bg-transparent focus:outline-none">
                        <option value="">All</option>
                        <option value="XS">XS</option>
                        <option value="SM">SM</option>
                        <option value="MD">MD</option>
                        <option value="LG">LG</option>
                        <option value="XL">XL</option>
                    </select>
                </td>
                {#if !meta.niyaz}
                    <td class="px-2 py-1">
                        <select bind:value={filters.here} class="w-full text-xs bg-transparent focus:outline-none">
                            <option value="">All</option>
                            <option value="Y">Yes</option>
                            <option value="N">No</option>
                        </select>
                    </td>
                    <td class="px-2 py-1">
                        <select bind:value={filters.filled} class="w-full text-xs bg-transparent focus:outline-none">
                            <option value="">All</option>
                            <option value="Y">Yes</option>
                            <option value="N">No</option>
                        </select>
                    </td>
                {/if}
                <td class="px-2 py-1">
                    <input bind:value={filters.name} placeholder="Filter…" class="w-full text-xs bg-transparent border-b border-gray-400 focus:outline-none placeholder-gray-400"/>
                </td>
            </tr>
        </thead>
        <tbody>
            {#each sortedRows as item, i}
                <tr class="border-t border-gray-200 {i % 2 === 1 ? 'bg-gray-50' : ''}">
                    <td class="px-2 py-1.5 text-right text-gray-500">{item.thaali}</td>
                    <td class="px-2 py-1.5 text-gray-700">{item.area ?? ""}</td>
                    {#if !meta.niyaz}
                        <td class="px-2 py-1.5 text-gray-600 text-xs">{item["bread+rice"] ?? ""}</td>
                    {/if}
                    <td class="px-2 py-1.5">
                        <span class="inline-block px-1.5 py-0.5 text-xs font-medium bg-gray-100 rounded">{item.size ?? ""}</span>
                    </td>
                    {#if !meta.niyaz}
                        <td class="px-2 py-1.5 text-center">
                            <input type="checkbox" bind:checked={item.here} onchange={() => onCheckboxChange(item)} class="cursor-pointer"/>
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            <input type="checkbox" bind:checked={item.filled} onchange={() => onCheckboxChange(item)} class="cursor-pointer"/>
                        </td>
                    {/if}
                    <td class="px-2 py-1.5 text-gray-700">{item.name ?? ""}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<!-- Nav buttons -->
<div class="mt-4 flex justify-center items-center gap-3 no-print">
    <button onclick={() => navigate(-1)} class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors">
        &laquo; Prev
    </button>
    {#if meta.save}
        {#if confirmingReset}
            <Dialog
                message="Clear all here and filled checkboxes?"
                confirmLabel="Reset"
                cancelLabel="Cancel"
                danger={true}
                onConfirm={handleReset}
                onCancel={() => confirmingReset = false}
            />
        {/if}
        <button onclick={() => confirmingReset = true} class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors">
            Reset
        </button>
        <button onclick={handleSave} disabled={!dirty || ps.saving} class="px-4 py-1.5 text-sm rounded text-white transition-colors bg-brand hover:bg-brand-dark disabled:opacity-40">
            {ps.saving ? "Saving…" : "Save"}
        </button>
    {/if}
    <button onclick={() => navigate(1)} class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors">
        Next &raquo;
    </button>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
    }
</style>
