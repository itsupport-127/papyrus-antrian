#!/bin/bash
# Quick restart script — Papyrus Antrian
# Jalankan dari: ~/apps/papyrus-antrian
# Usage: bash restart-antrian.sh

APP="papyrus-antrian-app"

echo "🔄 Clear cache & restart Papyrus Antrian..."

docker exec $APP php artisan view:clear
docker exec $APP php artisan cache:clear
docker exec $APP php artisan config:clear
docker exec $APP php artisan route:clear
docker exec $APP php artisan optimize:clear

echo "✅ Cache cleared!"

docker compose restart app

echo "⏳ Menunggu app ready..."
sleep 5

STATUS=$(docker inspect --format='{{.State.Status}}' $APP 2>/dev/null)
echo "📦 Status container: $STATUS"

echo ""
echo "✅ SELESAI! App berjalan di:"
echo "   http://103.150.226.74:8090"