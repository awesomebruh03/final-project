<script>
	import Panel from '#lib/components/Panel.svelte';
	import Badge from '#lib/components/Badge.svelte';
	import ChangeBadge from '#lib/components/ChangeBadge.svelte';
	import BarChart from '#lib/components/BarChart.svelte';
	import ProgressRow from '#lib/components/ProgressRow.svelte';
	import { data } from '#lib/data.svelte.js';
	import { view } from '#lib/view.svelte.js';
	import {
		monthSummary,
		monthlyTotals,
		bestWorst,
		yearToDate,
		dayOfWeek,
		weekOfMonth,
		pctChange
	} from '#lib/analytics.js';
	import {
		addMonths,
		formatMoney,
		formatMoneyRound,
		formatDate,
		formatTime,
		monthName,
		monthShort
	} from '#lib/format.js';
	import { resolve } from '$app/paths';

	const TEAL = '#0f766e';
	const DEEP = '#0b2b29';
	const AMBER = '#d97706';

	// ----- Numbers for the selected month and the month before it -----
	const summary = $derived(monthSummary(data.expenses, view.month));
	const prevKey = $derived(addMonths(view.month, -1));
	const prev = $derived(monthSummary(data.expenses, prevKey));
	const name = $derived(monthName(view.month));
	const prevName = $derived(monthName(prevKey));
	const prevShort = $derived(monthShort(prevKey));

	const months = $derived(monthlyTotals(data.expenses, view.month, view.range));
	const extremes = $derived(bestWorst(months));
	const rangeAverage = $derived(
		months.reduce((acc, month) => acc + month.total, 0) / months.length
	);
	const ytd = $derived(yearToDate(data.expenses, view.month));

	// ----- Daily spend -----
	let selectedDay = $state(-1);
	$effect(() => {
		// Forget the clicked day when the month changes.
		view.month;
		selectedDay = -1;
	});

	function dayLabel(index) {
		const day = index + 1;
		return day % 5 === 1 || day === summary.daysInMonth ? String(day) : '';
	}

	const dailyBars = $derived(
		summary.daily.map((value, i) => ({
			label: dayLabel(i),
			value,
			// Days that have not happened yet stay empty.
			color: i < summary.elapsed ? TEAL : 'transparent',
			title: `${monthShort(view.month)} ${i + 1}: ${formatMoney(value)}`
		}))
	);

	function selectDay(index) {
		selectedDay = selectedDay === index ? -1 : index;
	}

	const dayItems = $derived(
		selectedDay === -1
			? []
			: summary.items.filter((item) => Number(item.date.slice(8, 10)) === selectedDay + 1)
	);

	// ----- Monthly trend -----
	const trendBars = $derived(
		months.map((month) => {
			let color = TEAL;
			if (extremes && month.key === extremes.best.key) color = AMBER;
			if (month.key === view.month) color = DEEP;
			return {
				label: month.label,
				value: month.total,
				color,
				title: `${month.label}: ${formatMoney(month.total)}`
			};
		})
	);

	// ----- Day of week -----
	const dow = $derived(dayOfWeek(summary, view.month));
	const dowBars = $derived(
		dow.rows.map((row, i) => ({
			label: row.label,
			value: row.total,
			color: i === dow.top ? DEEP : TEAL,
			title: `${row.label}: ${formatMoney(row.total)}`
		}))
	);

	// ----- Week of the month -----
	const weeks = $derived(weekOfMonth(summary));
	const topWeek = $derived(
		weeks.reduce((best, w, i) => (w.total > weeks[best].total ? i : best), 0)
	);
	const weekBars = $derived(
		weeks.map((week, i) => ({
			label: week.label,
			value: week.total,
			color: i === topWeek && week.total > 0 ? DEEP : TEAL,
			title: `${week.label}: ${formatMoney(week.total)}`
		}))
	);
	const notStarted = $derived(weeks.find((week) => !week.started));

	// ----- Compared with last month -----
	const compare = $derived([
		{
			label: 'Total spent',
			value: formatMoney(summary.total),
			cur: summary.total,
			prev: prev.total,
			delta: pctChange(summary.total, prev.total),
			kind: 'pct',
			goodWhen: 'down'
		},
		{
			label: 'Number of expenses',
			value: String(summary.count),
			cur: summary.count,
			prev: prev.count,
			delta: prev.count > 0 ? summary.count - prev.count : null,
			kind: 'count',
			goodWhen: null
		},
		{
			label: 'Avg expense',
			value: formatMoney(summary.avgExpense),
			cur: summary.avgExpense,
			prev: prev.avgExpense,
			delta: pctChange(summary.avgExpense, prev.avgExpense),
			kind: 'pct',
			goodWhen: 'down'
		},
		{
			label: 'No-spend days',
			value: String(summary.noSpendDays),
			cur: summary.noSpendDays,
			prev: prev.noSpendDays,
			delta: prev.elapsed > 0 ? summary.noSpendDays - prev.noSpendDays : null,
			kind: 'count',
			goodWhen: 'up'
		}
	]);

	function barPercent(row) {
		const biggest = Math.max(row.cur, row.prev);
		return biggest > 0 ? (row.cur / biggest) * 100 : 0;
	}

	const smallCards = $derived([
		{ label: 'Largest expense', value: formatMoney(summary.largest) },
		{ label: 'Avg per day', value: formatMoney(summary.avgPerDay) },
		{ label: 'No-spend days', value: String(summary.noSpendDays) },
		{ label: `${view.range}-mo average`, value: formatMoneyRound(rangeAverage) },
		{ label: 'Year to date', value: formatMoneyRound(ytd) }
	]);
