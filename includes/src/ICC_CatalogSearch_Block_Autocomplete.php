<?php
class ICC_CatalogSearch_Block_Autocomplete extends Mage_CatalogSearch_Block_Autocomplete {
	
	protected function _toHtml() {
		$html = '';
		
		if (! $this->_beforeToHtml ()) {
			return $html;
		}
		
		$suggestData = $this->getSuggestData ();
		if (! ($count = count ( $suggestData ))) {
			return $html;
		}
		
		$count --;
		
		$html = '<ul><li style="display:none"></li>';
		foreach ( $suggestData as $index => $item ) {
			if ($index == 0) {
				$item ['row_class'] .= ' first';
			}
			
			if ($index == $count) {
				$item ['row_class'] .= ' last';
			}
			
			$html .= '<li title="' . $this->htmlEscape ( $item ['title'] ) . '" class="' . $item ['row_class'] . '">' . $this->htmlEscape ( $item ['title'] ) . '<span class="amount"> (' . $item ['num_of_results'] . ')</span></li>';
		}
		
		$html .= '</ul>';
		
		return $html;
	}
}
?>
