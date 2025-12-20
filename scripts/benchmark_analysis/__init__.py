# Copilot - Pending review
# Benchmark analysis tools package initializer

"""
GLPK Benchmark Analysis Tools

Python toolkit for analyzing GLPK lottery solver benchmark data.
Provides statistical analysis, visualization, and comparison tools.
"""

__version__ = '1.0.0'

from .benchmark_stats import BenchmarkAnalyzer, compare_sizes
from .benchmark_viz import BenchmarkVisualizer

__all__ = ['BenchmarkAnalyzer', 'BenchmarkVisualizer', 'compare_sizes']
