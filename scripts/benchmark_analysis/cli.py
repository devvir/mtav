#!/usr/bin/env python3
# Copilot - Pending review
"""
GLPK Benchmark Analysis CLI

Command-line interface for analyzing GLPK solver benchmark data.
"""

import click
from pathlib import Path
from typing import List
import sys

# Add lib directory to path
sys.path.insert(0, str(Path(__file__).parent / 'lib'))

from benchmark_stats import BenchmarkAnalyzer, compare_sizes
from benchmark_viz import BenchmarkVisualizer


@click.group()
@click.version_option(version='1.0.0')
def cli():
    """GLPK Benchmark Analysis Tools"""
    pass


@cli.command()
@click.argument('csv_file', type=click.Path(exists=True))
@click.option('--json', 'json_output', type=click.Path(), help='Export stats as JSON')
def analyze(csv_file: str, json_output: str):
    """
    Analyze a single benchmark CSV file and display statistics.

    Example:
        python cli.py analyze storage/benchmarks/glpk_random_30.csv
    """
    try:
        analyzer = BenchmarkAnalyzer(csv_file)

        # Print summary
        click.echo(analyzer.get_summary_text())

        # Export JSON if requested
        if json_output:
            analyzer.export_stats_json(json_output)
            click.echo(f"\nStatistics exported to: {json_output}")

    except Exception as e:
        click.echo(f"Error: {e}", err=True)
        sys.exit(1)


@cli.command()
@click.argument('csv_files', nargs=-1, type=click.Path(exists=True), required=True)
@click.option('--output', '-o', type=click.Path(), help='Output file for comparison table')
@click.option('--format', 'output_format', type=click.Choice(['table', 'csv', 'markdown']),
              default='table', help='Output format')
def compare(csv_files: tuple, output: str, output_format: str):
    """
    Compare statistics across multiple benchmark files.

    Example:
        python cli.py compare storage/benchmarks/glpk_random_*.csv
        python cli.py compare file1.csv file2.csv file3.csv --format markdown
    """
    try:
        # Handle glob expansion
        files = list(csv_files)

        if not files:
            click.echo("Error: No files provided", err=True)
            sys.exit(1)

        # Perform comparison
        comparison_df = compare_sizes(files)

        # Select key columns for display
        display_cols = [
            'size', 'scenario', 'total_runs',
            'time_mean', 'time_median', 'time_std',
            'time_p95', 'time_p99', 'time_max',
            'success_rate', 'timeout_rate'
        ]
        display_df = comparison_df[display_cols]

        # Format percentages and round numbers
        display_df['success_rate'] = display_df['success_rate'].apply(lambda x: f"{x:.2%}")
        display_df['timeout_rate'] = display_df['timeout_rate'].apply(lambda x: f"{x:.2%}")

        for col in ['time_mean', 'time_median', 'time_std', 'time_p95', 'time_p99', 'time_max']:
            display_df[col] = display_df[col].round(2)

        # Output based on format
        if output_format == 'table':
            from tabulate import tabulate
            table = tabulate(display_df, headers='keys', tablefmt='grid', showindex=False)
            click.echo(table)

        elif output_format == 'csv':
            csv_output = display_df.to_csv(index=False)
            click.echo(csv_output)

        elif output_format == 'markdown':
            md_output = display_df.to_markdown(index=False)
            click.echo(md_output)

        # Save to file if requested
        if output:
            if output_format == 'csv' or output.endswith('.csv'):
                display_df.to_csv(output, index=False)
            elif output_format == 'markdown' or output.endswith('.md'):
                with open(output, 'w') as f:
                    f.write(display_df.to_markdown(index=False))
            else:
                comparison_df.to_json(output, orient='records', indent=2)

            click.echo(f"\nComparison saved to: {output}")

    except Exception as e:
        click.echo(f"Error: {e}", err=True)
        import traceback
        traceback.print_exc()
        sys.exit(1)


@cli.command()
@click.argument('csv_file', type=click.Path(exists=True))
@click.option('--output-dir', '-o', required=True, type=click.Path(),
              help='Directory to save report and visualizations')
@click.option('--compare-with', '-c', multiple=True, type=click.Path(exists=True),
              help='Additional CSV files to include in comparison charts')
def report(csv_file: str, output_dir: str, compare_with: tuple):
    """
    Generate comprehensive report with statistics and visualizations.

    Example:
        python cli.py report storage/benchmarks/glpk_random_30.csv -o reports/30x30/
        python cli.py report glpk_random_30.csv -o reports/ -c glpk_random_20.csv -c glpk_random_10.csv
    """
    try:
        viz = BenchmarkVisualizer(csv_file)

        # Generate full report
        other_files = list(compare_with) if compare_with else None
        viz.generate_report(output_dir, other_files=other_files)

        click.echo(f"\n✓ Report generated successfully in: {output_dir}")

    except Exception as e:
        click.echo(f"Error: {e}", err=True)
        import traceback
        traceback.print_exc()
        sys.exit(1)


