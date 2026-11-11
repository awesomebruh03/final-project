<script>
	// A small rounded pill. tone: 'red' | 'green' | 'grey' | 'blue' | 'teal'
	const { tone = 'grey', children } = $props();

	const tones = {
		red: 'bg-red-50 text-red-700',
		green: 'bg-emerald-50 text-emerald-700',
		grey: 'bg-stone-100 text-muted',
		blue: 'bg-blue-50 text-blue-700',
		teal: 'bg-brand-50 text-brand-700'
	};
</script>

<span
	class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap {tones[
		tone
	] ?? tones.grey}"
>
	{@render children()}
</span>
