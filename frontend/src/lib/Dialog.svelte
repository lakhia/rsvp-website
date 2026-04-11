<script>
	let {
		message     = '',
		confirmLabel = '',
		cancelLabel  = 'OK',
		danger       = false,
		onConfirm    = null,
		onCancel     = () => {},
	} = $props();

	let cancelBtn  = $state(null);
	let confirmBtn = $state(null);

	$effect(() => {
		(confirmBtn ?? cancelBtn)?.focus();
	});

	function onKeydown(e) {
		if (e.key === 'Escape') onCancel();
	}
</script>

<svelte:window onkeydown={onKeydown} />

<!-- Backdrop -->
<div
	class="fixed inset-0 bg-black/40 z-40"
	onclick={onCancel}
	aria-hidden="true"
></div>

<!-- Dialog -->
<div
	role="dialog"
	aria-modal="true"
	aria-labelledby="dialog-msg"
	class="fixed z-50 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
		bg-white rounded-lg shadow-xl p-5 w-72 max-w-[90vw]"
>
	<p id="dialog-msg" class="text-gray-700 text-sm mb-5">{message}</p>
	<div class="flex justify-end gap-2">
		<button
			bind:this={cancelBtn}
			onclick={onCancel}
			class="px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors"
		>
			{cancelLabel}
		</button>
		{#if confirmLabel && onConfirm}
			<button
				bind:this={confirmBtn}
				onclick={onConfirm}
				class="px-3 py-1.5 text-sm rounded text-white transition-colors
					{danger ? 'bg-red-500 hover:bg-red-600' : 'bg-brand hover:bg-brand-dark'}"
			>
				{confirmLabel}
			</button>
		{/if}
	</div>
</div>
