#!/bin/bash
cd "$(dirname "$0")"
PORT=8765

echo "========================================"
echo "  Shooting Board - Demarrage"
echo "========================================"
echo ""

# Tester Python 3, puis Python
if command -v python3 &> /dev/null; then
    echo "Demarrage du serveur sur http://localhost:$PORT"
    echo "Appuyez sur Ctrl+C pour arreter."
    echo ""
    python3 -m http.server $PORT
elif command -v python &> /dev/null; then
    echo "Demarrage du serveur sur http://localhost:$PORT"
    echo "Appuyez sur Ctrl+C pour arreter."
    echo ""
    python -m http.server $PORT
else
    echo "Erreur : Python n'est pas installe."
    echo "Installez Python 3 puis reessayez."
    exit 1
fi
