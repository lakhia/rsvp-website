<script>
	import { goto, beforeNavigate } from '$app/navigation';
	import { page } from '$app/state';
	import { get, post } from '$lib/api.js';
	import { getDisplayDate } from '$lib/dates.js';
	import Loading from '$lib/Loading.svelte';
	import { PageState } from '$lib/PageState.svelte.js';
	import Message from '$lib/Message.svelte';
	import Dialog from '$lib/Dialog.svelte';

	const ps = new PageState();

	let events  = $state([]);
	let sizes   = $state([]);   // eligible sizes from server
	let dirty       = $state({});   // { [date]: true } — never unset on toggle-back
	let pendingHref = $state(null); // set when navigation is blocked by dirty state

	const offset    = $derived(parseInt(page.url.searchParams.get('offset')) || 0);
	const dateParam = $derived(page.url.searchParams.get('date') || '');
	const hasDirty  = $derived(Object.keys(dirty).length > 0);

	$effect(() => {
		loadData(offset, dateParam);
	});

	// No reactive reads → runs once on mount, cleans up on unmount
	$effect(() => {
		window.addEventListener('beforeunload', warnIfDirty);
		return () => window.removeEventListener('beforeunload', warnIfDirty);
	});

	beforeNavigate(({ cancel, to }) => {
		if (hasDirty) {
			cancel();
			pendingHref = to?.url?.href ?? '/';
		}
	});

	function warnIfDirty(e) {
		if (hasDirty) e.preventDefault();
	}

	async function loadData(o, d) {
		await ps.load(async () => {
			const res = await get('rsvp.php', { offset: o, date: d });
			events    = res.data  || [];
			sizes     = res.other || [];
			ps.msg    = res.msg   || '';
			dirty     = {};
		});
	}

	function mark(event) {
		dirty[event.date] = true;
		ps.msg = '';
	}

	function getSizes(currentSize) {
		return sizes.includes(currentSize) ? sizes : [...sizes, currentSize];
	}

	function onRsvpChange(ev) {
		ev.rsvp = ev.rsvp ? 0 : 1;
		if (ev.niyaz) {
			if (ev.rsvp) {
				ev.adults = parseInt(localStorage.getItem('adults')) || 0;
				ev.kids   = parseInt(localStorage.getItem('kids'))   || 0;
			} else {
				ev.adults = ev.kids = null;
			}
		}
		mark(ev);
	}

	function onCountChange(ev) {
		localStorage.setItem('adults', ev.adults ?? 0);
		localStorage.setItem('kids',   ev.kids   ?? 0);
		mark(ev);
	}

	async function handleSave() {
		const body = {};
		for (const ev of events) {
			if (dirty[ev.date]) {
				const row = {
					rsvp:     ev.rsvp     ? 1 : 0,
					size:     ev.size,
					lessRice: ev.lessRice ? 1 : 0,
				};
				if (ev.niyaz) {
					row.adults = ev.adults ?? 0;
					row.kids   = ev.kids   ?? 0;
				}
				body[ev.date] = row;
			}
		}
		await ps.save(async () => {
			const res = await post('rsvp.php', { offset }, body);
			events = res.data  || [];
			sizes  = res.other || [];
			ps.msg = res.msg   || 'Saved';
			dirty  = {};
		});
	}

	function navigate(delta) {
		goto(`/?offset=${offset + delta}`);
	}
</script>

<svelte:head>
	<title>{__APP_NAME__} - RSVP</title>
</svelte:head>

<h3 class="text-lg font-semibold text-gray-700 mb-4">
	RSVP for {localStorage.getItem('greet') ?? ''}
</h3>

