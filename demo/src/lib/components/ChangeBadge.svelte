<script>
	import Badge from './Badge.svelte';

	// Shows a change like "+12%" or "-2" in a coloured pill.
	//   delta:    the change (a percent when kind is 'pct', a plain count otherwise)
	//   goodWhen: 'up' | 'down' | null - which direction is good news.
	//             null = neutral grey. Bad news is red, good news is green.
	//   arrow:    show a little triangle in front
	//   suffix:   extra text, e.g. ' vs Oct'
	const { delta, kind = 'pct', goodWhen = null, arrow = false, suffix = '' } = $props();

	const valid = $derived(delta !== null && delta !== undefined && !Number.isNaN(delta));
	const rounded = $derived(valid ? Math.round(delta) : 0);

	const tone = $derived.by(() => {
		if (rounded === 0 || !goodWhen) return 'grey';
		const wentUp = rounded > 0;
		return wentUp === (goodWhen === 'up') ? 'green' : 'red';
	});
	const text = $derived((rounded > 0 ? '+' : '') + rounded + (kind === 'pct' ? '%' : ''));
</script>

{#if valid}
	<Badge {tone}>
		{#if arrow && rounded !== 0}<span class="text-[0.6rem]">{rounded > 0 ? '▲' : '▼'}</span>{/if}
		{text}{suffix}
	</Badge>
{/if}
