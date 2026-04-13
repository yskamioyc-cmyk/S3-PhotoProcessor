<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>S3-PhotoProcessor</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; background-color: #f7fafc; color: #2d3748; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .container { background: white; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); width: 100%; max-width: 800px; }
        .upload-section { margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; }
        .preview-section img { max-width: 100%; border-radius: 0.25rem; margin-top: 1rem; }
        .btn { color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.25rem; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.8rem; }
        .btn-primary { background: #4a5568; }
        .btn-danger { background: #e53e3e; padding: 0.3rem 0.6rem; font-size: 0.7rem; }
        .alert { color: #e53e3e; margin-bottom: 1rem; font-size: 0.9rem; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-top: 1rem; }
        .image-item { border: 1px solid #edf2f7; padding: 10px; border-radius: 5px; text-align: left; background: #fff; }
        .image-item img { width: 100%; height: 120px; object-fit: cover; border-radius: 3px; margin-bottom: 5px; }
        .image-info { font-size: 0.7rem; color: #718096; line-height: 1.4; }
        .image-title { font-weight: bold; font-size: 0.85rem; color: #2d3748; margin-bottom: 2px; }
        input[type="text"] { width: 100%; padding: 0.5rem; margin-top: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.25rem; }
    </style>
</head>
<body class="antialiased">
    <div class="container">
        <div class="upload-section">
            <h2>S3-PhotoProcessor</h2>
            
            @if($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif

            <form action="/upload" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="font-size: 0.9rem; font-weight: bold;">画像を選択 (.png, .jpg)</label><br>
                    <input type="file" name="image" accept="image/jpeg,image/png" required>
                    <input type="text" name="title" placeholder="画像タイトル（任意）">
                </div>
                <button type="submit" class="btn btn-primary">アップロード実行</button>
            </form>
        </div>

        <div class="preview-section">
            <h3 style="margin-top: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">保存済み画像一覧（直近5-10件）</h3>
            <div class="image-grid">
                @isset($images)
                    @forelse($images as $image)
                        <div class="image-item">
                            @php
                                $s3Url = Storage::disk('s3')->url($image->s3_key);
                                $displayS3Url = str_replace('aws:4566', 'localhost:4566', $s3Url);
                            @endphp
                            <a href="{{ $displayS3Url }}" target="_blank">
                                <img src="{{ $displayS3Url }}">
                            </a>
                            
                            {{-- ⑤ タイトル未記入の場合は画像IDを表示 --}}
                            <div class="image-title">{{ $image->title ?? 'ID: ' . $image->id }}</div>
                            
                            <div class="image-info">
                                <strong>Author:</strong> {{ $image->author }}<br> {{-- ⑥ --}}
                                <strong>Size:</strong> {{ number_format($image->size / 1024, 2) }} KB<br> {{-- ⑩ --}}
                                <strong>Type:</strong> {{ $image->mime_type }}<br> {{-- ④ --}}
                                <strong>Ver:</strong> {{ $image->version }}<br> {{-- ⑨ --}}
                            </div>

                            <form action="/delete" method="POST" style="margin-top: 10px;">
                                @csrf
                                <input type="hidden" name="path" value="{{ $image->s3_key }}">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('本当に削除しますか？')">削除</button>
                            </form>
                        </div>
                    @empty
                        <p style="color: #a0aec0; font-size: 0.8rem;">表示できるデータがありません。</p>
                    @endforelse
                @endisset
            </div>
        </div>
    </div>
</body>
</html>