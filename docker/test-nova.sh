#!/usr/bin/env bash
# Runs the PHP suite against the real laravel/nova in a throwaway copy of the repository.
# NOVA_VERSION picks the Nova release (default ^5.0, the newest your license may download).
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ ! -s "$root/auth.json" ]]; then
    echo "auth.json with your nova.laravel.com credentials is required in $root (see README, Development)." >&2
    exit 1
fi

work="$(mktemp -d "${TMPDIR:-/tmp}/nova-aegis-real-nova.XXXXXX")"
trap 'rm -rf "$work"' EXIT

git -C "$root" ls-files -z --cached --others --exclude-standard \
    | (cd "$root" && xargs -0 tar --create --file - --ignore-failed-read) \
    | tar --extract --ignore-zeros --file - --directory "$work"
cp "$root/auth.json" "$work/auth.json"

export UID
export GID="${GID:-$(id -g)}"

docker compose --project-directory "$root" run --rm --no-deps \
    --volume "$work:/work" --workdir /work \
    --env NOVA_VERSION="${NOVA_VERSION:-^5.0}" \
    php sh -ec '
        composer config repositories.nova composer https://nova.laravel.com
        composer require "laravel/nova:$NOVA_VERSION" --no-update --no-interaction
        composer update laravel/nova --with-all-dependencies --no-interaction --no-progress
        composer show laravel/nova | grep -E "^(name|versions)"
        vendor/bin/pest "$@"
    ' sh "$@"
