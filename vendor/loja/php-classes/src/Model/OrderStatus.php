<?php 

	namespace LojaVirtual\Model;
	use \LojaVirtual\DB\Sql;
	use \LojaVirtual\Model;
	
	/**
	 * Class PEDIDOS STATUS
	 */
	class OrderStatus extends Model
	{
		const EM_ABERTO = 1;
		const AGUARDANDO_PAGAMENTO = 2;
		const PAGO = 3;
		const ENTREGUE = 4;
	}






 ?>