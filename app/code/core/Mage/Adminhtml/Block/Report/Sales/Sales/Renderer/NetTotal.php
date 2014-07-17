<?php
class Mage_Adminhtml_Block_Report_Sales_Sales_Renderer_NetTotal extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $sales = $row->getData('total_income_amount');
		$refunds = $row->getData('total_refunded_amount');
		$net = $sales - $refunds;
		$net_total = number_format($net, 2, '.',',');
		return "$".$net_total;
    }
}
?>