</script>

<div class="space-y-5">
	{#if data.expenses.length === 0}
		<div class="flex flex-wrap items-center justify-between gap-3 card p-6">
			<p class="text-muted">No expenses yet. Add your first one and these charts will fill up.</p>
			<a href={resolve('/tracker/expenses?add=1')} class="btn-primary py-2.5!">Add expense</a>
		</div>
	{/if}

	<!-- Three headline cards -->
	<div class="grid gap-5 lg:grid-cols-3">
		<div class="card p-6">
			<p class="text-muted">Spent in {name}</p>
			<p class="mt-2 text-4xl font-extrabold tracking-tight text-ink">
				{formatMoney(summary.total)}
			</p>
			<div class="mt-3 flex min-h-6 flex-wrap gap-2">
				<ChangeBadge
					delta={pctChange(summary.total, prev.total)}
					goodWhen="down"
					arrow
					suffix=" vs {prevShort}"
				/>
			</div>
			<p class="mt-3 text-sm text-muted">{formatMoney(prev.total)} in {prevName}</p>
		</div>

		<div class="card p-6">
			<p class="text-muted">Expenses</p>
			<p class="mt-2 text-4xl font-extrabold tracking-tight text-ink">{summary.count}</p>
			<div class="mt-3 flex min-h-6 flex-wrap gap-2">
				<ChangeBadge
					kind="count"
					delta={prev.count > 0 ? summary.count - prev.count : null}
					arrow
					suffix=" vs {prevShort}"
				/>
				<Badge>Avg {formatMoney(summary.avgExpense)}</Badge>
			</div>
			<p class="mt-3 text-sm text-muted">
				{prev.count}
				{prev.count === 1 ? 'expense' : 'expenses'} in {prevName}
			</p>
		</div>

		<div class="card p-6">
			<p class="text-muted">Projected month-end</p>
			<p class="mt-2 text-4xl font-extrabold tracking-tight text-ink">
				{formatMoney(summary.projected)}
			</p>
			<div class="mt-3 flex min-h-6 flex-wrap gap-2">
				<Badge>At {formatMoney(summary.avgPerDay)} / day</Badge>
			</div>
			<p class="mt-3 text-sm text-muted">
				{summary.elapsed < summary.daysInMonth
					? `Based on ${summary.daysInMonth} days at the current pace`
					: 'This month is complete'}
			</p>
		</div>
	</div>

	<!-- Five small cards -->
	<div class="grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-5">
		{#each smallCards as card (card.label)}
			<div class="card rounded-2xl p-5">
				<p class="text-sm text-muted">{card.label}</p>
				<p class="mt-1 text-2xl font-extrabold tracking-tight text-ink">{card.value}</p>
			</div>
		{/each}
	</div>

	<div class="card rounded-2xl p-5">
		<p class="text-sm text-muted">Best / worst</p>
		<p class="mt-1 text-2xl font-extrabold tracking-tight text-ink">
			{extremes ? `${extremes.best.label} / ${extremes.worst.label}` : '—'}
		</p>
	</div>

	<!-- Daily spend -->
	<Panel title="Daily spend" hint="Click a bar for that day">
		<BarChart bars={dailyBars} height={190} gap={4} onselect={selectDay} selected={selectedDay} />

		<div class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted">
			<span>Average: {formatMoney(summary.avgPerDay)} / day</span>
			<span>Gaps are no-spend days</span>
		</div>

		{#if selectedDay !== -1}
			{@const date = view.month + '-' + String(selectedDay + 1).padStart(2, '0')}
			<div class="mt-4 rounded-2xl bg-stone-50 p-4">
				<p class="font-semibold text-ink">
					{formatDate(date)} · {formatMoney(summary.daily[selectedDay])}
				</p>
				{#if dayItems.length === 0}
					<p class="mt-1 text-sm text-muted">No spending this day.</p>
				{:else}
					<ul class="mt-2 space-y-1 text-sm">
						{#each dayItems as item (item.id)}
							<li class="flex justify-between gap-3">
								<span class="truncate text-ink">
									{item.note || item.category}
									<span class="text-muted">
										· {item.category}{item.time ? ' · ' + formatTime(item.time) : ''}
									</span>
								</span>
								<span class="font-semibold text-ink">{formatMoney(item.amount)}</span>
							</li>
						{/each}
					</ul>
				{/if}
			</div>
		{/if}
	</Panel>

	<!-- Monthly trend + day of week -->
	<div class="grid gap-5 lg:grid-cols-[3fr_2fr]">
		<Panel title="Monthly trend" hint="Range: last {view.range} months">
			<BarChart bars={trendBars} height={170} gap={view.range > 6 ? 8 : 16} />
			{#if extremes}
				<div class="mt-4 flex flex-wrap gap-2">
					<Badge tone="green"
						>Best: {extremes.best.label} {formatMoneyRound(extremes.best.total)}</Badge
					>
					<Badge tone="red"
						>Worst: {extremes.worst.label} {formatMoneyRound(extremes.worst.total)}</Badge
					>
				</div>
			{/if}
		</Panel>

		<Panel title="Day of week">
			<BarChart bars={dowBars} height={170} gap={10} />
			<p class="mt-4 text-sm text-ink">
				{#if dow.top === -1}
					No spending yet this month.
				{:else}
					You spend most on <strong>{dow.rows[dow.top].longLabel}</strong>
					(avg {formatMoneyRound(dow.rows[dow.top].avg)}).
				{/if}
			</p>
		</Panel>
	</div>

	<!-- Week of the month + comparison -->
	<div class="grid gap-5 lg:grid-cols-[4fr_5fr]">
		<Panel title="Week of the month" hint={notStarted ? `${notStarted.label} not started` : ''}>
			<BarChart bars={weekBars} height={150} gap={14} />
		</Panel>

		<Panel title="Compared with last month">
			<div class="space-y-4">
				{#each compare as row (row.label)}
					<ProgressRow
						label={row.label}
						value={row.value}
						percent={barPercent(row)}
						barColor={TEAL}
					>
						{#snippet badge()}
							<ChangeBadge delta={row.delta} kind={row.kind} goodWhen={row.goodWhen} />
						{/snippet}
					</ProgressRow>
				{/each}
			</div>
		</Panel>
	</div>
</div>
