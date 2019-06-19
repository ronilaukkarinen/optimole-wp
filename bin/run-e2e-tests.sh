#!/usr/bin/env bash
composer install --no-dev
npm install
npm run-script pre-e2e

eval "$(ssh-agent -s)"
chmod 600 /tmp/key
ssh-add /tmp/key
rsync -r --delete-after --quiet $TRAVIS_BUILD_DIR/dist/ root@testing.optimole.com:/var/www/optimole-wp
