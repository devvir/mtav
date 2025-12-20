#!/usr/bin/env python3
# Copilot - Pending review
"""
GLPK Benchmark Visualization Tools

This module provides visualization functions for GLPK solver benchmark data
using matplotlib and seaborn.
"""

import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns
from typing import Optional, List, Tuple
from pathlib import Path
from benchmark_stats import BenchmarkAnalyzer


class BenchmarkVisualizer:
    """
    Visualizer for GLPK benchmark data.

    Provides various chart types for analyzing solver performance patterns.
    """

    def __init__(self, csv_path: str, style: str = 'seaborn-v0_8-darkgrid'):
        """
        Initialize visualizer with a benchmark CSV file.

        Args:
            csv_path: Path to the benchmark CSV file
            style: Matplotlib style to use
        """
        self.analyzer = BenchmarkAnalyzer(csv_path)
        self.df = self.analyzer.df

        # Set plot style
        try:
            plt.style.use(style)
        except:
            plt.style.use('seaborn-v0_8')

        # Set seaborn theme
        sns.set_palette("husl")

    def plot_time_distribution(
        self,
        bins: int = 50,
        log_scale: bool = False,
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Plot histogram of execution time distribution.

        Args:
            bins: Number of histogram bins
            log_scale: Use logarithmic scale for x-axis
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        successful = self.df[self.df['status'] == 'SUCCESS']

        fig, ax = plt.subplots(figsize=(12, 6))

        ax.hist(successful['time_ms'], bins=bins, edgecolor='black', alpha=0.7)

        if log_scale:
            ax.set_xscale('log')

        ax.set_xlabel('Execution Time (ms)', fontsize=12)
        ax.set_ylabel('Frequency', fontsize=12)
        ax.set_title(
            f'GLPK Execution Time Distribution ({self.df["size"].iloc[0]}x{self.df["size"].iloc[0]}, {self.df["scenario"].iloc[0]})',
            fontsize=14,
            fontweight='bold'
        )

        # Add statistics annotation
        stats = self.analyzer.compute_stats()
        stats_text = f'Mean: {stats["time_mean"]:.1f}ms\nMedian: {stats["time_median"]:.1f}ms\nStd: {stats["time_std"]:.1f}ms'
        ax.text(0.98, 0.97, stats_text, transform=ax.transAxes,
                verticalalignment='top', horizontalalignment='right',
                bbox=dict(boxstyle='round', facecolor='wheat', alpha=0.5))

        ax.grid(True, alpha=0.3)
        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()

    def plot_status_breakdown(
        self,
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Plot pie chart of status breakdown (SUCCESS, TIMEOUT, FAILED).

        Args:
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        status_counts = self.df['status'].value_counts()

        fig, ax = plt.subplots(figsize=(10, 8))

        colors = {
            'SUCCESS': '#2ecc71',
            'TIMEOUT': '#e74c3c',
            'FAILED': '#e67e22',
            'INFEASIBLE': '#95a5a6'
        }

        plot_colors = [colors.get(status, '#3498db') for status in status_counts.index]

        wedges, texts, autotexts = ax.pie(
            status_counts.values,
            labels=status_counts.index,
            autopct='%1.2f%%',
            colors=plot_colors,
            startangle=90,
            textprops={'fontsize': 12}
        )

        # Make percentage text bold
        for autotext in autotexts:
            autotext.set_color('white')
            autotext.set_fontweight('bold')

        ax.set_title(
            f'GLPK Solver Status Breakdown ({self.df["size"].iloc[0]}x{self.df["size"].iloc[0]}, {self.df["scenario"].iloc[0]})',
            fontsize=14,
            fontweight='bold',
            pad=20
        )

        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()

    def plot_time_series(
        self,
        rolling_window: int = 100,
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Plot execution time over iterations (time series).

        Args:
            rolling_window: Window size for rolling average
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        successful = self.df[self.df['status'] == 'SUCCESS'].sort_values('iteration')

        fig, ax = plt.subplots(figsize=(14, 6))

        # Scatter plot of all points
        ax.scatter(successful['iteration'], successful['time_ms'],
                  alpha=0.3, s=10, label='Individual runs')

        # Rolling average
        rolling_mean = successful.set_index('iteration')['time_ms'].rolling(window=rolling_window).mean()
        ax.plot(rolling_mean.index, rolling_mean.values,
               color='red', linewidth=2, label=f'Rolling mean ({rolling_window} runs)')

        # Mark timeouts
        timeouts = self.df[self.df['status'] == 'TIMEOUT']
        if not timeouts.empty:
            ax.scatter(timeouts['iteration'], [ax.get_ylim()[1] * 0.95] * len(timeouts),
                      color='red', marker='x', s=100, label='Timeouts', zorder=5)

        ax.set_xlabel('Iteration', fontsize=12)
        ax.set_ylabel('Execution Time (ms)', fontsize=12)
        ax.set_title(
            f'GLPK Execution Time Over Iterations ({self.df["size"].iloc[0]}x{self.df["size"].iloc[0]}, {self.df["scenario"].iloc[0]})',
            fontsize=14,
            fontweight='bold'
        )
        ax.legend()
        ax.grid(True, alpha=0.3)

        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()

    def _extract_numeric_size(self, label: str) -> Tuple[int, int, str]:
        """
        Extract numeric size from label for natural sorting.
        Returns (width, height, scenario) tuple for sorting.
        """
        parts = label.split(' ')
        size_part = parts[0]  # e.g., "200x200"
        scenario = parts[1].strip('()')  # e.g., "random"

        dimensions = size_part.split('x')
        width = int(dimensions[0])
        height = int(dimensions[1])

        return (width, height, scenario)

    def plot_box_comparison(
        self,
        other_files: Optional[List[str]] = None,
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Plot box plot comparison across different sizes or scenarios.

        Args:
            other_files: List of other CSV files to compare (optional)
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        # Collect data from all files
        data_frames = []

        # Add current file
        current_df = self.df[self.df['status'] == 'SUCCESS'].copy()
        current_df['label'] = f"{current_df['size'].iloc[0]}x{current_df['size'].iloc[0]} ({current_df['scenario'].iloc[0]})"
        current_df['size_numeric'] = current_df['size'].iloc[0]
        data_frames.append(current_df[['label', 'time_ms', 'size_numeric']])

        # Add other files if provided
        if other_files:
            for file_path in other_files:
                analyzer = BenchmarkAnalyzer(file_path)
                df = analyzer.df[analyzer.df['status'] == 'SUCCESS'].copy()
                df['label'] = f"{df['size'].iloc[0]}x{df['size'].iloc[0]} ({df['scenario'].iloc[0]})"
                df['size_numeric'] = df['size'].iloc[0]
                data_frames.append(df[['label', 'time_ms', 'size_numeric']])

        combined = pd.concat(data_frames, ignore_index=True)

        # Sort by numeric size (natural sorting)
        combined = combined.sort_values('size_numeric')

        # Create categorical type with sorted order to preserve x-axis order
        sorted_labels = combined['label'].unique()
        combined['label'] = pd.Categorical(combined['label'], categories=sorted_labels, ordered=True)

        fig, ax = plt.subplots(figsize=(12, 6))

        sns.boxplot(data=combined, x='label', y='time_ms', ax=ax, order=sorted_labels)

        ax.set_xlabel('Configuration', fontsize=12)
        ax.set_ylabel('Execution Time (ms)', fontsize=12)
        ax.set_title('GLPK Execution Time Comparison', fontsize=14, fontweight='bold')
        ax.tick_params(axis='x', rotation=45)
        ax.grid(True, alpha=0.3, axis='y')

        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()

    def plot_box_comparison_log(
        self,
        other_files: Optional[List[str]] = None,
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Plot box plot comparison with logarithmic scale (better for wide ranges).

        Args:
            other_files: List of other CSV files to compare (optional)
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        # Collect data from all files
        data_frames = []

        # Add current file
        current_df = self.df[self.df['status'] == 'SUCCESS'].copy()
        current_df['label'] = f"{current_df['size'].iloc[0]}x{current_df['size'].iloc[0]} ({current_df['scenario'].iloc[0]})"
        current_df['size_numeric'] = current_df['size'].iloc[0]
        data_frames.append(current_df[['label', 'time_ms', 'size_numeric']])

        # Add other files if provided
        if other_files:
            for file_path in other_files:
                analyzer = BenchmarkAnalyzer(file_path)
                df = analyzer.df[analyzer.df['status'] == 'SUCCESS'].copy()
                df['label'] = f"{df['size'].iloc[0]}x{df['size'].iloc[0]} ({df['scenario'].iloc[0]})"
                df['size_numeric'] = df['size'].iloc[0]
                data_frames.append(df[['label', 'time_ms', 'size_numeric']])

        combined = pd.concat(data_frames, ignore_index=True)

        # Sort by numeric size (natural sorting)
        combined = combined.sort_values('size_numeric')

        # Create categorical type with sorted order to preserve x-axis order
        sorted_labels = combined['label'].unique()
        combined['label'] = pd.Categorical(combined['label'], categories=sorted_labels, ordered=True)

        fig, ax = plt.subplots(figsize=(12, 6))

        sns.boxplot(data=combined, x='label', y='time_ms', ax=ax, order=sorted_labels)

        # Apply logarithmic scale to y-axis
        ax.set_yscale('log')

        ax.set_xlabel('Configuration', fontsize=12)
        ax.set_ylabel('Execution Time (ms, log scale)', fontsize=12)
        ax.set_title('GLPK Execution Time Comparison (Logarithmic Scale)', fontsize=14, fontweight='bold')
        ax.tick_params(axis='x', rotation=45)
        ax.grid(True, alpha=0.3, axis='y', which='both')

        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()

    def plot_scatter_time_vs_iteration(
        self,
        highlight_outliers: bool = True,
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Plot scatter of execution time vs iteration to identify outliers.

        Args:
            highlight_outliers: Whether to highlight outliers
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        successful = self.df[self.df['status'] == 'SUCCESS']

        fig, ax = plt.subplots(figsize=(14, 6))

        # Plot all points
        ax.scatter(successful['iteration'], successful['time_ms'],
                  alpha=0.5, s=20, label='Normal runs')

        # Highlight outliers
        if highlight_outliers:
            outliers = self.analyzer.detect_outliers(method='iqr', threshold=1.5)
            if not outliers.empty:
                ax.scatter(outliers['iteration'], outliers['time_ms'],
                          color='red', s=50, alpha=0.7, label='Outliers (IQR)', zorder=5)

        # Mark timeouts
        timeouts = self.df[self.df['status'] == 'TIMEOUT']
        if not timeouts.empty:
            max_time = successful['time_ms'].max()
            ax.scatter(timeouts['iteration'], [max_time * 1.1] * len(timeouts),
                      color='orange', marker='x', s=100, label='Timeouts', zorder=5)

        ax.set_xlabel('Iteration', fontsize=12)
        ax.set_ylabel('Execution Time (ms)', fontsize=12)
        ax.set_title(
            f'GLPK Execution Time Scatter ({self.df["size"].iloc[0]}x{self.df["size"].iloc[0]}, {self.df["scenario"].iloc[0]})',
            fontsize=14,
            fontweight='bold'
        )
        ax.legend()
        ax.grid(True, alpha=0.3)

        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()

    def plot_percentile_comparison(
        self,
        other_files: List[str],
        percentiles: List[float] = [50, 75, 90, 95, 99],
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Compare percentiles across multiple benchmark files.

        Args:
            other_files: List of CSV files to compare
            percentiles: List of percentiles to plot
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        all_files = [self.analyzer.csv_path] + [Path(f) for f in other_files]
        data = []

        for file_path in all_files:
            analyzer = BenchmarkAnalyzer(str(file_path))
            successful = analyzer.df[analyzer.df['status'] == 'SUCCESS']
            size = analyzer.df['size'].iloc[0]

            for p in percentiles:
                value = successful['time_ms'].quantile(p / 100)
                data.append({
                    'size': size,
                    'percentile': f'P{int(p)}',
                    'time_ms': value
                })

        df = pd.DataFrame(data)

        fig, ax = plt.subplots(figsize=(12, 6))

        # Pivot for grouped bar plot
        pivot = df.pivot(index='size', columns='percentile', values='time_ms')

        # Sort index numerically
        pivot = pivot.sort_index()

        pivot.plot(kind='bar', ax=ax, width=0.8)

        ax.set_xlabel('Problem Size (NxN)', fontsize=12)
        ax.set_ylabel('Execution Time (ms)', fontsize=12)
        ax.set_title('GLPK Execution Time Percentiles by Size', fontsize=14, fontweight='bold')
        ax.legend(title='Percentile', bbox_to_anchor=(1.05, 1), loc='upper left')
        ax.grid(True, alpha=0.3, axis='y')
        ax.set_xticklabels(ax.get_xticklabels(), rotation=0)

        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()

    def plot_percentile_comparison_log(
        self,
        other_files: List[str],
        percentiles: List[float] = [50, 75, 90, 95, 99],
        save_path: Optional[str] = None,
        show: bool = True
    ):
        """
        Compare percentiles across multiple benchmark files with logarithmic scale.

        Args:
            other_files: List of CSV files to compare
            percentiles: List of percentiles to plot
            save_path: Path to save figure (optional)
            show: Whether to display the plot
        """
        all_files = [self.analyzer.csv_path] + [Path(f) for f in other_files]
        data = []

        for file_path in all_files:
            analyzer = BenchmarkAnalyzer(str(file_path))
            successful = analyzer.df[analyzer.df['status'] == 'SUCCESS']
            size = analyzer.df['size'].iloc[0]

            for p in percentiles:
                value = successful['time_ms'].quantile(p / 100)
                data.append({
                    'size': size,
                    'percentile': f'P{int(p)}',
                    'time_ms': value
                })

        df = pd.DataFrame(data)

        fig, ax = plt.subplots(figsize=(12, 6))

        # Pivot for grouped bar plot
        pivot = df.pivot(index='size', columns='percentile', values='time_ms')

        # Sort index numerically
        pivot = pivot.sort_index()

        pivot.plot(kind='bar', ax=ax, width=0.8)

        # Apply logarithmic scale to y-axis
        ax.set_yscale('log')

        ax.set_xlabel('Problem Size (NxN)', fontsize=12)
        ax.set_ylabel('Execution Time (ms, log scale)', fontsize=12)
        ax.set_title('GLPK Execution Time Percentiles by Size (Logarithmic Scale)', fontsize=14, fontweight='bold')
        ax.legend(title='Percentile', bbox_to_anchor=(1.05, 1), loc='upper left')
        ax.grid(True, alpha=0.3, axis='y', which='both')
        ax.set_xticklabels(ax.get_xticklabels(), rotation=0)

        plt.tight_layout()

        if save_path:
            plt.savefig(save_path, dpi=300, bbox_inches='tight')

        if show:
            plt.show()
        else:
            plt.close()

    def generate_report(
        self,
        output_dir: str,
        other_files: Optional[List[str]] = None
    ):
        """
        Generate a comprehensive report with all visualizations.

        Args:
            output_dir: Directory to save all plots
            other_files: Optional list of other CSV files for comparison
        """
        output_path = Path(output_dir)
        output_path.mkdir(parents=True, exist_ok=True)

        print(f"Generating report in {output_dir}...")

        # Time distribution
        print("  - Time distribution histogram...")
        self.plot_time_distribution(
            save_path=str(output_path / 'time_distribution.png'),
            show=False
        )

        # Time distribution (log scale)
        print("  - Time distribution histogram (log scale)...")
        self.plot_time_distribution(
            log_scale=True,
            save_path=str(output_path / 'time_distribution_log.png'),
            show=False
        )

        # Status breakdown
        print("  - Status breakdown pie chart...")
        self.plot_status_breakdown(
            save_path=str(output_path / 'status_breakdown.png'),
            show=False
        )

        # Time series
        print("  - Time series plot...")
        self.plot_time_series(
            save_path=str(output_path / 'time_series.png'),
            show=False
        )

        # Scatter plot
        print("  - Scatter plot with outliers...")
        self.plot_scatter_time_vs_iteration(
            save_path=str(output_path / 'scatter_outliers.png'),
            show=False
        )

        # Comparison plots if other files provided
        if other_files:
            print("  - Box plot comparison...")
            self.plot_box_comparison(
                other_files=other_files,
                save_path=str(output_path / 'box_comparison.png'),
                show=False
            )

            print("  - Percentile comparison...")
            self.plot_percentile_comparison(
                other_files=other_files,
                save_path=str(output_path / 'percentile_comparison.png'),
                show=False
            )

        # Save statistics summary
        print("  - Saving statistics summary...")
        stats_text = self.analyzer.get_summary_text()
        with open(output_path / 'statistics.txt', 'w') as f:
            f.write(stats_text)

        # Export JSON stats
        self.analyzer.export_stats_json(str(output_path / 'statistics.json'))

        # Export timeouts if any
        if self.analyzer.compute_stats()['timeout_count'] > 0:
            print("  - Exporting timeout data...")
            self.analyzer.export_timeouts_csv(str(output_path / 'timeouts.csv'))

        print(f"Report generated successfully in {output_dir}")


if __name__ == '__main__':
    # Example usage
    import sys

    if len(sys.argv) < 2:
        print("Usage: python benchmark_viz.py <csv_file> [output_dir]")
        sys.exit(1)

    viz = BenchmarkVisualizer(sys.argv[1])

    if len(sys.argv) >= 3:
        viz.generate_report(sys.argv[2])
    else:
        viz.plot_time_distribution()
        viz.plot_status_breakdown()
