#!/bin/bash
cd /home/itsupportjogja/apps/papyrus-antrian

echo "$(date): Mulai bersihkan cache Papyrus Antrian..." >> /home/itsupportjogja/apps/papyrus-antrian/cache-clear.log

docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan route:clear
docker compose exec -T app php artisan view:clear
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo "$(date): Selesai! Siap beroperasi." >> /home/itsupportjogja/apps/papyrus-antrian/cache-clear.log
echo "-------------------------------------------" >> /home/itsupportjogja/apps/papyrus-antrian/cache-clear.log
