
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #8176EE;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .header img {
            width: 50px;
            border-radius: 50%;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
            text-align: center;
        }
        .content h1 {
            font-size: 22px;
            margin: 0 0 10px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
        }
        .code {
            display: inline-block;
            background-color: #e1bee7;
            color: #8176EE;
            font-size: 24px;
            padding: 10px 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer {
            background-color: #8176EE;
            color: #fff;
            text-align: center;
            padding: 10px;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .container {
                width: 100%;
                margin: 0 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $doqta }}" alt="Doqta Logo">
            <h1>Doqta</h1>
        </div>
        <div class="content">
            <h1>Your Verification Code</h1>
            <p>Use the following code to verify your email address change:</p>
            <div class="code">{{ $otp }}</div>
            <p>This code will expire in 10 minutes.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Doqta. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
