<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();   // 画像ID
            $table->string('file_name');    // 画像ファイル名
            $table->string('s3_key');   // S3パス
            $table->string('mime_type');    // MIMEタイプ
            $table->string('title')->nullable();    // 画像タイトル
            $table->string('author')->default('Unknown');   // 権利者情報
            $table->integer('version')->default(1);     // バージョン情報
            $table->unsignedBigInteger('size');     // 画像サイズ
            $table->timestamps();   //  作成・更新日時
        });
    }
    public function down() { Schema::dropIfExists('images'); }
};
