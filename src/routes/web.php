<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// 1. トップページ（画像一覧表示）
Route::get('/', function () {
    // 直近の保存画像一覧を取得（要件定義に基づき降順）
    $images = DB::table('images')->orderBy('created_at', 'desc')->get();
    return view('welcome', ['images' => $images]);
});

// 2. 画像アップロード処理
Route::post('/upload', function (Request $request) {
    // バリデーション（要件定義に基づきpng, jpgを許可）
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        'title' => 'nullable|string|max:255',
    ]);

    try {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // S3(LocalStack)の 'uploads' ディレクトリに保存
            $path = Storage::disk('s3')->putFile('uploads', $file, 'public');

            // --- 要件定義に基づいたDB記録 ---
            DB::table('images')->insert([
                'file_name' => $file->getClientOriginalName(), // ①元ファイル名
                's3_key'    => $path,                          // ②S3パス
                'mime_type' => $file->getClientMimeType(),     // ④MIMEタイプ
                'title'     => $request->input('title'),       // ⑤タイトル（nullable）
                'author'    => '神尾悠介',                      // ⑥権利者情報（仮）
                'version'   => 1,                              // ⑨初期バージョン
                'size'      => $file->getSize(),               // ⑩画像サイズ(B)
                'created_at' => now(),                         // ⑦初回アップロード
                'updated_at' => now(),                         // ⑧更新履歴
            ]);

            // ブラウザ閲覧用URLの生成（LocalStack用の置換含む）
            $url = Storage::disk('s3')->url($path);
            $displayUrl = str_replace('aws:4566', 'localhost:4566', $url);

            // 最新のリストを取得してビューに返す
            $images = DB::table('images')->orderBy('created_at', 'desc')->get();
            return view('welcome', [
                'url' => $displayUrl,
                'images' => $images
            ]);
        }
    } catch (\Exception $e) {
        return back()->withErrors('アップロード失敗: ' . $e->getMessage());
    }
});

// 3. 画像削除処理
Route::post('/delete', function (Request $request) {
    $path = $request->input('path'); // ここには s3_key が渡される想定

    try {
        if ($path) {
            // S3から実体ファイルを削除
            if (Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
            }
            // DBからレコードを削除（カラム名を s3_key に変更）
            DB::table('images')->where('s3_key', $path)->delete();
            
            return redirect('/')->with('status', '画像を削除しました。');
        }
    } catch (\Exception $e) {
        return back()->withErrors('削除失敗: ' . $e->getMessage());
    }

    return back()->withErrors('ファイルが見つかりませんでした。');
});