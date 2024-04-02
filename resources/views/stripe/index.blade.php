<!DOCTYPE html>
<html lang="en">
<head>
  <title>Stripe account link</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <style>
  .loader {
    position: fixed;
    left: 0px;
    top: 0px;
    width: 100%;
    height: 100%;
    z-index: 9999;
    background: url('{{ asset("loading/loading.gif"); }}') 50% 50% no-repeat rgb(255,255,255);
   }
  </style>
</head>
<body>
<div class="loader"></div>
@if(isset($verifyAccountLink))
    <a id="verifyAccountLink" href="{{ $verifyAccountLink }}"></a>
@endif
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    var verifyAccountLink = $('#verifyAccountLink').attr('href');
    if(verifyAccountLink) {
      setTimeout(()=>{
          window.location.href= verifyAccountLink;
          $(".loader").css("display","none");   
      },3000)
    }
});
</script>
</body>
</html>
