<?php
class ICC_TrackingCode_Block_ClicktaleTop extends Mage_Core_Block_Text
{
    protected function _toHtml()
    {
        return '
<!-- ClickTale Top part -->
<script type="text/javascript">
var WRInitTime=(new Date()).getTime();
</script>
<!-- ClickTale end of Top part -->';
    }
}