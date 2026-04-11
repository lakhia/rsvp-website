<script>
	let {
		onPrev,
		onNext,
		onSave        = null,
		dirty         = false,
		saving        = false,
		prevDisabled  = false,
		class: klass  = '',
		children,
	} = $props();
</script>

<div class="mt-4 flex justify-center items-center gap-4 {klass}">
	<button
		onclick={onPrev}
		disabled={prevDisabled}
		class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors"
	>
		&laquo; Prev
	</button>
	{#if children}{@render children()}{/if}
	{#if onSave}
		<button
			onclick={onSave}
			disabled={!dirty || saving}
			class="px-4 py-1.5 text-sm rounded text-white transition-colors bg-brand hover:bg-brand-dark"
		>
			{saving ? 'Saving…' : 'Save'}
		</button>
	{/if}
	<button
		onclick={onNext}
		class="px-4 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-100 transition-colors"
	>
		Next &raquo;
	</button>
</div>
