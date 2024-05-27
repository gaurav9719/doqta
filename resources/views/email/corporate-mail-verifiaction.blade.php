<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify email</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Open+Sans');
        * {
            box-sizing: border-box;
        }
        
        body {
            background-color: #fafafa;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .c-email {
            width: 40vw;
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 0px 7px 22px 0px rgba(0, 0, 0, .1);
        }
        
        .c-email__header {
            background-color: #0fd59f;
            width: 100%;
            height: 60px;
        }
        
        .c-email__header__title {
            font-size: 23px;
            font-family: 'Open Sans';
            height: 60px;
            line-height: 60px;
            margin: 0;
            text-align: center;
            color: white;
        }
        
        .c-email__content {
            width: 100%;
            height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            background-color: #fff;
            padding: 15px;
        }
        
        .c-email__content__text {
            font-size: 20px;
            text-align: center;
            color: #343434;
            margin-top: 0;
        }
        
        .c-email__code {
            display: block;
            width: 60%;
            margin: 30px auto;
            background-color: #ddd;
            border-radius: 40px;
            padding: 20px;
            text-align: center;
            font-size: 36px;
            font-family: 'Open Sans';
            letter-spacing: 10px;
            box-shadow: 0px 7px 22px 0px rgba(0, 0, 0, .1);
        }
        
        .c-email__footer {
            width: 100%;
            height: 60px;
            background-color: #fff;
        }
        
        .text-title {
            font-family: 'Open Sans';
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-italic {
            font-style: italic;
        }
        
        .opacity-30 {
            opacity: 0.3;
        }
        
        .mb-0 {
            margin-bottom: 0;
        }
        /* ----new---- */
        
        table.email {
            width: 100%;
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 0px 7px 22px 0px rgb(0 0 0 / 10%);
            border-spacing: 0px;
            height: 349px;
        }
        
        tr.email_header {
            background: #0fd59f;
            color: white;
            width: 100%;
        }
        
        tbody {
            text-align: center;
            /* box-shadow: 0px 0px 23px -12px; */
            /* border-radius: 30px; */
            width: 100%;
            height: 300px;
            /* display: flex; */
            flex-direction: column;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            background-color: #fff;
            padding: 15px;
        }
        
        tr.email_header th {
            /* padding: 10px 0px; */
            font-size: 23px;
            font-family: 'Open Sans';
            height: 60px;
            /* border-radius: 40px 40px 0px 0px; */
            width: 100%;
        }
        
        tr.email_body td {
            font-size: 20px;
            font-family: 'Open Sans';
            color: #343434;
        }
        
        tr.email_body_year {
            /* display: block; */
            width: 60%;
            margin: 30px auto 0px;
            background-color: #ddd;
            border-radius: 40px;
            padding: 20px;
            text-align: center;
            font-size: 36px;
            font-family: 'Open Sans';
            letter-spacing: 10px;
            box-shadow: 0px 7px 22px 0px rgb(0 0 0 / 10%);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        tr.email_footer td {
            font-style: italic;
            font-family: 'Open Sans';
            font-size: 20px;
            text-align: center;
            color: #343434;
            margin-top: 0;
            opacity: 0.3;
        }
        
        .email_verify {
            width: 100%;
            max-width: 913px;
            padding: 10px;
        }
        
        tr.email_body {
            height: 70px;
        }
        
        tr.email_footer {
            height: 150px;
        }
    </style>

</head>

<body>
    <div class="email_verify">
        <table class="email">
            <tr class="email_header">

                <th>Your Email Verification Code</th>

            </tr>
            <tr class="email_body">
                <td>Hello, This is your corporate email verification code in field:</td>

            </tr>
            <tr class="email_body_year">
                <td>{{ $otp }}</td>

            </tr>
            <tr class="email_footer">
                <td>Verification code is valid only for 10 minutes</td>

            </tr>
        </table>
    </div>

</body>

</html>