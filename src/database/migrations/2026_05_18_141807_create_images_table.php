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
