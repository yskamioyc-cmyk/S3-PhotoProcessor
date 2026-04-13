#!/bin/bash

# artisan ファイルが存在しない場合のみインストールを実行
if [ ! -f "/var/www/html/artisan" ]; then
    echo "Artisan not found. Starting fresh installation..."

    # 一時ディレクトリにプロジェクトを作成
    composer create-project --prefer-dist "laravel/laravel=8.*" /tmp/laravel --remove-vcs

    # 一時ディレクトリの中身を現在のディレクトリ（/var/www/html）に移動
    # ドットファイル（.envなど）も含めて移動させます
    cp -rn /tmp/laravel/. /var/www/html/
    
    # 不要になった一時ディレクトリを削除
    rm -rf /tmp/laravel

    # 権限変更
    chmod -R 777 storage bootstrap/cache
    echo "Laravel installation finished successfully."
fi

# Docker本来のプロセス（php-fpm）を起動
exec "$@"