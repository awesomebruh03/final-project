<script>
	// A simple vertical bar chart made of HTML boxes.
	//   bars:     [{ label, value, color, title }]  (color is any CSS colour)
	//   onselect: optional, called with the bar index when a bar is clicked
	//   selected: index of the highlighted (clicked) bar, or -1
	const { bars, height = 160, gap = 12, onselect = null, selected = -1 } = $props();

	const max = $derived(Math.max(0, ...bars.map((bar) => bar.value)));
</script>

<div>
	<div class="flex items-end" style="height: {height}px; gap: {gap}px">
		{#each bars as bar, i (i)}
			<button
				type="button"
				class="flex h-full min-w-0 flex-1 items-end {onselect
					? 'cursor-pointer'
					: 'cursor-default'}"
				title={bar.title ?? ''}
				aria-label={bar.title ?? bar.label}
				tabindex={onselect ? 0 : -1}
				onclick={() => onselect?.(i)}
			>
				<!-- Empty bars become a thin flat line -->
				<span
					class="w-full rounded-md transition-opacity {selected !== -1 && selected !== i
						? 'opacity-50'
						: ''}"
					style="background: {bar.color}; height: {bar.value > 0 && max > 0
						? Math.max((bar.value / max) * 100, 3)
						: 0}%; min-height: 3px"
				></span>
			</button>
		{/each}
	</div>

	<div class="mt-2 flex" style="gap: {gap}px">
		{#each bars as bar, i (i)}
			<span class="min-w-0 flex-1 text-center text-xs whitespace-nowrap text-muted"
				>{bar.label}</span
			>
		{/each}
	</div>
</div>
