#!/bin/bash

# 1. ルートの .env 作成
if [ ! -f .env ]; then
    echo "Creating root .env from template..."
    cp .env.example .env
    # ユーザーに確認を促す
    echo "⚠️ .env has been created. If you need custom values, edit it and restart."
fi

# 2. コンテナ起動
docker compose up -d 

# 3. Docker環境の安定化待機（ドット表示の維持）
echo -n "Waiting for Docker containers to stabilize..."
seconds=0
# PHP-FPMが正常起動する、または15秒経過するまでドットを表示して待機
while ! docker compose logs app 2>&1 | grep -q "ready to handle connections" && [ $seconds -lt 15 ]; do
    sleep 3
    seconds=$((seconds + 3))
    echo -n "."
done
echo -e "\nDocker environment is ready!"

# 4. Laravel依存関係の確実なインストール命令
echo "Installing Laravel dependencies inside the container..."
docker compose exec -T app composer install

# 万が一、プロジェクト自体が初期化されていない場合（クローン直後でsrcが空の場合）のケア
if [ ! -f src/vendor/autoload.php ]; then
    echo "vendor not found. Initializing new Laravel project..."
    docker compose exec -T app composer create-project --prefer-dist laravel/laravel .
fi
echo "Laravel detected and dependencies installed!"

# 5. S3ライブラリ追加インストール
echo "Installing S3 adapter..."
docker compose exec -T app composer require league/flysystem-aws-s3-v3:"~1.0"

# 6. ファイルデプロイ
cp EXAMPLES/web.php src/routes/web.php
cp EXAMPLES/welcome.blade.php src/resources/views/welcome.blade.php

# --- 追加手順 5.5: マイグレーションファイルの自動生成（v.1.3.0設計対応） ---
echo "Cleaning up old migration files..."
rm -f src/database/migrations/*_create_images_table.php

echo "Generating migration for images table (v.1.3.0)..."
MIGRATION_FILE="src/database/migrations/$(date +%Y_%m_%d_%H%M%S)_create_images_table.php"

# エラーの原因だった s3_path を s3_key に修正し、必要なカラムを網羅
cat <<'EOF' > "$MIGRATION_FILE"
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('s3_key');       // s3_path から s3_key に修正
            $table->string('mime_type');
            $table->string('title')->nullable();
            $table->string('author')->nullable();
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('images'); }
};
EOF

# Imageモデルの作成
echo "Generating Image model..."
docker compose exec -T app php artisan make:model Image --force

# モデルファイルに最新の $fillable を自動で書き込む
docker compose exec -T app sed -i '/use HasFactory;/a \\    protected $fillable = ["file_name", "s3_key", "mime_type", "title", "author", "version", "size"];' app/Models/Image.php

# 7. 環境設定の修正
echo "Syncing environment variables to Laravel..."
cp src/.env.example src/.env

if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

sed -i "s/DB_HOST=127.0.0.1/DB_HOST=db/g" src/.env
sed -i "s/DB_DATABASE=laravel/DB_DATABASE=${DB_DATABASE}/g" src/.env
sed -i "s/DB_USERNAME=root/DB_USERNAME=${DB_USERNAME}/g" src/.env
sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASSWORD}/g" src/.env

if [ -f EXAMPLES/.env.laravel.example ]; then
    cat EXAMPLES/.env.laravel.example >> src/.env
fi

# 8. アプリケーションキーの生成とマイグレーションの実行
echo "Generating application key..."
docker compose exec -T app php artisan key:generate

echo "Running database migrations..."
# 一度リフレッシュして最新の構成でテーブルを作り直す
docker compose exec -T app php artisan migrate:fresh

# --- 追加：LocalStackへのS3バケット自動作成命令 ---
echo "Creating S3 bucket in LocalStack..."
# LocalStackが完全に起動するのを少し待ってから実行
sleep 3
docker compose exec -T aws awslocal s3 mb s3://my-test-bucket

echo "🚀 Setup complete! Access the application at http://localhost:8081"