@cli.command()
@click.argument('csv_file', type=click.Path(exists=True))
@click.option('--export', '-e', type=click.Path(), help='Export timeout data to CSV')
@click.option('--details/--no-details', default=False, help='Show detailed iteration list')
def timeouts(csv_file: str, export: str, details: bool):
    """
    Analyze timeout patterns in benchmark data.

    Example:
        python cli.py timeouts storage/benchmarks/glpk_random_30.csv --details
    """
    try:
        analyzer = BenchmarkAnalyzer(csv_file)
        timeout_info = analyzer.analyze_timeouts()
        stats = analyzer.compute_stats()

        click.echo("=== Timeout Analysis ===")
        click.echo(f"File: {Path(csv_file).name}")
        click.echo(f"Total runs: {stats['total_runs']}")
        click.echo(f"Timeout count: {timeout_info['timeout_count']} ({stats['timeout_rate']:.2%})")

        if timeout_info['timeout_count'] == 0:
            click.echo("\n✓ No timeouts found!")
            return

        click.echo(f"\nFirst timeout at iteration: {timeout_info['first_timeout']}")

        if 'timeout_gaps_mean' in timeout_info:
            click.echo(f"\nGap Statistics:")
            click.echo(f"  Mean gap: {timeout_info['timeout_gaps_mean']:.1f} iterations")
            click.echo(f"  Median gap: {timeout_info['timeout_gaps_median']:.1f} iterations")
            click.echo(f"  Min gap: {timeout_info['timeout_gaps_min']} iterations")
            click.echo(f"  Max gap: {timeout_info['timeout_gaps_max']} iterations")

        if details:
            click.echo(f"\nTimeout iterations ({len(timeout_info['timeout_iterations'])} total):")
            iterations = timeout_info['timeout_iterations']

            # Show first 20, last 20, and indicate if there are more
            if len(iterations) <= 40:
                click.echo(f"  {iterations}")
            else:
                click.echo(f"  First 20: {iterations[:20]}")
                click.echo(f"  ...")
                click.echo(f"  Last 20: {iterations[-20:]}")

        if export:
            analyzer.export_timeouts_csv(export)
            click.echo(f"\n✓ Timeout data exported to: {export}")

    except Exception as e:
        click.echo(f"Error: {e}", err=True)
        sys.exit(1)


@cli.command()
@click.argument('csv_file', type=click.Path(exists=True))
@click.option('--method', type=click.Choice(['iqr', 'zscore']), default='iqr',
              help='Outlier detection method')
@click.option('--threshold', type=float, default=1.5,
              help='Threshold for outlier detection (1.5 for IQR, 3 for zscore)')
@click.option('--top', type=int, default=10, help='Number of top outliers to show')
@click.option('--export', '-e', type=click.Path(), help='Export outliers to CSV')
def outliers(csv_file: str, method: str, threshold: float, top: int, export: str):
    """
    Detect and analyze outlier execution times.

    Example:
        python cli.py outliers storage/benchmarks/glpk_random_30.csv --top 20
        python cli.py outliers file.csv --method zscore --threshold 3
    """
    try:
        analyzer = BenchmarkAnalyzer(csv_file)
        outliers_df = analyzer.detect_outliers(method=method, threshold=threshold)

        click.echo(f"=== Outlier Analysis ({method.upper()}, threshold={threshold}) ===")
        click.echo(f"File: {Path(csv_file).name}")
        click.echo(f"Total outliers detected: {len(outliers_df)}")

        if len(outliers_df) == 0:
            click.echo("\n✓ No outliers detected!")
            return

        # Show top N outliers
        click.echo(f"\nTop {min(top, len(outliers_df))} outliers by execution time:")

        from tabulate import tabulate
        display_df = outliers_df.head(top)[['iteration', 'time_ms', 'size', 'scenario']]
        table = tabulate(display_df, headers='keys', tablefmt='grid', showindex=False)
        click.echo(table)

        # Statistics about outliers
        click.echo(f"\nOutlier Statistics:")
        click.echo(f"  Mean time: {outliers_df['time_ms'].mean():.2f}ms")
        click.echo(f"  Median time: {outliers_df['time_ms'].median():.2f}ms")
        click.echo(f"  Max time: {outliers_df['time_ms'].max():.2f}ms")

        if export:
            outliers_df.to_csv(export, index=False)
            click.echo(f"\n✓ Outliers exported to: {export}")

    except Exception as e:
        click.echo(f"Error: {e}", err=True)
        import traceback
        traceback.print_exc()
        sys.exit(1)


@cli.command()
@click.argument('csv_file', type=click.Path(exists=True))
@click.option('--plot-type', type=click.Choice([
    'distribution', 'status', 'timeseries', 'scatter', 'all'
]), default='distribution', help='Type of plot to generate')
@click.option('--output', '-o', type=click.Path(), help='Output file path for the plot')
@click.option('--log-scale/--no-log-scale', default=False, help='Use log scale (for distribution)')
def plot(csv_file: str, plot_type: str, output: str, log_scale: bool):
    """
    Generate individual visualization plots.

    Example:
        python cli.py plot file.csv --plot-type distribution -o dist.png
        python cli.py plot file.csv --plot-type timeseries
    """
    try:
        viz = BenchmarkVisualizer(csv_file)

        show = output is None

        if plot_type == 'distribution' or plot_type == 'all':
            viz.plot_time_distribution(
                log_scale=log_scale,
                save_path=output if plot_type == 'distribution' else None,
                show=show
            )

        if plot_type == 'status' or plot_type == 'all':
            viz.plot_status_breakdown(
                save_path=output if plot_type == 'status' else None,
                show=show
            )

        if plot_type == 'timeseries' or plot_type == 'all':
            viz.plot_time_series(
                save_path=output if plot_type == 'timeseries' else None,
                show=show
            )

        if plot_type == 'scatter' or plot_type == 'all':
            viz.plot_scatter_time_vs_iteration(
                save_path=output if plot_type == 'scatter' else None,
                show=show
            )

        if output and plot_type != 'all':
            click.echo(f"✓ Plot saved to: {output}")

    except Exception as e:
        click.echo(f"Error: {e}", err=True)
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == '__main__':
    cli()
