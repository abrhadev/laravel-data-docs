#!/bin/bash
set -e

coverage_file=${1:-"coverage.txt"}
shift

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Initialize thresholds as empty (no defaults)
min_line=""
min_method=""
min_class=""

# Track which options were provided
check_line=false
check_method=false
check_class=false

# Parse command-line arguments
for arg in "$@"; do
    case $arg in
        --min-line=*)
            min_line="${arg#*=}"
            check_line=true
            ;;
        --min-method=*)
            min_method="${arg#*=}"
            check_method=true
            ;;
        --min-class=*)
            min_class="${arg#*=}"
            check_class=true
            ;;
        *)
            echo "Unknown option: $arg"
            echo "Valid options: --min-line=X --min-method=X --min-class=X"
            exit 1
            ;;
    esac
done

# Check if coverage file exists
if [ ! -f "$coverage_file" ]; then
    echo "Error: Coverage file '$coverage_file' not found"
    exit 1
fi

# Display the coverage file contents with preserved colors
echo ""
cat "$coverage_file"
echo ""

# If no options provided, just display coverage and exit
if [ "$check_line" = false ] && [ "$check_method" = false ] && [ "$check_class" = false ]; then
    exit 0
fi

# Extract metrics from Summary section (supports both "Summary:" and "Report Summary:" formats)
classes=$(grep "Classes:" "$coverage_file" | grep -oP '[\d.]+(?=%)' 2>/dev/null || echo "0")
methods=$(grep "Methods:" "$coverage_file" | grep -oP '[\d.]+(?=%)' 2>/dev/null || echo "0")
lines=$(grep "Lines:" "$coverage_file" | grep -oP '[\d.]+(?=%)' 2>/dev/null || echo "0")

# Validate parsing
if [ -z "$classes" ] || [ -z "$methods" ] || [ -z "$lines" ]; then
    echo "Error: Could not parse coverage metrics from $coverage_file"
    exit 1
fi

# Display header
echo -e "${BOLD}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║            Code Coverage Report                           ║${NC}"
echo -e "${BOLD}╠═══════════════════════════════════════════════════════════╣${NC}"

# Display each metric (only if checked)
if [ "$check_class" = true ]; then
    printf "${BOLD}║${NC} Classes:  %6.2f%%  (Required: %5.2f%%)  " "$classes" "$min_class"
    if awk "BEGIN {exit !($classes >= $min_class)}"; then
        echo -e "${GREEN}✓ PASS${NC}       ${BOLD}║${NC}"
    else
        echo -e "${RED}✗ FAIL${NC}       ${BOLD}║${NC}"
    fi
fi

if [ "$check_method" = true ]; then
    printf "${BOLD}║${NC} Methods:  %6.2f%%  (Required: %5.2f%%)  " "$methods" "$min_method"
    if awk "BEGIN {exit !($methods >= $min_method)}"; then
        echo -e "${GREEN}✓ PASS${NC}       ${BOLD}║${NC}"
    else
        echo -e "${RED}✗ FAIL${NC}       ${BOLD}║${NC}"
    fi
fi

if [ "$check_line" = true ]; then
    printf "${BOLD}║${NC} Lines:    %6.2f%%  (Required: %5.2f%%)  " "$lines" "$min_line"
    if awk "BEGIN {exit !($lines >= $min_line)}"; then
        echo -e "${GREEN}✓ PASS${NC}       ${BOLD}║${NC}"
    else
        echo -e "${RED}✗ FAIL${NC}       ${BOLD}║${NC}"
    fi
fi

echo -e "${BOLD}╠═══════════════════════════════════════════════════════════╣${NC}"

# Check all thresholds and determine overall status (only check requested metrics)
all_pass=true

if [ "$check_class" = true ]; then
    if ! awk "BEGIN {exit !($classes >= $min_class)}"; then
        all_pass=false
    fi
fi

if [ "$check_method" = true ]; then
    if ! awk "BEGIN {exit !($methods >= $min_method)}"; then
        all_pass=false
    fi
fi

if [ "$check_line" = true ]; then
    if ! awk "BEGIN {exit !($lines >= $min_line)}"; then
        all_pass=false
    fi
fi

if [ "$all_pass" = true ]; then
    echo -e "${BOLD}║${NC} Overall Status: ${GREEN}${BOLD}✓ PASS${NC}                                   ${BOLD}║${NC}"
    echo -e "${BOLD}╚═══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    exit 0
else
    echo -e "${BOLD}║${NC} Overall Status: ${RED}${BOLD}✗ FAIL${NC}                                   ${BOLD}║${NC}"
    echo -e "${BOLD}╚═══════════════════════════════════════════════════════════╝${NC}"
    echo ""
    exit 1
fi

