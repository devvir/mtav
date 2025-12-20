#!/usr/bin/env python3
# Copilot - Pending review
"""
GLPK Benchmark Comparison Tools

This module provides utilities for comparing multiple benchmark runs,
analyzing size scaling, and detecting performance regressions.
"""

import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns
from typing import List, Dict, Optional
from pathlib import Path
from benchmark_stats import BenchmarkAnalyzer


class BenchmarkComparer:
    """
    Comparer for multiple GLPK benchmark files.

    Enables analysis of size scaling, scenario comparison, and performance trends.
    """

    def __init__(self, csv_paths: List[str]):
        """
        Initialize comparer with multiple benchmark CSV files.

        Args:
            csv_paths: List of paths to benchmark CSV files
        """
        self.csv_paths = [Path(p) for p in csv_paths]
        self.analyzers = {path: BenchmarkAnalyzer(str(path)) for path in self.csv_paths}

    def compare_all(self) -> pd.DataFrame:
        """
        Generate comprehensive comparison across all files.

        Returns:
            DataFrame with comparative statistics for all files
        """
        results = []

        for path, analyzer in self.analyzers.items():
            stats = analyzer.compute_stats()

            # Add file metadata
            stats['file'] = path.name
            stats['size'] = analyzer.df['size'].iloc[0]
            stats['scenario'] = analyzer.df['scenario'].iloc[0]

            results.append(stats)

        df = pd.DataFrame(results)
        return df.sort_values(['scenario', 'size'])

    def analyze_size_scaling(self, scenario: str = 'random') -> Dict:
        """
        Analyze how performance scales with problem size.

        Args:
            scenario: Scenario type to analyze

        Returns:
            Dictionary with scaling analysis
        """
        # Filter to specific scenario
        scenario_analyzers = {
            path: analyzer for path, analyzer in self.analyzers.items()
            if analyzer.df['scenario'].iloc[0] == scenario
        }

        if not scenario_analyzers:
            raise ValueError(f"No files found for scenario: {scenario}")

        sizes = []
        means = []
        medians = []
        maxes = []
        success_rates = []
        timeout_rates = []

        for path in sorted(scenario_analyzers.keys(),
                          key=lambda p: scenario_analyzers[p].df['size'].iloc[0]):
            analyzer = scenario_analyzers[path]
            stats = analyzer.compute_stats()

            sizes.append(analyzer.df['size'].iloc[0])
            means.append(stats['time_mean'])
            medians.append(stats['time_median'])
            maxes.append(stats['time_max'])
            success_rates.append(stats['success_rate'])
            timeout_rates.append(stats['timeout_rate'])        # Fit polynomial for scaling prediction
        if len(sizes) >= 3:
            # Fit quadratic model: time = a*size^2 + b*size + c
            coeffs = np.polyfit(sizes, means, 2)

            return {
                'scenario': scenario,
                'sizes': sizes,
                'mean_times': means,
                'median_times': medians,
                'max_times': maxes,
                'success_rates': success_rates,
                'timeout_rates': timeout_rates,
                'poly_coeffs': coeffs.tolist(),
                'scaling_order': 'quadratic' if abs(coeffs[0]) > 0.01 else 'linear'
            }
        else:
            return {
                'scenario': scenario,
                'sizes': sizes,
                'mean_times': means,
                'median_times': medians,
                'max_times': maxes,
                'success_rates': success_rates,
                'timeout_rates': timeout_rates,
            }

    def predict_time(self, size: int, scenario: str = 'random') -> Dict[str, float]:
        """
        Predict execution time for a given problem size.

        Args:
            size: Problem size (NxN)
            scenario: Scenario type

        Returns:
            Dictionary with predicted metrics
        """
        scaling = self.analyze_size_scaling(scenario)

        if 'poly_coeffs' not in scaling:
            raise ValueError("Need at least 3 data points for prediction")

        coeffs = scaling['poly_coeffs']
        predicted_mean = coeffs[0] * size**2 + coeffs[1] * size + coeffs[2]

        # Estimate timeout rate using logistic regression on existing data
        if len(scaling['sizes']) >= 2:
            # Simple linear extrapolation for timeout rate
            sizes = np.array(scaling['sizes'])
            timeout_rates = np.array(scaling['timeout_rates'])

            if len(sizes) >= 2 and timeout_rates[-1] > 0:
                # Fit linear model in log space for exponential growth
                log_rates = np.log(timeout_rates[timeout_rates > 0] + 1e-6)
                size_subset = sizes[timeout_rates > 0]

                if len(size_subset) >= 2:
                    slope = (log_rates[-1] - log_rates[0]) / (size_subset[-1] - size_subset[0])
                    intercept = log_rates[0] - slope * size_subset[0]
                    predicted_timeout_rate = min(1.0, np.exp(slope * size + intercept))
                else:
                    predicted_timeout_rate = timeout_rates[-1]
            else:
                predicted_timeout_rate = 0.0
        else:
            predicted_timeout_rate = 0.0

        return {
            'size': size,
            'predicted_mean_time': predicted_mean,
            'predicted_timeout_rate': predicted_timeout_rate,
            'prediction_based_on': f"{len(scaling['sizes'])} data points",
            'model': 'quadratic' if coeffs[0] > 0.01 else 'linear'
        }

    def plot_scaling_analysis(
        self,
        scenario: str = 'random',
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Plot size scaling analysis with multiple metrics.

        Args:
            scenario: Scenario type to plot
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        scaling = self.analyze_size_scaling(scenario)

        fig, axes = plt.subplots(2, 2, figsize=(14, 10))

        # Mean execution time
        ax = axes[0, 0]
        ax.plot(scaling['sizes'], scaling['mean_times'], 'o-', linewidth=2, markersize=8)

        # Add polynomial fit if available
        if 'poly_coeffs' in scaling:
            x_smooth = np.linspace(min(scaling['sizes']), max(scaling['sizes']), 100)
            y_smooth = np.polyval(scaling['poly_coeffs'], x_smooth)
            ax.plot(x_smooth, y_smooth, '--', alpha=0.5, label='Polynomial fit')
            ax.legend()

        ax.set_xlabel('Problem Size (NxN)', fontsize=11)
        ax.set_ylabel('Mean Time (ms)', fontsize=11)
        ax.set_title('Mean Execution Time Scaling', fontweight='bold')
        ax.grid(True, alpha=0.3)

        # Median execution time
        ax = axes[0, 1]
        ax.plot(scaling['sizes'], scaling['median_times'], 'o-',
                linewidth=2, markersize=8, color='green')
        ax.set_xlabel('Problem Size (NxN)', fontsize=11)
        ax.set_ylabel('Median Time (ms)', fontsize=11)
        ax.set_title('Median Execution Time Scaling', fontweight='bold')
        ax.grid(True, alpha=0.3)

        # Max execution time (log scale)
        ax = axes[1, 0]
        ax.plot(scaling['sizes'], scaling['max_times'], 'o-',
                linewidth=2, markersize=8, color='orange')
        ax.set_xlabel('Problem Size (NxN)', fontsize=11)
        ax.set_ylabel('Max Time (ms)', fontsize=11)
        ax.set_title('Maximum Execution Time Scaling', fontweight='bold')
        ax.set_yscale('log')
        ax.grid(True, alpha=0.3)

        # Timeout rate
        ax = axes[1, 1]
        timeout_pct = [r * 100 for r in scaling['timeout_rates']]
        ax.plot(scaling['sizes'], timeout_pct, 'o-',
                linewidth=2, markersize=8, color='red')
        ax.set_xlabel('Problem Size (NxN)', fontsize=11)
        ax.set_ylabel('Timeout Rate (%)', fontsize=11)
        ax.set_title('Timeout Rate Scaling', fontweight='bold')
        ax.grid(True, alpha=0.3)

        plt.suptitle(f'GLPK Performance Scaling Analysis ({scenario})',
                     fontsize=14, fontweight='bold', y=1.00)
        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()

    def get_size_comparison(self, size: int) -> Dict:
        """
        Get comparison data for all scenarios at a specific problem size.

        Args:
            size: Problem size to compare

        Returns:
            Dictionary with comparative statistics for all scenarios
        """
        size_analyzers = {
            path: analyzer for path, analyzer in self.analyzers.items()
            if analyzer.df['size'].iloc[0] == size
        }

        if not size_analyzers:
            raise ValueError(f"No files found for size: {size}")

        results = []
        for path, analyzer in sorted(size_analyzers.items()):
            stats = analyzer.compute_stats()
            stats['file'] = path.name
            stats['scenario'] = analyzer.df['scenario'].iloc[0]
            results.append(stats)

        return {
            'size': size,
            'scenarios': results,
            'scenario_names': sorted([r['scenario'] for r in results])
        }

    def compare_scenarios(
        self,
        size: int,
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Compare different scenarios at the same problem size.

        Args:
            size: Problem size to compare
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        # Filter to specific size
        size_analyzers = {
            path: analyzer for path, analyzer in self.analyzers.items()
            if analyzer.df['size'].iloc[0] == size
        }

        if not size_analyzers:
            raise ValueError(f"No files found for size: {size}")

        scenarios = []
        means = []
        medians = []
        stds = []
        success_rates = []
        timeout_rates = []
        max_times = []

        for path, analyzer in sorted(size_analyzers.items()):
            stats = analyzer.compute_stats()
            scenario_name = analyzer.df['scenario'].iloc[0]
            scenarios.append(scenario_name)
            means.append(stats['time_mean'])
            medians.append(stats['time_median'])
            stds.append(stats['time_std'])
            success_rates.append(stats['success_rate'] * 100)
            timeout_rates.append(stats['timeout_rate'] * 100)
            max_times.append(stats['time_max'])

        x = np.arange(len(scenarios))
        width = 0.35

        fig, axes = plt.subplots(2, 2, figsize=(14, 10))

        # Mean and median comparison
        ax = axes[0, 0]
        ax.bar(x - width/2, means, width, label='Mean', alpha=0.8)
        ax.bar(x + width/2, medians, width, label='Median', alpha=0.8)
        ax.set_xlabel('Scenario', fontsize=11)
        ax.set_ylabel('Time (ms)', fontsize=11)
        ax.set_title(f'Execution Time by Scenario ({size}x{size})', fontweight='bold')
        ax.set_xticks(x)
        ax.set_xticklabels(scenarios)
        ax.legend()
        ax.grid(True, alpha=0.3, axis='y')

        # Success rate comparison
        ax = axes[0, 1]
        ax.bar(scenarios, success_rates, alpha=0.8, color='green')
        ax.set_xlabel('Scenario', fontsize=11)
        ax.set_ylabel('Success Rate (%)', fontsize=11)
        ax.set_title(f'Success Rate by Scenario ({size}x{size})', fontweight='bold')
        ax.set_ylim(0, 105)
        ax.grid(True, alpha=0.3, axis='y')

        # Timeout rate comparison
        ax = axes[1, 0]
        ax.bar(scenarios, timeout_rates, alpha=0.8, color='red')
        ax.set_xlabel('Scenario', fontsize=11)
        ax.set_ylabel('Timeout Rate (%)', fontsize=11)
        ax.set_title(f'Timeout Rate by Scenario ({size}x{size})', fontweight='bold')
        ax.set_ylim(0, 105)
        ax.grid(True, alpha=0.3, axis='y')

        # Max time comparison (log scale)
        ax = axes[1, 1]
        ax.bar(scenarios, max_times, alpha=0.8, color='orange')
        ax.set_xlabel('Scenario', fontsize=11)
        ax.set_ylabel('Max Time (ms)', fontsize=11)
        ax.set_title(f'Maximum Execution Time by Scenario ({size}x{size})', fontweight='bold')
        ax.set_yscale('log')
        ax.grid(True, alpha=0.3, axis='y')

        plt.suptitle(f'Scenario Comparison - Size {size}x{size}',
                     fontsize=14, fontweight='bold', y=1.00)
        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()


if __name__ == '__main__':
    # Example usage
    import sys

    if len(sys.argv) < 3:
        print("Usage: python benchmark_compare.py <csv_file1> <csv_file2> [csv_file3 ...]")
        sys.exit(1)

    comparer = BenchmarkComparer(sys.argv[1:])
    comparison = comparer.compare_all()

    print("=== Benchmark Comparison ===\n")
    print(comparison[['size', 'scenario', 'time_mean', 'time_median',
                      'success_rate', 'timeout_rate']].to_string(index=False))

    # Try scaling analysis for random scenario
    try:
        print("\n=== Size Scaling Analysis (random) ===\n")
        scaling = comparer.analyze_size_scaling('random')
        print(f"Sizes analyzed: {scaling['sizes']}")
        print(f"Mean times: {[f'{t:.1f}ms' for t in scaling['mean_times']]}")

        if 'poly_coeffs' in scaling:
            print(f"\nScaling model: {scaling['scaling_order']}")

            # Predict for next size
            next_size = max(scaling['sizes']) + 5
            prediction = comparer.predict_time(next_size, 'random')
            print(f"\nPrediction for {next_size}x{next_size}:")
            print(f"  Mean time: {prediction['predicted_mean_time']:.1f}ms")
            print(f"  Timeout rate: {prediction['predicted_timeout_rate']:.2%}")
    except ValueError as e:
        print(f"Scaling analysis not available: {e}")
