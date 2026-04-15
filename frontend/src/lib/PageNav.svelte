<script>
  let {
    onPrev,
    onNext,
    onSave = null,
    dirty = false,
    saving = false,
    prevDisabled = false,
    class: klass = '',
    children,
  } = $props();
</script>

<div class="mt-4 flex justify-center items-center gap-2 {klass}">
  <button onclick={onPrev} disabled={prevDisabled} class="btn-secondary">
    &laquo; Prev
  </button>
  {#if children}{@render children()}{/if}
  {#if onSave}
    <button onclick={onSave} disabled={!dirty || saving} class="btn-primary min-w-22">
      {saving ? 'Saving…' : 'Save'}
    </button>
  {/if}
  <button onclick={onNext} class="btn-secondary"> Next &raquo; </button>
</div>
