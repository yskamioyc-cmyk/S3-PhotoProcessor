<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>S3 Image Uploader</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f7fafc; color: #2d3748; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .container { background: white; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); width: 100%; max-width: 600px; }
        .upload-section { margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; }
        .preview-section img { max-width: 100%; border-radius: 0.25rem; margin-top: 1rem; }
        .btn { color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.25rem; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.8rem; }
        .btn-primary { background: #4a5568; }
        .btn-danger { background: #e53e3e; margin-top: 1rem; }
        .alert { color: #e53e3e; margin-bottom: 1rem; font-size: 0.9rem; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; margin-top: 1rem; }
        .image-item { border: 1px solid #edf2f7; padding: 8px; border-radius: 5px; text-align: center; }
        .image-item img { width: 100%; height: 80px; object-fit: cover; border-radius: 3px; }
    </style>
</head>
<body class="antialiased">
    <div class="container">
        <div class="upload-section">
            <h2>画像をLocalStack S3へ保存</h2>
            
            @if($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif

            <form action="/upload" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <input type="file" name="image" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary">アップロード実行</button>
            </form>
        </div>

        <div class="preview-section">
            <h3>アップロード結果</h3>
            @if(isset($url))
                <p style="color: #48bb78;">成功！</p>
                <img src="{{ $url }}" alt="Uploaded Image">
                <form action="/delete" method="POST">
                    @csrf
                    @php
                        $parts = explode('4566/' . env('AWS_BUCKET', 'my-test-bucket') . '/', $url);
                        $s3Path = end($parts);
                    @endphp
                    <input type="hidden" name="path" value="{{ $s3Path }}">
                    <button type="submit" class="btn btn-danger">この画像を削除</button>
                </form>
            @else
                <p style="color: #a0aec0;">新規アップロード画像はありません。</p>
            @endif

            <h3 style="margin-top: 2rem; border-top: 1px solid #e2e8f0; pt: 1rem;">保存済み画像一覧（DB）</h3>
            <div class="image-grid">
                @isset($images)
                    @foreach($images as $image)
                        <div class="image-item">
                            @php
                                $s3Url = Storage::disk('s3')->url($image->s3_path);
                                $displayS3Url = str_replace('aws:4566', 'localhost:4566', $s3Url);
                            @endphp
                            <img src="{{ $displayS3Url }}">
                            <p style="font-size: 0.6rem; margin-top: 5px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                                {{ $image->file_name }}
                            </p>
                        </div>
                    @endforeach
                @else
                    <p style="color: #a0aec0; font-size: 0.8rem;">表示できるデータがありません。</p>
                @endisset
            </div>
        </div>
    </div>
</body>
</html>