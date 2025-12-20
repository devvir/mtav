#!/usr/bin/env python3
# Copilot - Pending review
"""
GLPK Benchmark Statistics Analyzer

This module provides statistical analysis functions for GLPK solver benchmark data.
It computes various metrics including mean, median, standard deviation, percentiles,
success rates, and timeout analysis.
"""

import pandas as pd
import numpy as np
from typing import Dict, List, Optional, Tuple
from pathlib import Path
import json


class BenchmarkAnalyzer:
    """
    Analyzer for GLPK benchmark CSV files.

    Provides comprehensive statistical analysis of solver performance including
    time distributions, success rates, timeout patterns, and outlier detection.
    """

    def __init__(self, csv_path: str):
        """
        Initialize analyzer with a benchmark CSV file.

        Args:
            csv_path: Path to the benchmark CSV file
        """
        self.csv_path = Path(csv_path)
        self.df = pd.read_csv(csv_path)
        self._validate_data()

    def _validate_data(self):
        """Validate that required columns exist."""
        required_cols = ['size', 'scenario', 'iteration', 'time_ms', 'status']
        missing = [col for col in required_cols if col not in self.df.columns]
        if missing:
            raise ValueError(f"Missing required columns: {missing}")

    def compute_stats(self) -> Dict[str, float]:
        """
        Compute comprehensive statistics for execution times.

        Returns:
            Dictionary containing:
                - time_mean: Mean execution time (ms)
                - time_median: Median execution time (ms)
                - time_std: Standard deviation (ms)
                - time_min: Minimum time (ms)
                - time_max: Maximum time (ms)
                - time_p25, time_p50, time_p75: Quartiles
                - time_p90, time_p95, time_p99: High percentiles
                - success_rate: Proportion of successful runs
                - timeout_rate: Proportion of timeouts
                - failed_rate: Proportion of failures
                - total_runs: Total number of runs
        """
        successful = self.df[self.df['status'] == 'SUCCESS']
        times = successful['time_ms']

        stats = {
            # Basic statistics
            'time_mean': float(times.mean()),
            'time_median': float(times.median()),
            'time_std': float(times.std()),
            'time_min': float(times.min()),
            'time_max': float(times.max()),

            # Quartiles
            'time_p25': float(times.quantile(0.25)),
            'time_p50': float(times.quantile(0.50)),
            'time_p75': float(times.quantile(0.75)),

            # High percentiles (for outlier analysis)
            'time_p90': float(times.quantile(0.90)),
            'time_p95': float(times.quantile(0.95)),
            'time_p99': float(times.quantile(0.99)),

            # Status rates
            'success_rate': float((self.df['status'] == 'SUCCESS').mean()),
            'timeout_rate': float((self.df['status'] == 'TIMEOUT').mean()),
            'failed_rate': float((self.df['status'] == 'FAILED').mean()),
            'infeasible_rate': float((self.df['status'] == 'INFEASIBLE').mean()),

            # Counts
            'total_runs': len(self.df),
            'success_count': int((self.df['status'] == 'SUCCESS').sum()),
            'timeout_count': int((self.df['status'] == 'TIMEOUT').sum()),
            'failed_count': int((self.df['status'] == 'FAILED').sum()),
            'infeasible_count': int((self.df['status'] == 'INFEASIBLE').sum()),
        }

        # Coefficient of variation (normalized dispersion)
        if stats['time_mean'] > 0:
            stats['time_cv'] = stats['time_std'] / stats['time_mean']
        else:
            stats['time_cv'] = 0.0

        # Interquartile range
        stats['time_iqr'] = stats['time_p75'] - stats['time_p25']

        # Skewness (if scipy available)
        try:
            from scipy import stats as scipy_stats
            stats['time_skewness'] = float(scipy_stats.skew(times))
            stats['time_kurtosis'] = float(scipy_stats.kurtosis(times))
        except ImportError:
            pass

        return stats

    def analyze_timeouts(self) -> Dict:
        """
        Analyze timeout patterns and characteristics.

        Returns:
            Dictionary containing:
                - timeout_iterations: List of iterations that timed out
                - timeout_count: Total number of timeouts
                - first_timeout: Iteration number of first timeout
                - timeout_clustering: Analysis of temporal clustering
        """
        timeouts = self.df[self.df['status'] == 'TIMEOUT']
        timeout_iters = sorted(timeouts['iteration'].tolist())

        result = {
            'timeout_iterations': timeout_iters,
            'timeout_count': len(timeout_iters),
            'first_timeout': timeout_iters[0] if timeout_iters else None,
        }

        # Analyze clustering (gaps between consecutive timeouts)
        if len(timeout_iters) > 1:
            gaps = np.diff(timeout_iters)
            result['timeout_gaps_mean'] = float(np.mean(gaps))
            result['timeout_gaps_median'] = float(np.median(gaps))
            result['timeout_gaps_min'] = int(np.min(gaps))
            result['timeout_gaps_max'] = int(np.max(gaps))

        return result

    def detect_outliers(self, method: str = 'iqr', threshold: float = 1.5) -> pd.DataFrame:
        """
        Detect outlier execution times.

        Args:
            method: Detection method ('iqr' or 'zscore')
            threshold: Threshold for outlier detection (1.5 for IQR, 3 for zscore)

        Returns:
            DataFrame of outlier rows
        """
        successful = self.df[self.df['status'] == 'SUCCESS'].copy()
        times = successful['time_ms']

        if method == 'iqr':
            q1 = times.quantile(0.25)
            q3 = times.quantile(0.75)
            iqr = q3 - q1
            lower = q1 - threshold * iqr
            upper = q3 + threshold * iqr
            outliers = successful[(times < lower) | (times > upper)]

        elif method == 'zscore':
            from scipy import stats
            z_scores = np.abs(stats.zscore(times))
            outliers = successful[z_scores > threshold]

        else:
            raise ValueError(f"Unknown method: {method}")

        return outliers.sort_values('time_ms', ascending=False)

    def get_time_distribution(self, bins: int = 50) -> Tuple[np.ndarray, np.ndarray]:
        """
        Get histogram data for time distribution.

        Args:
            bins: Number of histogram bins

        Returns:
            Tuple of (bin_edges, counts)
        """
        successful = self.df[self.df['status'] == 'SUCCESS']
        counts, edges = np.histogram(successful['time_ms'], bins=bins)
        return edges, counts

    def get_summary_text(self) -> str:
        """
        Generate a formatted text summary of statistics.

        Returns:
            Multi-line string with formatted statistics
        """
        stats = self.compute_stats()
        size = self.df['size'].iloc[0]
        scenario = self.df['scenario'].iloc[0]

        summary = f"""
=== GLPK Benchmark Statistics ===
File: {self.csv_path.name}
Size: {size}x{size}
Scenario: {scenario}
Total Runs: {stats['total_runs']}

Time Statistics (ms):
  Mean:        {stats['time_mean']:>12.2f}
  Median:      {stats['time_median']:>12.2f}
  Std Dev:     {stats['time_std']:>12.2f}
  Min:         {stats['time_min']:>12.2f}
  Max:         {stats['time_max']:>12.2f}
  IQR:         {stats['time_iqr']:>12.2f}
  CV:          {stats['time_cv']:>12.4f}

Percentiles (ms):
  P25:         {stats['time_p25']:>12.2f}
  P50:         {stats['time_p50']:>12.2f}
  P75:         {stats['time_p75']:>12.2f}
  P90:         {stats['time_p90']:>12.2f}
  P95:         {stats['time_p95']:>12.2f}
  P99:         {stats['time_p99']:>12.2f}

Status Breakdown:
  SUCCESS:     {stats['success_count']:>6} ({stats['success_rate']:>6.2%})
  TIMEOUT:     {stats['timeout_count']:>6} ({stats['timeout_rate']:>6.2%})
  FAILED:      {stats['failed_count']:>6} ({stats['failed_rate']:>6.2%})
  INFEASIBLE:  {stats['infeasible_count']:>6} ({stats['infeasible_rate']:>6.2%})
"""

        # Add timeout analysis if any exist
        if stats['timeout_count'] > 0:
            timeout_info = self.analyze_timeouts()
            summary += f"""
Timeout Analysis:
  First timeout at iteration: {timeout_info['first_timeout']}
  Timeout count: {timeout_info['timeout_count']}
"""
            if 'timeout_gaps_mean' in timeout_info:
                summary += f"""  Mean gap between timeouts: {timeout_info['timeout_gaps_mean']:.1f}
  Median gap: {timeout_info['timeout_gaps_median']:.1f}
"""

        return summary

    def export_stats_json(self, output_path: str):
        """Export statistics as JSON file."""
        stats = self.compute_stats()
        with open(output_path, 'w') as f:
            json.dump(stats, f, indent=2)

    def export_timeouts_csv(self, output_path: str):
        """Export timeout cases to separate CSV."""
        timeouts = self.df[self.df['status'] == 'TIMEOUT']
        timeouts.to_csv(output_path, index=False)


def compare_sizes(csv_paths: List[str]) -> pd.DataFrame:
    """
    Compare statistics across multiple benchmark files (typically different sizes).

    Args:
        csv_paths: List of paths to benchmark CSV files

    Returns:
        DataFrame with comparative statistics
    """
    results = []

    for path in csv_paths:
        analyzer = BenchmarkAnalyzer(path)
        stats = analyzer.compute_stats()

        # Add identifiers
        stats['file'] = Path(path).name
        stats['size'] = analyzer.df['size'].iloc[0]
        stats['scenario'] = analyzer.df['scenario'].iloc[0]

        results.append(stats)

    df = pd.DataFrame(results)
    return df.sort_values('size')


if __name__ == '__main__':
    # Example usage
    import sys

    if len(sys.argv) < 2:
        print("Usage: python benchmark_stats.py <csv_file>")
        sys.exit(1)

    analyzer = BenchmarkAnalyzer(sys.argv[1])
    print(analyzer.get_summary_text())
