#!/bin/bash
# Copilot - Pending review
# Quick setup script for benchmark analysis tools

set -e

echo "=== GLPK Benchmark Analysis Tools Setup ==="
echo ""

# Check Python version
if ! command -v python3 &> /dev/null; then
    echo "Error: python3 not found. Please install Python 3.7 or higher."
    exit 1
fi

PYTHON_VERSION=$(python3 --version | cut -d' ' -f2 | cut -d'.' -f1,2)
echo "✓ Python version: $PYTHON_VERSION"

# Create virtual environment if it doesn't exist
if [ ! -d "venv" ]; then
    echo ""
    echo "Creating virtual environment..."
    python3 -m venv venv
    echo "✓ Virtual environment created"
else
    echo "✓ Virtual environment already exists"
fi

# Activate virtual environment
echo ""
echo "Activating virtual environment..."
source venv/bin/activate

# Upgrade pip
echo ""
echo "Upgrading pip..."
pip install --upgrade pip > /dev/null 2>&1
echo "✓ pip upgraded"

# Install requirements
echo ""
echo "Installing requirements..."
pip install -r requirements.txt
echo "✓ Requirements installed"

echo ""
echo "=== Setup Complete ==="
echo ""
echo "To use the tools:"
echo "  1. Activate the virtual environment: source venv/bin/activate"
echo "  2. Run CLI commands: python cli.py --help"
echo "  3. Or use Python scripts directly: python benchmark_stats.py <file>"
echo ""
echo "Quick examples:"
echo "  python cli.py analyze ../../storage/benchmarks/glpk_random_30.csv"
echo "  python cli.py report ../../storage/benchmarks/glpk_random_30.csv -o reports/"
echo "  python cli.py compare ../../storage/benchmarks/glpk_random_*.csv"
echo ""
