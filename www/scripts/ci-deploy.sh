#!/bin/bash
SOURCE=$BITBUCKET_CLONE_DIR/www/
TARGET=/mnt/dev/www/deployments/$1
ssh dev@console.fenomen.ee "mkdir -p $TARGET; ln -sf deployments/$1/web /mnt/dev/www/$1"
rsync -zrSlh --stats $SOURCE dev@console.fenomen.ee:$TARGET
ssh dev@console.fenomen.ee "cd $TARGET && bash ./scripts/ci-post-deploy.sh $1"
