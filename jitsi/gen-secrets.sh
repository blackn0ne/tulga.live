#!/usr/bin/env bash
set -euo pipefail

random_secret() {
    openssl rand -hex 32
}

cat <<EOF
JWT_APP_SECRET=$(random_secret)
JICOFO_AUTH_PASSWORD=$(random_secret)
JVB_AUTH_PASSWORD=$(random_secret)
EOF
