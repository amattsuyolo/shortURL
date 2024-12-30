<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>短網址生成器</title>
</head>
<body>
    <h1>短網址生成器</h1>
    <form action="/short-url" method="POST">
        @csrf
        <label for="url">原始網址：</label>
        <input type="url" id="url" name="url" required>
        <br>
        <label for="limit">訪問限制 (選填)：</label>
        <input type="number" id="limit" name="limit" min="1">
        <br>
        <button type="submit">生成短網址</button>
    </form>

    @if(session('short_url'))
        <p>短網址：<a href="{{ session('short_url') }}" target="_blank">{{ session('short_url') }}</a></p>
    @endif
</body>
</html>
