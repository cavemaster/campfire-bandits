#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
python3 "$DIR/sync_dateien.py"
echo ""
read -p "Drücke [Enter] zum Schließen des Fensters..."
