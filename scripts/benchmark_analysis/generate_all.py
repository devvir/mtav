#!/usr/bin/env python3
# Copilot - Pending review
"""
Generate ALL benchmark analysis artifacts at once.

This script:
1. Finds all benchmark CSV files in storage/benchmarks/
2. Generates individual statistics and visualizations for each file
3. Generates comparison reports across all files
4. Outputs everything to output/ directory

Usage: python3 generate_all.py
"""

import os
import sys
from pathlib import Path
from datetime import datetime
import json

# Set matplotlib to non-interactive backend BEFORE importing pyplot
import matplotlib
matplotlib.use('Agg')

# Add lib directory to path to import our modules
sys.path.insert(0, str(Path(__file__).parent / 'lib'))

from benchmark_stats import BenchmarkAnalyzer
from benchmark_viz import BenchmarkVisualizer
from benchmark_compare import BenchmarkComparer

def find_benchmark_files(base_dir):
    """Find all benchmark CSV files."""
    benchmark_dir = Path(base_dir)
    csv_files = sorted(benchmark_dir.glob('glpk_*.csv'))
    return csv_files

def generate_individual_analysis(csv_file, output_dir):
    """Generate all analysis artifacts for a single benchmark file."""
    print(f"\n{'='*70}")
    print(f"Processing: {csv_file.name}")
    print(f"{'='*70}")

    file_output_dir = output_dir / csv_file.stem
    file_output_dir.mkdir(parents=True, exist_ok=True)

    # Initialize analyzer and visualizer
    analyzer = BenchmarkAnalyzer(str(csv_file))
    visualizer = BenchmarkVisualizer(str(csv_file))

    # 1. Generate statistics
    print("  → Computing statistics...")
    stats = analyzer.compute_stats()

    # Save as JSON
    stats_json = file_output_dir / 'statistics.json'
    with open(stats_json, 'w') as f:
        json.dump(stats, f, indent=2)
    print(f"    ✓ Saved: {stats_json.name}")

    # Save as text
    stats_txt = file_output_dir / 'statistics.txt'
    with open(stats_txt, 'w') as f:
        f.write(f"Benchmark Statistics: {csv_file.name}\n")
        f.write(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        f.write("="*70 + "\n\n")
        for key, value in stats.items():
            f.write(f"{key}: {value}\n")
    print(f"    ✓ Saved: {stats_txt.name}")

    # 2. Analyze timeouts (if any)
    print("  → Analyzing timeouts...")
    timeout_stats = analyzer.analyze_timeouts()
    if timeout_stats['timeout_count'] > 0:
        timeout_file = file_output_dir / 'timeout_analysis.json'
        with open(timeout_file, 'w') as f:
            json.dump(timeout_stats, f, indent=2)
        print(f"    ✓ Found {timeout_stats['timeout_count']} timeouts, saved: {timeout_file.name}")
    else:
        print(f"    ✓ No timeouts detected")

    # 3. Detect outliers
    print("  → Detecting outliers...")
    outliers = analyzer.detect_outliers()
    if len(outliers) > 0:
        outlier_file = file_output_dir / 'outliers.json'
        outlier_data = {
            'count': len(outliers),
            'outliers': outliers.to_dict('records')
        }
        with open(outlier_file, 'w') as f:
            json.dump(outlier_data, f, indent=2)
        print(f"    ✓ Found {len(outliers)} outliers, saved: {outlier_file.name}")
    else:
        print(f"    ✓ No outliers detected")

    # 4. Generate visualizations
    print("  → Generating visualizations...")

    viz_count = 0

    # Time distribution histogram
    viz_file = file_output_dir / 'time_distribution.png'
    visualizer.plot_time_distribution(save_path=str(viz_file), show=False)
    print(f"    ✓ {viz_file.name}")
    viz_count += 1

    # Time distribution histogram (log scale)
    viz_file = file_output_dir / 'time_distribution_log.png'
    visualizer.plot_time_distribution(log_scale=True, save_path=str(viz_file), show=False)
    print(f"    ✓ {viz_file.name}")
    viz_count += 1

    # Status breakdown pie chart
    viz_file = file_output_dir / 'status_breakdown.png'
    visualizer.plot_status_breakdown(save_path=str(viz_file), show=False)
    print(f"    ✓ {viz_file.name}")
    viz_count += 1

    # Time series
    viz_file = file_output_dir / 'time_series.png'
    visualizer.plot_time_series(save_path=str(viz_file), show=False)
    print(f"    ✓ {viz_file.name}")
    viz_count += 1

    # Scatter plot (time vs iteration) with outliers
    viz_file = file_output_dir / 'scatter_outliers.png'
    visualizer.plot_scatter_time_vs_iteration(highlight_outliers=True, save_path=str(viz_file), show=False)
    print(f"    ✓ {viz_file.name}")
    viz_count += 1

    print(f"  → Generated {viz_count} visualizations")

    return stats

def generate_comparison_analysis(csv_files, output_dir):
    """Generate comparison analysis across all benchmark files, grouped by scenario."""
    print(f"\n{'='*70}")
    print(f"Generating Comparison Analysis")
    print(f"{'='*70}")

    comparison_dir = output_dir / 'comparisons'
    comparison_dir.mkdir(parents=True, exist_ok=True)

    # Group files by scenario
    scenarios = {}
    for csv_file in csv_files:
        # Extract scenario from filename (e.g., glpk_random_10.csv -> random)
        parts = csv_file.stem.split('_')
        if len(parts) >= 2:
            scenario = parts[1]
            if scenario not in scenarios:
                scenarios[scenario] = []
            scenarios[scenario].append(csv_file)

    print(f"  → Found {len(scenarios)} scenario(s): {', '.join(sorted(scenarios.keys()))}")
    print(f"  → Total files: {len(csv_files)}")

    # Generate comparisons for each scenario
    for scenario in sorted(scenarios.keys()):
        scenario_files = sorted(scenarios[scenario])
        print(f"\n  Scenario: {scenario} ({len(scenario_files)} sizes)")
        generate_scenario_comparison(scenario, scenario_files, comparison_dir)

def generate_scenario_comparison(scenario, csv_files, comparison_dir):
    """Generate comparison analysis for a single scenario."""
    # Convert to strings for comparer
    file_paths = [str(f) for f in csv_files]

    comparer = BenchmarkComparer(file_paths)
    comparison_df = comparer.compare_all()

    # Convert to list of dicts for JSON serialization
    comparison = comparison_df.to_dict('records')

    # Create scenario-specific subdirectory
    scenario_dir = comparison_dir / scenario
    scenario_dir.mkdir(parents=True, exist_ok=True)

    # Save comparison as JSON
    comparison_json = scenario_dir / 'size_comparison.json'
    with open(comparison_json, 'w') as f:
        json.dump(comparison, f, indent=2)
    print(f"    ✓ Saved: {comparison_json.name}")

    # Save comparison as text
    comparison_txt = scenario_dir / 'size_comparison.txt'
    with open(comparison_txt, 'w') as f:
        f.write(f"Benchmark Size Comparison - {scenario.capitalize()} Scenario\n")
        f.write(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        f.write("="*70 + "\n\n")
        f.write(f"Files analyzed: {len(file_paths)}\n\n")

        for item in comparison:
            f.write(f"\nSize: {item['size']}x{item['size']}\n")
            f.write(f"  Total runs: {item['total_runs']}\n")
            f.write(f"  Mean time: {item['time_mean']:.2f} ms\n")
            f.write(f"  Median time: {item['time_median']:.2f} ms\n")
            f.write(f"  Std dev: {item['time_std']:.2f} ms\n")
            f.write(f"  P95: {item['time_p95']:.2f} ms\n")
            f.write(f"  P99: {item['time_p99']:.2f} ms\n")
            f.write(f"  Max: {item['time_max']:.2f} ms\n")
            f.write(f"  Success rate: {item['success_rate']*100:.2f}%\n")
            f.write(f"  Timeout rate: {item['timeout_rate']*100:.2f}%\n")
    print(f"    ✓ Saved: {comparison_txt.name}")

    # Generate comparison visualizations
    if len(csv_files) > 1:
        # Box plot comparison
        viz_file = scenario_dir / 'box_comparison.png'
        visualizer = BenchmarkVisualizer(str(csv_files[0]))
        comparison_files = [str(f) for f in csv_files[1:]]
        visualizer.plot_box_comparison(comparison_files, save_path=str(viz_file), show=False)
        print(f"    ✓ {viz_file.name}")

        # Box plot comparison (logarithmic scale)
        viz_file = scenario_dir / 'box_comparison_log.png'
        visualizer.plot_box_comparison_log(comparison_files, save_path=str(viz_file), show=False)
        print(f"    ✓ {viz_file.name}")

        # Percentile comparison
        viz_file = scenario_dir / 'percentile_comparison.png'
        visualizer.plot_percentile_comparison(comparison_files, save_path=str(viz_file), show=False)
        print(f"    ✓ {viz_file.name}")

        # Percentile comparison (logarithmic scale)
        viz_file = scenario_dir / 'percentile_comparison_log.png'
        visualizer.plot_percentile_comparison_log(comparison_files, save_path=str(viz_file), show=False)
        print(f"    ✓ {viz_file.name}")

    # Generate scaling analysis if we have enough data points
    if len(csv_files) >= 3:
        try:
            scaling = comparer.analyze_scaling()

            scaling_file = scenario_dir / 'scaling_analysis.json'
            with open(scaling_file, 'w') as f:
                json.dump(scaling, f, indent=2)
            print(f"    ✓ Saved: {scaling_file.name}")

            # Save scaling as text
            scaling_txt = scenario_dir / 'scaling_analysis.txt'
            with open(scaling_txt, 'w') as f:
                f.write(f"Scaling Analysis - {scenario.capitalize()}\n")
                f.write(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
                f.write("="*70 + "\n\n")
                f.write(f"Scenario: {scaling['scenario']}\n")
                f.write(f"Scaling order: {scaling.get('scaling_order', 'N/A')}\n\n")

                f.write("Observed sizes and mean times:\n")
                for size, mean_time in zip(scaling['sizes'], scaling['mean_times']):
                    f.write(f"  {size}x{size}: {mean_time:.2f} ms\n")

                if 'poly_coeffs' in scaling:
                    f.write(f"\nPolynomial coefficients: {scaling['poly_coeffs']}\n")
            print(f"    ✓ Saved: {scaling_txt.name}")
        except Exception as e:
            print(f"    ⚠ Scaling analysis skipped: {e}")

def generate_by_size_analysis(csv_files, output_dir):
    """Generate comparison analysis across all benchmark files, grouped by size."""
    print(f"\n{'='*70}")
    print(f"Generating By-Size Comparison Analysis")
    print(f"{'='*70}")

    by_size_dir = output_dir / 'by_size'
    by_size_dir.mkdir(parents=True, exist_ok=True)

    # Group files by size
    sizes = {}
    for csv_file in csv_files:
        # Extract size from filename (e.g., glpk_random_10.csv -> 10)
        parts = csv_file.stem.split('_')
        if len(parts) >= 3:
            try:
                size = int(parts[2])
                if size not in sizes:
                    sizes[size] = []
                sizes[size].append(csv_file)
            except ValueError:
                continue

    print(f"  → Found {len(sizes)} size(s): {', '.join(map(str, sorted(sizes.keys())))}")

    # Generate comparisons for each size
    for size in sorted(sizes.keys()):
        size_files = sorted(sizes[size])
        print(f"\n  Size: {size}x{size} ({len(size_files)} scenarios)")
        generate_size_comparison(size, size_files, by_size_dir)

def generate_size_comparison(size, csv_files, by_size_dir):
    """Generate comparison analysis for a single size across all scenarios."""
    # Convert to strings for comparer
    file_paths = [str(f) for f in csv_files]

    comparer = BenchmarkComparer(file_paths)
    comparison_data = comparer.get_size_comparison(size)

    # Create size-specific subdirectory
    size_dir = by_size_dir / f'size_{size}'
    size_dir.mkdir(parents=True, exist_ok=True)

    # Save comparison as JSON
    comparison_json = size_dir / 'scenario_comparison.json'
    # Convert to JSON-serializable format
    scenarios_data = []
    for scenario in comparison_data['scenarios']:
        scenario_dict = scenario.copy()
        scenario_dict['scenario'] = scenario_dict['scenario']
        scenarios_data.append(scenario_dict)

    comparison_dict = {
        'size': comparison_data['size'],
        'scenarios': scenarios_data,
        'scenario_names': comparison_data['scenario_names']
    }

    with open(comparison_json, 'w') as f:
        json.dump(comparison_dict, f, indent=2)
    print(f"    ✓ Saved: {comparison_json.name}")

    # Save comparison as text
    comparison_txt = size_dir / 'scenario_comparison.txt'
    with open(comparison_txt, 'w') as f:
        f.write(f"Benchmark Scenario Comparison - Size {size}x{size}\n")
        f.write(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        f.write("="*70 + "\n\n")
        f.write(f"Scenarios analyzed: {len(comparison_data['scenarios'])}\n")
        f.write(f"Scenario names: {', '.join(comparison_data['scenario_names'])}\n\n")

        for scenario_stats in comparison_data['scenarios']:
            f.write(f"\n{scenario_stats['scenario'].upper()}\n")
            f.write(f"  File: {scenario_stats['file']}\n")
            f.write(f"  Total runs: {scenario_stats['total_runs']}\n")
            f.write(f"  Mean time: {scenario_stats['time_mean']:.2f} ms\n")
            f.write(f"  Median time: {scenario_stats['time_median']:.2f} ms\n")
            f.write(f"  Std dev: {scenario_stats['time_std']:.2f} ms\n")
            f.write(f"  P95: {scenario_stats['time_p95']:.2f} ms\n")
            f.write(f"  P99: {scenario_stats['time_p99']:.2f} ms\n")
            f.write(f"  Max: {scenario_stats['time_max']:.2f} ms\n")
            f.write(f"  Success rate: {scenario_stats['success_rate']*100:.2f}%\n")
            f.write(f"  Timeout rate: {scenario_stats['timeout_rate']*100:.2f}%\n")
    print(f"    ✓ Saved: {comparison_txt.name}")

    # Generate comparison visualizations
    if len(csv_files) > 1:
        # Scenario comparison chart
        try:
            viz_file = size_dir / 'scenario_comparison.png'
            comparer.compare_scenarios(size, save_path=str(viz_file), show=False)
            print(f"    ✓ {viz_file.name}")
        except Exception as e:
            print(f"    ⚠ Scenario comparison visualization skipped: {e}")


def main():
    """Main execution."""
    print("\n" + "="*70)
    print("BENCHMARK ANALYSIS - GENERATE ALL")
    print("="*70)

    # Setup paths
    script_dir = Path(__file__).parent
    benchmark_dir = script_dir / '../../storage/benchmarks'
    output_dir = script_dir / 'output'

    print(f"\nBenchmark directory: {benchmark_dir.resolve()}")
    print(f"Output directory: {output_dir.resolve()}")

    # Find all benchmark files
    csv_files = find_benchmark_files(benchmark_dir)

    if not csv_files:
        print("\n✗ No benchmark files found!")
        print(f"  Looking for: {benchmark_dir}/glpk_*.csv")
        return 1

    print(f"\nFound {len(csv_files)} benchmark files:")
    for f in csv_files:
        print(f"  • {f.name}")

    # Create output directory
    output_dir.mkdir(parents=True, exist_ok=True)

    # Generate individual analysis for each file
    all_stats = {}
    for csv_file in csv_files:
        try:
            stats = generate_individual_analysis(csv_file, output_dir)
            all_stats[csv_file.stem] = stats
        except Exception as e:
            print(f"\n✗ Error processing {csv_file.name}: {e}")
            import traceback
            traceback.print_exc()

    # Generate comparison analysis (by scenario)
    if len(csv_files) > 1:
        try:
            generate_comparison_analysis(csv_files, output_dir)
        except Exception as e:
            print(f"\n✗ Error generating comparison: {e}")
            import traceback
            traceback.print_exc()

    # Generate by-size comparison analysis
    if len(csv_files) > 1:
        try:
            generate_by_size_analysis(csv_files, output_dir)
        except Exception as e:
            print(f"\n✗ Error generating by-size comparison: {e}")
            import traceback
            traceback.print_exc()

    # Generate summary
    print(f"\n{'='*70}")
    print("SUMMARY")
    print(f"{'='*70}")
    print(f"\n✓ Analysis complete!")
    print(f"\nOutput location: {output_dir.resolve()}")
    print(f"\nStructure:")
    print(f"  output/")
    print(f"    ├── glpk_random_5/      (individual analysis per file)")
    print(f"    ├── glpk_random_10/")
    print(f"    ├── ... (one folder per benchmark)")
    print(f"    ├── comparisons/        (scenario-grouped comparisons)")
    print(f"    │   ├── random/         (all sizes for random scenario)")
    print(f"    │   ├── identical/")
    print(f"    │   ├── opposite/")
    print(f"    │   └── realistic/")
    print(f"    └── by_size/            (size-grouped comparisons)")
    print(f"        ├── size_5/         (all scenarios for size 5x5)")
    print(f"        ├── size_10/")
    print(f"        ├── size_20/")
    print(f"        └── ... (one folder per size)")
    print(f"\nEach individual benchmark folder contains:")
    print(f"  • statistics.json/txt    (all metrics)")
    print(f"  • timeout_analysis.json  (if timeouts found)")
    print(f"  • outliers.json          (if outliers found)")
    print(f"  • 5 visualization PNG files")
    print(f"\nEach scenario comparison folder contains:")
    print(f"  • size_comparison.json/txt       (statistics by size)")
    print(f"  • scaling_analysis.json/txt      (predictions)")
    print(f"  • box_comparison.png             (box plots across sizes)")
    print(f"  • box_comparison_log.png         (log scale, better for fast runs)")
    print(f"  • percentile_comparison.png      (P50/P75/P90/P95/P99)")
    print(f"\nEach by-size folder contains:")
    print(f"  • scenario_comparison.json/txt   (statistics by scenario)")
    print(f"  • scenario_comparison.png        (comparison chart)")
    print(f"\nTotal files processed: {len(all_stats)}")

    return 0

if __name__ == '__main__':
    sys.exit(main())
