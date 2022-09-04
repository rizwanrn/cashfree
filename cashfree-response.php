<?php
require 'includes/functions.php';
$page = "Thank you!";
$paymentstts = "EXPIRED";
$paymentmsg = "Request time out, Try again!";

if (isset($_GET['order_id']) || isset($_GET['order_token'])) {
$order_id = $_GET['order_id'];
$order_token = $_GET['order_token'];
$selectThisOrd = select("orders","order_id='$order_id' ORDER BY id DESC LIMIT 1");
if (howMany($selectThisOrd)>0) {
  $fetchThisOrd = fetch($selectThisOrd);
  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => "https://sandbox.cashfree.com/pg/orders/".$order_id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-api-version: 2022-01-01",
        "x-client-id: 17814226bf91d39ab958ad1482241871",
        "x-client-secret: 1e7c6c04ab1d0a98aaba80ce82c57bd74724f9fa"
    ],
  ]);

  $response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);
  // var_dump($response);
  // exit();
  if ($err) {
        $paymentmsg = "cURL Error #:" . $err;
  } else {
    $res = json_decode($response);
    if ($res->order_status=="PAID") {
        $paymentstts = "Paid Successfully.";
        $paymentmsg = "Thank you for purchasing from ".site_name.".";
        update("tbl_payment",["status" => "Success"],"order_id='".$fetchThisOrd['id']."'");
    }else{
        $page = "Try again!";
      deleteRow("tbl_payment","order_id='".$fetchThisOrd['id']."'");
      deleteRow("orderitems","orderid='".$fetchThisOrd['id']."'");
      deleteRow("orders","id='".$fetchThisOrd['id']."'");
      $paymentmsg = "This order is aborted. If payment deducted from your account then request for Refund or Contact us for more details.";
    }
  }
}
}?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Place favicon.ico in the root directory -->
    <?php include 'includes/style.php';?>

</head>
   <body>
    
    <div class="wrapper">
        <!--Header Area Start-->
    <?php include 'includes/header.php';?>
        <!--Header Area End-->
        <!--Page Banner2 Area Start-->
        <div class="page-banner2-area">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="page-banner2-title">
                            <h2><?=$page?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--Page Banner Area End-->
        <!--Breadcrumb Start-->
        <div class="breadcrumb-Area">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="breadcrumb-content">
                            <ul>
                                <li><a href="index.php">Home</a></li>
                                <li class="active"><a href="#"><?=$page?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--Breadcrumb End-->
        <div class="jumbotron text-xs-center">
          <h1 class="display-3"><?=$page?></h1>
          <h4 class="display-3"><?=$paymentstts?></h4>
          <p class="lead"><?=$paymentmsg?></p>
            <hr>
          <p class="lead"><?php echo ($paymentstts == "Paid Successfully.") ? 'Your Order id #'.$order_id:'';?></p>
            <h4>You will be redirecting in 1 min.</h4>
          <p class="lead">
            <a class="btn btn-primary btn-sm" href="index.php" role="button">Continue Shopping</a>
          </p>
        </div>
      <?php include 'includes/footer.php';
      include 'includes/script.php';?>
      <script>
        $(function(){
            $("#cartData").attr('action', 'index.php');
            setTimeout(function(){
            $("#checkout-my-cart").click(); }, 60000);
        });
    </script>
  </body>
</html>
