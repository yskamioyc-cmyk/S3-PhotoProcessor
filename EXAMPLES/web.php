<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // 追加：DB操作用
use Illuminate\Http\Request;

// 1. トップページ
Route::get('/', function () {
    // DBから全画像データを取得（これがないとwelcome.blade.phpでエラーになります）
    $images = DB::table('images')->orderBy('created_at', 'desc')->get();
    return view('welcome', ['images' => $images]);
});

// 2. 画像アップロード処理
Route::post('/upload', function (Request $request) {
    $request->validate([
        'image' => 'required|image|max:2048',
    ]);

    try {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // S3(LocalStack)に保存
            $path = Storage::disk('s3')->putFile('uploads', $file, 'public');

            // --- 追加：DBに記録 ---
            DB::table('images')->insert([
                'file_name' => $file->getClientOriginalName(),
                's3_path'   => $path,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // URLを取得し、ブラウザ閲覧用に置換
            $url = Storage::disk('s3')->url($path);
            $displayUrl = str_replace('aws:4566', 'localhost:4566', $url);

            // 成功後もDBから最新のリストを取得して渡す
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

// 3. 画像削除処理（DB連動版）
Route::post('/delete', function (Request $request) {
    $path = $request->input('path');

    try {
        if ($path) {
            // S3から削除
            if (Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
            }
            // DBからも削除
            DB::table('images')->where('s3_path', $path)->delete();
            
            return redirect('/')->with('status', '画像を削除しました。');
        }
    } catch (\Exception $e) {
        return back()->withErrors('削除失敗: ' . $e->getMessage());
    }

    return back()->withErrors('ファイルが見つかりませんでした。');
});

// 4. 通信テスト用
Route::get('/s3-upload-test', function () {
    try {
        Storage::disk('s3')->put('test.txt', 'Connection OK');
        return response()->json(['status' => 'Success', 'content' => Storage::disk('s3')->get('test.txt')]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'Error', 'message' => $e->getMessage()], 500);
    }
});