#!/usr/bin/env python3
# Copilot - Pending review
"""
Quick test script to verify the benchmark analysis tools work correctly.
"""

import sys
from pathlib import Path

# Add parent directory to path so we can import modules
sys.path.insert(0, str(Path(__file__).parent))

def test_imports():
    """Test that all modules can be imported."""
    print("Testing imports...")
    try:
        from benchmark_stats import BenchmarkAnalyzer, compare_sizes
        from benchmark_viz import BenchmarkVisualizer
        print("  ✓ All imports successful")
        return True
    except ImportError as e:
        print(f"  ✗ Import error: {e}")
        return False

def test_basic_analysis():
    """Test basic analysis on a sample file."""
    print("\nTesting basic analysis...")

    # Find a benchmark file
    benchmark_dir = Path(__file__).parent.parent.parent / 'storage' / 'benchmarks'
    csv_files = list(benchmark_dir.glob('glpk_random_*.csv'))

    if not csv_files:
        print("  ⚠ No benchmark files found in storage/benchmarks/")
        print("    Skipping analysis test")
        return True

    # Use the first available file
    test_file = csv_files[0]
    print(f"  Using test file: {test_file.name}")

    try:
        from benchmark_stats import BenchmarkAnalyzer

        # Create analyzer
        analyzer = BenchmarkAnalyzer(str(test_file))

        # Compute stats
        stats = analyzer.compute_stats()

        # Verify required fields
        required_fields = ['time_mean', 'time_median', 'time_std', 'success_rate']
        for field in required_fields:
            if field not in stats:
                print(f"  ✗ Missing required field: {field}")
                return False

        print(f"  ✓ Analysis successful")
        print(f"    - Size: {analyzer.df['size'].iloc[0]}x{analyzer.df['size'].iloc[0]}")
        print(f"    - Total runs: {stats['total_runs']}")
        print(f"    - Mean time: {stats['time_mean']:.2f}ms")
        print(f"    - Success rate: {stats['success_rate']:.2%}")

        return True

    except Exception as e:
        print(f"  ✗ Analysis error: {e}")
        import traceback
        traceback.print_exc()
        return False

def test_visualization():
    """Test visualization module initialization."""
    print("\nTesting visualization...")

    benchmark_dir = Path(__file__).parent.parent.parent / 'storage' / 'benchmarks'
    csv_files = list(benchmark_dir.glob('glpk_random_*.csv'))

    if not csv_files:
        print("  ⚠ No benchmark files found")
        print("    Skipping visualization test")
        return True

    test_file = csv_files[0]

    try:
        from benchmark_viz import BenchmarkVisualizer

        # Create visualizer (don't actually show plots)
        viz = BenchmarkVisualizer(str(test_file))

        print(f"  ✓ Visualizer initialized successfully")
        return True

    except Exception as e:
        print(f"  ✗ Visualization error: {e}")
        return False

def main():
    """Run all tests."""
    print("=== Benchmark Analysis Tools - Quick Test ===\n")

    tests = [
        test_imports,
        test_basic_analysis,
        test_visualization,
    ]

    results = []
    for test in tests:
        try:
            results.append(test())
        except Exception as e:
            print(f"\n✗ Unexpected error in {test.__name__}: {e}")
            results.append(False)

    print("\n" + "="*50)
    if all(results):
        print("✓ All tests passed!")
        print("\nYou can now use the tools:")
        print("  python cli.py --help")
        print("  python benchmark_stats.py <csv_file>")
        return 0
    else:
        print("✗ Some tests failed")
        print("\nPlease check the error messages above.")
        return 1

if __name__ == '__main__':
    sys.exit(main())
