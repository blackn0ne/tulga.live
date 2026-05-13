# Jitsi сервер для `jitsi.tulga.kz`

Этот каталог содержит минимальный набор файлов для поднятия собственного `Jitsi Meet` на том же Ubuntu VPS, где уже живет сайт.

Схема здесь такая:

- основной сайт остается на `tulga.live`
- `Jitsi` поднимается в Docker
- Apache на хосте принимает `https://jitsi.tulga.kz`
- Apache проксирует запросы в контейнер `Jitsi` на `127.0.0.1:8088`
- медиа-трафик идет напрямую в `JVB` через `UDP 10000`

## Что лежит в папке

- `docker-compose.yml` — Docker-стек `Jitsi`
- `.env.example` — переменные окружения
- `apache-jitsi.tulga.kz.conf.example` — пример Apache vhost для субдомена
- `gen-secrets.sh` — генерация секретов

## Предварительные условия

Нужно:

- Ubuntu сервер с `root` или `sudo`
- DNS-запись `A` для `jitsi.tulga.kz` на IP этого VPS
- уже работающий Apache на сервере
- свободный `UDP 10000`

## 1. Установить Docker и Compose

На Ubuntu:

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg lsb-release

sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo \"$VERSION_CODENAME\") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

sudo systemctl enable docker
sudo systemctl start docker
```

Проверка:

```bash
docker --version
docker compose version
```

## 2. Залить папку на сервер

Рекомендуемый путь:

```bash
sudo mkdir -p /opt/jitsi
sudo chown -R $USER:$USER /opt/jitsi
```

Скопируй содержимое этой папки в:

```bash
/opt/jitsi
```

## 3. Подготовить `.env`

```bash
cd /opt/jitsi
cp .env.example .env
chmod +x gen-secrets.sh
./gen-secrets.sh
```

Скрипт выведет 3 значения:

- `JWT_APP_SECRET`
- `JICOFO_AUTH_PASSWORD`
- `JVB_AUTH_PASSWORD`

Открой `.env` и вставь их.

Также обязательно поправь:

```env
PUBLIC_URL=https://jitsi.tulga.kz
JVB_ADVERTISE_IPS=ТВОЙ_ПУБЛИЧНЫЙ_IP_СЕРВЕРА
JWT_APP_ID=tulga-live
JWT_ACCEPTED_ISSUERS=tulga-live
JWT_ACCEPTED_AUDIENCES=jitsi
```

Если у сервера один внешний IP, в `JVB_ADVERTISE_IPS` укажи именно его.

## 4. Открыть firewall

Если используется `ufw`:

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 10000/udp
sudo ufw reload
```

Если `80/443` уже открыты для сайта, главное не забыть именно:

```bash
sudo ufw allow 10000/udp
```

## 5. Включить Apache-модули

```bash
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod proxy_wstunnel
sudo a2enmod headers
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

## 6. Настроить Apache vhost для `jitsi.tulga.kz`

Скопируй пример:

```bash
sudo cp apache-jitsi.tulga.kz.conf.example /etc/apache2/sites-available/jitsi.tulga.kz.conf
```

Потом:

```bash
sudo a2ensite jitsi.tulga.kz.conf
sudo systemctl reload apache2
```

## 7. Выпустить SSL сертификат

Если `certbot` еще не установлен:

```bash
sudo apt install -y certbot python3-certbot-apache
```

Выпуск сертификата:

```bash
sudo certbot --apache -d jitsi.tulga.kz
```

После этого проверь, что в `/etc/apache2/sites-available/jitsi.tulga.kz.conf` пути к сертификатам соответствуют:

```apache
SSLCertificateFile /etc/letsencrypt/live/jitsi.tulga.kz/fullchain.pem
SSLCertificateKeyFile /etc/letsencrypt/live/jitsi.tulga.kz/privkey.pem
```

И затем:

```bash
sudo systemctl reload apache2
```

## 8. Поднять Jitsi

```bash
cd /opt/jitsi
docker compose pull
docker compose up -d
```

Проверка контейнеров:

```bash
docker compose ps
```

Логи:

```bash
docker compose logs -f
```

## 9. Проверка с браузера

Открой:

- `https://jitsi.tulga.kz`

Если страница открылась, сервер уже жив.

## 10. Что потом нужно прописать в Laravel

Когда перейдем к интеграции приложения с этим сервером, в Laravel нужно будет добавить такие же значения:

```env
JITSI_PUBLIC_URL=https://jitsi.tulga.kz
JITSI_DOMAIN=jitsi.tulga.kz
JITSI_JWT_APP_ID=tulga-live
JITSI_JWT_APP_SECRET=тот_же_JWT_APP_SECRET_что_в_/opt/jitsi/.env
JITSI_JWT_AUDIENCE=jitsi
JITSI_JWT_ISSUER=tulga-live
```

Важно:

- `JWT_APP_SECRET` на сервере `Jitsi` и в Laravel должен быть один и тот же
- иначе платформа не сможет выдавать валидные moderator/participant токены

## Полезные команды

Остановить:

```bash
docker compose down
```

Перезапустить:

```bash
docker compose down
docker compose up -d
```

Обновить образы:

```bash
docker compose pull
docker compose up -d
```

## Важные замечания

### 1. Почему через Apache reverse proxy

Потому что сайт уже живет на этом же VPS. Если дать `Jitsi` напрямую слушать `80/443`, он конфликтует с текущим веб-сервером.

### 2. Почему не `meet.jit.si`

Потому что на публичном сервере нельзя надежно навязать логику:

- teacher = moderator
- student = participant
- без внешнего логина

### 3. Что еще не готово только этим набором файлов

Эти файлы поднимают сам `Jitsi` сервер.

Но чтобы платформа автоматически выдавала:

- teacher -> moderator
- student -> participant

нужен следующий шаг: доработка Laravel-кода под JWT токены.

## Если что-то не запускается

Сначала смотри:

```bash
docker compose logs -f web
docker compose logs -f prosody
docker compose logs -f jicofo
docker compose logs -f jvb
```

Чаще всего проблема одна из этих:

- неверный `JVB_ADVERTISE_IPS`
- не открыт `UDP 10000`
- Apache не включил `proxy_wstunnel`
- сертификат еще не выпущен
- в `.env` оставлены placeholder-секреты
