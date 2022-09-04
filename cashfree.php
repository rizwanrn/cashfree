<?php
require 'includes/functions.php';
/*Order And Checkout For Cashfree*/
if (isset($_POST["cashfreeorder"]) AND isset($_SESSION["checkout"])) {
  $checkOut = $_SESSION["checkout"];
  $promoCode = $checkOut['0'];
  $grandTotal = $checkOut['1'];
  $userId = $checkOut['2'];


  $merchantTransactionId = $userId.rand();
  // $orderId = "ORD".$userId.rand();
  $uniqueOrderId = "ORD" . date('dmy').rand(1000, 9999);
  $merchantTransactionId = "TXN".$merchantTransactionId;

  if ($grandTotal > 0) {
    $curl = curl_init();
    $grandTotalPrice = $_SESSION["checkout"][1];
      // "amount"=> (int)$grandTotalPrice*100,
    $data = [
      "order_id"=> $uniqueOrderId,
      "order_amount"=> (int)$grandTotalPrice*100,
      "order_currency"=> "INR",
      "order_note"=> "Paying for Clothing Shop product.",
      "customer_details"=> [
        "customer_id"=> $_SESSION["user"]["id"],
        "customer_name"=> $_SESSION["user"]["fullname"],
        "customer_email"=> $_SESSION["user"]["email"],
        "customer_phone"=> $_SESSION["user"]["phone"]
      ],
      "order_meta"=> [
        "return_url"=> "https://your-domain.com/cashfree-response.php?order_id={order_id}&order_token={order_token}"
      ]
    ];
    $data = json_encode($data);
    curl_setopt_array($curl, [
      CURLOPT_URL => "https://sandbox.cashfree.com/pg/orders",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_POSTFIELDS => $data,
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Content-Type: application/json",
        "x-api-version: 2022-01-01",
        "x-client-id: 17814226bf91d39ab958ad1482241871",
        "x-client-secret: 1e7c6c04ab1d0a98aaba80ce82c57bd74724f9fa"
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    // var_dump($response)."<br>";
    // exit();
    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
      $res = json_decode($response);
      if ($res->order_status=="ACTIVE") {
        $updateProfile = update("customers", ["fullname" => $_POST["fullname"], "phone" => $_POST["phone"], "country" => $_POST["country"], "address" => $_POST["address"], "state" => $_POST["state"], "city" => $_POST["city"], "zipcode" => $_POST["zipcode"]], "id='$userId'");

        $selectCustomer = select("customers","id='$userId'");
        $fetchCustomer = fetch($selectCustomer);
        $firstname = $fetchCustomer['firstname'];
        $lastname = $fetchCustomer['lastname'];
        $email = $fetchCustomer['email'];

        $billingAddress = $_POST["address"] ." ". $_POST["city"] .", ". $_POST["country"];
        $orderZipCode = $_POST["zipcode"];
        $userIP = getUserIP();
        $orderToken = $res->order_token;
        mysqli_query($conn, "INSERT INTO orders (order_id,uid,totalprice,paymentmode,txn_mid,user_ip,promocode,billing_address,zipcode)VALUES('$uniqueOrderId','$userId','$grandTotal','Cashfree','$orderToken','$userIP','$promoCode','$billingAddress','$orderZipCode')");

        $orderLastId = mysqli_insert_id($conn);
        $_SESSION["orderId"] = $orderLastId;
        $_SESSION["uniqueOrderId"] = $uniqueOrderId;

        $get = $_SESSION["orderCart"];
        $allProductName = "";
        foreach ($get as $key => $value) {

          $productsToken = $value['0'];
          $orderQuan = $value['5'];
          $totalprice = $value['4'] * $value['5'];
          $size = $value['7'];
          $allProductName.= $value["2"].", ";

          saveData("orderitems", ["orderid" => $orderLastId, "pid" => $value['0'], "pquantity" => $value['5'], "size" => $size, "color" => $value['3'], "productprice" => $value['4'], "productname" => $value['2'], "totalprice" => $totalprice]);

          $selectProduct = mysqli_query($conn, "SELECT * FROM products WHERE token ='$productsToken'");
          $fetchProduct = fetch($selectProduct);

          $allSize = explode(',', $fetchProduct["custom_size"]);
          $oldProductQuantity = explode(',', $fetchProduct['quantity']);
          $i=0;
            foreach ($allSize as $key) {
              if ($size == $key) {
                $no = $i++;
              }
              $i++;
            }

            $ProductQuantity = $oldProductQuantity[$no];

          //Descresing the Quantity
            $ProductQuantity = $oldProductQuantity[$no]-$orderQuan;
            $ProductQuantitys = array($no => $ProductQuantity);
            $newProductQuantity = array_replace($oldProductQuantity, $ProductQuantitys);

            $newProductQuantity = implode(",", $newProductQuantity);

            mysqli_query($conn, "UPDATE products SET quantity='$newProductQuantity' WHERE token='$productsToken'");
        }
        $phone = $_POST["phone"];
        $site_name = site_name;
        mysqli_query($conn, "INSERT INTO tbl_payment(status,order_id,firstname,lastname,amount, txnid, email, productinfo,paymentmode, posted_hash, create_key)VALUES('Pending','$orderLastId','$firstname','$lastname','$grandTotal','','$email','$site_name','Cashfree','','')");
        // header("location:".$res->payment_link); uncomment this if not using json
        exit(json_encode(array(
          'response' => array(
            "code" => '4',
            "msg" => 'Item ordered successfully!',
            "redirect" => $res->payment_link, // if you are not using json you can directly redirect to this page, this link provides payment page by cashfree.
          ),
        )));
      }else{
        exit(json_encode(array(
          'response' => array(
            "code" => '0',
            "msg" => 'Something went wrong!',
          ),
        )));
      }
    }
  }else{
    exit(json_encode(array(
      'response' => array(
        "code" => '0',
        "msg" => 'You can not checkout!',
      ),
    )));
  }
}
