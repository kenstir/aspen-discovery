<?php

class ReadingHistorySummary {
	public int $totalYearlyCheckouts = 0;
	// Cost Savings formatted for display
	public string $yearlyCostSavings = '';
	public array $monthlyCheckouts = [];

	public int $topMonth;
	public int $maxMonthlyCheckouts;
	public int $averageCheckouts;

}