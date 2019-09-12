<?php 
	
	namespace LojaVirtual\Model;
	use \LojaVirtual\DB\Sql;
	use \LojaVirtual\Model;
	use \LojaVirtual\Mailer;



	/**
	 * Classe Usuario
	 */

	class Category extends Model
	{
		
		public static function listAll()
		{
			$sql = new Sql();

			return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
		}

		public function save()
		{
			$sql = new Sql();

			$results = $sql->select("CALL sp_categories_save (:idcategory, :descategory)", array(
				":idcategory"=>$this->getidcategory(),
				":descategory"=>$this->getdescategory()
			));

			$this->setData($results[0]);

			Category::updatefile();

		}

		public function get($idcategory)
		{
			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory",[
				":idcategory"=>$idcategory
			]);

			$this->setData($results[0]);
		}
		public function delete()
		{
			$sql = new Sql();

			$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory",[
				":idcategory"=>$this->getidcategory()
			]);

			Category::updatefile();
		}

		public static function updatefile()
		{
			$categories = Category::listAll();
			$html = [];

			foreach ($categories as $row) {
				
				array_push($html, '<li><a href="/categories/'.$row['idcategory']. '">'.$row['descategory'].'</a></li>');
			}

			file_put_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
		}

		public function getProducts($related = true)
		{
			$sql = new Sql();
			if ($related === true) {
				return $sql->select("
					SELECT * FROM tb_products WHERE idproduct IN(
						SELECT a.idproduct
						FROM tb_products a
						INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
						WHERE b.idcategory = :idcategory
					);
				", [
					':idcategory'=>$this->getidcategory()
				]);
			} else {
				return $sql->select("
					SELECT * FROM tb_products WHERE idproduct NOT IN(
						SELECT a.idproduct
						FROM tb_products a
						INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
						WHERE b.idcategory = :idcategory
					);
				", [
					':idcategory'=>$this->getidcategory()
				]);
			}
		}

		public function getProductsPage ($page = 1, $itensPerPage = 8) //ALTERAR O NUMERO DE ITENS POR PAGINA PARA VIEWER NO SITE
		{
			$start = ($page - 1) * $itensPerPage;
			$sql = new Sql();
			$results = $sql->select("
				SELECT sql_calc_found_rows * 
				FROM tb_products a
				INNER JOIN tb_productscategories b 
				ON a.idproduct = b.idproduct
				INNER JOIN tb_categories c 
				ON c.idcategory = b.idcategory
				WHERE c.idcategory = :idcategory
				LIMIT $start, $itensPerPage;
				", [
					":idcategory"=>$this->getidcategory()
				]);

				$resultTotal = $sql->SELECT("SELECT FOUND_ROWS() AS nrtotal;");
				return [
					'data'=>Product::checkList($results),
					'total'=>$resultTotal[0]["nrtotal"],
					'pages'=>ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
				];
		}

		public function addProduct(Product $product)
		{
			$sql = new Sql();

			$sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES (:idcategory, :idproduct)", [
				":idcategory"=>$this->getidcategory(),
				":idproduct"=>$product->getidproduct()
			]);
		}
		public function removeProduct(Product $product)
		{
			$sql = new Sql();

			$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
				":idcategory"=>$this->getidcategory(),
				":idproduct"=>$product->getidproduct()
			]);
		}
	}
 ?>