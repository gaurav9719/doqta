<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot password</title>
</head>
<style>
      @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap');

    body{

        font-family: 'Montserrat', sans-serif;
    }
    @media only screen and (max-width:767px) {

        table{

            padding: 10px !important;
        }
        .mainlogo,.userImage{

            width: 160px !important;
         
        }
    }
</style>
<body style="background-color: #fff;">
    <!--------->
    <div style="
        border-radius: 10px;
        box-shadow: 0px 0px 9px -6px #0274B0;
        background: #ffff;
        /* border: 1px solid #e1fff6; */
        max-width: 800px;
        width: 100%;
        margin: 30px auto;">
        <table width="100%" style="border: 1px solid #fff; padding: 40px 80px;margin:0 auto;border-spacing:0px;">
            <tbody style="border-spacing:0px;">
            
            
            <tr
            style="text-align:center; width:100%;font-size: 18px;font-family: 'Montserrat';color: #0274B0; font-weight: 500;line-height: 1.5;">
            <td style="padding: 20px 0px 10px;">  Hello dear, this is your password verification code in field:</td>
        </tr>
     
        <tr
        style="height:60px;
        text-align: center;
        color: #0274B0;
        width: 100%;
        font-size: 35px;
        font-weight: 700;
        font-family: 'Montserrat';">
        <td>{{ $data['otp'] }}</td>
    </tr>
    <tr
        style="    text-align: center;
        width: 100%;
        color: #f85104;
        font-size: 14px;
        font-family: 'Montserrat';
        font-weight: 400;">
        <td>Valid for 10 minutes only</td>
    </tr>
        <tr
        style="text-align:center;height: 30px; width:100%;font-size: 16px;font-family: 'Montserrat';">
       <td> <div style="border:1px solid #d9d9d9;"></div></td>
    </tr>
        <tr
        style="    text-align: center;
        width: 100%;
      
        font-size: 14px;
        font-family: 'Montserrat';
        font-weight: 400;">
        <td>Best regards</td>
    </tr>
  
            </tbody>
         </table>

    </div>
</body>

</html>