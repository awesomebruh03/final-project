<script>
	import Logo from '#lib/components/Logo.svelte';
	import { auth } from '#lib/auth.svelte.js';
	import { resolve } from '$app/paths';

	// Static sample bars for the preview card (heights in %).
	const sampleBars = [40, 62, 28, 78, 50, 100];
</script>

<div class="min-h-screen bg-canvas">
	<div class="mx-auto max-w-[1180px] px-6 py-8 sm:px-8">
		<header class="flex items-center justify-between">
			<a href={resolve('/')} aria-label="Spendly home"><Logo /></a>
			{#if auth.user}
				<a href={resolve('/tracker')} class="btn-outline !px-5 !py-3">Open tracker</a>
			{:else}
				<a href={resolve('/auth')} class="btn-outline !px-5 !py-3">Log in</a>
			{/if}
		</header>

		<main class="grid items-center gap-14 pt-16 pb-16 md:grid-cols-2 md:pt-24">
			<div>
				<h1 class="text-5xl leading-[1.05] font-extrabold tracking-tight text-ink sm:text-6xl">
					Know where your money goes.
				</h1>
				<p class="mt-6 max-w-md text-xl leading-relaxed text-muted">
					Log expenses in seconds, see spending by category, and watch your monthly totals over
					time.
				</p>

				<div class="mt-8 flex flex-wrap gap-3">
					{#if auth.user}
						<a href={resolve('/tracker')} class="btn-primary">Go to your tracker</a>
					{:else}
						<a href={resolve('/auth?mode=register')} class="btn-primary">Create free account</a>
						<a href={resolve('/auth')} class="btn-outline">Log in</a>
					{/if}
				</div>
			</div>

			<!-- Decorative sample preview -->
			<div class="card p-8" aria-hidden="true">
				<p class="text-muted">November spending</p>
				<p class="mt-5 text-5xl font-extrabold tracking-tight text-ink">$1,240.50</p>

				<div class="mt-8 flex h-40 items-end gap-4">
					{#each sampleBars as height, i (i)}
						<div
							class="flex-1 rounded-lg {i === sampleBars.length - 1
								? 'bg-brand-600'
								: 'bg-brand-100'}"
							style="height: {height * 0.7}%"
						></div>
					{/each}
				</div>

				<p class="mt-6 text-sm text-muted">Sample preview</p>
			</div>
		</main>
	</div>
</div>
