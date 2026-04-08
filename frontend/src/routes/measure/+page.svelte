<script>
	import { goto } from '$app/navigation';
	import { page } from '$app/state';
	import { get, post } from '$lib/api.js';
	import { isAdmin } from '$lib/auth.js';

	let menus    = $state([]);
	let allIngreds = $state([]);   // full ingredient list for autocomplete
	let msg      = $state('');
	let dirty    = $state(false);
	let saving   = $state(false);

	// Active dropdown state: { menuIdx, ingredIdx, matches, highlighted }
	let dropdown = $state(null);

	const offset = $derived(parseInt(page.url.searchParams.get('offset')) || 0);
	const pageNum = $derived(offset / 10 + 1);

	$effect(() => {
		if (!isAdmin()) { goto('/'); return; }
		loadData(offset);
	});

	async function loadData(o) {
		msg = '';
		try {
			const [menuRes, ingredRes] = await Promise.all([
				get('measure.php', { offset: o, len: 10 }),
				get('ingred.php')
			]);
			menus      = menuRes.data  || [];
			allIngreds = ingredRes.data || [];
			msg        = menuRes.msg   || '';
			dirty      = false;
			dropdown   = null;
		} catch (e) {
			msg = e.message || 'Request failed, try again';
		}
	}

	async function handleSave() {
		saving = true;
		try {
			const res = await post('measure.php', { offset, len: 10 }, menus);
			menus = res.data || [];
			msg   = res.msg  || 'Saved';
			dirty = false;
		} catch (e) {
			msg = e.message || 'Request failed, try again';
		} finally {
			saving = false;
		}
	}

	// Autocomplete logic
	function getMatches(query) {
		if (!query) return [];
		const q = query.toLowerCase();
		return allIngreds.filter(i => i.name.toLowerCase().includes(q));
	}

	function onIngredInput(mi, ii, ingred) {
		dirty = true;
		dropdown = {
			menuIdx: mi,
			ingredIdx: ii,
			matches: getMatches(ingred.name),
			highlighted: 0
		};
	}

	function onIngredKeydown(e, mi, ii, ingred) {
		if (!dropdown || dropdown.menuIdx !== mi || dropdown.ingredIdx !== ii) return;
		const { matches } = dropdown;

		if (e.key === 'ArrowDown') {
			e.preventDefault();
			dropdown.highlighted = Math.min(dropdown.highlighted + 1, matches.length - 1);
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
		ingred.id   = match.id;
		ingred.unit = match.unit;
		dropdown = null;
		dirty = true;
	}

	function onIngredBlur() {
		// Small delay so click on dropdown item fires first
		setTimeout(() => { dropdown = null; }, 150);
	}

	const inputClass  = 'w-full bg-transparent border-b border-transparent hover:border-gray-300 focus:border-brand focus:outline-none text-gray-700 placeholder-gray-300 text-sm';
	const narrowClass = 'bg-transparent border-b border-transparent hover:border-gray-300 focus:border-brand focus:outline-none text-gray-700 placeholder-gray-300 text-sm';
</script>

<svelte:head>
	<title>{__APP_NAME__} - Measures</title>
</svelte:head>

<h3 class="text-lg font-semibold text-gray-700 mb-4">
	Menu Measurements, page {pageNum}
</h3>

<div class="overflow-x-auto">
	<table class="w-full min-w-[600px] text-sm border-collapse">
		<thead>
			<tr class="bg-gray-200 text-gray-700 text-xs uppercase tracking-wide text-left">
				<th class="px-3 py-2 font-medium w-[25%]">Menu</th>
				<th class="px-3 py-2 font-medium">Ingredients per thaali</th>
			</tr>
		</thead>
		<tbody>
			{#each menus as menu, mi}
				<tr class="border-t border-gray-200 align-top {mi % 2 === 1 ? 'bg-gray-50' : ''}">
					<td class="px-3 py-3 text-gray-700 font-medium">{menu.menu}</td>
					<td class="px-3 py-3">
						<!-- ingredient grid: 3 cols mobile, 6 cols (2×3) on large screens -->
						<div class="grid items-center gap-x-3 gap-y-1 [grid-template-columns:60px_50px_1fr] lg:[grid-template-columns:60px_50px_1fr_60px_50px_1fr]">
							{#each menu.ingred as ingred, ii}
								<!-- Amount -->
								<input
									type="number"
									bind:value={ingred.multiplier}
									oninput={() => dirty = true}
									placeholder="0.0"
									step="0.01"
									class="text-right bg-transparent border-b border-transparent hover:border-gray-300 focus:border-brand focus:outline-none text-gray-700 placeholder-gray-300 text-sm"
								/>
								<!-- Unit -->
								<span class="text-gray-400 text-sm">{ingred.unit ?? ''}</span>
								<!-- Name + autocomplete dropdown -->
								<div class="relative">
									<input
										type="text"
										bind:value={ingred.name}
										oninput={() => onIngredInput(mi, ii, ingred)}
										onkeydown={(e) => onIngredKeydown(e, mi, ii, ingred)}
										onblur={onIngredBlur}
										placeholder="ingredient"
										class={inputClass}
									/>
									{#if dropdown?.menuIdx === mi && dropdown?.ingredIdx === ii && dropdown.matches.length > 0}
										<ul class="absolute z-20 left-0 right-0 top-full mt-0.5 bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-y-auto text-sm">
											{#each dropdown.matches as match, k}
												<li>
													<button
														type="button"
														onmousedown={() => selectMatch(ingred, match)}
														class="w-full text-left px-3 py-1.5 hover:bg-gray-100 transition-colors
															{k === dropdown.highlighted ? 'bg-gray-100 font-medium' : ''}"
													>
														{match.name}
														<span class="text-gray-400 text-xs ml-1">{match.unit}</span>
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

{#if msg}
	<p class="mt-3 text-center text-sm text-red-600">{msg}</p>
{/if}

<div class="mt-4 flex justify-center gap-4">
	<button onclick={() => goto(`/measure?offset=${Math.max(0, offset - 10)}`)}
		disabled={offset === 0}
		class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors disabled:opacity-40">
		&laquo; Prev
	</button>
	<button onclick={handleSave} disabled={!dirty || saving}
		class="px-4 py-1.5 text-sm rounded text-white transition-colors bg-brand hover:bg-brand-dark disabled:opacity-40">
		{saving ? 'Saving…' : 'Save'}
	</button>
	<button onclick={() => goto(`/measure?offset=${offset + 10}`)}
		class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors">
		Next &raquo;
	</button>
</div>