{#if pendingHref}
	<Dialog
		message="You have unsaved changes. Discard them and leave?"
		confirmLabel="Discard & leave"
		cancelLabel="Stay"
		onConfirm={() => { dirty = {}; goto(pendingHref); pendingHref = null; }}
		onCancel={() => pendingHref = null}
	/>
{/if}

{#if ps.loading}
	<Loading />
{:else}
<div class="overflow-x-auto">
	<table class="w-full min-w-[560px] text-sm border-collapse">
		<thead>
			<tr class="bg-gray-200 text-gray-700 text-left text-xs uppercase tracking-wide">
				<th class="px-3 py-2 font-medium w-[15%]">Day</th>
				<th class="px-3 py-2 font-medium w-[40%]">Details</th>
				<th class="px-3 py-2 font-medium text-center w-[15%]">No bread<br>/ Rice</th>
				<th class="px-3 py-2 font-medium text-center w-[12%]">RSVP</th>
				<th class="px-3 py-2 font-medium w-[18%]">Size / Count</th>
			</tr>
		</thead>
		<tbody>
			{#each events as ev, i}
				<tr class="border-t border-gray-200 {i % 2 === 1 ? 'bg-gray-50' : ''}">
					<!-- Day -->
					<td class="px-3 py-2 whitespace-nowrap text-gray-700">
						{getDisplayDate(ev.date)}
					</td>

					<!-- Details -->
					<td class="px-3 py-2 text-gray-600">{ev.details ?? ''}</td>

					<!-- No rice/bread -->
					<td class="px-3 py-2 text-center">
						{#if ev.enabled && !ev.niyaz}
							<input
								type="checkbox"
								bind:checked={ev.lessRice}
								disabled={ev.readonly || !ev.rsvp}
								onchange={() => mark(ev)}
								class="cursor-pointer disabled:opacity-40"
							/>
						{/if}
					</td>

					<!-- RSVP button -->
					<td class="px-3 py-2 text-center">
						{#if ev.enabled}
							<button
								onclick={() => onRsvpChange(ev)}
								disabled={ev.readonly}
								class="w-16 py-0.5 rounded text-sm font-medium transition-colors disabled:opacity-40
									{ev.rsvp ? 'bg-yes hover:bg-yes-dark' : 'bg-no hover:bg-no-dark'}"
							>
								{ev.rsvp ? 'Yes' : 'No'}
							</button>
						{/if}
					</td>

					<!-- Size / Count -->
					<td class="px-3 py-2">
						{#if ev.enabled && !ev.niyaz}
							<select
								bind:value={ev.size}
								disabled={ev.readonly || !ev.rsvp}
								onchange={() => mark(ev)}
								class="border border-gray-300 rounded px-1 py-0.5 text-sm disabled:opacity-40"
							>
								{#each getSizes(ev.size) as s}
									<option value={s}>{s}</option>
								{/each}
							</select>
						{:else if ev.enabled && ev.niyaz}
							<div class="flex flex-col gap-1">
								<label class="flex items-center gap-1 text-xs text-gray-600">
									<input
										type="number"
										bind:value={ev.adults}
										disabled={ev.readonly || !ev.rsvp}
										onchange={() => onCountChange(ev)}
										class="w-12 border border-gray-300 rounded px-1 py-0.5 text-sm disabled:opacity-40"
									/>
									Adults
								</label>
								<label class="flex items-center gap-1 text-xs text-gray-600">
									<input
										type="number"
										bind:value={ev.kids}
										disabled={ev.readonly || !ev.rsvp}
										onchange={() => onCountChange(ev)}
										class="w-12 border border-gray-300 rounded px-1 py-0.5 text-sm disabled:opacity-40"
									/>
									Kids
								</label>
							</div>
						{/if}
					</td>
				</tr>
			{/each}
		</tbody>
	</table>
</div>
{/if}

<Message msg={ps.msg} msgType={ps.msgType} />

<div class="mt-4 flex justify-center items-center gap-4">
	<button
		onclick={() => navigate(-7)}
		class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors"
	>
		&laquo; Prev
	</button>

	<button
		onclick={handleSave}
		disabled={!hasDirty || ps.saving}
		class="px-4 py-1.5 text-sm rounded text-white transition-colors
			bg-brand hover:bg-brand-dark disabled:opacity-40"
	>
		{ps.saving ? 'Saving…' : 'Save'}
	</button>

	<button
		onclick={() => navigate(7)}
		class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors"
	>
		Next &raquo;
	</button>
</div>
