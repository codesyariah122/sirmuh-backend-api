<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <style>
        body {
            background-color: #f8d7da;
            color: #721c24;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            background-color: #f8d7da;
            padding: 20px;
            max-width: 600px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 48px;
            margin-bottom: 10px;
        }
        p {
            font-size: 18px;
            margin-top: 0;
        }
        .icon {
            font-size: 72px;
            color: #f5c6cb;
        }
        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #721c24;
            border: none;
            border-radius: 4px;
            text-decoration: none;
        }
        .button:hover {
            background-color: #501112;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⚠️</div>
        <h1>Error parameters !!</h1>
        <p>{{ $message }}</p>
        <a href="{{ url()->previous() }}" class="button">Go Back</a>
    </div>
</body>
</html>
