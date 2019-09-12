<?php 

use \LojaVirtual\Page;
use \LojaVirtual\Model\Product;
use \LojaVirtual\Model\Category;
use \LojaVirtual\Model\Cart;
use \LojaVirtual\Model\Address;
use \LojaVirtual\Model\User;
use \LojaVirtual\Model\Order;
use \LojaVirtual\Model\OrderStatus;


$app->get('/', function() {

	$products = Product::listAll();

	$page = new Page();

	$page->setTpl("index", [
		"products"=>Product::checkList($products)
	]);
});


$app->get("/categories/:idcategory", function($idcategory){

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($page);

	$pages = [];

	for ($i=1; $i <= $pagination['pages']; $i++) { 
		array_push($pages, ['link'=>'/categories/'. $category->getidcategory() . '?page=' . $i,
			'page'=>$i
	]);
	}

	$page = new Page();

	$page->setTpl("category",[
		"category"=>$category->getValues(),
		"products"=>$pagination["data"],
		"pages"=>$pages
	]);
});

$app->get("/products/:desurl", function($desurl){

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", [
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);
});

$app->get("/cart", function() {

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);
});

$app->get("/cart/:idproduct/add", function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for ($i = 0; $i < $qtd; $i++) { 
		
		$cart->addProduct($product);
	}



	header("Location: /cart");
	exit();
});

$app->get("/cart/:idproduct/minus", function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);

	header("Location: /cart");
	exit();
});

$app->get("/cart/:idproduct/remove", function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit();
});

$app->post("/cart/freight", function(){

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit();
});

$app->get("/checkout", function(){

	User::verifyLogin(False);

	$address = new Address(); 
	$cart = Cart::getFromSession();

	if (isset($_GET['zipcode'])) {

		$_GET['zipcode'] = $cart->getdeszipcode();

	}


	if (isset($_GET['zipcode'])) {
		
		$address->loadFromCEP($_GET['zipcode']);

		$cart->setdeszipcode($_GET['zipcode']);

		$cart->save();

		$cart->getCalculateTotal(); 
	}

	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Address::getMsgError()
	]);
});

$app->post("/checkout", function(){

	User::verifyLogin(false);

	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
			
		Address::setMsgError("Informe o CEP.");
		header("Location: /checkout");
		exit();
	}

	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
			
		Address::setMsgError("Informe o Endereço.");
		header("Location: /checkout");
		exit();
	}

	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
			
		Address::setMsgError("Informe o Bairro.");
		header("Location: /checkout");
		exit();
	}

	if (!isset($_POST['descity']) || $_POST['descity'] === '') {
			
		Address::setMsgError("Informe a cidade.");
		header("Location: /checkout");
		exit();
	}

	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
			
		Address::setMsgError("Informe o estado");
		header("Location: /checkout");
		exit();
	}

	if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
			
		Address::setMsgError("Informe o país");
		header("Location: /checkout");
		exit();
	}



	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	$cart = Cart::getFromSession();

	$totals = $cart->getCalculateTotal();

	$order = new Order();

	$order->setData([

		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);

	$order->save();


	header("Location: /order/".$order->getidorder());

	exit();

	
});




$app->get("/login", function(){

	$page = new Page();

	$page->setTpl("login", [
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name' => '', 'email' => '', 'phone' => '']
	]);
});

$app->post("/login", function(){

	try {

		User::login($_POST['login'], $_POST['password']);
		
	} catch (Exception $e) {

		User::setError($e->getMessage());
		
	}

	header("Location: /checkout");

	exit();
});

$app->get("/logout", function(){

	User::logout();

	header("Location: /login");

	exit();
});

$app->post("/register", function(){

	$_SESSION['registerValues'] = $_POST;

	if (!isset($_POST['name']) || $_POST['name'] == '' ) {
		
		User::setErrorRegister("Preencha o seu nome.");
		header("Location: /login");
		exit();
	}

	if (!isset($_POST['email']) || $_POST['email'] == '' ) {
	
	User::setErrorRegister("Preencha o seu e-mail.");
	header("Location: /login");
	exit();
	}

	if (!isset($_POST['password']) || $_POST['password'] == '' ) {
	
	User::setErrorRegister("Preencha o sua senha.");
	header("Location: /login");
	exit();
	}

	if (User::checkLoginExist($_POST['email']) === true) {
			
		User::setErrorRegister("Este endereço de e-mail já está sendo utilizado por outro usuário.");
		header("Location: /login");

		exit();

	}

	$user = new User();

	$user->setData([
		'inadmin'=> 0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	User::login($_POST['email'],  $_POST['password']);

	header("Location: /checkout");
	exit();

});
$app->get('/forgot', function() {
    
	$page = new Page();


	$page->setTpl("forgot");
});

$app->post('/forgot', function() {
    
	$user = User::getForgot($_POST["email"], false);
	
	header("Location: /forgot/sent");
	exit();
});

$app->get('/forgot/sent', function() {
    
	$page = new Page();


	$page->setTpl("forgot-sent");
});

$app->get('/forgot/reset', function() {

	$user = User::validForgotDecrypt($_GET["code"]);
    
	$page = new Page();


	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});
$app->post('/forgot/reset', function() {

	$forgot = User::validForgotDecrypt($_POST["code"]);

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = User::getPasswordHash($_POST["password"]);

	$user->setPassword($password);
	
	$page = new Page();

	$page->setTpl("forgot-reset-success");
});

$app->get('/profile', function() {

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile",[
		'user'=>$user->getValues(),
		'profileMsg'=>User::getSuccess(),
		'profileError'=>User::getError()
	]);


});

$app->post('/profile', function() {

	User::verifyLogin(false);
	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		User::setError("Preencha seu nome.");
		header("Location: /profile");
		exit();
	}
	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
		User::setError("Preencha seu e-mail.");
		header("Location: /profile");
		exit();
	}

	$user = User::getFromSession();

	if ($_POST['desemail'] !== $user->getdesemail()) {
		
		if (User::checkLoginExist($_POST['desemail']) === true) {
		 	
			User::setError("Este endereço de e-mail já está cadastrado.");

		 } 
	}

	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);

	$user->update();

	User::setSuccess("Dados alterados com sucesso");

	header("Location: /profile");

	exit();
});

$app->get("/order/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$page = new Page();

	$page->setTpl("payment", [

		'order'=>$order->getValues()
	]);

});

$app->get("/boleto/:idorder", function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
	$valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado/*+$taxa_boleto*/, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " .  $order->getdesstate() . " - " . $order->getdescountry() . " - CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Virtual E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: email@email.com";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto boleto PHP";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "0000"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "00000";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "0"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "LojaVirtual";
	$dadosboleto["cpf_cnpj"] = "00.000.000/0000-00";
	$dadosboleto["endereco"] = "Rua Fulano de Tal, 123 - Ficticio, 00000-000";
	$dadosboleto["cidade_uf"] = "Rio Branco - AC";
	$dadosboleto["cedente"] = "LOJA VIRTUAL LTDA - ME";

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");
});

 ?>